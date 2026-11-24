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
}
