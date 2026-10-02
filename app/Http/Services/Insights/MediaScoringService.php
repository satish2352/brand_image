<?php

namespace App\Http\Services\Insights;

/**
 * Documented, rule-based scoring for the Media Location Insights.
 *
 * Nothing here fabricates traffic, footfall, impressions or ROI. Every score
 * returns a status:
 *   estimated    all inputs the formula needs were present
 *   incomplete   calculated from some inputs; `missing` lists the rest
 *   unavailable  a required input is missing, so no number is shown
 *
 * All inputs come from our own media record or from Google Maps places
 * returned by SerpApi. No AI provider is involved.
 */
class MediaScoringService
{
    /* Keywords matched against a place's name + category label + category keys
       (lower-case). Covers Google Maps types (SerpApi) and Geoapify keys such
       as education.college, office.company, commercial.shopping_mall. */
    public const AUDIENCE_RULES = [
        'Students'  => ['school', 'college', 'university', 'institute', 'coaching', 'academy', 'hostel', 'library'],
        'Corporate' => ['office', 'corporate', 'it park', 'business park', 'company', 'bank', 'coworking', 'tech park', 'industrial', 'building.commercial', 'commercial building'],
        'Family'    => ['mall', 'supermarket', 'hypermarket', 'park', 'restaurant', 'cinema', 'theatre', 'temple', 'hospital', 'clinic', 'market', 'store', 'garden', 'playground', 'attraction', 'sights'],
        'Premium'   => ['hotel', 'resort', 'jewel', 'showroom', 'car dealer', 'boutique', 'luxury', 'club', 'fine dining'],
    ];

    /* Place types that signal a premium catchment for the Premium Location Rating. */
    public const PREMIUM_PLACE_KEYWORDS = ['mall', 'hotel', 'resort', 'jewel', 'showroom', 'car dealer', 'bank', 'it park', 'business park', 'corporate', 'multiplex', 'office.it', 'coworking', 'office.financial', 'university'];

    public const SECTORS = ['FMCG', 'Real Estate', 'Automobile', 'Political Campaign', 'Retail Launch'];

    /* How each sector is shown on the insights panel. */
    public const SECTOR_LABELS = [
        'FMCG'               => 'Best for FMCG Campaigns',
        'Real Estate'        => 'Best for Real Estate Promotions',
        'Automobile'         => 'Best for Automobile Advertising',
        'Political Campaign' => 'Best for Political Campaigns',
        'Retail Launch'      => 'Best for Retail/Product Launches',
    ];

    /**
     * VISIBILITY SCORE (1–10), Estimated.
     *
     *   Size          0–4   face area sq ft: <100 → 1, 100–299 → 2, 300–599 → 3, ≥600 → 4
     *   Illumination  0–2   any "Lit" type → 2, Non-Lit → 0
     *   Exposure      0–3   on a highway (highway_id or area type Highway) → 3,
     *                       Urban → 2, Rural → 1
     *   Facing noted  0–1   facing recorded (not blank / "nil") → 1
     *
     * Size is required. Illumination / exposure / facing that are missing score
     * 0 and make the result "incomplete". There is no verified road-exposure or
     * sight-line data in the project, so this is an estimate from the record.
     */
    public function visibility(array $m): array
    {
        $area = $this->faceArea($m);

        if ($area <= 0) {
            return $this->result(null, 'unavailable', ['size'], [], 'Media size is not recorded.');
        }

        $parts = [];
        $missing = [];

        $parts['size'] = $area >= 600 ? 4 : ($area >= 300 ? 3 : ($area >= 100 ? 2 : 1));

        $illum = mb_strtolower(trim((string) ($m['illumination_name'] ?? '')));
        if ($illum === '') {
            $missing[] = 'illumination';
            $parts['illumination'] = 0;
        } else {
            $parts['illumination'] = str_contains($illum, 'non') ? 0 : 2;
        }

        $areaType = mb_strtolower(trim((string) ($m['areatype_name'] ?? '')));
        if (!empty($m['highway_id']) || str_contains($areaType, 'highway')) {
            $parts['exposure'] = 3;
        } elseif (str_starts_with($areaType, 'urb')) {   // master value is spelt "Urbun"
            $parts['exposure'] = 2;
        } elseif (str_contains($areaType, 'rural')) {
            $parts['exposure'] = 1;
        } else {
            $missing[] = 'area type / highway';
            $parts['exposure'] = 0;
        }

        $facing = mb_strtolower(trim((string) ($m['facing'] ?? '')));
        if ($facing === '' || $facing === 'nil' || $facing === '-') {
            $missing[] = 'facing';
            $parts['facing'] = 0;
        } else {
            $parts['facing'] = 1;
        }

        $score = max(1, min(10, array_sum($parts)));

        return $this->result(
            (float) $score,
            $missing ? 'incomplete' : 'estimated',
            $missing,
            $parts + ['face_area_sqft' => round($area, 2)]
        );
    }

    /**
     * PREMIUM LOCATION RATING (1–10), Estimated — needs nearby places.
     *
     * Points from what the provider returned ($cap = most places one request
     * can return, so density is relative to what could have come back):
     *   Density         0–3  places / cap: ≥75% → 3, ≥40% → 2, ≥10% → 1
     *   Variety         0–2  distinct place groups (education, office,
     *                        healthcare …): ≥4 → 2, ≥2 → 1
     *   Premium places  0–2  malls, hotels, banks, IT offices …: ≥3 → 2, ≥1 → 1
     *   Avg rating      0–3  (average rating − 3) / 2 × 3   } only when the
     *   Review volume   0–2  median reviews ≥500 → 2, ≥100 → 1 } provider sends
     *                                                          ratings (SerpApi)
     *   score = points earned / points available × 10
     *
     * Geoapify sends no ratings, so its rating is out of the first 7 points,
     * scaled to 10. Fewer than 3 places → unavailable.
     */
    public function premiumLocation(array $places, int $cap = 20): array
    {
        $count = count($places);

        if ($count < 3) {
            return $this->result(null, 'unavailable', ['nearby places'], ['places' => $count],
                $count === 0 ? 'No nearby places were found around this location.'
                    : 'Too few nearby places to rate the location (need at least 3).');
        }

        $parts = [];
        $ratio = $count / max(1, $cap);
        $parts['density'] = $ratio >= 0.75 ? 3 : ($ratio >= 0.4 ? 2 : ($ratio >= 0.1 ? 1 : 0));

        $groups = array_unique(array_filter(array_map(
            fn($p) => $p['group'] ?? $p['category'] ?? null, $places)));
        $parts['variety'] = count($groups) >= 4 ? 2 : (count($groups) >= 2 ? 1 : 0);

        $premium = 0;
        foreach ($places as $p) {
            if ($this->matchesAny($this->placeText($p), self::PREMIUM_PLACE_KEYWORDS)) {
                $premium++;
            }
        }
        $parts['premium_places'] = $premium >= 3 ? 2 : ($premium >= 1 ? 1 : 0);
        $available = 7;

        $ratings = array_values(array_filter(array_column($places, 'rating'), 'is_numeric'));
        $reviews = array_values(array_filter(array_column($places, 'reviews'), 'is_numeric'));
        $median = $this->median($reviews);

        if ($ratings) {
            $parts['rating'] = round(max(0, min(3, (array_sum($ratings) / count($ratings) - 3) / 2 * 3)), 1);
            $available += 3;
        }
        if ($reviews) {
            $parts['reviews'] = $median >= 500 ? 2 : ($median >= 100 ? 1 : 0);
            $available += 2;
        }

        $score = max(1, min(10, round(array_sum($parts) / $available * 10, 1)));

        return $this->result(
            (float) $score,
            'estimated',
            [],
            $parts + [
                'points_available'    => $available,
                'places'              => $count,
                'place_groups'        => count($groups),
                'premium_place_count' => $premium,
                'ratings_used'        => (bool) $ratings,
            ]
        );
    }

    /**
     * AUDIENCE TYPE — Inferred, NOT demographic data.
     *
     * Each nearby place is matched against AUDIENCE_RULES; a category's share is
     * its matches over all matches. Categories with ≥15% share are listed (max 3).
     * "Rural" comes from the media's own area type. Nothing matched → "Other".
     */
    public function audience(array $places, array $m): array
    {
        $counts = array_fill_keys(array_keys(self::AUDIENCE_RULES), 0);
        $examples = [];

        foreach ($places as $p) {
            $text = $this->placeText($p);
            foreach (self::AUDIENCE_RULES as $category => $keywords) {
                if ($this->matchesAny($text, $keywords)) {
                    $counts[$category]++;
                    if (count($examples[$category] ?? []) < 3) {
                        $examples[$category][] = $p['title'] ?? ($p['category'] ?? 'Unnamed place');
                    }
                }
            }
        }

        $total = array_sum($counts);
        $types = [];

        if ($total > 0) {
            arsort($counts);
            foreach ($counts as $category => $n) {
                if ($n > 0 && $n / $total >= 0.15 && count($types) < 3) {
                    $types[] = [
                        'type'     => $category,
                        'share'    => (int) round($n / $total * 100),
                        'examples' => $examples[$category] ?? [],
                    ];
                }
            }
        }

        if (str_contains(mb_strtolower((string) ($m['areatype_name'] ?? '')), 'rural')) {
            array_unshift($types, ['type' => 'Rural', 'share' => null, 'examples' => [], 'from' => 'area type']);
        }

        if (!$types) {
            if (!$places) {
                return ['status' => 'unavailable', 'types' => [], 'reason' => 'No nearby-place data yet.'];
            }
            $types[] = ['type' => 'Other', 'share' => null, 'examples' => []];
        }

        return ['status' => 'inferred', 'types' => array_slice($types, 0, 3)];
    }

    /**
     * SECTOR SUGGESTIONS — rule-based, not an AI model.
     *
     * Each sector sums weighted signals, capped at 100:
     *   FMCG               Family ×40, Students ×30, Density ×30
     *   Real Estate        Corporate ×35, Premium ×35, Exposure ×30
     *   Automobile         Highway ×50, Premium ×25, Corporate ×25
     *   Political Campaign Density ×40, Exposure ×30, Rural/Family ×30
     *   Retail Launch      Family ×40, Premium ×35, Density ×25
     * where Family/Students/Corporate/Premium = that audience's share (0–1),
     * Density = premium-rating density part / 3, Highway = on a highway (0/1),
     * Exposure = visibility exposure part / 3.
     * ≥60 → Strong fit, ≥35 → Moderate fit, else Low fit.
     */
    public function recommendations(array $audience, array $visibility, array $premium, array $m): array
    {
        $share = [];
        foreach ($audience['types'] ?? [] as $t) {
            $share[$t['type']] = $t['share'] !== null ? $t['share'] / 100 : 1.0;
        }
        $s = fn($k) => $share[$k] ?? 0.0;

        $density  = ($premium['inputs']['density'] ?? 0) / 3;
        $exposure = ($visibility['inputs']['exposure'] ?? 0) / 3;
        $highway  = (!empty($m['highway_id']) || str_contains(mb_strtolower((string) ($m['areatype_name'] ?? '')), 'highway')) ? 1 : 0;

        if (!($audience['types'] ?? []) && $density == 0 && $exposure == 0) {
            return ['status' => 'unavailable', 'items' => [], 'reason' => 'Not enough location data to suggest sectors.'];
        }

        $raw = [
            'FMCG'               => [$s('Family') * 40 + $s('Students') * 30 + $density * 30, 'family / student places nearby and busy surroundings'],
            'Real Estate'        => [$s('Corporate') * 35 + $s('Premium') * 35 + $exposure * 30, 'corporate / premium catchment and road exposure'],
            'Automobile'         => [$highway * 50 + $s('Premium') * 25 + $s('Corporate') * 25, 'highway location and premium / corporate catchment'],
            'Political Campaign' => [$density * 40 + $exposure * 30 + max($s('Rural'), $s('Family')) * 30, 'general reach: density, exposure and local community'],
            'Retail Launch'      => [$s('Family') * 40 + $s('Premium') * 35 + $density * 25, 'shoppers and premium places nearby'],
        ];

        $items = [];
        foreach ($raw as $sector => [$score, $basis]) {
            $score = (int) round(min(100, $score));
            $items[] = [
                'sector' => $sector,
                'score'  => $score,
                'fit'    => $score >= 60 ? 'Strong fit' : ($score >= 35 ? 'Moderate fit' : 'Low fit'),
                'basis'  => $basis,
            ];
        }
        usort($items, fn($a, $b) => $b['score'] <=> $a['score']);

        return ['status' => 'rule_based', 'items' => $items];
    }

    /**
     * VALUE SCORE (1–10), Estimated — NOT ROI.
     *
     *   quality     = average of the available Visibility and Premium scores
     *   price_part  = 10 × (1 − price percentile among active media of the same
     *                 category in the same city); cheaper than peers → higher
     *   value       = 0.6 × quality + 0.4 × price_part
     *
     * Needs a price and a Visibility or Premium score. With fewer than 3 priced
     * peers the price part is skipped (value = quality) and marked incomplete.
     * Real ROI needs campaign performance data, which the project does not hold.
     */
    public function value(array $m, array $visibility, array $premium, array $peerPrices): array
    {
        $price = (float) ($m['price'] ?? 0);

        $scores = array_values(array_filter([$visibility['score'] ?? null, $premium['score'] ?? null], fn($v) => $v !== null));

        if ($price <= 0 || !$scores) {
            return $this->result(null, 'unavailable',
                $price <= 0 ? ['price'] : ['visibility / premium score'], [],
                'Needs a monthly price and at least one location score.');
        }

        $quality = array_sum($scores) / count($scores);
        $peerPrices = array_values(array_filter($peerPrices, fn($p) => $p > 0));
        $missing = [];

        if (count($peerPrices) >= 3) {
            $cheaperOrEqual = count(array_filter($peerPrices, fn($p) => $p < $price));
            $percentile = $cheaperOrEqual / count($peerPrices);
            $pricePart = 10 * (1 - $percentile);
            $value = 0.6 * $quality + 0.4 * $pricePart;
        } else {
            $missing[] = 'price comparison (fewer than 3 similar media in this city)';
            $pricePart = null;
            $value = $quality;
        }

        if (count($scores) < 2) {
            $missing[] = count(array_filter([$visibility['score'] ?? null])) ? 'premium location score' : 'visibility score';
        }

        return $this->result(
            round(max(1, min(10, $value)), 1),
            $missing ? 'incomplete' : 'estimated',
            $missing,
            [
                'quality'    => round($quality, 1),
                'price_part' => $pricePart !== null ? round($pricePart, 1) : null,
                'peers'      => count($peerPrices),
                'price'      => $price,
            ]
        );
    }

    /** Great-circle distance in km. */
    public function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $r = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $r * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    private function faceArea(array $m): float
    {
        $w = (float) ($m['width'] ?? 0);
        $h = (float) ($m['height'] ?? 0);

        return ($w > 0 && $h > 0) ? $w * $h : (float) ($m['area_auto'] ?? 0);
    }

    private function placeText(array $p): string
    {
        return mb_strtolower(implode(' ', array_filter([
            $p['title'] ?? '',
            $p['category'] ?? '',
            implode(' ', $p['categories'] ?? []),
        ])));
    }

    private function matchesAny(string $text, array $keywords): bool
    {
        foreach ($keywords as $k) {
            if (str_contains($text, $k)) {
                return true;
            }
        }

        return false;
    }

    private function median(array $values): float
    {
        if (!$values) {
            return 0;
        }
        sort($values);
        $n = count($values);
        $mid = intdiv($n, 2);

        return $n % 2 ? (float) $values[$mid] : ($values[$mid - 1] + $values[$mid]) / 2;
    }

    private function result(?float $score, string $status, array $missing, array $inputs, ?string $reason = null): array
    {
        return array_filter([
            'score'   => $score,
            'status'  => $status,
            'missing' => $missing,
            'inputs'  => $inputs,
            'reason'  => $reason,
        ], fn($v) => $v !== null);
    }
}
