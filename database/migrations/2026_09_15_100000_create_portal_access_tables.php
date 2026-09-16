<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Timed access to the media search.
 *
 * Three tables:
 *
 *  - settings          key/value, already present on this database but with no
 *                      migration behind it, so it is created here for every
 *                      other environment and its defaults seeded idempotently.
 *  - visitor_sessions  the free preview, keyed by a cookie token rather than a
 *                      user, since the visitor has not signed in yet.
 *  - search_sessions   the post-login window, keyed by website_users.id.
 *
 * Both session tables store started_at / expires_at as real timestamps. The
 * server compares expires_at against its own clock on every request, so the
 * browser's clock, its localStorage and its JavaScript are all irrelevant to
 * whether access is granted.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Already exists on the live database, seeded by hand. Created here so
        // a fresh checkout matches.
        if (!Schema::hasTable('settings')) {
            Schema::create('settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->text('value')->nullable();
                $table->string('group')->nullable()->index();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('visitor_sessions')) {
            Schema::create('visitor_sessions', function (Blueprint $table) {
                $table->id();

                // What the cookie carries. Unique so a token can only ever name
                // one preview: a visitor cannot mint themselves a second one by
                // replaying an old value.
                $table->string('visitor_token', 64)->unique();

                // nullable: MySQL refuses a second TIMESTAMP column with an
                // implicit default under strict mode. Always written in code.
                $table->timestamp('started_at')->nullable();
                $table->timestamp('expires_at')->nullable();

                // active | expired. Kept alongside expires_at so an expiry can
                // be recorded once rather than recomputed on every read.
                $table->string('status', 20)->default('active');

                // Diagnostics only — never used to identify or gate anyone.
                $table->string('ip_address', 45)->nullable();
                $table->string('user_agent', 255)->nullable();

                $table->timestamps();

                // The lookup the middleware makes on every guarded request.
                $table->index(['status', 'expires_at']);
            });
        }

        if (!Schema::hasTable('search_sessions')) {
            Schema::create('search_sessions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');

                // nullable: MySQL refuses a second TIMESTAMP column with an
                // implicit default under strict mode. Always written in code.
                $table->timestamp('started_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->string('status', 20)->default('active');

                // Which login opened it, so "log out and back in" can be told
                // apart from "same session, new tab".
                $table->string('opened_by', 40)->nullable();

                $table->timestamps();

                $table->index(['user_id', 'status']);
                $table->index(['status', 'expires_at']);

                $table->foreign('user_id')
                    ->references('id')->on('website_users')
                    ->onDelete('cascade');
            });

            // One ACTIVE session per user, enforced by the database rather than
            // by hope: two tabs racing a login cannot end up with two windows.
            // MySQL has no partial index, so the generated column is NULL for
            // expired rows and unique values only collide among active ones.
            if (DB::connection()->getDriverName() === 'mysql') {
                DB::statement("
                    ALTER TABLE `search_sessions`
                    ADD COLUMN `active_user_id` BIGINT UNSIGNED
                        GENERATED ALWAYS AS (CASE WHEN `status` = 'active' THEN `user_id` END) STORED,
                    ADD UNIQUE KEY `search_sessions_one_active_per_user` (`active_user_id`)
                ");
            }
        }

        $this->seedDefaults();
    }

    public function down(): void
    {
        Schema::dropIfExists('search_sessions');
        Schema::dropIfExists('visitor_sessions');
        // `settings` is deliberately left alone: it predates this migration on
        // the live database and may grow rows that have nothing to do with the
        // access gate.
    }

    /**
     * Seed the four portal_access settings, without overwriting a value an
     * admin has already chosen. Safe to re-run.
     */
    private function seedDefaults(): void
    {
        $defaults = [
            'guest_preview_minutes'      => (string) config('portal_access.guest_preview_minutes', 2),
            'search_session_minutes'     => (string) config('portal_access.search_session_minutes', 5),
            'portal_access_gate_enabled' => config('portal_access.gate_enabled', true) ? '1' : '0',
            'exempt_paying_customers'    => config('portal_access.exempt_paying_customers', true) ? '1' : '0',
        ];

        foreach ($defaults as $key => $value) {
            $exists = DB::table('settings')->where('key', $key)->exists();

            if (!$exists) {
                DB::table('settings')->insert([
                    'key'        => $key,
                    'value'      => $value,
                    'group'      => 'portal_access',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
};
