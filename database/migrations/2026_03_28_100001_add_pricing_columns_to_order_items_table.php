<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('per_day_price', 10, 2)->nullable()->after('price');
            $table->integer('total_days')->nullable()->after('per_day_price');
            $table->decimal('total_price', 10, 2)->nullable()->after('total_days');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['per_day_price', 'total_days', 'total_price']);
        });
    }
};
