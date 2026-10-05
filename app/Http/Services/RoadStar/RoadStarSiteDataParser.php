<?php

namespace App\Http\Services\RoadStar;

/**
 * Turns one site's value from POST /sitedata into something storable.
 *
 * RoadStar answers {"<site id>": <value>} per site, where <value> is:
 *   - the 13 metrics, as a nested array (as the PDF shows) or as that array
 *     JSON-encoded in a string (what the dev server actually sends);
 *   - a message string: "Site not available. Please add the site first", or
 *     "error : The data is not available for the start date selected…";
 *   - null: the site exists but RoadStar has no data for the period asked.
 *
 * Metric order is fixed by the documentation ("Response Structure"):
 *   0 Unique Reach ["776044"]          7  OTS 1+…5+ [[labels],[values]]
 *   1 Impressions ["3138517"]          8  Weekday vs weekend
 *   2 Frequency 4.04                   9  Age group %
 *   3 Date-wise impressions            10 Gender %
 *   4 Day-wise average impressions     11 Mobile affluence %
 *   5 Month-wise impressions           12 Mobile phone brand %
 *   6 Hourly average impressions
 * A breakdown section is [[labels], [values]] → [['label'=>…, 'value'=>…], …].
 * A section missing or malformed is stored as null — never filled in.
 */
class RoadStarSiteDataParser
{
    public const DATA          = 'data';
    public const NO_DATA       = 'no_data';
    public const NOT_AVAILABLE = 'not_available';
    public const REJECTED      = 'rejected';
    public const INVALID       = 'invalid';

    /** Breakdown sections by index → roadstar_audience_data column. */
    public const BREAKDOWNS = [
        3  => 'date_wise_impressions',
        4  => 'day_wise_avg_impressions',
        5  => 'month_wise_impressions',
        6  => 'hourly_avg_impressions',
        7  => 'effective_frequency',
        8  => 'weekday_weekend_impressions',
        9  => 'age_groups',
        10 => 'gender',
        11 => 'mobile_affluence',
        12 => 'mobile_brands',
    ];

    /**
     * @return array{state: string, message: ?string, metrics: ?array, raw: mixed}
     *         metrics: unique_reach, impressions, frequency + BREAKDOWNS columns
     */
    public static function parse(mixed $value): array
    {
        if ($value === null) {
            return self::result(self::NO_DATA, 'RoadStar has no audience data for this site in the requested period.');
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $value = $decoded;
            } else {
                $message = trim(mb_substr($value, 0, 300));
                $state = (stripos($message, 'not available') !== false && stripos($message, 'add the site') !== false)
                    ? self::NOT_AVAILABLE
                    : self::REJECTED;

                return self::result($state, 'RoadStar: ' . $message, null, $value);
            }
        }

        if (!is_array($value) || !array_is_list($value)) {
            return self::result(self::INVALID, 'RoadStar returned audience data in an unexpected format.', null, $value);
        }

        $metrics = [
            'unique_reach' => self::firstNumber($value[0] ?? null),
            'impressions'  => self::firstNumber($value[1] ?? null),
            'frequency'    => self::firstNumber($value[2] ?? null),
        ];

        if ($metrics['unique_reach'] === null || $metrics['impressions'] === null) {
            return self::result(self::INVALID, 'RoadStar audience data is missing Unique Reach or Impressions.', null, $value);
        }

        $metrics['unique_reach'] = (int) round($metrics['unique_reach']);
        $metrics['impressions'] = (int) round($metrics['impressions']);
        $metrics['frequency'] = $metrics['frequency'] !== null ? round((float) $metrics['frequency'], 2) : null;

        foreach (self::BREAKDOWNS as $index => $column) {
            $metrics[$column] = self::pairs($value[$index] ?? null);
        }

        return self::result(self::DATA, null, $metrics, $value);
    }

    /** ["776044"] → 776044; 4.04 → 4.04; anything else → null. */
    private static function firstNumber(mixed $section): int|float|null
    {
        if (is_array($section)) {
            $section = $section[0] ?? null;
        }

        return self::number($section);
    }

    /** [[labels], [values]] → [['label' => 'Mon', 'value' => 318368], …] */
    private static function pairs(mixed $section): ?array
    {
        if (!is_array($section) || count($section) < 2 || !is_array($section[0] ?? null) || !is_array($section[1] ?? null)) {
            return null;
        }

        $labels = array_values($section[0]);
        $values = array_values($section[1]);
        $rows = [];
        for ($i = 0, $n = min(count($labels), count($values)); $i < $n; $i++) {
            if (!is_scalar($labels[$i])) {
                continue;
            }
            $rows[] = ['label' => (string) $labels[$i], 'value' => self::number($values[$i])];
        }

        return $rows ?: null;
    }

    private static function number(mixed $value): int|float|null
    {
        if (is_int($value) || is_float($value)) {
            return is_finite((float) $value) ? $value : null;
        }
        if (is_string($value) && is_numeric(trim($value))) {
            $value = trim($value);
            return preg_match('/^-?\d+$/', $value) ? (int) $value : (float) $value;
        }

        return null;
    }

    private static function result(string $state, ?string $message, ?array $metrics = null, mixed $raw = null): array
    {
        return ['state' => $state, 'message' => $message, 'metrics' => $metrics, 'raw' => $raw];
    }
}
