<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media_management', function (Blueprint $table) {
            $table->index('category_id',              'idx_media_category');
            $table->index('vendor_id',                'idx_media_vendor');
            $table->index('state_id',                 'idx_media_state');
            $table->index('district_id',              'idx_media_district');
            $table->index('city_id',                  'idx_media_city');
            $table->index('area_id',                  'idx_media_area');
            $table->index(['is_deleted', 'is_active'], 'idx_media_status');
        });
    }

    public function down(): void
    {
        Schema::table('media_management', function (Blueprint $table) {
            $table->dropIndex('idx_media_category');
            $table->dropIndex('idx_media_vendor');
            $table->dropIndex('idx_media_state');
            $table->dropIndex('idx_media_district');
            $table->dropIndex('idx_media_city');
            $table->dropIndex('idx_media_area');
            $table->dropIndex('idx_media_status');
        });
    }
};
