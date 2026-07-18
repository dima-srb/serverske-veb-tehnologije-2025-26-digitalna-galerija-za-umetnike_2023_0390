<?php

namespace Database\Seeders;

use App\Models\Artist;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ArtistSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $artists = [
            [
                'name' => 'Vincent van Gogh',
                'email' => 'vincent.van.gogh@egallery.test',
                'bio' => 'Dutch Post-Impressionist painter known for expressive color, forceful brushwork, and a profound influence on modern art.',
                'country' => 'Netherlands',
            ],
            [
                'name' => 'Claude Monet',
                'email' => 'claude.monet@egallery.test',
                'bio' => 'French painter and a leading founder of Impressionism, celebrated for his studies of light, atmosphere, and landscape.',
                'country' => 'France',
            ],
            [
                'name' => 'Georges Seurat',
                'email' => 'georges.seurat@egallery.test',
                'bio' => 'French Post-Impressionist artist who developed the painting technique commonly known as Pointillism.',
                'country' => 'France',
            ],
            [
                'name' => 'Grant Wood',
                'email' => 'grant.wood@egallery.test',
                'bio' => 'American painter associated with Regionalism and best known for scenes of the rural American Midwest.',
                'country' => 'United States',
            ],
            [
                'name' => 'Katsushika Hokusai',
                'email' => 'katsushika.hokusai@egallery.test',
                'bio' => 'Japanese ukiyo-e artist whose prints and paintings had a lasting influence in Japan and internationally.',
                'country' => 'Japan',
            ],
            [
                'name' => 'Demo Artist One',
                'email' => 'demo.artist.one@egallery.test',
                'bio' => 'Demo artist profile used for factory-generated artworks.',
                'country' => 'Serbia',
            ],
            [
                'name' => 'Demo Artist Two',
                'email' => 'demo.artist.two@egallery.test',
                'bio' => 'Demo artist profile used for factory-generated artworks.',
                'country' => 'Serbia',
            ],
        ];

        foreach ($artists as $artistData) {
            $user = User::query()->updateOrCreate(
                ['email' => $artistData['email']],
                [
                    'name' => $artistData['name'],
                    'email_verified_at' => now(),
                    'password' => Hash::make('password'),
                    'role' => User::ROLE_ARTIST,
                ],
            );

            Artist::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'bio' => $artistData['bio'],
                    'country' => $artistData['country'],
                    'website_url' => null,
                    'social_links' => null,
                ],
            );
        }
    }
}
