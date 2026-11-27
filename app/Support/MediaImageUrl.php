<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * URL of a media photo (media_images.images / panorama_image).
 *
 * Photos are linked through fileConstants.IMAGE_VIEW (FILE_VIEW in .env),
 * which normally points at the live server. On a local copy a photo uploaded
 * locally only exists on this disk, so when the file is here we serve it from
 * here; otherwise the configured URL is used as before.
 */
class MediaImageUrl
{
    public static function for(?string $file): ?string
    {
        if (!$file) {
            return null;
        }

        $path = trim(config('fileConstants.IMAGE_ADD'), '/') . '/' . $file;

        if (app()->environment('local') && Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->url($path);
        }

        return config('fileConstants.IMAGE_VIEW') . $file;
    }

    /** Grey "No photo yet" tile for media without any photo (no file needed). */
    public static function placeholder(): string
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="400" height="300" viewBox="0 0 400 300">'
            . '<rect width="400" height="300" fill="#F1F5F9"/>'
            . '<text x="200" y="158" text-anchor="middle" font-family="Montserrat,Arial,sans-serif" font-size="20" fill="#94A3B8">No photo yet</text></svg>';

        return 'data:image/svg+xml;charset=utf-8,' . rawurlencode($svg);
    }

    /**
     * For data handed to JavaScript (map popups): each row as an array with
     * `first_image_url` added, so the page does not glue IMAGE_VIEW + file itself.
     */
    public static function withUrls(iterable $rows, string $field = 'first_image', string $as = 'first_image_url'): array
    {
        $out = [];
        foreach ($rows as $row) {
            $item = is_array($row) ? $row : (method_exists($row, 'toArray') ? $row->toArray() : (array) $row);
            $item[$as] = self::for($item[$field] ?? null);
            $out[] = $item;
        }

        return $out;
    }
}
