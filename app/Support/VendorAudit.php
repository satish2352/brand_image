<?php

namespace App\Support;

use App\Models\PanelNotification;
use App\Models\Vendor;
use App\Models\VendorActivityLog;
use Illuminate\Support\Facades\Log;

/**
 * Activity log + notifications for the Vendor Inventory module (PRD 2.11).
 *
 * Every write in the vendor panel, and every admin action that touches a
 * vendor's inventory, goes through here so the two panels see the same trail.
 * Logging must never break the action it records, so failures are reported
 * to the application log and swallowed.
 */
class VendorAudit
{
    /** An action taken by a signed-in vendor in the vendor panel. */
    public static function vendorDid(Vendor $vendor, string $action, array $details = [], ?object $media = null, ?int $orderId = null): void
    {
        self::write([
            'vendor_id'  => $vendor->id,
            'actor_type' => 'vendor',
            'actor_id'   => $vendor->id,
            'actor_name' => $vendor->vendor_name,
        ], $action, $details, $media, $orderId);
    }

    /** An action taken by a Brand Adda admin on a vendor's inventory. */
    public static function adminDid(?int $vendorId, string $action, array $details = [], ?object $media = null, ?int $orderId = null): void
    {
        self::write([
            'vendor_id'  => $vendorId,
            'actor_type' => 'admin',
            'actor_id'   => session('user_id'),
            'actor_name' => session('name') ?: 'Brand Adda Admin',
        ], $action, $details, $media, $orderId);
    }

    public static function notifyAdmin(string $type, string $title, ?string $message = null, ?string $link = null, ?int $vendorId = null, ?int $mediaId = null, ?int $orderId = null): void
    {
        self::notify('admin', $vendorId, $type, $title, $message, $link, $mediaId, $orderId);
    }

    public static function notifyVendor(int $vendorId, string $type, string $title, ?string $message = null, ?string $link = null, ?int $mediaId = null, ?int $orderId = null): void
    {
        self::notify('vendor', $vendorId, $type, $title, $message, $link, $mediaId, $orderId);
    }

    /** Hoarding / site code for a media row (model or stdClass). */
    public static function codeOf(?object $media): ?string
    {
        if (!$media) {
            return null;
        }

        if (!empty($media->hoarding_code)) {
            return $media->hoarding_code;
        }
        if (!empty($media->media_code)) {
            return $media->media_code;
        }

        return isset($media->id) ? '#' . $media->id : null;
    }

    /**
     * The fields that differ between two snapshots, as label => [old, new].
     * Used for "Media Updated — changed fields".
     */
    public static function diff(array $before, array $after, array $labels): array
    {
        $changes = [];

        foreach ($labels as $field => $label) {
            if (!array_key_exists($field, $after)) {
                continue;
            }
            $old = $before[$field] ?? null;
            $new = $after[$field];

            if ((string) $old !== (string) $new) {
                $changes[$label] = [$old, $new];
            }
        }

        return $changes;
    }

    private static function write(array $actor, string $action, array $details, ?object $media, ?int $orderId): void
    {
        try {
            VendorActivityLog::create($actor + [
                'media_id'   => $media->id ?? null,
                'media_code' => self::codeOf($media),
                'order_id'   => $orderId,
                'action'     => $action,
                'details'    => $details ?: null,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Vendor activity log failed: ' . $e->getMessage());
        }
    }

    private static function notify(string $audience, ?int $vendorId, string $type, string $title, ?string $message, ?string $link, ?int $mediaId, ?int $orderId): void
    {
        try {
            PanelNotification::create([
                'audience'  => $audience,
                'vendor_id' => $vendorId,
                'type'      => $type,
                'title'     => $title,
                'message'   => $message,
                'link'      => $link,
                'media_id'  => $mediaId,
                'order_id'  => $orderId,
                'is_read'   => false,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Panel notification failed: ' . $e->getMessage());
        }
    }
}
