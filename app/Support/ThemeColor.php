<?php

namespace App\Support;

/**
 * Colour maths for the vendor-chosen panel / website colours: whatever the
 * vendor picks, text on it stays readable (WCAG contrast), and the lighter /
 * darker shades the design needs are derived from the one colour.
 */
class ThemeColor
{
    public const DEFAULT = '#0F172A';

    /** Swatches offered in the picker (all readable with white or navy text). */
    public const PRESETS = [
        '#0F172A' => 'Navy (default)',
        '#1E3A8A' => 'Royal Blue',
        '#0F766E' => 'Teal',
        '#166534' => 'Green',
        '#7C2D12' => 'Brown',
        '#991B1B' => 'Red',
        '#6B21A8' => 'Purple',
        '#374151' => 'Charcoal',
        '#F8FAFC' => 'Light',
    ];

    public static function normalize(?string $hex): string
    {
        $hex = strtoupper(trim((string) $hex));

        return preg_match('/^#[0-9A-F]{6}$/', $hex) ? $hex : self::DEFAULT;
    }

    /** Relative luminance (WCAG 2). */
    public static function luminance(string $hex): float
    {
        $hex = ltrim(self::normalize($hex), '#');
        $rgb = array_map(fn ($i) => hexdec(substr($hex, $i, 2)) / 255, [0, 2, 4]);
        $lin = array_map(fn ($c) => $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4, $rgb);

        return 0.2126 * $lin[0] + 0.7152 * $lin[1] + 0.0722 * $lin[2];
    }

    public static function isLight(string $hex): bool
    {
        // White text works below ~0.18 luminance; above it, dark text reads better.
        return self::luminance($hex) > 0.18;
    }

    /** Main text colour to put on this background. */
    public static function text(string $hex): string
    {
        return self::isLight($hex) ? '#0F172A' : '#FFFFFF';
    }

    /** Secondary (muted) text colour on this background. */
    public static function muted(string $hex): string
    {
        return self::isLight($hex) ? '#475569' : '#CBD5E1';
    }

    /**
     * Mix the colour towards white (+) or black (-). Used for the slightly
     * lighter panels and hover states inside a coloured area.
     */
    public static function shade(string $hex, float $amount): string
    {
        $hex = ltrim(self::normalize($hex), '#');
        $target = $amount >= 0 ? 255 : 0;
        $amount = abs($amount);

        $out = '';
        foreach ([0, 2, 4] as $i) {
            $c = hexdec(substr($hex, $i, 2));
            $out .= str_pad(dechex((int) round($c + ($target - $c) * $amount)), 2, '0', STR_PAD_LEFT);
        }

        return '#' . strtoupper($out);
    }

    /** A shade for cards / hover that stays visible on the colour itself. */
    public static function raised(string $hex): string
    {
        return self::isLight($hex) ? self::shade($hex, -0.06) : self::shade($hex, 0.1);
    }

    /** "r, g, b" for rgba() overlays. */
    public static function rgb(string $hex): string
    {
        $hex = ltrim(self::normalize($hex), '#');

        return implode(', ', array_map(fn ($i) => hexdec(substr($hex, $i, 2)), [0, 2, 4]));
    }
}
