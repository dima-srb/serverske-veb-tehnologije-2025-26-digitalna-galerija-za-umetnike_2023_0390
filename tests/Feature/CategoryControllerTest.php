<?php

use App\Models\Artwork;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('allows everyone to list and view categories', function () {
    $secondCategory = Category::factory()->create(['name' => 'Sculpture']);
    $firstCategory = Category::factory()->create(['name' => 'Painting']);

    $this->getJson('/api/categories')
        ->assertOk()
        ->assertJsonPath('count', 2)
        ->assertJsonPath('categories.0.id', $firstCategory->id)
        ->assertJsonPath('categories.1.id', $secondCategory->id);

    $this->getJson("/api/categories/{$firstCategory->id}")
        ->assertOk()
        ->assertJsonPath('category.id', $firstCategory->id)
        ->assertJsonPath('category.name', 'Painting');
});

it('requires authentication for category mutations', function () {
    $category = Category::factory()->create();

    $this->postJson('/api/categories', [
        'name' => 'Painting',
    ])->assertUnauthorized();

    $this->patchJson("/api/categories/{$category->id}", [
        'name' => 'Updated category',
    ])->assertUnauthorized();

    $this->deleteJson("/api/categories/{$category->id}")
        ->assertUnauthorized();
});

it('forbids non-admin users from mutating categories', function (string $role) {
    $user = User::factory()->create(['role' => $role]);
    $category = Category::factory()->create();

    Sanctum::actingAs($user);

    $this->postJson('/api/categories', [
        'name' => 'Painting',
    ])->assertForbidden();

    $this->patchJson("/api/categories/{$category->id}", [
        'name' => 'Updated category',
    ])->assertForbidden();

    $this->deleteJson("/api/categories/{$category->id}")
        ->assertForbidden();
})->with([
    User::ROLE_VISITOR,
    User::ROLE_ARTIST,
]);

it('allows an admin to create update and delete a category', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    Sanctum::actingAs($admin);

    $categoryId = $this->postJson('/api/categories', [
        'name' => 'Painting',
        'description' => 'Paintings of every period.',
    ])
        ->assertCreated()
        ->assertJsonPath('category.name', 'Painting')
        ->json('category.id');

    $this->patchJson("/api/categories/{$categoryId}", [
        'name' => 'Fine Art Painting',
        'description' => null,
    ])
        ->assertOk()
        ->assertJsonPath('category.name', 'Fine Art Painting')
        ->assertJsonPath('category.description', null);

    $this->deleteJson("/api/categories/{$categoryId}")
        ->assertOk()
        ->assertJsonPath('message', 'Category deleted successfully.');

    $this->assertDatabaseMissing('categories', ['id' => $categoryId]);
});

it('validates unique category names', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $painting = Category::factory()->create(['name' => 'Painting']);
    $drawing = Category::factory()->create(['name' => 'Drawing']);

    Sanctum::actingAs($admin);

    $this->postJson('/api/categories', [
        'name' => 'Painting',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('name');

    $this->patchJson("/api/categories/{$drawing->id}", [
        'name' => $painting->name,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('name');
});

it('does not delete a category that contains artworks', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $category = Category::factory()->create();

    Artwork::factory()->create(['category_id' => $category->id]);

    Sanctum::actingAs($admin);

    $this->deleteJson("/api/categories/{$category->id}")
        ->assertStatus(409)
        ->assertJsonPath(
            'message',
            'Category cannot be deleted because it contains artworks.'
        );

    $this->assertDatabaseHas('categories', ['id' => $category->id]);
});
