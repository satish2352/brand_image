<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The vendor-chosen panel / website colours (2026_11_25_110000) were removed
 * from the Vendor Panel; the panel and vendor websites always use the
 * Brand Adda colours. Drops the now-unused columns.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            foreach (['panel_color', 'site_color'] as $column) {
                if (Schema::hasColumn('vendors', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            if (!Schema::hasColumn('vendors', 'panel_color')) {
                $table->string('panel_color', 7)->nullable()->after('logo');
            }
            if (!Schema::hasColumn('vendors', 'site_color')) {
                $table->string('site_color', 7)->nullable()->after('panel_color');
            }
        });
    }
};
