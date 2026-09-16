<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Share your requirement" — what a visitor sends the team when their search
 * window has closed, or when they would rather describe what they want than
 * hunt for it.
 *
 * Most fields are free text on purpose: the brief says mandatory but with no
 * validation beyond presence, because a client writing "about 3 weeks" in
 * Campaign Duration is more useful than a rejected form.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('media_requirements')) {
            return;
        }

        Schema::create('media_requirements', function (Blueprint $table) {
            $table->id();

            // Null for a visitor who never signed in; the contact fields below
            // are what the team actually calls back on.
            $table->unsignedBigInteger('user_id')->nullable();

            $table->string('full_name');
            $table->string('email')->nullable();
            $table->string('mobile_no', 20);

            $table->string('campaign_name')->nullable();
            $table->string('city');
            $table->string('area_location');
            $table->string('media_type');

            $table->date('campaign_start_date');
            $table->date('campaign_end_date');
            $table->string('campaign_duration');
            $table->string('required_media_count');

            $table->string('approx_budget')->nullable();
            $table->string('target_audience')->nullable();
            $table->string('preferred_location')->nullable();
            $table->string('preferred_media_size')->nullable();
            $table->text('additional_comments')->nullable();

            // new | in_progress | closed — the team's working state.
            $table->string('status', 20)->default('new')->index();

            // Where it came from, so the team can tell a timed-out search from
            // someone who just used the form.
            $table->string('source', 40)->nullable();

            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_requirements');
    }
};
