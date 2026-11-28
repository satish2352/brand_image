<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Assigns a unique hoarding_code (HD000001, HD000002, ...) to every
     * existing media_management row that does not already have one.
     * Idempotent: rows that already have a code are skipped, and numbering
     * continues from the current max so re-running never collides.
     */
    public function up(): void
    {
        DB::transaction(function () {
            // Determine the highest existing numeric suffix so we continue cleanly.
            $maxCode = DB::table('media_management')
                ->whereNotNull('hoarding_code')
                ->where('hoarding_code', 'like', 'HD%')
                ->orderByRaw('CAST(SUBSTRING(hoarding_code, 3) AS UNSIGNED) DESC')
                ->value('hoarding_code');

            $seq = $maxCode ? (int) substr($maxCode, 2) : 0;

            $rows = DB::table('media_management')
                ->whereNull('hoarding_code')
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
     * Intentionally a no-op for data safety: dropping the column (handled by the
     * schema migration's down()) already removes the codes. We do not blank
     * codes here to avoid wiping data on an isolated rollback.
     */
    public function down(): void
    {
        // no-op
    }
};
