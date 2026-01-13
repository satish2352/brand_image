<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
        $this->call(UserSeeder::class);
        $this->call(CategorySeeder::class);
        $this->call(FacingDirectionSeeder::class);
        $this->call(IlluminationSeeder::class);
        $this->call(StateSeeder::class);
        $this->call(DistrictSeeder::class);
    }
}
