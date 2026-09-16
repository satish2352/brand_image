<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Key/value settings, read on nearly every request by the access gate.
 *
 * Values are cached so the gate does not cost a query per request, and the
 * cache is dropped whenever a value is written. A missing key falls back to
 * config/portal_access.php, which in turn reads .env — so an environment that
 * has never opened the admin screen still behaves sensibly.
 */
class Setting extends Model
{
    protected $table = 'settings';

    protected $fillable = ['key', 'value', 'group'];

    private const CACHE_PREFIX = 'setting:';

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = Cache::rememberForever(self::CACHE_PREFIX . $key, function () use ($key) {
            // false, not null: null is a legitimate stored value, and caching it
            // as "missing" would re-query forever.
            return static::where('key', $key)->value('value') ?? false;
        });

        return $value === false ? $default : $value;
    }

    /**
     * Read a setting as a positive integer, falling back when the stored value
     * is absent or nonsense (an admin clearing the box, say).
     */
    public static function getInt(string $key, int $default): int
    {
        $value = static::get($key);

        return is_numeric($value) && (int) $value > 0 ? (int) $value : $default;
    }

    public static function getBool(string $key, bool $default): bool
    {
        $value = static::get($key);

        return $value === null ? $default : in_array((string) $value, ['1', 'true', 'on', 'yes'], true);
    }

    public static function put(string $key, mixed $value, ?string $group = null): void
    {
        static::updateOrCreate(
            ['key' => $key],
            array_filter([
                'value' => (string) $value,
                'group' => $group,
            ], fn($v) => $v !== null)
        );

        Cache::forget(self::CACHE_PREFIX . $key);
    }
}
