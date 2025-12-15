<?php

namespace Database\Seeders;

use App\Models\Employees;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        Employees::create(
            [
                // 'plant_id' => 0,
                // 'department_id' => 0,
                // 'projects_id' => 0,
                'designation_id' => 0,
                'role_id' => 0,
                'employee_code' => 0,
                'employee_name' => 0,
                'employee_type' => 0,
                'employee_email' => 'alfadmin@gmail.com',
                'employee_user_name' => 'alfadmin@gmail.com',
                'employee_password' => Hash::make('alfadmin'),
            ]

        );

        
    }
}
