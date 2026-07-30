<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Repair drifted NOT NULL constraints on media_management.
 *
 * Every column below is declared nullable by
 * 2025_12_17_083321_create_media_management_table (and, for highway_id /
 * hoarding_code, by 2026_06_22_100004), because each one belongs to a single
 * media category: a Hoarding fills Area Type, Illumination, Facing and Address;
 * a Wall Painting fills none of them; a Transit Media fills Transit Type
 * instead. The Add Media form hides the fields a category does not use, and
 * MediaImportSchema::columnsForSlug() leaves them out of that category's
 * upload template for the same reason.
 *
 * On installations where the table was built before those migrations (or by
 * hand from an older dump) some of these columns ended up NOT NULL. The
 * Add Media form still appeared to work there, because a hidden <select>
 * submits an empty string that MySQL silently coerces to 0 outside strict
 * mode. The bulk importer writes a real NULL, and NULL into a NOT NULL column
 * is error 1048 whatever the sql_mode:
 *
 *   SQLSTATE[23000]: Integrity constraint violation: 1048
 *   Column 'areatype_id' cannot be null
 *
 * areatype_id was simply the first offender MySQL reported; every column in
 * the same INSERT is a candidate, so all of them are checked here rather than
 * fixing one and waiting for the next upload to name the next one.
 *
 * Existing rows are not touched. Columns that are already nullable are left
 * alone, so this is safe to re-run and a no-op on a correctly migrated
 * database.
 */
return new class extends Migration
{
    /**
     * Columns that hold either an auto-generated value or a field belonging to
     * one specific category, and must therefore accept NULL.
     */
    private const OPTIONAL_COLUMNS = [
        // category driven master references
        'areatype_id',
        'illumination_id',
        'highway_id',
        'facing_id',
        'radius_id',
        'vendor_id',
        // codes and free text
        'media_code',
        'hoarding_code',
        'media_title',
        'address',
        'facing',
        // Mall Media
        'mall_name',
        'media_format',
        // Airport Branding
        'airport_name',
        'zone_type',
        'media_type',
        // Transit / Transmit Media
        'transit_type',
        'branding_type',
        'vehicle_count',
        // Office Branding
        'building_name',
        'wall_length',
        // derived / uploaded
        'area_auto',
        'panorama_image',
    ];

    public function up(): void
    {
        if (!Schema::hasTable('media_management')) {
            return;
        }

        if (DB::connection()->getDriverName() !== 'mysql') {
            // The nullability is already correct in the schema definition; only
            // MySQL installations have been seen to drift.
            return;
        }

        foreach ($this->drifted() as $column) {
            DB::statement($this->modifyToNullable($column));
        }
    }

    /**
     * Deliberately irreversible.
     *
     * Restoring NOT NULL would have to invent a value for every row a category
     * legitimately left empty, turning "this Wall Painting has no Area Type"
     * into a reference to area type 0. Losing that distinction is worse than
     * being unable to roll back a constraint that was never intended.
     */
    public function down(): void
    {
        // no-op
    }

    /**
     * The subset of OPTIONAL_COLUMNS that exists on this database and is
     * currently NOT NULL, with the type information needed to rebuild it.
     *
     * @return array<int,object>
     */
    private function drifted(): array
    {
        return DB::table('information_schema.COLUMNS')
            ->select([
                'COLUMN_NAME',
                'COLUMN_TYPE',
                'CHARACTER_SET_NAME',
                'COLLATION_NAME',
                'COLUMN_COMMENT',
            ])
            ->where('TABLE_SCHEMA', DB::connection()->getDatabaseName())
            ->where('TABLE_NAME', 'media_management')
            ->where('IS_NULLABLE', 'NO')
            ->whereIn('COLUMN_NAME', self::OPTIONAL_COLUMNS)
            ->get()
            ->all();
    }

    /**
     * Rebuild the column exactly as it is, but nullable — the stored type,
     * width, charset, collation and comment are all carried over so nothing
     * else about the column changes.
     */
    private function modifyToNullable(object $column): string
    {
        $sql = 'ALTER TABLE `media_management` MODIFY `'
            . $column->COLUMN_NAME . '` ' . $column->COLUMN_TYPE;

        if (!empty($column->CHARACTER_SET_NAME)) {
            $sql .= ' CHARACTER SET ' . $column->CHARACTER_SET_NAME;
        }

        if (!empty($column->COLLATION_NAME)) {
            $sql .= ' COLLATE ' . $column->COLLATION_NAME;
        }

        $sql .= ' NULL';

        if (!empty($column->COLUMN_COMMENT)) {
            $sql .= ' COMMENT ' . DB::connection()->getPdo()->quote($column->COLUMN_COMMENT);
        }

        return $sql;
    }
};
