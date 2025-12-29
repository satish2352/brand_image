<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('media_management', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('state_id');
            $table->unsignedBigInteger('district_id');
            $table->unsignedBigInteger('city_id');
            $table->unsignedBigInteger('area_id');
            $table->unsignedBigInteger('category_id');
            $table->string('media_code')->nullable();
            $table->string('media_title')->nullable();
            $table->text('address')->nullable();
            $table->decimal('width', 8, 2);
            $table->decimal('height', 8, 2);
            $table->unsignedBigInteger('illumination_id')->nullable();
            $table->string('facing_id')->nullable();
            $table->string('radius_id')->nullable();
            $table->string('area_type')->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->integer('minimum_booking_days')->nullable();
            $table->float('price', 10, 2);
            $table->string('vendor_name');
            $table->string('mall_name')->nullable();
            $table->string('media_format')->nullable();
            $table->string('airport_name')->nullable();
            $table->string('zone_type')->nullable();
            $table->string('media_type')->nullable();
            $table->string('transit_type')->nullable();
            $table->string('branding_type')->nullable();
            $table->string('vehicle_count')->nullable();
            $table->string('building_name')->nullable();
            $table->string('wall_length')->nullable();
            $table->string('area_auto')->nullable();
            $table->tinyInteger('is_active')->default(1);
            $table->tinyInteger('is_deleted')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media_management');
    }
};
