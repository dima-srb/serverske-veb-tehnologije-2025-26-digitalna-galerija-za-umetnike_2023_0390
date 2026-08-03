<?php

use App\Models\Artist;
use App\Models\Artwork;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows everyone to list artists ordered by name', function () {
    $secondUser = User::factory()->create([
        'name' => 'Vincent van Gogh',
        'role' => User::ROLE_ARTIST,
    ]);
    $firstUser = User::factory()->create([
        'name' => 'Claude Monet',
        'role' => User::ROLE_ARTIST,
    ]);

    Artist::factory()->create(['user_id' => $secondUser->id]);
    Artist::factory()->create(['user_id' => $firstUser->id]);

    $this->getJson('/api/artists')
        ->assertOk()
        ->assertJsonPath('count', 2)
        ->assertJsonPath('artists.0.name', 'Claude Monet')
        ->assertJsonPath('artists.1.name', 'Vincent van Gogh')
        ->assertJsonMissingPath('artists.0.artworks');
});

it('allows everyone to view an artist with artworks and categories', function () {
    $user = User::factory()->create([
        'name' => 'Claude Monet',
        'role' => User::ROLE_ARTIST,
    ]);
    $artist = Artist::factory()->create(['user_id' => $user->id]);
    $category = Category::factory()->create(['name' => 'Painting']);
    $artwork = Artwork::factory()->create([
        'artist_id' => $artist->id,
        'category_id' => $category->id,
        'title' => 'Water Lilies',
    ]);

    $this->getJson("/api/artists/{$artist->id}")
        ->assertOk()
        ->assertJsonPath('artist.id', $artist->id)
        ->assertJsonPath('artist.name', 'Claude Monet')
        ->assertJsonPath('artist.artworks.0.id', $artwork->id)
        ->assertJsonPath('artist.artworks.0.title', 'Water Lilies')
        ->assertJsonPath('artist.artworks.0.category.id', $category->id)
        ->assertJsonMissingPath('artist.artworks.0.artist');
});

it('returns not found for an unknown artist', function () {
    $this->getJson('/api/artists/999')->assertNotFound();
});
