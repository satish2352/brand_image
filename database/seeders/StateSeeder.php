<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('states')->insert([
            [
                'id'         => 1,
                'state_name' => 'Maharashtra',
                'is_active'  => 1,
                'is_deleted' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}
