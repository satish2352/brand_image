<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Pivot table for the many-to-many relationship between hoardings
     * (media_management) and landmarks. One hoarding can have multiple
     * landmarks and one landmark can belong to multiple hoardings.
     */
    public function up(): void
    {
        Schema::create('media_landmark', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('media_id');
            $table->unsignedBigInteger('landmark_id');
            $table->timestamps();

            $table->foreign('media_id')
                ->references('id')->on('media_management')
                ->onDelete('cascade');

            $table->foreign('landmark_id')
                ->references('id')->on('landmark')
                ->onDelete('cascade');

            $table->unique(['media_id', 'landmark_id'], 'uq_media_landmark');
            $table->index('media_id', 'idx_media_landmark_media');
            $table->index('landmark_id', 'idx_media_landmark_landmark');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media_landmark');
    }
};
