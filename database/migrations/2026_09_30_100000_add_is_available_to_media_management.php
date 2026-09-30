<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * is_available : set to 0 from the admin Media List ("Not Available") to
     * keep a hoarding on the website but stop it being booked. Separate from
     * is_active, which hides the record from the website altogether.
     */
    public function up(): void
    {
        Schema::table('media_management', function (Blueprint $table) {
            if (!Schema::hasColumn('media_management', 'is_available')) {
                $table->tinyInteger('is_available')->default(1)->after('is_active');
            }
        });
    }

    public function down(): void
    {
        Schema::table('media_management', function (Blueprint $table) {
            if (Schema::hasColumn('media_management', 'is_available')) {
                $table->dropColumn('is_available');
            }
        });
    }
};
