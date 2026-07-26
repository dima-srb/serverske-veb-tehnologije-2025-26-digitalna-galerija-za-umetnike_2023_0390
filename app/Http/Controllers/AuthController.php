<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $isArtist = $request->input('role') === User::ROLE_ARTIST;

        $validated = $request->validate(array_merge([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', Rule::in([
                User::ROLE_VISITOR,
                User::ROLE_ARTIST,
            ])],
        ], $this->artistProfileRules($isArtist)));

        $user = DB::transaction(function () use ($validated): User {
            $user = User::query()->create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => $validated['role'],
            ]);

            if ($user->role === User::ROLE_ARTIST) {
                $user->artist()->create($this->artistData($validated));
            }

            return $user;
        });

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'data' => new UserResource($user->load('artist')),
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()
            ->where('email', $validated['email'])
            ->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'The provided credentials are incorrect.',
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'data' => new UserResource($user->load('artist')),
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'You have successfully logged out.',
        ]);
    }

    public function me(Request $request): UserResource
    {
        /** @var User $user */
        $user = $request->user();

        return new UserResource($user->load('artist'));
    }

    public function updateProfile(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate(array_merge([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
        ], $this->artistProfileRules($user->role === User::ROLE_ARTIST)));

        DB::transaction(function () use ($user, $validated): void {
            $userData = Arr::only($validated, ['name']);

            if ($userData !== []) {
                $user->update($userData);
            }

            $artistData = $this->artistData($validated);

            if ($user->role === User::ROLE_ARTIST && $artistData !== []) {
                $user->artist()->updateOrCreate([], $artistData);
            }
        });

        return response()->json([
            'message' => 'Profile updated successfully.',
            'data' => new UserResource($user->refresh()->load('artist')),
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function artistProfileRules(bool $allowed): array
    {
        return [
            'bio' => [
                'sometimes',
                Rule::prohibitedIf(! $allowed),
                'nullable',
                'string',
                'max:5000',
            ],
            'country' => [
                'sometimes',
                Rule::prohibitedIf(! $allowed),
                'nullable',
                'string',
                'max:255',
            ],
            'website_url' => [
                'sometimes',
                Rule::prohibitedIf(! $allowed),
                'nullable',
                'url:http,https',
                'max:255',
            ],
            'social_links' => [
                'sometimes',
                Rule::prohibitedIf(! $allowed),
                'nullable',
                'array',
            ],
            'social_links.*' => [
                'nullable',
                'url:http,https',
                'max:2048',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function artistData(array $validated): array
    {
        return Arr::only($validated, [
            'bio',
            'country',
            'website_url',
            'social_links',
        ]);
    }
}
