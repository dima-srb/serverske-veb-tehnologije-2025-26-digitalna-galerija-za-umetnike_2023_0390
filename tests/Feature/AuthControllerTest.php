<?php

use App\Models\Artist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('registers a visitor without an artist profile', function () {
    $response = $this->postJson('/api/register', [
        'name' => 'Visitor User',
        'email' => 'visitor@example.com',
        'password' => 'password123',
        'role' => User::ROLE_VISITOR,
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.name', 'Visitor User')
        ->assertJsonPath('data.role', User::ROLE_VISITOR)
        ->assertJsonPath('data.artist', null)
        ->assertJsonPath('token_type', 'Bearer')
        ->assertJsonStructure(['access_token']);

    $user = User::query()->where('email', 'visitor@example.com')->firstOrFail();

    expect($user->artist)->toBeNull();
});

it('registers an artist and creates the artist profile', function () {
    $response = $this->postJson('/api/register', [
        'name' => 'New Artist',
        'email' => 'artist@example.com',
        'password' => 'password123',
        'role' => User::ROLE_ARTIST,
        'bio' => 'Artist biography.',
        'country' => 'Serbia',
        'website_url' => 'https://example.com',
        'social_links' => [
            'instagram' => 'https://instagram.com/new-artist',
        ],
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.role', User::ROLE_ARTIST)
        ->assertJsonPath('data.artist.bio', 'Artist biography.')
        ->assertJsonPath('data.artist.country', 'Serbia');

    $user = User::query()->where('email', 'artist@example.com')->firstOrFail();
    $artist = $user->artist()->firstOrFail();

    expect($artist->social_links)->toBe([
        'instagram' => 'https://instagram.com/new-artist',
    ]);
});

it('creates an empty artist profile when optional profile data is omitted', function () {
    $this->postJson('/api/register', [
        'name' => 'Minimal Artist',
        'email' => 'minimal.artist@example.com',
        'password' => 'password123',
        'role' => User::ROLE_ARTIST,
    ])->assertCreated();

    $user = User::query()
        ->where('email', 'minimal.artist@example.com')
        ->firstOrFail();

    expect($user->artist)->not->toBeNull();
});

it('does not allow public admin registration', function () {
    $this->postJson('/api/register', [
        'name' => 'Invalid Admin',
        'email' => 'admin@example.com',
        'password' => 'password123',
        'role' => User::ROLE_ADMIN,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('role');

    $this->assertDatabaseMissing('users', ['email' => 'admin@example.com']);
});

it('does not accept artist profile data for a visitor registration', function () {
    $this->postJson('/api/register', [
        'name' => 'Invalid Visitor',
        'email' => 'invalid.visitor@example.com',
        'password' => 'password123',
        'role' => User::ROLE_VISITOR,
        'bio' => 'This field is not allowed.',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('bio');
});

it('updates an artist user and artist profile', function () {
    $artist = Artist::factory()->create();
    $user = $artist->user;

    Sanctum::actingAs($user);

    $this->patchJson('/api/user', [
        'name' => 'Updated Artist',
        'bio' => 'Updated biography.',
        'country' => 'France',
        'website_url' => 'https://artist.example.com',
        'social_links' => [
            'portfolio' => 'https://portfolio.example.com',
        ],
    ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Updated Artist')
        ->assertJsonPath('data.artist.country', 'France');

    expect($user->refresh()->name)->toBe('Updated Artist')
        ->and($artist->refresh()->bio)->toBe('Updated biography.')
        ->and($artist->social_links)->toBe([
            'portfolio' => 'https://portfolio.example.com',
        ]);
});

it('allows a visitor to update only the user name', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $this->patchJson('/api/user', [
        'name' => 'Updated Visitor',
    ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Updated Visitor');

    $this->patchJson('/api/user', [
        'bio' => 'Not allowed.',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('bio');
});

it('logs in and logs out using sanctum tokens', function () {
    $user = User::factory()->create();

    $loginResponse = $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $token = $loginResponse
        ->assertOk()
        ->assertJsonPath('data.id', $user->id)
        ->json('access_token');

    expect($token)->toBeString();

    $this->withToken($token)
        ->postJson('/api/logout')
        ->assertOk();

    expect($user->tokens()->count())->toBe(0);
});
