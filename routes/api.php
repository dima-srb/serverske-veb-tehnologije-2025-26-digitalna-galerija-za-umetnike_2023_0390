<?php

use App\Http\Controllers\ArtistController;
use App\Http\Controllers\ArtworkController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ExternalArtworkController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::apiResource('categories', CategoryController::class)->only([
    'index',
    'show',
]);

Route::apiResource('artists', ArtistController::class)->only([
    'index',
    'show',
]);

Route::apiResource('artworks', ArtworkController::class)->only([
    'index',
    'show',
]);

Route::get('/categories/{category}/artworks', [ArtworkController::class, 'indexByCategory'])
    ->name('categories.artworks.index');

Route::get('/artists/{artist}/artworks', [ArtworkController::class, 'indexByArtist'])
    ->name('artists.artworks.index');

Route::prefix('external/artworks')->name('external.artworks.')->group(function (): void {
    Route::get('/art-institute', [ExternalArtworkController::class, 'artInstitute'])
        ->name('art-institute');
    Route::get('/cleveland', [ExternalArtworkController::class, 'cleveland'])
        ->name('cleveland');
});

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'me']);
    Route::patch('/user', [AuthController::class, 'updateProfile']);

    Route::apiResource('categories', CategoryController::class)->only([
        'store',
        'update',
        'destroy',
    ]);

    Route::apiResource('artworks', ArtworkController::class)->only([
        'store',
        'update',
        'destroy',
    ]);
});
