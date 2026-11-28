<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Assigns a unique hoarding_code (HD000001, HD000002, ...) to every
     * existing media_management row that still has none. This is a SECOND
     * backfill pass: the original backfill (2026_06_22_100005) only ran once
     * and never reached the live data, so older rows still render as "-".
     *
     * Differences from the first backfill, on purpose:
     *  - catches BOTH NULL and empty-string ('') codes.
     *  - numbering continues from the current max code, so already-coded rows
     *    (HD000001..HD000003) are untouched and the new codes never collide.
     *  - idempotent: re-running assigns nothing once every row has a code.
     */
    public function up(): void
    {
        DB::transaction(function () {
            // Highest existing numeric suffix, so we continue cleanly.
            $maxCode = DB::table('media_management')
                ->whereNotNull('hoarding_code')
                ->where('hoarding_code', 'like', 'HD%')
                ->orderByRaw('CAST(SUBSTRING(hoarding_code, 3) AS UNSIGNED) DESC')
                ->value('hoarding_code');

            $seq = $maxCode ? (int) substr($maxCode, 2) : 0;

            // Rows with no usable code: NULL or blank/whitespace.
            $rows = DB::table('media_management')
                ->where(function ($q) {
                    $q->whereNull('hoarding_code')
                        ->orWhereRaw("TRIM(hoarding_code) = ''");
                })
                ->orderBy('id')
                ->pluck('id');

            foreach ($rows as $id) {
                $seq++;
                $code = 'HD' . str_pad($seq, 6, '0', STR_PAD_LEFT);

                DB::table('media_management')
                    ->where('id', $id)
                    ->update(['hoarding_code' => $code]);
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * No-op for data safety: we never blank codes on rollback.
     */
    public function down(): void
    {
        // no-op
    }
};
