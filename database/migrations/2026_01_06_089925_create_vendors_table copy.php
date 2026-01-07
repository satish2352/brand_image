<?php

// database/migrations/xxxx_xx_xx_create_illuminations_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('illuminations', function (Blueprint $table) {
            $table->id();
            $table->string('illumination_name', 255)->unique();
            $table->tinyInteger('is_active')->default(1);
            $table->tinyInteger('is_deleted')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('illuminations');
    }
};
