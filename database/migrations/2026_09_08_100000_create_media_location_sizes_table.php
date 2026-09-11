<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Per-panel sizes for media that is not a single rectangle.
 *
 * A Hoarding has one Width x Height. A Bus Shelter does not - it advertises on
 * a Front, a Back and a Side panel, each with its own dimensions - so those
 * live in their own rows here rather than as six more columns on the already
 * very wide media_management table. A future position is then data, not a
 * migration.
 *
 * media_management.width / height are also relaxed to NULL by this migration:
 * a Bus Shelter genuinely has no single pair, and storing 0 x 0 would feed a
 * real-looking zero into the site's Media Size filter.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('media_location_sizes')) {
            Schema::create('media_location_sizes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('media_id');
                // front | back | side - see MediaLocationSize::POSITIONS
                $table->string('position', 20);
                $table->decimal('width', 8, 2)->nullable();
                $table->decimal('height', 8, 2)->nullable();
                $table->timestamps();

                // One row per panel per media, and the lookup the form and the
                // details page both make.
                $table->unique(['media_id', 'position']);
                $table->index('media_id');
            });
        }

        $this->relaxDimensions();
    }

    public function down(): void
    {
        Schema::dropIfExists('media_location_sizes');
        // width / height are deliberately left nullable - see the note in
        // 2026_07_30_100000_make_optional_media_columns_nullable.
    }

    /**
     * Let media_management.width / height accept NULL for categories that have
     * no single dimension pair. Existing values are untouched, and a column
     * that is already nullable is left alone, so this is safe to re-run.
     */
    private function relaxDimensions(): void
    {
        if (!Schema::hasTable('media_management') || DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        $notNull = DB::table('information_schema.COLUMNS')
            ->select(['COLUMN_NAME', 'COLUMN_TYPE'])
            ->where('TABLE_SCHEMA', DB::connection()->getDatabaseName())
            ->where('TABLE_NAME', 'media_management')
            ->where('IS_NULLABLE', 'NO')
            ->whereIn('COLUMN_NAME', ['width', 'height'])
            ->get();

        foreach ($notNull as $column) {
            DB::statement(
                'ALTER TABLE `media_management` MODIFY `'
                . $column->COLUMN_NAME . '` ' . $column->COLUMN_TYPE . ' NULL'
            );
        }
    }
};
