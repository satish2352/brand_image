<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_images', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('media_id');
            $table->string('filename');
            $table->string('path'); // storage path
            $table->boolean('is_deleted')->default(false); // soft delete for images
            $table->timestamps();

            $table->foreign('media_id')->references('id')->on('media_master')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_images');
    }
};
