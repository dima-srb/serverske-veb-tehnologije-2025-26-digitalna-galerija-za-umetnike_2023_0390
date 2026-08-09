<?php

use App\Models\Artist;
use App\Models\Artwork;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function fakeArtworkUpload(string $name = 'artwork.png'): UploadedFile
{
    return UploadedFile::fake()->createWithContent(
        $name,
        base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=')
    );
}

it('lists artworks with filters search and pagination', function () {
    $artist = Artist::factory()->create();
    $otherArtist = Artist::factory()->create();
    $painting = Category::factory()->create(['name' => 'Painting']);
    $print = Category::factory()->create(['name' => 'Print']);

    Artwork::factory()->count(2)->sequence(
        ['title' => 'Sunrise Study'],
        ['title' => 'Sunrise Over Water'],
    )->create([
        'artist_id' => $artist->id,
        'category_id' => $painting->id,
    ]);

    Artwork::factory()->create([
        'artist_id' => $artist->id,
        'category_id' => $print->id,
        'title' => 'Sunrise Print',
    ]);
    Artwork::factory()->create([
        'artist_id' => $otherArtist->id,
        'category_id' => $painting->id,
        'title' => 'Sunrise by Another Artist',
    ]);
    Artwork::factory()->create([
        'artist_id' => $artist->id,
        'category_id' => $painting->id,
        'title' => 'Moonlight',
    ]);

    $this->getJson('/api/artworks?'.http_build_query([
        'search' => 'Sunrise',
        'category_id' => $painting->id,
        'artist_id' => $artist->id,
        'per_page' => 1,
        'page' => 1,
    ]))
        ->assertOk()
        ->assertJsonPath('count', 1)
        ->assertJsonPath('total', 2)
        ->assertJsonPath('per_page', 1)
        ->assertJsonPath('current_page', 1)
        ->assertJsonPath('last_page', 2)
        ->assertJsonPath('filters.search', 'Sunrise')
        ->assertJsonPath('filters.category_id', (string) $painting->id)
        ->assertJsonPath('filters.artist_id', (string) $artist->id)
        ->assertJsonPath('artworks.0.artist.id', $artist->id)
        ->assertJsonPath('artworks.0.category.id', $painting->id);
});

it('lists artworks for a category using the public nested route', function () {
    $category = Category::factory()->create();
    $otherCategory = Category::factory()->create();

    Artwork::factory()->count(2)->create(['category_id' => $category->id]);
    Artwork::factory()->create(['category_id' => $otherCategory->id]);

    $response = $this->getJson("/api/categories/{$category->id}/artworks?per_page=1");

    $response
        ->assertOk()
        ->assertJsonPath('count', 1)
        ->assertJsonPath('total', 2)
        ->assertJsonPath('per_page', 1)
        ->assertJsonPath('filters.category_id', $category->id);

    expect(collect($response->json('artworks'))->pluck('category_id')->unique()->all())
        ->toBe([$category->id]);
});

it('lists artworks for an artist using the public nested route', function () {
    $artist = Artist::factory()->create();
    $otherArtist = Artist::factory()->create();

    Artwork::factory()->count(2)->create(['artist_id' => $artist->id]);
    Artwork::factory()->create(['artist_id' => $otherArtist->id]);

    $response = $this->getJson("/api/artists/{$artist->id}/artworks?per_page=1");

    $response
        ->assertOk()
        ->assertJsonPath('count', 1)
        ->assertJsonPath('total', 2)
        ->assertJsonPath('per_page', 1)
        ->assertJsonPath('filters.artist_id', $artist->id);

    expect(collect($response->json('artworks'))->pluck('artist_id')->unique()->all())
        ->toBe([$artist->id]);
});

it('allows everyone to view an artwork', function () {
    $artwork = Artwork::factory()->create(['title' => 'Public Artwork']);

    $this->getJson("/api/artworks/{$artwork->id}")
        ->assertOk()
        ->assertJsonPath('artwork.id', $artwork->id)
        ->assertJsonPath('artwork.title', 'Public Artwork')
        ->assertJsonPath('artwork.artist.id', $artwork->artist_id)
        ->assertJsonPath('artwork.category.id', $artwork->category_id);
});

it('requires authentication for artwork mutations', function () {
    $category = Category::factory()->create();
    $artwork = Artwork::factory()->create();

    $this->postJson('/api/artworks', [
        'category_id' => $category->id,
        'title' => 'Guest Artwork',
        'image_url' => 'https://example.com/guest.jpg',
    ])->assertUnauthorized();

    $this->patchJson("/api/artworks/{$artwork->id}", [
        'title' => 'Unauthorized Update',
    ])->assertUnauthorized();

    $this->deleteJson("/api/artworks/{$artwork->id}")
        ->assertUnauthorized();
});

it('forbids non-artist users from creating artworks', function (string $role) {
    $user = User::factory()->create(['role' => $role]);
    $category = Category::factory()->create();

    Sanctum::actingAs($user);

    $this->postJson('/api/artworks', [
        'category_id' => $category->id,
        'title' => 'Forbidden Artwork',
        'image_url' => 'https://example.com/forbidden.jpg',
    ])->assertForbidden();
})->with([
    User::ROLE_VISITOR,
    User::ROLE_ADMIN,
]);

it('allows an artist to create an artwork using an image url', function () {
    $artist = Artist::factory()->create();
    $category = Category::factory()->create();

    Sanctum::actingAs($artist->user);

    $this->postJson('/api/artworks', [
        'category_id' => $category->id,
        'title' => 'Remote Image Artwork',
        'description' => 'Created with an external image URL.',
        'image_url' => 'https://example.com/artwork.jpg',
        'creation_date' => '2026',
        'dimensions' => '120 x 80 cm',
    ])
        ->assertCreated()
        ->assertJsonPath('artwork.artist_id', $artist->id)
        ->assertJsonPath('artwork.category_id', $category->id)
        ->assertJsonPath('artwork.image_url', 'https://example.com/artwork.jpg');

    $this->assertDatabaseHas('artworks', [
        'artist_id' => $artist->id,
        'title' => 'Remote Image Artwork',
    ]);
});

it('allows an artist to create an artwork using a local upload', function () {
    Storage::fake('public');

    $artist = Artist::factory()->create();
    $category = Category::factory()->create();

    Sanctum::actingAs($artist->user);

    $response = $this->withHeader('Accept', 'application/json')->post('/api/artworks', [
        'category_id' => $category->id,
        'title' => 'Uploaded Artwork',
        'image' => fakeArtworkUpload(),
    ]);

    $response->assertCreated();

    $imagePath = ltrim(
        str_replace('/storage/', '', parse_url($response->json('artwork.image_url'), PHP_URL_PATH)),
        '/'
    );

    Storage::disk('public')->assertExists($imagePath);
});

it('requires exactly one image source when creating an artwork', function () {
    Storage::fake('public');

    $artist = Artist::factory()->create();
    $category = Category::factory()->create();

    Sanctum::actingAs($artist->user);

    $this->postJson('/api/artworks', [
        'category_id' => $category->id,
        'title' => 'Missing Image',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['image', 'image_url']);

    $this->withHeader('Accept', 'application/json')->post('/api/artworks', [
        'category_id' => $category->id,
        'title' => 'Two Images',
        'image' => fakeArtworkUpload(),
        'image_url' => 'https://example.com/artwork.jpg',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['image', 'image_url']);
});

it('allows only the owning artist to update or delete an artwork', function () {
    $owner = Artist::factory()->create();
    $otherArtist = Artist::factory()->create();
    $artwork = Artwork::factory()->create(['artist_id' => $owner->id]);

    Sanctum::actingAs($otherArtist->user);

    $this->patchJson("/api/artworks/{$artwork->id}", [
        'title' => 'Stolen Update',
    ])->assertForbidden();

    $this->deleteJson("/api/artworks/{$artwork->id}")
        ->assertForbidden();

    expect($artwork->fresh())->not->toBeNull();
});

it('replaces and removes local images when the owner updates an artwork', function () {
    Storage::fake('public');

    $artist = Artist::factory()->create();
    $oldPath = 'artworks/old-image.jpg';
    Storage::disk('public')->put($oldPath, 'old image');

    $artwork = Artwork::factory()->create([
        'artist_id' => $artist->id,
        'image_url' => Storage::disk('public')->url($oldPath),
    ]);

    Sanctum::actingAs($artist->user);

    $response = $this->withHeader('Accept', 'application/json')
        ->patch("/api/artworks/{$artwork->id}", [
            'title' => 'Updated Artwork',
            'image' => fakeArtworkUpload('replacement.png'),
        ]);

    $response
        ->assertOk()
        ->assertJsonPath('artwork.title', 'Updated Artwork');

    $newPath = ltrim(
        str_replace('/storage/', '', parse_url($response->json('artwork.image_url'), PHP_URL_PATH)),
        '/'
    );

    Storage::disk('public')->assertMissing($oldPath);
    Storage::disk('public')->assertExists($newPath);

    $this->patchJson("/api/artworks/{$artwork->id}", [
        'image_url' => 'https://example.com/replacement.jpg',
    ])
        ->assertOk()
        ->assertJsonPath('artwork.image_url', 'https://example.com/replacement.jpg');

    Storage::disk('public')->assertMissing($newPath);
});

it('deletes the owners artwork and its local image', function () {
    Storage::fake('public');

    $artist = Artist::factory()->create();
    $imagePath = 'artworks/deleted-image.jpg';
    Storage::disk('public')->put($imagePath, 'image');

    $artwork = Artwork::factory()->create([
        'artist_id' => $artist->id,
        'image_url' => Storage::disk('public')->url($imagePath),
    ]);

    Sanctum::actingAs($artist->user);

    $this->deleteJson("/api/artworks/{$artwork->id}")
        ->assertOk()
        ->assertJsonPath('message', 'Artwork deleted successfully.');

    $this->assertDatabaseMissing('artworks', ['id' => $artwork->id]);
    Storage::disk('public')->assertMissing($imagePath);
});
