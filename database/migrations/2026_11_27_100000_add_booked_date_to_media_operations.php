<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Operations also cover bookings entered by the vendor or admin as booked dates
 * (media_booked_date, no website / Admin Booking order). Such an operation
 * points at its booked-date row instead of an order, so the order columns
 * become optional.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media_operations', function (Blueprint $table) {
            $table->unsignedBigInteger('order_id')->nullable()->change();
            $table->unsignedBigInteger('order_item_id')->nullable()->change();
        });

        if (!Schema::hasColumn('media_operations', 'booked_date_id')) {
            Schema::table('media_operations', function (Blueprint $table) {
                $table->unsignedBigInteger('booked_date_id')->nullable()->after('order_item_id')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('media_operations', 'booked_date_id')) {
            Schema::table('media_operations', function (Blueprint $table) {
                $table->dropIndex(['booked_date_id']);
                $table->dropColumn('booked_date_id');
            });
        }
        // order_id / order_item_id stay nullable: rows made from booked dates have none.
    }
};
