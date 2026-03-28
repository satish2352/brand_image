<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // media_management — heavily used in search & filters
        Schema::table('media_management', function (Blueprint $table) {
            $table->index('city_id',     'idx_mm_city_id');
            $table->index('area_id',     'idx_mm_area_id');
            $table->index('category_id', 'idx_mm_category_id');
            $table->index('state_id',    'idx_mm_state_id');
            $table->index('district_id', 'idx_mm_district_id');
            $table->index('is_active',   'idx_mm_is_active');
            $table->index(['latitude', 'longitude'], 'idx_mm_lat_lng');
        });

        // areas — used in location filtering & joins
        Schema::table('areas', function (Blueprint $table) {
            $table->index('city_id',     'idx_areas_city_id');
            $table->index('district_id', 'idx_areas_district_id');
            $table->index('state_id',    'idx_areas_state_id');
            $table->index('is_active',   'idx_areas_is_active');
        });

        // vendors — used in media management joins
        Schema::table('vendors', function (Blueprint $table) {
            $table->index('city_id',     'idx_vendors_city_id');
            $table->index('district_id', 'idx_vendors_district_id');
            $table->index('state_id',    'idx_vendors_state_id');
            $table->index('is_active',   'idx_vendors_is_active');
        });

        // media_booked_date — queried on every media availability check
        Schema::table('media_booked_date', function (Blueprint $table) {
            $table->index('media_id',              'idx_mbd_media_id');
            $table->index(['from_date', 'to_date'], 'idx_mbd_date_range');
        });

        // cart_items — queried on every page load via View Composer
        Schema::table('cart_items', function (Blueprint $table) {
            $table->index('user_id',     'idx_cart_user_id');
            $table->index('session_id',  'idx_cart_session_id');
            $table->index('campaign_id', 'idx_cart_campaign_id');
            $table->index('media_id',    'idx_cart_media_id');
        });

        // orders — used in user order history & admin reports
        Schema::table('orders', function (Blueprint $table) {
            $table->index('user_id',        'idx_orders_user_id');
            $table->index('payment_status', 'idx_orders_payment_status');
            $table->index('created_at',     'idx_orders_created_at');
        });

        // order_items — foreign key on order_id already exists; add media_id
        Schema::table('order_items', function (Blueprint $table) {
            $table->index('media_id', 'idx_order_items_media_id');
        });

        // notifications — queried for unread counts & admin alerts
        Schema::table('notifications', function (Blueprint $table) {
            $table->index('user_id',  'idx_notif_user_id');
            $table->index('order_id', 'idx_notif_order_id');
            $table->index('media_id', 'idx_notif_media_id');
            $table->index('is_read',  'idx_notif_is_read');
        });

        // website_users — used in login, OTP verification, status checks
        Schema::table('website_users', function (Blueprint $table) {
            $table->index('mobile_number', 'idx_wu_mobile_number');
            $table->index('is_active',     'idx_wu_is_active');
        });
    }

    public function down(): void
    {
        Schema::table('media_management', function (Blueprint $table) {
            $table->dropIndex('idx_mm_city_id');
            $table->dropIndex('idx_mm_area_id');
            $table->dropIndex('idx_mm_category_id');
            $table->dropIndex('idx_mm_state_id');
            $table->dropIndex('idx_mm_district_id');
            $table->dropIndex('idx_mm_is_active');
            $table->dropIndex('idx_mm_lat_lng');
        });

        Schema::table('areas', function (Blueprint $table) {
            $table->dropIndex('idx_areas_city_id');
            $table->dropIndex('idx_areas_district_id');
            $table->dropIndex('idx_areas_state_id');
            $table->dropIndex('idx_areas_is_active');
        });

        Schema::table('vendors', function (Blueprint $table) {
            $table->dropIndex('idx_vendors_city_id');
            $table->dropIndex('idx_vendors_district_id');
            $table->dropIndex('idx_vendors_state_id');
            $table->dropIndex('idx_vendors_is_active');
        });

        Schema::table('media_booked_date', function (Blueprint $table) {
            $table->dropIndex('idx_mbd_media_id');
            $table->dropIndex('idx_mbd_date_range');
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropIndex('idx_cart_user_id');
            $table->dropIndex('idx_cart_session_id');
            $table->dropIndex('idx_cart_campaign_id');
            $table->dropIndex('idx_cart_media_id');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('idx_orders_user_id');
            $table->dropIndex('idx_orders_payment_status');
            $table->dropIndex('idx_orders_created_at');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex('idx_order_items_media_id');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('idx_notif_user_id');
            $table->dropIndex('idx_notif_order_id');
            $table->dropIndex('idx_notif_media_id');
            $table->dropIndex('idx_notif_is_read');
        });

        Schema::table('website_users', function (Blueprint $table) {
            $table->dropIndex('idx_wu_mobile_number');
            $table->dropIndex('idx_wu_is_active');
        });
    }
};
