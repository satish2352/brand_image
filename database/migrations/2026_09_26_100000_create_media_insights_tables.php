<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Media Location Insights (nearby places from Geoapify or SerpApi).
 *
 * - place_results    one saved nearby-places result per provider + rounded
 *                    LOCATION (not per media), so hoardings standing together
 *                    share a single API call.
 * - api_usage_logs   every attempt, per provider: spent, served from our
 *                    cache, blocked by the budget, or failed. Budgets are
 *                    SUM(credits_consumed) over a day (Geoapify) or a
 *                    month (SerpApi).
 * - media_insights   the calculated values for one media, plus the
 *                    coordinates and expiry used to decide when it is due a
 *                    refresh (queried by the scheduled batch, so indexed).
 *
 * media_id follows the project convention: unsignedBigInteger + index, no FK
 * constraint (media_management rows are soft-deleted via is_deleted).
 * No API credentials are stored in any of these tables.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('place_results')) {
            Schema::create('place_results', function (Blueprint $table) {
                $table->id();
                // geoapify | serpapi
                $table->string('provider', 20)->index();
                // sha1 of provider | request signature | rounded lat | rounded lng
                $table->string('location_key', 64)->unique();
                // Categories (Geoapify) or query (SerpApi) the result answers.
                $table->string('query', 500);
                $table->decimal('latitude', 10, 7);
                $table->decimal('longitude', 10, 7);
                $table->unsignedInteger('radius_m')->nullable();
                // Normalised place fields only — never the raw API response.
                $table->json('places')->nullable();
                $table->unsignedSmallInteger('place_count')->default(0);
                $table->string('external_id', 100)->nullable();
                $table->timestamp('fetched_at')->nullable();
                $table->timestamp('expires_at')->nullable()->index();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('api_usage_logs')) {
            Schema::create('api_usage_logs', function (Blueprint $table) {
                $table->id();
                $table->string('provider', 20);
                $table->unsignedBigInteger('media_id')->nullable()->index();
                $table->string('query', 500)->nullable();
                $table->string('location_key', 64)->nullable()->index();
                // success | failed | cache_hit | quota_blocked | quota_exceeded
                // | not_configured | missing_location
                $table->string('status', 20)->index();
                $table->unsignedSmallInteger('http_status')->nullable();
                // Billing units the provider charged: Geoapify 1 credit per 20
                // places returned; SerpApi 1 per successful search. 0 when the
                // request failed or was never sent.
                $table->unsignedSmallInteger('credits_consumed')->default(0);
                $table->unsignedSmallInteger('result_count')->nullable();
                $table->string('external_id', 100)->nullable();
                $table->string('error_message', 500)->nullable();
                $table->unsignedInteger('duration_ms')->nullable();
                // admin | scheduler
                $table->string('source', 20)->default('admin');
                // Admin (users.id) who pressed Refresh Insights.
                $table->unsignedBigInteger('triggered_by')->nullable();
                $table->timestamp('requested_at');
                $table->timestamps();

                $table->index(['provider', 'requested_at']);
            });
        }

        if (!Schema::hasTable('media_insights')) {
            Schema::create('media_insights', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('media_id')->unique();
                $table->unsignedBigInteger('place_result_id')->nullable()->index();

                $table->decimal('visibility_score', 4, 1)->nullable();
                $table->string('visibility_status', 20)->default('unavailable');

                $table->decimal('premium_score', 4, 1)->nullable();
                $table->string('premium_status', 20)->default('unavailable');

                $table->decimal('value_score', 4, 1)->nullable();
                $table->string('value_status', 20)->default('unavailable');

                // Inferred audience, rule-based sector suggestions, the
                // hoarding's own nearby places (distance from ITS coordinates)
                // and the inputs behind every score.
                $table->json('audience')->nullable();
                $table->json('recommendations')->nullable();
                $table->json('nearby_places')->nullable();
                $table->json('details')->nullable();

                // Refresh bookkeeping. The coordinates the places were fetched
                // for: when the hoarding is moved these stop matching and the
                // batch picks it up again.
                $table->string('places_provider', 20)->nullable();
                $table->decimal('places_latitude', 10, 7)->nullable();
                $table->decimal('places_longitude', 10, 7)->nullable();
                $table->timestamp('places_fetched_at')->nullable();
                $table->timestamp('places_expires_at')->nullable()->index();
                $table->unsignedTinyInteger('failed_attempts')->default(0);
                $table->timestamp('last_attempt_at')->nullable();

                $table->timestamp('calculated_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('media_insights');
        Schema::dropIfExists('api_usage_logs');
        Schema::dropIfExists('place_results');
    }
};
