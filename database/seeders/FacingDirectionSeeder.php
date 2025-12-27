<?php

namespace Database\Seeders;

use App\Models\FacingDirection;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FacingDirectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        FacingDirection::create(
            [
                'id' => 1,
                'facing_name' => 'North',
            ]
        );
        FacingDirection::create(
            [
                'id' => 2,
                'facing_name' => 'South',
            ]
        );
        FacingDirection::create(
            [
                'id' => 3,
                'facing_name' => 'East',
            ]
        );
        FacingDirection::create(
            [
                'id' => 4,
                'facing_name' => 'West',
            ]
        );
        FacingDirection::create(
            [
                'id' => 5,
                'facing_name' => 'North-East',
            ]
        );
        FacingDirection::create(
            [
                'id' => 6,
                'facing_name' => 'North-West',
            ]
        );
    }
}
