<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MediaImage extends Model
{
    protected $table = 'media_images';

    protected $fillable = [
        'media_id',
        'images',
        'is_active',
        'is_deleted'
    ];
}
