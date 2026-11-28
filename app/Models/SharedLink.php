<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * A shortlist of hoardings shared with one client at /shared/{token}.
 *
 * @see \App\Http\Controllers\Website\SharedLinkController
 */
class SharedLink extends Model
{
    use HasFactory;

    protected $table = 'shared_links';

    protected $fillable = [
        'token',
        'title',
        'created_by',
    ];

    public function items()
    {
        return $this->hasMany(SharedLinkItem::class);
    }

    /**
     * The media ids on this link, in the order they were shortlisted.
     */
    public function mediaIds(): array
    {
        return $this->items()
            ->orderBy('id')
            ->pluck('media_id')
            ->all();
    }

    /**
     * A token that cannot be guessed by counting. 32 chars of Str::random is
     * far more than this needs, but the column is cheap and the link is the
     * only thing standing between one client's shortlist and another's.
     */
    public static function newToken(): string
    {
        do {
            $token = Str::random(32);
        } while (static::where('token', $token)->exists());

        return $token;
    }
}
