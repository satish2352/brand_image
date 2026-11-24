<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class VendorSlider extends Model
{
    protected $fillable = ['vendor_id', 'media_type', 'file', 'title', 'sort_order', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->file);
    }
}
