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
        Schema::create('plant_masters', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('plant_code');
            $table->string('plant_name');
            $table->string('address')->nullable();
            $table->string('city');
            $table->string('plant_short_name')->nullable();
            $table->string('created_by')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_deleted')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plant_masters');
    }
};
