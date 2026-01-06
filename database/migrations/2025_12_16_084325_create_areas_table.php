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
        Schema::create('areas', function (Blueprint $table) {
            $table->id(); // bigint auto increment

            $table->unsignedBigInteger('state_id');
            $table->unsignedBigInteger('district_id');
            $table->unsignedBigInteger('city_id');
            $table->string('area_name', 255);
            $table->string('common_stdiciar_name', 255);
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->tinyInteger('is_active')->default(1);
            $table->tinyInteger('is_deleted')->default(0);

            $table->timestamps(); // created_at, updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('areas');
    }
};
