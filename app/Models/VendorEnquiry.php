<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** An enquiry sent from a vendor's website Contact page. */
class VendorEnquiry extends Model
{
    public const STATUSES = [
        'new'       => 'New',
        'contacted' => 'Contacted',
        'closed'    => 'Closed',
    ];

    /** Badge colours: [background, text]. */
    public const COLORS = [
        'new'       => ['#FFEDD5', '#9A3412'],
        'contacted' => ['#DBEAFE', '#1E40AF'],
        'closed'    => ['#DCFCE7', '#166534'],
    ];

    protected $fillable = [
        'vendor_id', 'media_id', 'name', 'email', 'mobile', 'company', 'city', 'message', 'status', 'read_at', 'ip',
    ];

    protected $casts = ['read_at' => 'datetime'];

    public function media()
    {
        return $this->belongsTo(MediaManagement::class, 'media_id');
    }

    public function scopeForVendor($query, int $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }

    /** Mobile digits with India's country code, for tel: / WhatsApp links. */
    public function getPhoneIntlAttribute(): string
    {
        $digits = preg_replace('/\D/', '', (string) $this->mobile);

        return strlen($digits) === 10 ? '91' . $digits : $digits;
    }
}
