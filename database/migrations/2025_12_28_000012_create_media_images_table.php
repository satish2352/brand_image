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
        Schema::create('media_images', function (Blueprint $table) {


            $table->engine = 'InnoDB';


            $table->id();


            $table->unsignedBigInteger('media_id');


            $table->string('images');


            $table->boolean('is_active')->default(true);
            $table->boolean('is_deleted')->default(false);


            $table->timestamps();


            $table->foreign('media_id')
                ->references('id')
                ->on('media_management')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media_images');
    }
};
