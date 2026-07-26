<?php

use App\Http\Resources\ArtworkResource;
use App\Http\Resources\CategoryResource;
use App\Models\Artwork;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('transforms an artwork with loaded artist and category relationships', function () {
    $artwork = Artwork::factory()
        ->create()
        ->load(['artist', 'category']);

    $data = ArtworkResource::make($artwork)
        ->response()
        ->getData(true)['data'];

    expect($data)
        ->toMatchArray([
            'id' => $artwork->id,
            'artist_id' => $artwork->artist_id,
            'category_id' => $artwork->category_id,
            'title' => $artwork->title,
            'image_url' => $artwork->image_url,
        ])
        ->and($data['artist']['id'])->toBe($artwork->artist_id)
        ->and($data['category']['id'])->toBe($artwork->category_id);
});

it('includes category artworks only when the relationship is loaded', function () {
    $category = Category::factory()->create();
    Artwork::factory()->count(2)->create([
        'category_id' => $category->id,
    ]);

    $withoutArtworks = CategoryResource::make($category)
        ->response()
        ->getData(true)['data'];

    expect($withoutArtworks)->not->toHaveKey('artworks');

    $withArtworks = CategoryResource::make($category->load('artworks'))
        ->response()
        ->getData(true)['data'];

    expect($withArtworks['artworks'])->toHaveCount(2)
        ->and($withArtworks['artworks'][0])->not->toHaveKey('category');
});
