<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Turns search_sessions into a history rather than a current-state table.
 *
 * The rule this exists for: the 5-minute search trial belongs to the account
 * and is consumed once. Returning tomorrow, logging in again, or switching
 * browser must not produce a second one — only an admin granting one may.
 *
 * Telling those apart needs two columns:
 *
 *  - type        search_trial   the one automatic window a user ever gets
 *                admin_granted  handed out by the team afterwards
 *  - created_by  users.id of the admin who granted it, or null for automatic
 *
 * Expired rows are kept, not deleted: they are the evidence that the trial was
 * used, and the admin screens read the history off them.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('search_sessions')) {
            return;
        }

        Schema::table('search_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('search_sessions', 'type')) {
                $table->string('type', 30)
                    ->default('search_trial')
                    ->after('user_id');
            }

            if (!Schema::hasColumn('search_sessions', 'created_by')) {
                // Nullable: an automatic trial has no grantor, and removing a
                // staff account must not delete a customer's session history.
                $table->unsignedBigInteger('created_by')
                    ->nullable()
                    ->after('opened_by');
            }
        });

        Schema::table('search_sessions', function (Blueprint $table) {
            // The question asked on every login: has this account already had
            // its trial?
            $table->index(['user_id', 'type'], 'search_sessions_user_type_index');
        });

        // Anything already recorded was an automatic trial — admin granting
        // did not exist before this migration.
        DB::table('search_sessions')->whereNull('type')->update(['type' => 'search_trial']);
    }

    public function down(): void
    {
        if (!Schema::hasTable('search_sessions')) {
            return;
        }

        Schema::table('search_sessions', function (Blueprint $table) {
            $table->dropIndex('search_sessions_user_type_index');

            if (Schema::hasColumn('search_sessions', 'created_by')) {
                $table->dropColumn('created_by');
            }

            if (Schema::hasColumn('search_sessions', 'type')) {
                $table->dropColumn('type');
            }
        });
    }
};
