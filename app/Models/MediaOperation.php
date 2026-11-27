<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * The operational life of one booked media item: installation, monitoring,
 * end-date photos and removal. Created by VendorOperationsService::syncBookings()
 * from an order_item (website / Admin Booking order) or, for a booking the
 * vendor or admin entered as booked dates, from a media_booked_date row.
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
        'vendor_id', 'media_id', 'order_id', 'order_item_id', 'booked_date_id', 'stage',
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
