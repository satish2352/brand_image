<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vendor-chosen colours (Vendor Panel → Website & Slider):
 *  - panel_color: background of the vendor's own panel sidebar
 *  - site_color:  brand colour of the vendor's public website
 * Null means the Brand Adda default (navy #0F172A).
 */
return new class extends Migration
{
    public function up(): void
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

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            foreach (['panel_color', 'site_color'] as $column) {
                if (Schema::hasColumn('vendors', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
