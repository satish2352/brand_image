<?php

namespace Database\Seeders;

use App\Models\Vendor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * LOCAL DEVELOPMENT ONLY: gives one vendor a known Vendor Panel password so
 * the panel can be tried without sending real login emails.
 *
 *   php artisan db:seed --class=VendorPanelTestSeeder
 *
 * Login: the vendor's email (default: the "Rohini Test" vendor) with password
 * VendorTest@123. In production, use Admin → Vendor Inventory & Access →
 * Send Login instead.
 */
class VendorPanelTestSeeder extends Seeder
{
    public const PASSWORD = 'VendorTest@123';

    public function run(): void
    {
        if (!app()->environment('local')) {
            $this->command?->warn('Skipped: VendorPanelTestSeeder only runs in the local environment.');
            return;
        }

        $vendor = Vendor::where('is_deleted', 0)
            ->where(fn ($q) => $q->where('email', env('VENDOR_TEST_EMAIL', 'rohini@gmail.com'))->orWhere('vendor_name', 'Rohini Test'))
            ->first();

        if (!$vendor) {
            $this->command?->warn('No test vendor found.');
            return;
        }

        $vendor->forceFill(['password' => Hash::make(self::PASSWORD)])->save();
        $this->command?->info('Vendor Panel test login set for ' . $vendor->email);
    }
}
