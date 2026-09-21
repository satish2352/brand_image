<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * How many panels of this size the shelter carries.
 *
 * A Bus Shelter's Front is not always one board: a site may have two or three
 * identical faces on the same side. Recording that as a quantity beside the
 * dimensions keeps one row per position - which is what the unique key on
 * (media_id, position) is for - instead of inventing front_2, front_3.
 *
 * Defaults to 1 so every panel already stored keeps meaning exactly what it
 * meant before this column existed.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('media_location_sizes') || Schema::hasColumn('media_location_sizes', 'quantity')) {
            return;
        }

        Schema::table('media_location_sizes', function (Blueprint $table) {
            $table->unsignedSmallInteger('quantity')->default(1)->after('height');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('media_location_sizes') && Schema::hasColumn('media_location_sizes', 'quantity')) {
            Schema::table('media_location_sizes', function (Blueprint $table) {
                $table->dropColumn('quantity');
            });
        }
    }
};
