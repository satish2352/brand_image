<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A short record of every bulk upload that was actually published.
 *
 * A sheet whose Hoarding Code cells are blank means "add these as new records",
 * and the codes it gets are generated at publish time — they live in the
 * database, not in the file on the admin's computer. Uploading that same
 * untouched file a second time therefore has nothing to match on and adds a
 * second copy of every row, which is almost never what was intended.
 *
 * Keeping the hash of each published sheet lets the preview screen say "this
 * exact file was already imported, here is when and what it added" before
 * anything is written a second time.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('media_import_history')) {
            return;
        }

        Schema::create('media_import_history', function (Blueprint $table) {
            $table->id();
            // sha256 of the uploaded sheet's bytes.
            $table->string('file_hash', 64)->index('idx_import_history_hash');
            $table->string('file_name')->nullable();
            $table->unsignedInteger('rows_published')->default(0);
            $table->unsignedInteger('inserted')->default(0);
            $table->unsignedInteger('updated')->default(0);
            $table->unsignedBigInteger('published_by')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_import_history');
    }
};
