<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * The operational life of one booked media item (one order_item): installation,
 * monitoring, end-date photos and removal. Created from bookings by
 * VendorOperationsService::syncBookings().
 */
class MediaOperation extends Model
{
    public const STAGES = [
        'installation' => 'Installation',
        'monitoring'   => 'Monitoring Pics',
        'end_date'     => 'End Date Pics',
        'completed'    => 'Completed / Media Remove',
    ];

    protected $fillable = [
        'vendor_id', 'media_id', 'order_id', 'order_item_id', 'stage',
        'installation_shared_at', 'end_date_shared_at', 'completed_at', 'removal_note',
    ];

    protected $casts = [
        'installation_shared_at' => 'datetime',
        'end_date_shared_at'     => 'datetime',
        'completed_at'           => 'datetime',
    ];

    public function photos()
    {
        return $this->hasMany(MediaOperationPhoto::class, 'operation_id');
    }

    public function media()
    {
        return $this->belongsTo(MediaManagement::class, 'media_id');
    }
}
