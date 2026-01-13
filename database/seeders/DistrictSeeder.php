<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DistrictSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('districts')->insert([
            ['id' => 1,  'state_id' => 1, 'district_name' => 'Ahmednagar',                              'is_active' => 1, 'is_deleted' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2,  'state_id' => 1, 'district_name' => 'Akola',                                   'is_active' => 1, 'is_deleted' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3,  'state_id' => 1, 'district_name' => 'Amravati',                                'is_active' => 1, 'is_deleted' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4,  'state_id' => 1, 'district_name' => 'Aurangabad / Chhatrapati Sambhajinagar', 'is_active' => 1, 'is_deleted' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5,  'state_id' => 1, 'district_name' => 'Beed',                                    'is_active' => 1, 'is_deleted' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6,  'state_id' => 1, 'district_name' => 'Bhandara',                                'is_active' => 1, 'is_deleted' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7,  'state_id' => 1, 'district_name' => 'Buldhana',                                'is_active' => 1, 'is_deleted' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 8,  'state_id' => 1, 'district_name' => 'Chandrapur',                              'is_active' => 1, 'is_deleted' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 9,  'state_id' => 1, 'district_name' => 'Dhule',                                   'is_active' => 1, 'is_deleted' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 10, 'state_id' => 1, 'district_name' => 'Gadchiroli',                              'is_active' => 1, 'is_deleted' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 11, 'state_id' => 1, 'district_name' => 'Gondia',                                  'is_active' => 1, 'is_deleted' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 12, 'state_id' => 1, 'district_name' => 'Hingoli',                                 'is_active' => 1, 'is_deleted' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 13, 'state_id' => 1, 'district_name' => 'Jalgaon',                                 'is_active' => 1, 'is_deleted' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 14, 'state_id' => 1, 'district_name' => 'Jalna',                                   'is_active' => 1, 'is_deleted' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 15, 'state_id' => 1, 'district_name' => 'Kolhapur',                                'is_active' => 1, 'is_deleted' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 16, 'state_id' => 1, 'district_name' => 'Latur',                                   'is_active' => 1, 'is_deleted' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 17, 'state_id' => 1, 'district_name' => 'Mumbai City',                             'is_active' => 1, 'is_deleted' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 18, 'state_id' => 1, 'district_name' => 'Mumbai Suburban',                         'is_active' => 1, 'is_deleted' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 19, 'state_id' => 1, 'district_name' => 'Nagpur',                                  'is_active' => 1, 'is_deleted' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 20, 'state_id' => 1, 'district_name' => 'Nanded',                                  'is_active' => 1, 'is_deleted' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 21, 'state_id' => 1, 'district_name' => 'Nandurbar',                               'is_active' => 1, 'is_deleted' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 22, 'state_id' => 1, 'district_name' => 'Nashik',                                  'is_active' => 1, 'is_deleted' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 23, 'state_id' => 1, 'district_name' => 'Osmanabad (Dharashiv)',                   'is_active' => 1, 'is_deleted' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 24, 'state_id' => 1, 'district_name' => 'Palghar',                                 'is_active' => 1, 'is_deleted' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 25, 'state_id' => 1, 'district_name' => 'Parbhani',                                'is_active' => 1, 'is_deleted' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 26, 'state_id' => 1, 'district_name' => 'Pune',                                    'is_active' => 1, 'is_deleted' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 27, 'state_id' => 1, 'district_name' => 'Raigad',                                  'is_active' => 1, 'is_deleted' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 28, 'state_id' => 1, 'district_name' => 'Ratnagiri',                               'is_active' => 1, 'is_deleted' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 29, 'state_id' => 1, 'district_name' => 'Sangli',                                  'is_active' => 1, 'is_deleted' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 30, 'state_id' => 1, 'district_name' => 'Satara',                                  'is_active' => 1, 'is_deleted' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 31, 'state_id' => 1, 'district_name' => 'Sindhudurg',                              'is_active' => 1, 'is_deleted' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 32, 'state_id' => 1, 'district_name' => 'Solapur',                                 'is_active' => 1, 'is_deleted' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 33, 'state_id' => 1, 'district_name' => 'Thane',                                   'is_active' => 1, 'is_deleted' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 34, 'state_id' => 1, 'district_name' => 'Wardha',                                  'is_active' => 1, 'is_deleted' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 35, 'state_id' => 1, 'district_name' => 'Washim',                                  'is_active' => 1, 'is_deleted' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 36, 'state_id' => 1, 'district_name' => 'Yavatmal',                                'is_active' => 1, 'is_deleted' => 0, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
