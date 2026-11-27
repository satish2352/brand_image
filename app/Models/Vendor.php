<?php

// app/Models/Vendor.php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * A vendor is both a master record (admin Vendor master) and, since the
 * Vendor Inventory Management module, an account that signs in to
 * /vendor-panel through the `vendor` guard. Extending Authenticatable instead
 * of Model changes nothing for the existing master screens.
 */
class Vendor extends Authenticatable
{
    protected $table = 'vendors';

    protected $fillable = [
        'state_id',
        'district_id',
        'city_id',
        'vendor_name',
        'company_name',
        'vendor_code',
        'slug',
        'mobile',
        'email',
        'address',
        'logo',
        'is_active',
        'is_deleted',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_active'           => 'boolean',
        'is_deleted'          => 'boolean',
        'credentials_sent_at' => 'datetime',
        'last_login_at'       => 'datetime',
    ];

    protected static function booted(): void
    {
        // Every vendor gets a showcase address the moment it is created.
        static::creating(function (Vendor $vendor) {
            if (empty($vendor->slug)) {
                $vendor->slug = static::uniqueSlug((string) $vendor->vendor_name);
            }
        });
    }

    public function media()
    {
        return $this->hasMany(MediaManagement::class, 'vendor_id');
    }

    public function sliders()
    {
        return $this->hasMany(VendorSlider::class, 'vendor_id')->orderBy('sort_order');
    }

    /** Public URL of the uploaded logo, or null when the vendor has none. */
    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo ? Storage::disk('public')->url($this->logo) : null;
    }

    /** Name shown in the vendor panel: the company name, or the vendor name until one is set. */
    public function getDisplayNameAttribute(): string
    {
        return trim((string) $this->company_name) ?: (string) $this->vendor_name;
    }

    /** Two-letter monogram used when no logo has been uploaded. */
    public function getInitialsAttribute(): string
    {
        $words = preg_split('/\s+/', trim((string) $this->display_name)) ?: [];
        $letters = collect($words)->filter()->take(2)->map(fn ($w) => Str::upper(Str::substr($w, 0, 1)))->implode('');

        return $letters ?: 'V';
    }

    /** brand-adda.co.in/{slug} — the vendor's public showcase. */
    public function getWebsiteUrlAttribute(): string
    {
        return url($this->slug);
    }

    /** A slug no other vendor holds, derived from the vendor name. */
    public static function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'vendor';
        $slug = $base;
        $n = 2;

        while (static::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base . '-' . $n++;
        }

        return $slug;
    }
}
