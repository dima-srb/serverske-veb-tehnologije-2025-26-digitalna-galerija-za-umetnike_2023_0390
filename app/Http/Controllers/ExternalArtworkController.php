<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Throwable;

class ExternalArtworkController extends Controller
{
    private const ART_INSTITUTE_API_URL = 'https://api.artic.edu/api/v1/artworks/search';

    private const ART_INSTITUTE_WEBSITE_URL = 'https://www.artic.edu';

    private const CLEVELAND_API_URL = 'https://openaccess-api.clevelandart.org/api/artworks/';

    private const CLEVELAND_WEBSITE_URL = 'https://www.clevelandart.org';

    /**
     * Fetch public-domain artworks from the Art Institute of Chicago.
     */
    public function artInstitute(Request $request): JsonResponse
    {
        $validated = $this->validateListingRequest($request);
        $page = (int) ($validated['page'] ?? 1);
        $perPage = (int) ($validated['per_page'] ?? 10);
        $search = $validated['search'] ?? null;

        $query = [
            'page' => $page,
            'limit' => $perPage,
            'fields' => implode(',', [
                'id',
                'title',
                'artist_display',
                'description',
                'date_display',
                'dimensions',
                'medium_display',
                'artwork_type_title',
                'department_title',
                'image_id',
                'is_public_domain',
            ]),
            'query' => [
                'term' => [
                    'is_public_domain' => 'true',
                ],
            ],
        ];

        if ($search !== null && $search !== '') {
            $query['q'] = $search;
        }

        $response = $this->fetch(self::ART_INSTITUTE_API_URL, $query);

        if (! $response?->successful()) {
            return $this->serviceUnavailable('Art Institute of Chicago');
        }

        $payload = $response->json();

        if (! is_array($payload) || ! is_array($payload['data'] ?? null)) {
            return $this->serviceUnavailable('Art Institute of Chicago');
        }

        $pagination = is_array($payload['pagination'] ?? null)
            ? $payload['pagination']
            : [];
        $iiifUrl = data_get($payload, 'config.iiif_url', self::ART_INSTITUTE_WEBSITE_URL.'/iiif/2');
        $websiteUrl = data_get($payload, 'config.website_url', self::ART_INSTITUTE_WEBSITE_URL);

        $artworks = collect($payload['data'])
            ->map(fn (array $artwork): array => [
                'external_id' => $artwork['id'] ?? null,
                'title' => $artwork['title'] ?? null,
                'artist' => $artwork['artist_display'] ?? null,
                'description' => $this->plainText($artwork['description'] ?? null),
                'creation_date' => $artwork['date_display'] ?? null,
                'dimensions' => $artwork['dimensions'] ?? null,
                'medium' => $artwork['medium_display'] ?? null,
                'category' => $artwork['artwork_type_title'] ?? null,
                'department' => $artwork['department_title'] ?? null,
                'image_url' => $this->artInstituteImageUrl($iiifUrl, $artwork['image_id'] ?? null),
                'source_url' => isset($artwork['id'])
                    ? rtrim((string) $websiteUrl, '/').'/artworks/'.$artwork['id']
                    : null,
                'is_public_domain' => $artwork['is_public_domain'] ?? null,
            ])
            ->values()
            ->all();

        return response()->json([
            'source' => 'Art Institute of Chicago',
            'source_url' => self::ART_INSTITUTE_WEBSITE_URL,
            'count' => count($artworks),
            'total' => (int) ($pagination['total'] ?? count($artworks)),
            'per_page' => (int) ($pagination['limit'] ?? $perPage),
            'current_page' => (int) ($pagination['current_page'] ?? $page),
            'last_page' => (int) ($pagination['total_pages'] ?? 1),
            'search' => $search,
            'artworks' => $artworks,
        ]);
    }

    /**
     * Fetch CC0 artworks with images from the Cleveland Museum of Art.
     */
    public function cleveland(Request $request): JsonResponse
    {
        $validated = $this->validateListingRequest($request);
        $page = (int) ($validated['page'] ?? 1);
        $perPage = (int) ($validated['per_page'] ?? 10);
        $search = $validated['search'] ?? null;

        $query = [
            'skip' => ($page - 1) * $perPage,
            'limit' => $perPage,
            'has_image' => 1,
            'cc0' => 1,
            'fields' => implode(',', [
                'id',
                'title',
                'description',
                'creation_date',
                'creators',
                'technique',
                'type',
                'department',
                'measurements',
                'url',
                'images',
                'share_license_status',
            ]),
        ];

        if ($search !== null && $search !== '') {
            $query['q'] = $search;
        }

        $response = $this->fetch(self::CLEVELAND_API_URL, $query);

        if (! $response?->successful()) {
            return $this->serviceUnavailable('Cleveland Museum of Art');
        }

        $payload = $response->json();

        if (! is_array($payload) || ! is_array($payload['data'] ?? null)) {
            return $this->serviceUnavailable('Cleveland Museum of Art');
        }

        $total = (int) data_get($payload, 'info.total', count($payload['data']));

        $artworks = collect($payload['data'])
            ->map(fn (array $artwork): array => [
                'external_id' => $artwork['id'] ?? null,
                'title' => $artwork['title'] ?? null,
                'artist' => $this->clevelandArtists($artwork['creators'] ?? []),
                'description' => $this->plainText($artwork['description'] ?? null),
                'creation_date' => $artwork['creation_date'] ?? null,
                'dimensions' => $artwork['measurements'] ?? null,
                'medium' => $artwork['technique'] ?? null,
                'category' => $artwork['type'] ?? null,
                'department' => $artwork['department'] ?? null,
                'image_url' => data_get($artwork, 'images.web.url'),
                'source_url' => $artwork['url'] ?? null,
                'is_public_domain' => ($artwork['share_license_status'] ?? null) === 'CC0',
            ])
            ->values()
            ->all();

        return response()->json([
            'source' => 'Cleveland Museum of Art',
            'source_url' => self::CLEVELAND_WEBSITE_URL,
            'count' => count($artworks),
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => max(1, (int) ceil($total / $perPage)),
            'search' => $search,
            'artworks' => $artworks,
        ]);
    }

    /**
     * @return array{search?: string|null, page?: int, per_page?: int}
     */
    private function validateListingRequest(Request $request): array
    {
        return $request->validate([
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:20'],
        ]);
    }

    private function fetch(string $url, array $query): ?Response
    {
        try {
            return Http::acceptJson()
                ->timeout(10)
                ->retry(2, 200)
                ->get($url, $query);
        } catch (Throwable) {
            return null;
        }
    }

    private function serviceUnavailable(string $source): JsonResponse
    {
        return response()->json([
            'message' => 'External artwork service is currently unavailable.',
            'source' => $source,
        ], 502);
    }

    private function artInstituteImageUrl(mixed $iiifUrl, mixed $imageId): ?string
    {
        if (! is_string($iiifUrl) || ! is_string($imageId) || $imageId === '') {
            return null;
        }

        return rtrim($iiifUrl, '/').'/'.$imageId.'/full/843,/0/default.jpg';
    }

    private function clevelandArtists(mixed $creators): ?string
    {
        if (! is_array($creators)) {
            return null;
        }

        $artists = collect($creators)
            ->pluck('description')
            ->filter(fn (mixed $artist): bool => is_string($artist) && $artist !== '')
            ->implode('; ');

        return $artists !== '' ? $artists : null;
    }

    private function plainText(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        $text = trim(html_entity_decode(strip_tags($value)));

        return $text !== '' ? $text : null;
    }
}
