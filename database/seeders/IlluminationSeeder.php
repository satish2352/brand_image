<?php

namespace Database\Seeders;

use App\Models\Illumination;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class IlluminationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Illumination::create(
            [
                'id' => 1,
                'illumination_name' => 'Non-Lit',
            ]
        );
        Illumination::create(
            [
                'id' => 2,
                'illumination_name' => 'Front Lit',
            ]
        );
        Illumination::create(
            [
                'id' => 3,
                'illumination_name' => 'Back Lit',
            ]
        );
        Illumination::create(
            [
                'id' => 4,
                'illumination_name' => 'Side Lit',
            ]
        );
        Illumination::create(
            [
                'id' => 5,
                'illumination_name' => 'Top Lit',
            ]
        );
        Illumination::create(
            [
                'id' => 6,
                'illumination_name' => 'Bottom Lit',
            ]
        );
    }
}
