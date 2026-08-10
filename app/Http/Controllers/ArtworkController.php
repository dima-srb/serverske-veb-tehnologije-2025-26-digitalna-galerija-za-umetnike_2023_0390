<?php

namespace App\Http\Controllers;

use App\Http\Resources\ArtworkResource;
use App\Models\Artist;
use App\Models\Artwork;
use App\Models\Category;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use RuntimeException;
use Throwable;

class ArtworkController extends Controller
{
    private const IMAGE_DIRECTORY = 'artworks';

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['sometimes', 'string', 'max:255'],
            'category_id' => ['sometimes', 'integer', 'exists:categories,id'],
            'artist_id' => ['sometimes', 'integer', 'exists:artists,id'],
            'sort_by' => [
                'sometimes',
                Rule::in(['id', 'title', 'creation_date', 'created_at', 'updated_at']),
            ],
            'sort_direction' => ['sometimes', Rule::in(['asc', 'desc'])],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ]);

        $query = Artwork::query()->with([
            'artist.user',
            'category',
        ]);

        if (! empty($validated['search'])) {
            $search = $validated['search'];

            $query->where(function ($query) use ($search): void {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('creation_date', 'like', "%{$search}%")
                    ->orWhereHas('category', function ($query) use ($search): void {
                        $query->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('artist.user', function ($query) use ($search): void {
                        $query->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if (isset($validated['category_id'])) {
            $query->where('category_id', $validated['category_id']);
        }

        if (isset($validated['artist_id'])) {
            $query->where('artist_id', $validated['artist_id']);
        }

        $sortBy = $validated['sort_by'] ?? 'created_at';
        $sortDirection = $validated['sort_direction'] ?? 'desc';

        $query->orderBy($sortBy, $sortDirection);

        if ($sortBy !== 'id') {
            $query->orderBy('id', $sortDirection);
        }

        $artworks = $query
            ->paginate((int) ($validated['per_page'] ?? 10))
            ->withQueryString();

        return response()->json([
            'count' => $artworks->count(),
            'total' => $artworks->total(),
            'per_page' => $artworks->perPage(),
            'current_page' => $artworks->currentPage(),
            'last_page' => $artworks->lastPage(),
            'filters' => $request->only([
                'search',
                'category_id',
                'artist_id',
                'sort_by',
                'sort_direction',
            ]),
            'artworks' => ArtworkResource::collection($artworks->getCollection()),
        ]);
    }

    /**
     * Display artworks that belong to the specified category.
     */
    public function indexByCategory(Request $request, Category $category): JsonResponse
    {
        $request->merge(['category_id' => $category->id]);

        return $this->index($request);
    }

    /**
     * Display artworks that belong to the specified artist.
     */
    public function indexByArtist(Request $request, Artist $artist): JsonResponse
    {
        $request->merge(['artist_id' => $artist->id]);

        return $this->index($request);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $artist = $this->currentArtist($request);

        if (! $artist) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'artist_id' => ['prohibited'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'creation_date' => ['nullable', 'string', 'max:255'],
            'dimensions' => ['nullable', 'string', 'max:255'],
            'image' => [
                'required_without:image_url',
                Rule::prohibitedIf($request->filled('image_url')),
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
            'image_url' => [
                'required_without:image',
                Rule::prohibitedIf($request->hasFile('image')),
                'url:http,https',
                'max:2048',
            ],
        ]);

        $storedImagePath = null;

        if ($request->hasFile('image')) {
            [$imageUrl, $storedImagePath] = $this->storeUploadedImage($request);
        } else {
            $imageUrl = $validated['image_url'];
        }

        try {
            $artwork = Artwork::query()->create([
                'artist_id' => $artist->id,
                'category_id' => $validated['category_id'],
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'image_url' => $imageUrl,
                'creation_date' => $validated['creation_date'] ?? null,
                'dimensions' => $validated['dimensions'] ?? null,
            ]);
        } catch (Throwable $exception) {
            if ($storedImagePath !== null) {
                Storage::disk('public')->delete($storedImagePath);
            }

            throw $exception;
        }

        $artwork->load(['artist.user', 'category']);

        return response()->json([
            'message' => 'Artwork created successfully.',
            'artwork' => new ArtworkResource($artwork),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Artwork $artwork): JsonResponse
    {
        $artwork->load(['artist.user', 'category']);

        return response()->json([
            'artwork' => new ArtworkResource($artwork),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Artwork $artwork): JsonResponse
    {
        if (! $this->ownsArtwork($request, $artwork)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'artist_id' => ['prohibited'],
            'category_id' => ['sometimes', 'required', 'integer', 'exists:categories,id'],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'creation_date' => ['sometimes', 'nullable', 'string', 'max:255'],
            'dimensions' => ['sometimes', 'nullable', 'string', 'max:255'],
            'image' => [
                'sometimes',
                'required',
                Rule::prohibitedIf($request->filled('image_url')),
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
            'image_url' => [
                'sometimes',
                'required',
                Rule::prohibitedIf($request->hasFile('image')),
                'url:http,https',
                'max:2048',
            ],
        ]);

        $updates = Arr::only($validated, [
            'category_id',
            'title',
            'description',
            'creation_date',
            'dimensions',
        ]);

        $storedImagePath = null;
        $oldImageUrl = $artwork->image_url;

        if ($request->hasFile('image')) {
            [$updates['image_url'], $storedImagePath] = $this->storeUploadedImage($request);
        } elseif (array_key_exists('image_url', $validated)) {
            $updates['image_url'] = $validated['image_url'];
        }

        if ($updates === []) {
            $artwork->load(['artist.user', 'category']);

            return response()->json([
                'message' => 'Nothing to update.',
                'artwork' => new ArtworkResource($artwork),
            ]);
        }

        try {
            $artwork->update($updates);
        } catch (Throwable $exception) {
            if ($storedImagePath !== null) {
                Storage::disk('public')->delete($storedImagePath);
            }

            throw $exception;
        }

        if (array_key_exists('image_url', $updates)) {
            $this->deleteLocalImage($oldImageUrl);
        }

        $artwork->load(['artist.user', 'category']);

        return response()->json([
            'message' => 'Artwork updated successfully.',
            'artwork' => new ArtworkResource($artwork),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Artwork $artwork): JsonResponse
    {
        if (! $this->ownsArtwork($request, $artwork)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $imageUrl = $artwork->image_url;

        $artwork->delete();
        $this->deleteLocalImage($imageUrl);

        return response()->json([
            'message' => 'Artwork deleted successfully.',
        ]);
    }

    private function currentArtist(Request $request): ?Artist
    {
        $user = $request->user();

        if ($user?->role !== User::ROLE_ARTIST) {
            return null;
        }

        return $user->artist;
    }

    private function ownsArtwork(Request $request, Artwork $artwork): bool
    {
        $artist = $this->currentArtist($request);

        return $artist !== null && $artist->id === $artwork->artist_id;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function storeUploadedImage(Request $request): array
    {
        $path = $request->file('image')?->store(self::IMAGE_DIRECTORY, 'public');

        if (! is_string($path)) {
            throw new RuntimeException('Image upload failed.');
        }

        return [Storage::disk('public')->url($path), $path];
    }

    private function deleteLocalImage(?string $imageUrl): void
    {
        if ($imageUrl === null) {
            return;
        }

        $urlHost = parse_url($imageUrl, PHP_URL_HOST);
        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);

        if (
            is_string($urlHost)
            && $urlHost !== ''
            && (! is_string($appHost) || strcasecmp($urlHost, $appHost) !== 0)
        ) {
            return;
        }

        $urlPath = parse_url($imageUrl, PHP_URL_PATH);
        $publicStoragePrefix = '/storage/';

        if (! is_string($urlPath) || ! str_starts_with($urlPath, $publicStoragePrefix)) {
            return;
        }

        $storagePath = ltrim(substr($urlPath, strlen($publicStoragePrefix)), '/');

        if (! str_starts_with($storagePath, self::IMAGE_DIRECTORY.'/')) {
            return;
        }

        Storage::disk('public')->delete($storagePath);
    }
}
