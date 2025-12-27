<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Category::create(
            [
                'id' => 1,
                'category_name' => 'Hoardings/Billboards',
            ]
        );
        Category::create(
            [
                'id' => 2,
                'category_name' => 'Digital Wall painting/Wall Painting',
            ]
        );
        Category::create(
            [
                'id' => 3,
                'category_name' => 'Airport Branding',
            ]
        );
        Category::create(
            [
                'id' => 4,
                'category_name' => 'Transmit Media (Bus, Auto, Cab, Metro)',
            ]
        );
        Category::create(
            [
                'id' => 5,
                'category_name' => 'Office Branding/ Corporate Branding',
            ]
        );
        Category::create(
            [
                'id' => 6,
                'category_name' => 'Wall Wrap',
            ]
        );
    }
}
