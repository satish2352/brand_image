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
        // Schema::create('notifications', function (Blueprint $table) {
        //     $table->bigIncrements('id');
        //     $table->string('type');
        //     $table->morphs('notifiable');
        //     $table->text('data');
        //     $table->timestamp('read_at')->nullable();
        //     $table->timestamps();
        // });
        Schema::create('notifications', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('media_id')->nullable();
            $table->unsignedBigInteger('user_id')->notNull();
            $table->unsignedBigInteger('order_id')->notNull();

            $table->boolean('is_read')->default(0);   // 0 = unread, 1 = read

            $table->timestamps();  // created_at & updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
