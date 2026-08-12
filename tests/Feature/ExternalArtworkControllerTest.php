<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

it('fetches and normalizes artworks from the Art Institute of Chicago', function () {
    Http::fake([
        'api.artic.edu/*' => Http::response([
            'pagination' => [
                'total' => 12,
                'limit' => 2,
                'current_page' => 2,
                'total_pages' => 6,
            ],
            'data' => [[
                'id' => 16568,
                'title' => 'Water Lilies',
                'artist_display' => 'Claude Monet',
                'description' => '<p>A pond in Giverny.</p>',
                'date_display' => '1906',
                'dimensions' => '89.9 × 94.1 cm',
                'medium_display' => 'Oil on canvas',
                'artwork_type_title' => 'Painting',
                'department_title' => 'Painting and Sculpture of Europe',
                'image_id' => 'image-123',
                'is_public_domain' => true,
            ]],
            'config' => [
                'iiif_url' => 'https://www.artic.edu/iiif/2',
                'website_url' => 'https://www.artic.edu',
            ],
        ]),
    ]);

    $this->getJson('/api/external/artworks/art-institute?search=monet&page=2&per_page=2')
        ->assertOk()
        ->assertJsonPath('source', 'Art Institute of Chicago')
        ->assertJsonPath('total', 12)
        ->assertJsonPath('current_page', 2)
        ->assertJsonPath('artworks.0.external_id', 16568)
        ->assertJsonPath('artworks.0.title', 'Water Lilies')
        ->assertJsonPath('artworks.0.artist', 'Claude Monet')
        ->assertJsonPath('artworks.0.description', 'A pond in Giverny.')
        ->assertJsonPath('artworks.0.image_url', 'https://www.artic.edu/iiif/2/image-123/full/843,/0/default.jpg')
        ->assertJsonPath('artworks.0.is_public_domain', true);

    Http::assertSent(fn (Request $request): bool => str_starts_with($request->url(), 'https://api.artic.edu/api/v1/artworks/search?')
        && $request['q'] === 'monet'
        && (int) $request['page'] === 2
        && (int) $request['limit'] === 2
    );
});

it('fetches and normalizes artworks from the Cleveland Museum of Art', function () {
    Http::fake([
        'openaccess-api.clevelandart.org/*' => Http::response([
            'info' => ['total' => 5],
            'data' => [[
                'id' => 130707,
                'title' => 'Head of Saint John the Baptist',
                'description' => '<p>An oil painting.</p>',
                'creation_date' => 'c. 1550–1650',
                'creators' => [[
                    'description' => 'Unknown Spanish or Italian artist',
                ]],
                'technique' => 'Oil on canvas',
                'type' => 'Painting',
                'department' => 'European Painting and Sculpture',
                'measurements' => '50 × 75.2 cm',
                'url' => 'https://clevelandart.org/art/1953.424',
                'images' => [
                    'web' => [
                        'url' => 'https://openaccess-cdn.clevelandart.org/image.jpg',
                    ],
                ],
                'share_license_status' => 'CC0',
            ]],
        ]),
    ]);

    $this->getJson('/api/external/artworks/cleveland?search=saint&page=2&per_page=2')
        ->assertOk()
        ->assertJsonPath('source', 'Cleveland Museum of Art')
        ->assertJsonPath('total', 5)
        ->assertJsonPath('current_page', 2)
        ->assertJsonPath('last_page', 3)
        ->assertJsonPath('artworks.0.external_id', 130707)
        ->assertJsonPath('artworks.0.artist', 'Unknown Spanish or Italian artist')
        ->assertJsonPath('artworks.0.image_url', 'https://openaccess-cdn.clevelandart.org/image.jpg')
        ->assertJsonPath('artworks.0.is_public_domain', true);

    Http::assertSent(fn (Request $request): bool => str_starts_with($request->url(), 'https://openaccess-api.clevelandart.org/api/artworks/?')
        && $request['q'] === 'saint'
        && (int) $request['skip'] === 2
        && (int) $request['limit'] === 2
        && (int) $request['has_image'] === 1
        && (int) $request['cc0'] === 1
    );
});

it('validates external artwork listing parameters before calling an API', function () {
    Http::fake();

    $this->getJson('/api/external/artworks/art-institute?page=0&per_page=21')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['page', 'per_page']);

    Http::assertNothingSent();
});

it('returns bad gateway when an external artwork API is unavailable', function () {
    Http::fake([
        'api.artic.edu/*' => Http::response([], 503),
        'openaccess-api.clevelandart.org/*' => Http::response([], 503),
    ]);

    $this->getJson('/api/external/artworks/art-institute')
        ->assertStatus(502)
        ->assertJsonPath('source', 'Art Institute of Chicago');

    $this->getJson('/api/external/artworks/cleveland')
        ->assertStatus(502)
        ->assertJsonPath('source', 'Cleveland Museum of Art');
});
