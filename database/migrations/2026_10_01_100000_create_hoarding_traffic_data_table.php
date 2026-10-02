<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TomTom Traffic Flow for each hoarding — one row per media.
 *
 * Filled by `refresh:hoarding-traffic` (initial fetch + 15-day refresh) and
 * read by every page/API; normal requests never call TomTom.
 *
 * These are live speeds / travel times on the road segment nearest the
 * hoarding at fetch time. They are NOT a daily vehicle count and nothing
 * derived from them is stored here.
 *
 * media_id follows the project convention: unsignedBigInteger, no FK
 * constraint (media_management rows are soft-deleted via is_deleted).
 * latitude/longitude are the coordinates the request was made FOR, so a
 * hoarding that is moved is picked up again. No API key is stored.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('hoarding_traffic_data')) {
            return;
        }

        Schema::create('hoarding_traffic_data', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('media_id')->unique();

            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);

            // flowSegmentData fields (KMPH, seconds).
            $table->string('frc', 10)->nullable();
            $table->decimal('current_speed', 6, 2)->nullable();
            $table->decimal('free_flow_speed', 6, 2)->nullable();
            $table->unsignedInteger('current_travel_time')->nullable();
            $table->unsignedInteger('free_flow_travel_time')->nullable();
            $table->decimal('confidence', 5, 4)->nullable();
            $table->boolean('road_closure')->nullable();

            // When the values above were fetched (null = never succeeded).
            $table->timestamp('traffic_updated_at')->nullable()->index();
            // When the scheduled refresh may call TomTom again.
            $table->timestamp('next_refresh_at')->nullable()->index();

            // success | failed — of the latest attempt.
            $table->string('last_api_status', 20)->nullable();
            $table->text('last_api_error')->nullable();
            $table->unsignedSmallInteger('last_http_status')->nullable();
            // Every attempt, success or not; counted for the daily request limit.
            $table->timestamp('last_attempt_at')->nullable()->index();

            // flowSegmentData as TomTom returned it, for auditing.
            $table->json('raw_response')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hoarding_traffic_data');
    }
};
