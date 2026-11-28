<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * 1. Seed the Landmark master with the standard landmark list.
     * 2. Seed the Highway master with the example highways.
     * 3. Migrate every hoarding currently using the "Highway" Area Type:
     *      - point it to a default highway ("Other Highways")
     *      - clear its areatype_id
     * 4. Soft-delete the "Highway" row from the areatype master so it no
     *    longer appears in the Area Type dropdown (Feature 2).
     */
    public function up(): void
    {
        DB::transaction(function () {
            $now = now();

            /* ---------------- 1. Landmark master seed ---------------- */
            $landmarks = [
                'Bus Stand',
                'Hospital',
                'Petrol Pump',
                'Railway Station',
                'Airport',
                'Mall',
                'Market',
                'Colleges',
                'Other Major Landmarks',
            ];

            foreach ($landmarks as $name) {
                $exists = DB::table('landmark')
                    ->where('landmark_name', $name)
                    ->where('is_deleted', 0)
                    ->exists();

                if (!$exists) {
                    DB::table('landmark')->insert([
                        'landmark_name' => $name,
                        'is_active'     => 1,
                        'is_deleted'    => 0,
                        'created_at'    => $now,
                        'updated_at'    => $now,
                    ]);
                }
            }

            /* ---------------- 2. Highway master seed ---------------- */
            $highways = [
                ['Mumbai-Agra Highway', 'National'],
                ['Pune-Bangalore Highway', 'National'],
                ['NH-48', 'National'],
                ['NH-60', 'National'],
                ['Samruddhi Mahamarg', 'Expressway'],
                ['Other Highways', 'Other'],
            ];

            foreach ($highways as [$name, $type]) {
                $exists = DB::table('highway')
                    ->where('highway_name', $name)
                    ->where('is_deleted', 0)
                    ->exists();

                if (!$exists) {
                    DB::table('highway')->insert([
                        'highway_name' => $name,
                        'highway_type' => $type,
                        'is_active'    => 1,
                        'is_deleted'   => 0,
                        'created_at'   => $now,
                        'updated_at'   => $now,
                    ]);
                }
            }

            /* ---------------- 3 + 4. Migrate Highway Area Type ---------------- */
            $highwayAreaTypeIds = DB::table('areatype')
                ->where('is_deleted', 0)
                ->whereRaw('LOWER(areatype_name) = ?', ['highway'])
                ->pluck('id')
                ->all();

            if (!empty($highwayAreaTypeIds)) {
                $defaultHighwayId = DB::table('highway')
                    ->where('highway_name', 'Other Highways')
                    ->where('is_deleted', 0)
                    ->value('id');

                // Reassign affected hoardings: keep them intact, move them to a
                // highway and clear the (now removed) Highway area type.
                DB::table('media_management')
                    ->whereIn('areatype_id', $highwayAreaTypeIds)
                    ->update([
                        'highway_id'  => $defaultHighwayId,
                        'areatype_id' => null,
                        'updated_at'  => $now,
                    ]);

                // Soft-delete the Highway area type row(s).
                DB::table('areatype')
                    ->whereIn('id', $highwayAreaTypeIds)
                    ->update([
                        'is_active'  => 0,
                        'is_deleted' => 1,
                        'updated_at' => $now,
                    ]);
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * Restores the "Highway" area type row(s) so the dropdown shows it again.
     * The hoardings' previous areatype linkage is not automatically restored
     * (their original value was "Highway"); they keep their assigned highway_id.
     */
    public function down(): void
    {
        DB::table('areatype')
            ->whereRaw('LOWER(areatype_name) = ?', ['highway'])
            ->update([
                'is_active'  => 1,
                'is_deleted' => 0,
                'updated_at' => now(),
            ]);
    }
};
