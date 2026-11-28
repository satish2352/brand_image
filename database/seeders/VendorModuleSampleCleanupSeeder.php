<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Removes the Vendor Inventory sample data added by VendorModuleSampleSeeder.
 *
 *   php artisan db:seed --class=VendorModuleSampleCleanupSeeder
 */
class VendorModuleSampleCleanupSeeder extends Seeder
{
    public function run(): void
    {
        if (!app()->environment('local')) {
            $this->command?->warn('Skipped: sample data cleanup is only for the local environment.');
            return;
        }

        VendorModuleSampleSeeder::cleanup();
        $this->command?->info('Vendor module sample data removed.');
    }
}
