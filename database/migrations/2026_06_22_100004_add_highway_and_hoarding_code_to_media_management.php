<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Additive, nullable columns only — fully backward compatible.
     *  - highway_id   : one highway per hoarding (Feature 2)
     *  - hoarding_code : unique auto-generated identifier e.g. HD000001 (Feature 3)
     *    NOTE: this is SEPARATE from the existing `media_code` (MSH_NSK_VND_01),
     *    which is left completely untouched.
     */
    public function up(): void
    {
        Schema::table('media_management', function (Blueprint $table) {
            if (!Schema::hasColumn('media_management', 'highway_id')) {
                $table->unsignedBigInteger('highway_id')->nullable()->after('areatype_id');
                $table->index('highway_id', 'idx_media_highway');
            }

            if (!Schema::hasColumn('media_management', 'hoarding_code')) {
                $table->string('hoarding_code', 20)->nullable()->after('media_code');
                $table->unique('hoarding_code', 'uq_media_hoarding_code');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('media_management', function (Blueprint $table) {
            if (Schema::hasColumn('media_management', 'highway_id')) {
                $table->dropIndex('idx_media_highway');
                $table->dropColumn('highway_id');
            }
            if (Schema::hasColumn('media_management', 'hoarding_code')) {
                $table->dropUnique('uq_media_hoarding_code');
                $table->dropColumn('hoarding_code');
            }
        });
    }
};
