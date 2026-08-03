<?php

namespace App\Http\Controllers;

use App\Http\Resources\ArtistResource;
use App\Models\Artist;
use Illuminate\Http\JsonResponse;

class ArtistController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $artists = Artist::query()
            ->with('user')
            ->join('users', 'artists.user_id', '=', 'users.id')
            ->select('artists.*')
            ->orderBy('users.name')
            ->get();

        return response()->json([
            'count' => $artists->count(),
            'artists' => ArtistResource::collection($artists),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Artist $artist): JsonResponse
    {
        $artist->load([
            'user',
            'artworks.category',
        ]);

        return response()->json([
            'artist' => new ArtistResource($artist),
        ]);
    }
}
