<?php

namespace Database\Factories;

use App\Models\Artist;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Artist>
 */
class ArtistFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state([
                'role' => User::ROLE_ARTIST,
            ]),
            'bio' => fake()->optional()->paragraph(),
            'country' => fake()->optional()->country(),
            'website_url' => fake()->optional()->url(),
            'social_links' => [
                'instagram' => sprintf('https://www.instagram.com/%s', fake()->userName()),
                'facebook' => sprintf('https://www.facebook.com/%s', fake()->userName()),
            ],
        ];
    }
}
