<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Vendor Master: the vendor's registered company / firm name, shown in the vendor panel. */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('vendors', 'company_name')) {
            Schema::table('vendors', function (Blueprint $table) {
                $table->string('company_name')->nullable()->after('vendor_name');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('vendors', 'company_name')) {
            Schema::table('vendors', function (Blueprint $table) {
                $table->dropColumn('company_name');
            });
        }
    }
};
