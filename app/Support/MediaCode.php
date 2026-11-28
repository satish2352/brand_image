<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * The site code a media record is issued: HD000034, BS004944.
 *
 * One scheme per category that has one, matched on the category slug, because
 * that is the only thing every caller already has — the single Add form, the
 * bulk importer and the views all reach this from a different direction.
 *
 * A category not listed here is issued no code at all, and its record shows a
 * dash. That is deliberate: a number minted for media nobody identifies that
 * way burns sequence positions and makes the code meaningless as a reference.
 * Adding a category to SCHEMES is the whole job of giving it codes.
 *
 * Every code lives in media_management.hoarding_code. The column predates the
 * second scheme and keeps its name so exports, imports, the campaign PDFs and
 * the search filter keep working unchanged; what it holds is "this record's
 * code", and labelFor() is what decides the words shown beside it.
 */
class MediaCode
{
    /** How many digits follow the prefix. HD000034, BS004944. */
    public const WIDTH = 6;

    /**
     * slug fragment => [prefix, label]
     *
     * Matched as a substring so 'hoardings-billboards', 'hoarding' and a
     * renamed 'Bus Shelters' all resolve. Order matters only if a slug could
     * match two fragments, which none of these do.
     */
    private const SCHEMES = [
        'hoarding'    => ['HD', 'Hoarding Code'],
        'billboard'   => ['HD', 'Hoarding Code'],
        'bus-shelter' => ['BS', 'Bus Shelter Code'],
    ];

    /** The prefix this category's media are coded with, or null for none. */
    public static function prefixFor(?string $slug): ?string
    {
        return self::schemeFor($slug)[0] ?? null;
    }

    /** What to call the code on screen. */
    public static function labelFor(?string $slug): string
    {
        return self::schemeFor($slug)[1] ?? 'Site Code';
    }

    /**
     * What to call a code, read off the code itself.
     *
     * For views: the record already carries HD000034 or BS004944, and asking
     * the code what it is saves threading the category slug through every
     * query that renders one.
     */
    public static function labelForCode(?string $code): string
    {
        $code = strtoupper(trim((string) $code));

        foreach (self::SCHEMES as $scheme) {
            if ($code !== '' && str_starts_with($code, $scheme[0])) {
                return $scheme[1];
            }
        }

        return 'Site Code';
    }

    /** Whether this category is issued a code at all. */
    public static function issuedFor(?string $slug): bool
    {
        return self::prefixFor($slug) !== null;
    }

    /**
     * The highest number already used under a prefix, or 0.
     *
     * LENGTH(prefix) + 1 rather than a hard 3: BS and HD happen to be the same
     * width, but the next scheme need not be.
     */
    public static function sequence(string $prefix): int
    {
        $max = DB::table('media_management')
            ->whereNotNull('hoarding_code')
            ->where('hoarding_code', 'like', $prefix . '%')
            ->orderByRaw('CAST(SUBSTRING(hoarding_code, ?) AS UNSIGNED) DESC', [strlen($prefix) + 1])
            ->value('hoarding_code');

        return $max ? (int) substr($max, strlen($prefix)) : 0;
    }

    /** The next free code under a prefix. */
    public static function next(string $prefix): string
    {
        return self::format($prefix, self::sequence($prefix) + 1);
    }

    public static function format(string $prefix, int $sequence): string
    {
        return $prefix . str_pad((string) $sequence, self::WIDTH, '0', STR_PAD_LEFT);
    }

    /** @return array{0:string,1:string}|null */
    private static function schemeFor(?string $slug): ?array
    {
        $slug = strtolower(trim((string) $slug));

        if ($slug === '') {
            return null;
        }

        foreach (self::SCHEMES as $fragment => $scheme) {
            if (str_contains($slug, $fragment)) {
                return $scheme;
            }
        }

        return null;
    }
}
