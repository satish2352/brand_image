<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Enquiries sent from a vendor's website Contact page (/{vendor-slug}/contact).
 * Each belongs to one vendor and is listed in that vendor's panel.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('vendor_enquiries')) {
            return;
        }

        Schema::create('vendor_enquiries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vendor_id')->index();
            $table->unsignedBigInteger('media_id')->nullable();
            $table->string('name', 120);
            $table->string('email', 150);
            $table->string('mobile', 20);
            $table->string('company', 150)->nullable();
            $table->string('city', 100)->nullable();
            $table->text('message');
            // new | contacted | closed
            $table->string('status', 20)->default('new')->index();
            $table->timestamp('read_at')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_enquiries');
    }
};
