<?php

namespace Database\Seeders;

use App\Models\CategoryProduct;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategoryProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $categories = [
            'Electronics',
            'Clothing',
            'Home & Garden',
            'Sports & Outdoors',
            'Books',
            'Toys & Games',
            'Health & Beauty',
            'Automotive',
            'Furniture',
            'Office Supplies',
        ];

        foreach ($categories as $category) {
            CategoryProduct::create([
                'name' => $category,
            ]);
        }
    }
}
