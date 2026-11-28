<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One shortlisted hoarding on a shared link.
 */
class SharedLinkItem extends Model
{
    use HasFactory;

    protected $table = 'shared_link_items';

    protected $fillable = [
        'shared_link_id',
        'media_id',
    ];

    public function sharedLink()
    {
        return $this->belongsTo(SharedLink::class);
    }
}
