<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('session_id', 100)->nullable();

            $table->unsignedBigInteger('media_id')->nullable();
            $table->unsignedBigInteger('campaign_id')->nullable();

            $table->decimal('price', 10, 2);
            $table->enum('cart_type', ['NORMAL', 'CAMPAIGN'])->default('NORMAL');
            $table->enum('status', ['ACTIVE', 'ORDERED'])->default('ACTIVE');
            $table->integer('qty')->default(1);
            $table->tinyInteger('is_active')->default(1);
            $table->tinyInteger('is_deleted')->default(0);
            $table->timestamps();

            // $table->foreign('user_id')
            //     ->references('id')
            //     ->on('users')
            //     ->onDelete('cascade');

            // $table->foreign('media_id')
            //     ->references('id')
            //     ->on('media_management')
            //     ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
