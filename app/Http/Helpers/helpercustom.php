<?php

use Illuminate\Support\Facades\Storage;

function uploadImage($file, $folder)
{
    if (!$file) {
        return null;
    }

    // Generate unique file name
    $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

    // Convert image to base64 and back (as requested)
    $base64 = base64_encode(file_get_contents($file));
    $binary = base64_decode($base64);

    // Path inside public disk
    // This WILL create: upload/images/media automatically
    $path = $folder . '/' . $fileName;

    // Store file (AUTO creates folders)
    Storage::disk('public')->put($path, $binary);

    // Safety check
    if (!Storage::disk('public')->exists($path)) {
        throw new Exception('Image not created in storage');
    }

    // Return only filename for DB
    return $fileName;
}
function removeImage($fileName, $folder)
{
    if (!$fileName) return false;

    $path = $folder . '/' . $fileName;

    if (Storage::disk('public')->exists($path)) {
        Storage::disk('public')->delete($path);
        return true;
    }

    return false;
}
