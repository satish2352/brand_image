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
            $table->string('media_code')->unique();
            $table->string('media_title');
            $table->text('address');
            $table->decimal('width', 8, 2);
            $table->decimal('height', 8, 2);
            $table->unsignedBigInteger('illumination_id');
            $table->string('facing_id');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);

            // Booking & pricing
            $table->integer('minimum_booking_days')->default(1);
            $table->float('price', 10, 2);
            $table->string('vendor_name');
            // $table->json('images');
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
