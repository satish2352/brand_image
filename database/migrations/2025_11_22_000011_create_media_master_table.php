<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_master', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('state_id');
            $table->unsignedBigInteger('district_id');
            $table->unsignedBigInteger('city_id');
            $table->string('location_name'); // user visible name
            $table->unsignedBigInteger('type_id'); // FK types table
            $table->unsignedBigInteger('radius_id')->nullable(); // FK radius_master (optional)
            $table->decimal('price', 12, 2)->nullable();
            $table->text('address')->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['Available','Booked','Under Maintenance'])->default('Available');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_deleted')->default(false);
            $table->timestamps();

            $table->foreign('state_id')->references('id')->on('states')->onDelete('cascade');
            $table->foreign('district_id')->references('id')->on('districts')->onDelete('cascade');
            $table->foreign('city_id')->references('id')->on('cities')->onDelete('cascade');
            $table->foreign('type_id')->references('id')->on('types')->onDelete('restrict');
            // radius_master foreign optional - ensure table exists
            $table->foreign('radius_id')->references('id')->on('radius_master')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_master');
    }
};
