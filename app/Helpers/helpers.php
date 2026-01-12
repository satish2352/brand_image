<?php

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
}
