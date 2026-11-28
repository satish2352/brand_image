<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Notifications for the two panels of the Vendor Inventory module:
 * audience 'admin' (Brand Adda team) or 'vendor' (one vendor's panel).
 * Kept apart from the order-based `notifications` table the admin bell uses.
 */
class PanelNotification extends Model
{
    protected $fillable = [
        'audience', 'vendor_id', 'type', 'title', 'message', 'link', 'media_id', 'order_id', 'is_read',
    ];

    protected $casts = ['is_read' => 'boolean'];

    public function scopeForAdmin($query)
    {
        return $query->where('audience', 'admin');
    }

    public function scopeForVendor($query, int $vendorId)
    {
        return $query->where('audience', 'vendor')->where('vendor_id', $vendorId);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
}
