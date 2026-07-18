<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Painting',
                'description' => 'Works created primarily by applying pigment to a surface.',
            ],
            [
                'name' => 'Print',
                'description' => 'Works produced by transferring an image from a prepared matrix onto another surface.',
            ],
            [
                'name' => 'Drawing',
                'description' => 'Works made primarily with lines and marks on paper or another support.',
            ],
            [
                'name' => 'Sculpture',
                'description' => 'Three-dimensional artworks shaped, carved, modeled, cast, or assembled.',
            ],
            [
                'name' => 'Photography',
                'description' => 'Artworks created through photographic processes.',
            ],
            [
                'name' => 'Digital Art',
                'description' => 'Artworks created or presented using digital technology.',
            ],
        ];

        foreach ($categories as $category) {
            Category::query()->updateOrCreate(
                ['name' => $category['name']],
                ['description' => $category['description']],
            );
        }

        Category::factory()->count(3)->create();
    }
}
