<?php

namespace Database\Seeders;

use App\Models\Artist;
use App\Models\Artwork;
use App\Models\Category;
use Illuminate\Database\Seeder;

class ArtworkSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $artists = Artist::query()
            ->with('user')
            ->get()
            ->keyBy(fn (Artist $artist): ?string => $artist->user?->email);

        $categories = Category::query()
            ->whereIn('name', [
                'Painting',
                'Print',
                'Drawing',
                'Sculpture',
                'Photography',
                'Digital Art',
            ])
            ->get()
            ->keyBy('name');

        $realArtworks = [
            [
                'artist_email' => 'vincent.van.gogh@egallery.test',
                'category' => 'Painting',
                'title' => 'The Bedroom',
                'description' => "Van Gogh's depiction of his bedroom in the Yellow House in Arles.",
                'image_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/7/77/Vincent_van_Gogh_-_Van_Gogh%27s_Bedroom_in_Arles_-_Google_Art_Project.jpg/1280px-Vincent_van_Gogh_-_Van_Gogh%27s_Bedroom_in_Arles_-_Google_Art_Project.jpg',
                'creation_date' => '1889',
                'dimensions' => '73.6 × 92.3 cm',
            ],
            [
                'artist_email' => 'vincent.van.gogh@egallery.test',
                'category' => 'Painting',
                'title' => 'Self-Portrait',
                'description' => 'A self-portrait painted during the artist’s time in Paris.',
                'image_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/b/b2/Vincent_van_Gogh_-_Self-Portrait_-_Google_Art_Project.jpg/1280px-Vincent_van_Gogh_-_Self-Portrait_-_Google_Art_Project.jpg',
                'creation_date' => '1887',
                'dimensions' => '41 × 32.5 cm',
            ],
            [
                'artist_email' => 'vincent.van.gogh@egallery.test',
                'category' => 'Painting',
                'title' => "The Poet's Garden",
                'description' => 'A richly colored view of the public garden near the Yellow House in Arles.',
                'image_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/1/14/Vincent_van_Gogh_-_The_Poet%27s_Garden_-_1933.433_-_Art_Institute_of_Chicago.jpg/1280px-Vincent_van_Gogh_-_The_Poet%27s_Garden_-_1933.433_-_Art_Institute_of_Chicago.jpg',
                'creation_date' => '1888',
                'dimensions' => '73 × 92.1 cm',
            ],
            [
                'artist_email' => 'claude.monet@egallery.test',
                'category' => 'Painting',
                'title' => 'Water Lilies',
                'description' => "One of Monet's paintings of the water garden at his home in Giverny.",
                'image_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/6/65/Claude_Monet_-_Water_Lilies_-_1933.1157_-_Art_Institute_of_Chicago.jpg/1280px-Claude_Monet_-_Water_Lilies_-_1933.1157_-_Art_Institute_of_Chicago.jpg',
                'creation_date' => '1906',
                'dimensions' => '89.9 × 94.1 cm',
            ],
            [
                'artist_email' => 'claude.monet@egallery.test',
                'category' => 'Painting',
                'title' => 'Stacks of Wheat (End of Summer)',
                'description' => "Part of Monet's series examining stacks of wheat under changing light and weather.",
                'image_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/6/68/Claude_Monet_-_Stacks_of_Wheat_%28End_of_Summer%29_-_1985.1103_-_Art_Institute_of_Chicago.jpg/1280px-Claude_Monet_-_Stacks_of_Wheat_%28End_of_Summer%29_-_1985.1103_-_Art_Institute_of_Chicago.jpg',
                'creation_date' => '1890–91',
                'dimensions' => '60 × 100.5 cm',
            ],
            [
                'artist_email' => 'claude.monet@egallery.test',
                'category' => 'Painting',
                'title' => 'Cliff Walk at Pourville',
                'description' => 'A coastal landscape showing two figures on a windswept cliff in Normandy.',
                'image_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/a4/Claude_Monet_-_Cliff_Walk_at_Pourville_-_Google_Art_Project.jpg/1280px-Claude_Monet_-_Cliff_Walk_at_Pourville_-_Google_Art_Project.jpg',
                'creation_date' => '1882',
                'dimensions' => '66.5 × 82.3 cm',
            ],
            [
                'artist_email' => 'georges.seurat@egallery.test',
                'category' => 'Painting',
                'title' => 'A Sunday on La Grande Jatte — 1884',
                'description' => "Seurat's monumental depiction of Parisians at leisure on an island in the Seine.",
                'image_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/b/b7/Georges_Seurat_-_A_Sunday_on_La_Grande_Jatte_--_1884_-_Google_Art_Project.jpg/1280px-Georges_Seurat_-_A_Sunday_on_La_Grande_Jatte_--_1884_-_Google_Art_Project.jpg',
                'creation_date' => '1884–86, border added 1888–89',
                'dimensions' => '207.5 × 308.1 cm',
            ],
            [
                'artist_email' => 'grant.wood@egallery.test',
                'category' => 'Painting',
                'title' => 'American Gothic',
                'description' => "Grant Wood's iconic portrait of two figures posed before an Iowa farmhouse.",
                'image_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/c/cc/Grant_Wood_-_American_Gothic_-_Google_Art_Project.jpg/1280px-Grant_Wood_-_American_Gothic_-_Google_Art_Project.jpg',
                'creation_date' => '1930',
                'dimensions' => '78 × 65.3 cm',
            ],
            [
                'artist_email' => 'katsushika.hokusai@egallery.test',
                'category' => 'Print',
                'title' => 'Under the Wave off Kanagawa (The Great Wave)',
                'description' => "Hokusai's celebrated woodblock print from the series Thirty-Six Views of Mount Fuji.",
                'image_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/0/0a/The_Great_Wave_off_Kanagawa.jpg/1280px-The_Great_Wave_off_Kanagawa.jpg',
                'creation_date' => '1830/33',
                'dimensions' => '25.4 × 37.6 cm',
            ],
        ];

        foreach ($realArtworks as $artworkData) {
            $artist = $artists->get($artworkData['artist_email']);
            $category = $categories->get($artworkData['category']);

            if (! $artist || ! $category) {
                continue;
            }

            Artwork::query()->updateOrCreate(
                [
                    'artist_id' => $artist->id,
                    'title' => $artworkData['title'],
                ],
                [
                    'category_id' => $category->id,
                    'description' => $artworkData['description'],
                    'image_url' => $artworkData['image_url'],
                    'creation_date' => $artworkData['creation_date'],
                    'dimensions' => $artworkData['dimensions'],
                ],
            );
        }

        $demoArtistEmails = [
            'demo.artist.one@egallery.test',
            'demo.artist.two@egallery.test',
        ];

        $demoArtists = $artists
            ->filter(fn (Artist $artist): bool => in_array(
                $artist->user?->email,
                $demoArtistEmails,
                true,
            ))
            ->values();

        if ($demoArtists->isEmpty() || $categories->isEmpty()) {
            return;
        }

        $missingFactoryArtworkCount = max(
            0,
            8 - Artwork::query()
                ->whereIn('artist_id', $demoArtists->modelKeys())
                ->count(),
        );

        if ($missingFactoryArtworkCount === 0) {
            return;
        }

        Artwork::factory()
            ->count($missingFactoryArtworkCount)
            ->state(fn (): array => [
                'artist_id' => $demoArtists->random()->id,
                'category_id' => $categories->random()->id,
            ])
            ->create();
    }
}
