<?php

namespace Database\Factories;

use App\Models\Artist;
use App\Models\Artwork;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Artwork>
 */
class ArtworkFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $width = fake()->numberBetween(20, 300);
        $height = fake()->numberBetween(20, 300);

        return [
            'artist_id' => Artist::factory(),
            'category_id' => Category::factory(),
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->paragraph(),
            'image_url' => sprintf(
                'https://picsum.photos/seed/%s/1200/800',
                fake()->uuid()
            ),
            'creation_date' => fake()->boolean(80)
                ? (string) fake()->numberBetween(1400, (int) date('Y'))
                : null,
            'dimensions' => fake()->boolean(80)
                ? sprintf('%d x %d cm', $width, $height)
                : null,
        ];
    }
}
