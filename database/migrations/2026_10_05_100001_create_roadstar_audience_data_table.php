<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * RoadStar audience for one hoarding over one period — POST /sitedata.
 *
 * Historical: one row per (media, period). Re-syncing the same period
 * replaces that row; a new period adds a row. Every metric column is one of
 * the 13 values the documentation lists for /sitedata, in its order:
 *
 *   [0]  Unique Reach                → unique_reach
 *   [1]  Impressions                 → impressions
 *   [2]  Frequency                   → frequency
 *   [3]  Date-wise impressions       → date_wise_impressions
 *   [4]  Day-wise avg impressions    → day_wise_avg_impressions
 *   [5]  Month-wise impressions      → month_wise_impressions
 *   [6]  Hourly avg impressions      → hourly_avg_impressions
 *   [7]  OTS 1+ … 5+ (effective freq) → effective_frequency
 *   [8]  Weekday vs weekend          → weekday_weekend_impressions
 *   [9]  Age group %                 → age_groups
 *   [10] Gender %                    → gender
 *   [11] Mobile affluence %          → mobile_affluence
 *   [12] Mobile phone brand %        → mobile_brands
 *
 * Breakdowns are JSON lists of {label, value} in RoadStar's order (a JSON
 * object would let MySQL reorder the keys). raw_response is RoadStar's value
 * for this one site as returned — no credentials or headers are in it.
 *
 * media_id follows the project convention: unsignedBigInteger + index, no FK
 * constraint (media_management rows are soft-deleted via is_deleted, as for
 * hoarding_traffic_data and media_insights).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('roadstar_audience_data')) {
            return;
        }

        Schema::create('roadstar_audience_data', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('media_id');
            $table->string('roadstar_site_id', 100)->index();

            // st / ed sent to /sitedata.
            $table->date('period_start');
            $table->date('period_end');

            $table->unsignedBigInteger('unique_reach')->nullable();
            $table->unsignedBigInteger('impressions')->nullable();
            $table->decimal('frequency', 8, 2)->nullable();

            $table->json('date_wise_impressions')->nullable();
            $table->json('day_wise_avg_impressions')->nullable();
            $table->json('month_wise_impressions')->nullable();
            $table->json('hourly_avg_impressions')->nullable();
            $table->json('effective_frequency')->nullable();
            $table->json('weekday_weekend_impressions')->nullable();
            $table->json('age_groups')->nullable();
            $table->json('gender')->nullable();
            $table->json('mobile_affluence')->nullable();
            $table->json('mobile_brands')->nullable();

            $table->json('raw_response')->nullable();
            $table->timestamp('fetched_at')->index();
            $table->timestamps();

            $table->unique(['media_id', 'period_start', 'period_end'], 'uq_roadstar_audience_period');
            $table->index(['media_id', 'fetched_at'], 'idx_roadstar_audience_latest');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roadstar_audience_data');
    }
};
