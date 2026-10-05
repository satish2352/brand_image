<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * RoadStar mapping + sync bookkeeping on the existing hoarding table.
 *
 * roadstar_site_id is RoadStar's site identifier ("arr", e.g.
 * "CWMS/MUM/WEH-019" or our own "BI/HD000034"). It is NOT the media id and
 * not necessarily the hoarding code: an admin can map a hoarding to a site
 * RoadStar already has, or register it with /addsite. Unique, because one
 * RoadStar site's audience cannot belong to two hoardings.
 *
 * roadstar_sync_status   pending | queued | success | no_data | not_available | failed
 * roadstar_last_synced_at last time RoadStar gave a definitive answer for the
 *                         site (data, no data for the period, or not
 *                         registered) — the 15-day refresh runs off this.
 * roadstar_last_attempt_at last request or queueing, success or not — a failed
 *                         or queued hoarding is left alone for ROADSTAR_RETRY_HOURS.
 * roadstar_sync_error     last error text (never contains credentials).
 *
 * The audience values themselves live in roadstar_audience_data.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media_management', function (Blueprint $table) {
            if (!Schema::hasColumn('media_management', 'roadstar_site_id')) {
                $table->string('roadstar_site_id', 100)->nullable()->after('hoarding_code');
                $table->unique('roadstar_site_id', 'uq_media_roadstar_site');
            }
            if (!Schema::hasColumn('media_management', 'roadstar_sync_status')) {
                $table->string('roadstar_sync_status', 20)->nullable()->after('roadstar_site_id');
                $table->index('roadstar_sync_status', 'idx_media_roadstar_status');
            }
            if (!Schema::hasColumn('media_management', 'roadstar_last_synced_at')) {
                $table->timestamp('roadstar_last_synced_at')->nullable()->after('roadstar_sync_status');
                $table->index('roadstar_last_synced_at', 'idx_media_roadstar_synced');
            }
            if (!Schema::hasColumn('media_management', 'roadstar_last_attempt_at')) {
                $table->timestamp('roadstar_last_attempt_at')->nullable()->after('roadstar_last_synced_at');
            }
            if (!Schema::hasColumn('media_management', 'roadstar_sync_error')) {
                $table->string('roadstar_sync_error', 500)->nullable()->after('roadstar_last_attempt_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('media_management', function (Blueprint $table) {
            if (Schema::hasColumn('media_management', 'roadstar_site_id')) {
                $table->dropUnique('uq_media_roadstar_site');
            }
            if (Schema::hasColumn('media_management', 'roadstar_sync_status')) {
                $table->dropIndex('idx_media_roadstar_status');
            }
            if (Schema::hasColumn('media_management', 'roadstar_last_synced_at')) {
                $table->dropIndex('idx_media_roadstar_synced');
            }
        });

        $columns = array_values(array_filter([
            'roadstar_site_id', 'roadstar_sync_status', 'roadstar_last_synced_at',
            'roadstar_last_attempt_at', 'roadstar_sync_error',
        ], fn($c) => Schema::hasColumn('media_management', $c)));

        if ($columns) {
            Schema::table('media_management', fn(Blueprint $table) => $table->dropColumn($columns));
        }
    }
};
