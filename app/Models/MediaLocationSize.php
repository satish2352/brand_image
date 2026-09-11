<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One advertising panel of a media record - a Bus Shelter's Front, Back or
 * Side - with its own width and height. See the media_location_sizes migration
 * for why these are rows rather than columns on media_management.
 */
class MediaLocationSize extends Model
{
    /**
     * The panels the Add / Edit Media form offers, in display order.
     * Keys are what is stored; values are what the admin sees.
     */
    public const POSITIONS = [
        'front' => 'Front',
        'back'  => 'Back',
        'side'  => 'Side',
    ];

    protected $table = 'media_location_sizes';

    protected $fillable = [
        'media_id',
        'position',
        'width',
        'height',
    ];

    protected $casts = [
        'width'  => 'decimal:2',
        'height' => 'decimal:2',
    ];

    public function getLabelAttribute(): string
    {
        return self::POSITIONS[$this->position] ?? ucfirst($this->position);
    }

    /**
     * Square feet for this panel, or null when it was left blank.
     */
    public function getAreaAttribute(): ?float
    {
        if ($this->width === null || $this->height === null) {
            return null;
        }

        return round((float) $this->width * (float) $this->height, 2);
    }
}
