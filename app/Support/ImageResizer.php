<?php

namespace App\Support;

/**
 * Shrinks uploaded pictures to something a web page can actually afford.
 *
 * Nothing in this application ever resized an image: whatever the admin picked
 * was stored as-is and handed to the browser at full camera resolution. A 4000px
 * phone photo displayed inside a 360px card costs the visitor several megabytes
 * to download and decode for no visible gain, and the home page was shipping
 * roughly 25MB of them.
 *
 * Everything here is deliberately forgiving. An upload must never fail because
 * a picture could not be optimised — when GD is missing, the format is one we
 * do not re-encode, or the file is too large to open inside the memory limit,
 * the original bytes are returned untouched and the upload carries on.
 */
class ImageResizer
{
    /**
     * Widest we ever store. 1920 still covers a full-bleed hero on a desktop
     * screen; the slider images are the only thing displayed anywhere near that
     * size and everything else is shown far smaller.
     */
    public const MAX_WIDTH = 1920;

    /** Tallest we ever store, so a portrait photo cannot dodge the width cap. */
    public const MAX_HEIGHT = 1920;

    /**
     * 82 is the usual sweet spot: visually indistinguishable from the original
     * at normal viewing sizes, typically a fifth of the bytes of the quality-95
     * output phone cameras and Photoshop's "save for web" produce.
     */
    public const QUALITY = 82;

    /**
     * Formats we re-encode. GIF is deliberately absent — GD flattens an
     * animated one to a single frame, which is a visible change, not an
     * optimisation.
     */
    private const HANDLED = ['image/jpeg', 'image/png', 'image/webp'];

    /**
     * Below this, a picture is already small enough that re-encoding risks
     * losing more in quality than it saves in bytes.
     */
    private const SKIP_UNDER_BYTES = 150 * 1024;

    /**
     * Return $binary shrunk to fit inside the given box, or the original bytes
     * when it cannot (or should not) be touched.
     *
     * Safe to call on anything — the caller does not have to check the file is
     * an image first.
     */
    public static function optimise(
        string $binary,
        int $maxWidth = self::MAX_WIDTH,
        int $maxHeight = self::MAX_HEIGHT,
        int $quality = self::QUALITY
    ): string {
        if ($binary === '' || !extension_loaded('gd')) {
            return $binary;
        }

        $info = @getimagesizefromstring($binary);
        if ($info === false) {
            return $binary;
        }

        [$width, $height] = $info;
        $mime = strtolower($info['mime'] ?? '');

        if ($width < 1 || $height < 1 || !in_array($mime, self::HANDLED, true)) {
            return $binary;
        }

        $scale = min(1.0, $maxWidth / $width, $maxHeight / $height);

        // Already small in both senses: inside the box and not a heavyweight
        // file. Re-encoding would only trade quality for nothing.
        if ($scale >= 1.0 && strlen($binary) < self::SKIP_UNDER_BYTES) {
            return $binary;
        }

        if (!self::canAfford($width, $height)) {
            return $binary;
        }

        $source = @imagecreatefromstring($binary);
        if ($source === false) {
            return $binary;
        }

        try {
            // A phone photo is stored in the sensor's own orientation with an
            // EXIF tag saying how to turn it. Re-encoding drops that tag, so an
            // unrotated copy would come out lying on its side.
            $source = self::applyExifOrientation($source, $binary, $mime);

            // Rotating swaps the axes, so the box has to be re-measured against
            // the upright image rather than the stored one.
            $width = imagesx($source);
            $height = imagesy($source);
            $scale = min(1.0, $maxWidth / $width, $maxHeight / $height);

            $targetWidth = max(1, (int) floor($width * $scale));
            $targetHeight = max(1, (int) floor($height * $scale));

            if ($targetWidth !== $width || $targetHeight !== $height) {
                $resized = @imagecreatetruecolor($targetWidth, $targetHeight);
                if ($resized === false) {
                    return $binary;
                }

                self::preserveTransparency($resized, $mime);

                $ok = @imagecopyresampled(
                    $resized,
                    $source,
                    0, 0, 0, 0,
                    $targetWidth, $targetHeight,
                    $width, $height
                );

                if (!$ok) {
                    imagedestroy($resized);

                    return $binary;
                }

                imagedestroy($source);
                $source = $resized;
            }

            $encoded = self::encode($source, $mime, $quality);
        } finally {
            if (isset($source) && $source instanceof \GdImage) {
                imagedestroy($source);
            }
        }

        // A re-encode that came out bigger is not an optimisation. Happens with
        // flat graphics saved as PNG, where our output is honestly worse.
        if ($encoded === null || $encoded === '' || strlen($encoded) >= strlen($binary)) {
            return $binary;
        }

        return $encoded;
    }

    /**
     * Would decoding an image this size fit in what is left of memory_limit?
     *
     * GD holds every pixel as 4 bytes whatever the file's own compression, so a
     * 30 megapixel photo needs ~120MB to open. Without this check a single
     * oversized upload takes the whole request down with a fatal error, which
     * the caller cannot catch.
     */
    private static function canAfford(int $width, int $height): bool
    {
        $limit = self::memoryLimitBytes();

        if ($limit <= 0) {
            return true; // no limit configured
        }

        // The source and the resized copy are both held at once, plus the
        // encode buffer; 2.5x the source with a 1.6 safety factor is the usual
        // rule of thumb for GD.
        $needed = (int) ($width * $height * 4 * 2.5 * 1.6);

        return ($limit - memory_get_usage(true)) > $needed;
    }

    private static function memoryLimitBytes(): int
    {
        $raw = trim((string) ini_get('memory_limit'));

        if ($raw === '' || $raw === '-1') {
            return 0;
        }

        $value = (int) $raw;

        return match (strtolower(substr($raw, -1))) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => $value,
        };
    }

    /**
     * PNG and WebP can carry an alpha channel, and a fresh truecolor canvas is
     * opaque black. Without this every transparent logo gains a black backdrop.
     */
    private static function preserveTransparency(\GdImage $canvas, string $mime): void
    {
        if ($mime === 'image/jpeg') {
            return;
        }

        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        imagefill($canvas, 0, 0, imagecolorallocatealpha($canvas, 0, 0, 0, 127));
    }

    /**
     * Turn the image upright according to its EXIF Orientation tag.
     *
     * Only JPEG carries one, and only when the exif extension is available —
     * everywhere else the image is already the right way up.
     */
    private static function applyExifOrientation(\GdImage $image, string $binary, string $mime): \GdImage
    {
        if ($mime !== 'image/jpeg' || !function_exists('exif_read_data')) {
            return $image;
        }

        // exif_read_data() reads from a stream, and we only have the bytes.
        $exif = @exif_read_data('data://image/jpeg;base64,' . base64_encode($binary));
        $orientation = (int) ($exif['Orientation'] ?? 0);

        $degrees = match ($orientation) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        if ($degrees === 0) {
            return $image;
        }

        $rotated = @imagerotate($image, $degrees, 0);

        if ($rotated === false) {
            return $image;
        }

        imagedestroy($image);

        return $rotated;
    }

    /**
     * Write the image back out in the format it arrived in.
     */
    private static function encode(\GdImage $image, string $mime, int $quality): ?string
    {
        ob_start();

        $ok = match ($mime) {
            'image/jpeg' => imagejpeg($image, null, $quality),
            'image/webp' => imagewebp($image, null, $quality),
            // PNG takes a 0-9 compression level, not a quality. 8 is strong
            // compression that is still quick; PNG is lossless either way.
            'image/png' => self::encodePng($image),
            default => false,
        };

        $output = ob_get_clean();

        return $ok ? $output : null;
    }

    private static function encodePng(\GdImage $image): bool
    {
        imagealphablending($image, false);
        imagesavealpha($image, true);

        return imagepng($image, null, 8);
    }
}
