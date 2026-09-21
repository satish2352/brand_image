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

    /** Panels of one size, when a position carries more than a single board. */
    public const DEFAULT_QUANTITY = 1;

    protected $fillable = [
        'media_id',
        'position',
        'width',
        'height',
        'quantity',
    ];

    protected $casts = [
        'width'    => 'decimal:2',
        'height'   => 'decimal:2',
        'quantity' => 'integer',
    ];

    public function getLabelAttribute(): string
    {
        return self::POSITIONS[$this->position] ?? ucfirst($this->position);
    }

    /**
     * Square feet for ONE board of this panel, or null when it was left blank.
     */
    public function getAreaAttribute(): ?float
    {
        if ($this->width === null || $this->height === null) {
            return null;
        }

        return round((float) $this->width * (float) $this->height, 2);
    }

    /**
     * Square feet for every board at this position — what the record's total
     * area is actually built from.
     */
    public function getTotalAreaAttribute(): ?float
    {
        $area = $this->area;

        return $area === null ? null : round($area * $this->quantityOrDefault(), 2);
    }

    /**
     * The stored quantity, or 1 — rows written before the column existed have
     * no value of their own and each stand for a single board.
     */
    public function quantityOrDefault(): int
    {
        $quantity = (int) ($this->quantity ?? self::DEFAULT_QUANTITY);

        return $quantity > 0 ? $quantity : self::DEFAULT_QUANTITY;
    }
}
