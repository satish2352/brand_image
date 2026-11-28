<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class MediaOperationPhoto extends Model
{
    public const SHOTS = [
        'newspaper' => 'Newspaper Shot',
        'long'      => 'Long Shot',
        'close'     => 'Close Shot',
    ];

    protected $fillable = [
        'operation_id', 'stage', 'shot_type', 'photo_date', 'file', 'shared_at', 'uploaded_by_vendor_id',
    ];

    protected $casts = [
        'photo_date' => 'date',
        'shared_at'  => 'datetime',
    ];

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->file);
    }
}
