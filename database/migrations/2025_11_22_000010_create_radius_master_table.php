<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRadiusMasterTable extends Migration
{
    public function up()
    {
        Schema::create('radius_master', function (Blueprint $table) {
            $table->id();
            $table->string('radius', 50); // e.g. 5km-10km
            $table->boolean('is_active')->default(1);
            $table->boolean('is_deleted')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('radius_master');
    }
}
