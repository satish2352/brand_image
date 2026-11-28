<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class HomeSlider extends Model
{
    /**
     * The home page caches the active sliders for half an hour (see
     * HomeController::index), so every admin write has to drop this key —
     * otherwise a slider that was just added stays invisible on the site until
     * the TTL runs out. The key lives here so the reader and the writers cannot
     * drift apart.
     */
    public const CACHE_KEY = 'home_sliders';
    public const CACHE_TTL = 1800;

    protected $fillable = [
        'desktop_image',
        'mobile_image',
        'is_active',
        'is_deleted'
    ];

    public static function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
