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
function haversineKm($lat1, $lon1, $lat2, $lon2)
{
    $earth = 6371;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $lat1 = deg2rad($lat1);
    $lat2 = deg2rad($lat2);

    $a = sin($dLat / 2) ** 2 + cos($lat1) * cos($lat2) * sin($dLon / 2) ** 2;


    return $earth * 2 * asin(sqrt($a));
}
if (!function_exists('formatAmountShort')) {
    function formatAmountShort($amount)
    {
        if ($amount >= 10000000) {
            return round($amount / 10000000, 2) . ' Cr';
        } elseif ($amount >= 100000) {
            return round($amount / 100000, 2) . ' L';
        } elseif ($amount >= 1000) {
            return round($amount / 1000, 1) . ' K';
        }

        return number_format($amount);
    }
    if (!function_exists('getYears')) {
        function getYears($startYear = 2025)
        {
            $years = [];
            $current = date('Y');

            for ($y = $startYear; $y <= $current; $y++) {
                $years[] = $y;
            }

            return $years;
        }
    }

    if (!function_exists('getMonths')) {
        function getMonths()
        {
            return [
                1 => 'January',
                2 => 'February',
                3 => 'March',
                4 => 'April',
                5 => 'May',
                6 => 'June',
                7 => 'July',
                8 => 'August',
                9 => 'September',
                10 => 'October',
                11 => 'November',
                12 => 'December'
            ];
        }
    }
}
