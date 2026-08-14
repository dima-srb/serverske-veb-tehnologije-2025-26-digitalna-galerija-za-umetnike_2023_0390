<?php

use Illuminate\Routing\Route as IlluminateRoute;
use Illuminate\Support\Facades\Route;

it('serves Swagger UI and documents every application API operation', function () {
    $this->get('/api/documentation')->assertOk();

    $specification = json_decode(
        (string) file_get_contents(storage_path('api-docs/api-docs.json')),
        true,
        512,
        JSON_THROW_ON_ERROR,
    );

    $documentedOperations = collect($specification['paths'])
        ->flatMap(function (array $operations, string $path): array {
            return collect(array_keys($operations))
                ->filter(fn (string $method): bool => in_array($method, [
                    'get',
                    'post',
                    'put',
                    'patch',
                    'delete',
                ], true))
                ->map(fn (string $method): string => "{$method} {$path}")
                ->all();
        })
        ->sort()
        ->values()
        ->all();

    $applicationOperations = collect(Route::getRoutes())
        ->filter(fn (IlluminateRoute $route): bool => str_starts_with($route->uri(), 'api/')
            && str_starts_with($route->getActionName(), 'App\\Http\\Controllers\\')
        )
        ->flatMap(function (IlluminateRoute $route): array {
            $path = '/'.substr($route->uri(), 4);

            return collect($route->methods())
                ->reject(fn (string $method): bool => $method === 'HEAD')
                ->map(fn (string $method): string => strtolower($method)." {$path}")
                ->all();
        })
        ->sort()
        ->values()
        ->all();

    expect($specification['info']['title'])->toBe('E-Gallery API')
        ->and($specification['components']['securitySchemes']['bearerAuth']['scheme'])->toBe('bearer')
        ->and($specification['paths']['/artworks']['post']['requestBody']['content'])
        ->toHaveKeys(['application/json', 'multipart/form-data'])
        ->and($documentedOperations)->toBe($applicationOperations);
});
