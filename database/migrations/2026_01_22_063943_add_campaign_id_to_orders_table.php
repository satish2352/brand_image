<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('campaign_id')
                ->nullable()
                ->after('user_id');

            // If campaigns table exists, uncomment below
            // $table->foreign('campaign_id')
            //       ->references('id')
            //       ->on('campaigns')
            //       ->onDelete('set null')
            //       ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // If foreign key added
            // $table->dropForeign(['campaign_id']);

            $table->dropColumn('campaign_id');
        });
    }
};
