<?php

namespace App\Http\Controllers;

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     title="E-Gallery API",
 *     version="1.0.0",
 *     description="REST API za digitalnu galeriju umetnickih dela. Javne rute omogucavaju pregled umetnika, kategorija, lokalnih radova i dela iz javnih muzejskih API-ja. Zasticene rute koriste Laravel Sanctum Bearer tokene."
 * )
 *
 * @OA\Server(
 *     url="/api",
 *     description="API base path"
 * )
 *
 * @OA\Tag(name="Auth", description="Registracija, prijava i odjava")
 * @OA\Tag(name="Users", description="Profil trenutno prijavljenog korisnika")
 * @OA\Tag(name="Artists", description="Javni pregled umetnika")
 * @OA\Tag(name="Categories", description="Pregled i administratorsko upravljanje kategorijama")
 * @OA\Tag(name="Artworks", description="Pregled, pretraga, filtriranje i upravljanje umetnickim delima")
 * @OA\Tag(name="External Artworks", description="Javna umetnicka dela iz eksternih muzejskih API-ja")
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="Sanctum token",
 *     description="Uneti token dobijen kroz /register ili /login. Authorization header: Bearer {token}"
 * )
 *
 * @OA\Schema(
 *     schema="MessageResponse",
 *     type="object",
 *     required={"message"},
 *
 *     @OA\Property(property="message", type="string", example="Operation completed successfully.")
 * )
 *
 * @OA\Schema(
 *     schema="ValidationError",
 *     type="object",
 *     required={"message","errors"},
 *
 *     @OA\Property(property="message", type="string", example="The given data was invalid."),
 *     @OA\Property(property="errors", type="object")
 * )
 *
 * @OA\Schema(
 *     schema="ExternalServiceError",
 *     type="object",
 *     required={"message","source"},
 *
 *     @OA\Property(property="message", type="string", example="External artwork service is currently unavailable."),
 *     @OA\Property(property="source", type="string", example="Art Institute of Chicago")
 * )
 *
 * @OA\Schema(
 *     schema="Artist",
 *     type="object",
 *     required={"id","user_id"},
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="user_id", type="integer", example=2),
 *     @OA\Property(property="name", type="string", nullable=true, example="Claude Monet"),
 *     @OA\Property(property="bio", type="string", nullable=true, example="French painter and founder of Impressionism."),
 *     @OA\Property(property="country", type="string", nullable=true, example="France"),
 *     @OA\Property(property="website_url", type="string", format="uri", nullable=true, example="https://example.com/monet"),
 *     @OA\Property(
 *         property="social_links",
 *         type="array",
 *         nullable=true,
 *
 *         @OA\Items(type="string", format="uri", example="https://instagram.com/artist")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     required={"id","name","email","role"},
 *
 *     @OA\Property(property="id", type="integer", example=2),
 *     @OA\Property(property="name", type="string", example="Ana Artist"),
 *     @OA\Property(property="email", type="string", format="email", example="ana@example.com"),
 *     @OA\Property(property="role", type="string", enum={"admin","artist","visitor"}, example="artist"),
 *     @OA\Property(property="artist", ref="#/components/schemas/Artist", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="Category",
 *     type="object",
 *     required={"id","name"},
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Painting"),
 *     @OA\Property(property="description", type="string", nullable=true, example="Paintings made with traditional or contemporary techniques.")
 * )
 *
 * @OA\Schema(
 *     schema="Artwork",
 *     type="object",
 *     required={"id","artist_id","category_id","title","image_url"},
 *
 *     @OA\Property(property="id", type="integer", example=10),
 *     @OA\Property(property="artist_id", type="integer", example=1),
 *     @OA\Property(property="category_id", type="integer", example=1),
 *     @OA\Property(property="title", type="string", example="Water Lilies"),
 *     @OA\Property(property="description", type="string", nullable=true, example="A view of the artist's garden pond."),
 *     @OA\Property(property="image_url", type="string", format="uri", example="http://localhost:8000/storage/artworks/water-lilies.jpg"),
 *     @OA\Property(property="creation_date", type="string", nullable=true, example="1906"),
 *     @OA\Property(property="dimensions", type="string", nullable=true, example="89.9 x 94.1 cm"),
 *     @OA\Property(property="artist", ref="#/components/schemas/Artist", nullable=true),
 *     @OA\Property(property="category", ref="#/components/schemas/Category", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="ArtistDetails",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/Artist"),
 *         @OA\Schema(
 *
 *             @OA\Property(property="artworks", type="array", @OA\Items(ref="#/components/schemas/Artwork"))
 *         )
 *     }
 * )
 *
 * @OA\Schema(
 *     schema="AuthResponse",
 *     type="object",
 *     required={"data","access_token","token_type"},
 *
 *     @OA\Property(property="data", ref="#/components/schemas/User"),
 *     @OA\Property(property="access_token", type="string", example="1|plain-text-token"),
 *     @OA\Property(property="token_type", type="string", example="Bearer")
 * )
 *
 * @OA\Schema(
 *     schema="RegisterRequest",
 *     type="object",
 *     required={"name","email","password","role"},
 *
 *     @OA\Property(property="name", type="string", maxLength=255, example="Ana Artist"),
 *     @OA\Property(property="email", type="string", format="email", maxLength=255, example="ana@example.com"),
 *     @OA\Property(property="password", type="string", format="password", minLength=8, example="password123"),
 *     @OA\Property(property="role", type="string", enum={"artist","visitor"}, example="artist"),
 *     @OA\Property(property="bio", type="string", maxLength=5000, nullable=true, example="Contemporary visual artist."),
 *     @OA\Property(property="country", type="string", maxLength=255, nullable=true, example="Serbia"),
 *     @OA\Property(property="website_url", type="string", format="uri", maxLength=255, nullable=true),
 *     @OA\Property(property="social_links", type="array", nullable=true, @OA\Items(type="string", format="uri"))
 * )
 *
 * @OA\Schema(
 *     schema="UpdateProfileRequest",
 *     type="object",
 *
 *     @OA\Property(property="name", type="string", maxLength=255, example="Ana Updated"),
 *     @OA\Property(property="bio", type="string", maxLength=5000, nullable=true),
 *     @OA\Property(property="country", type="string", maxLength=255, nullable=true),
 *     @OA\Property(property="website_url", type="string", format="uri", maxLength=255, nullable=true),
 *     @OA\Property(property="social_links", type="array", nullable=true, @OA\Items(type="string", format="uri"))
 * )
 *
 * @OA\Schema(
 *     schema="CategoryWriteRequest",
 *     type="object",
 *
 *     @OA\Property(property="name", type="string", maxLength=255, example="Sculpture"),
 *     @OA\Property(property="description", type="string", nullable=true, example="Three-dimensional artworks.")
 * )
 *
 * @OA\Schema(
 *     schema="CategoryCreateRequest",
 *     type="object",
 *     required={"name"},
 *
 *     @OA\Property(property="name", type="string", maxLength=255, example="Sculpture"),
 *     @OA\Property(property="description", type="string", nullable=true, example="Three-dimensional artworks.")
 * )
 *
 * @OA\Schema(
 *     schema="ArtworkCreateUrlRequest",
 *     type="object",
 *     required={"category_id","title","image_url"},
 *
 *     @OA\Property(property="category_id", type="integer", example=1),
 *     @OA\Property(property="title", type="string", maxLength=255, example="My Artwork"),
 *     @OA\Property(property="description", type="string", nullable=true),
 *     @OA\Property(property="creation_date", type="string", maxLength=255, nullable=true, example="2026"),
 *     @OA\Property(property="dimensions", type="string", maxLength=255, nullable=true, example="120 x 80 cm"),
 *     @OA\Property(property="image_url", type="string", format="uri", maxLength=2048, example="https://example.com/artwork.jpg")
 * )
 *
 * @OA\Schema(
 *     schema="ArtworkCreateUploadRequest",
 *     type="object",
 *     required={"category_id","title","image"},
 *
 *     @OA\Property(property="category_id", type="integer", example=1),
 *     @OA\Property(property="title", type="string", maxLength=255, example="My Uploaded Artwork"),
 *     @OA\Property(property="description", type="string", nullable=true),
 *     @OA\Property(property="creation_date", type="string", maxLength=255, nullable=true, example="2026"),
 *     @OA\Property(property="dimensions", type="string", maxLength=255, nullable=true, example="120 x 80 cm"),
 *     @OA\Property(property="image", type="string", format="binary", description="JPG, JPEG, PNG ili WEBP; maksimalno 5 MB")
 * )
 *
 * @OA\Schema(
 *     schema="ArtworkUpdateUrlRequest",
 *     type="object",
 *
 *     @OA\Property(property="category_id", type="integer", example=2),
 *     @OA\Property(property="title", type="string", maxLength=255, example="Updated Artwork"),
 *     @OA\Property(property="description", type="string", nullable=true),
 *     @OA\Property(property="creation_date", type="string", maxLength=255, nullable=true),
 *     @OA\Property(property="dimensions", type="string", maxLength=255, nullable=true),
 *     @OA\Property(property="image_url", type="string", format="uri", maxLength=2048, example="https://example.com/replacement.jpg")
 * )
 *
 * @OA\Schema(
 *     schema="ArtworkUpdateUploadRequest",
 *     type="object",
 *
 *     @OA\Property(property="category_id", type="integer", example=2),
 *     @OA\Property(property="title", type="string", maxLength=255, example="Updated Artwork"),
 *     @OA\Property(property="description", type="string", nullable=true),
 *     @OA\Property(property="creation_date", type="string", maxLength=255, nullable=true),
 *     @OA\Property(property="dimensions", type="string", maxLength=255, nullable=true),
 *     @OA\Property(property="image", type="string", format="binary", description="Nova JPG, JPEG, PNG ili WEBP slika; maksimalno 5 MB")
 * )
 *
 * @OA\Schema(
 *     schema="ArtworkListResponse",
 *     type="object",
 *     required={"count","total","per_page","current_page","last_page","filters","artworks"},
 *
 *     @OA\Property(property="count", type="integer", example=10),
 *     @OA\Property(property="total", type="integer", example=42),
 *     @OA\Property(property="per_page", type="integer", example=10),
 *     @OA\Property(property="current_page", type="integer", example=1),
 *     @OA\Property(property="last_page", type="integer", example=5),
 *     @OA\Property(property="filters", type="object"),
 *     @OA\Property(property="artworks", type="array", @OA\Items(ref="#/components/schemas/Artwork"))
 * )
 *
 * @OA\Schema(
 *     schema="ExternalArtwork",
 *     type="object",
 *
 *     @OA\Property(property="external_id", type="integer", nullable=true, example=16568),
 *     @OA\Property(property="title", type="string", nullable=true, example="Water Lilies"),
 *     @OA\Property(property="artist", type="string", nullable=true, example="Claude Monet"),
 *     @OA\Property(property="description", type="string", nullable=true),
 *     @OA\Property(property="creation_date", type="string", nullable=true, example="1906"),
 *     @OA\Property(property="dimensions", type="string", nullable=true),
 *     @OA\Property(property="medium", type="string", nullable=true, example="Oil on canvas"),
 *     @OA\Property(property="category", type="string", nullable=true, example="Painting"),
 *     @OA\Property(property="department", type="string", nullable=true),
 *     @OA\Property(property="image_url", type="string", format="uri", nullable=true),
 *     @OA\Property(property="source_url", type="string", format="uri", nullable=true),
 *     @OA\Property(property="is_public_domain", type="boolean", nullable=true, example=true)
 * )
 *
 * @OA\Schema(
 *     schema="ExternalArtworkListResponse",
 *     type="object",
 *     required={"source","source_url","count","total","per_page","current_page","last_page","artworks"},
 *
 *     @OA\Property(property="source", type="string", example="Art Institute of Chicago"),
 *     @OA\Property(property="source_url", type="string", format="uri"),
 *     @OA\Property(property="count", type="integer", example=10),
 *     @OA\Property(property="total", type="integer", example=100),
 *     @OA\Property(property="per_page", type="integer", example=10),
 *     @OA\Property(property="current_page", type="integer", example=1),
 *     @OA\Property(property="last_page", type="integer", example=10),
 *     @OA\Property(property="search", type="string", nullable=true, example="monet"),
 *     @OA\Property(property="artworks", type="array", @OA\Items(ref="#/components/schemas/ExternalArtwork"))
 * )
 *
 * @OA\Parameter(parameter="ArtworkSearch", name="search", in="query", required=false, description="Pretraga po delu, umetniku, kategoriji ili datumu", @OA\Schema(type="string", maxLength=255, example="monet"))
 * @OA\Parameter(parameter="ArtworkCategoryId", name="category_id", in="query", required=false, @OA\Schema(type="integer", example=1))
 * @OA\Parameter(parameter="ArtworkArtistId", name="artist_id", in="query", required=false, @OA\Schema(type="integer", example=1))
 * @OA\Parameter(parameter="ArtworkSortBy", name="sort_by", in="query", required=false, @OA\Schema(type="string", enum={"id","title","creation_date","created_at","updated_at"}, default="created_at"))
 * @OA\Parameter(parameter="ArtworkSortDirection", name="sort_direction", in="query", required=false, @OA\Schema(type="string", enum={"asc","desc"}, default="desc"))
 * @OA\Parameter(parameter="ArtworkPerPage", name="per_page", in="query", required=false, @OA\Schema(type="integer", minimum=1, maximum=50, default=10))
 * @OA\Parameter(parameter="Page", name="page", in="query", required=false, @OA\Schema(type="integer", minimum=1, default=1))
 * @OA\Parameter(parameter="ExternalSearch", name="search", in="query", required=false, @OA\Schema(type="string", maxLength=255, nullable=true, example="landscape"))
 * @OA\Parameter(parameter="ExternalPerPage", name="per_page", in="query", required=false, @OA\Schema(type="integer", minimum=1, maximum=20, default=10))
 *
 * @OA\Post(
 *     path="/register",
 *     operationId="registerUser",
 *     tags={"Auth"},
 *     summary="Registracija visitor ili artist korisnika",
 *     description="Admin registracija nije dozvoljena. Artist moze poslati i opciona profilna polja.",
 *
 *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/RegisterRequest")),
 *
 *     @OA\Response(response=201, description="Korisnik je registrovan", @OA\JsonContent(ref="#/components/schemas/AuthResponse")),
 *     @OA\Response(response=422, description="Validaciona greska", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
 * )
 *
 * @OA\Post(
 *     path="/login",
 *     operationId="loginUser",
 *     tags={"Auth"},
 *     summary="Prijava korisnika",
 *
 *     @OA\RequestBody(
 *         required=true,
 *
 *         @OA\JsonContent(
 *             required={"email","password"},
 *
 *             @OA\Property(property="email", type="string", format="email", example="ana@example.com"),
 *             @OA\Property(property="password", type="string", format="password", example="password123")
 *         )
 *     ),
 *
 *     @OA\Response(response=200, description="Uspesna prijava", @OA\JsonContent(ref="#/components/schemas/AuthResponse")),
 *     @OA\Response(response=401, description="Pogresni kredencijali", @OA\JsonContent(ref="#/components/schemas/MessageResponse")),
 *     @OA\Response(response=422, description="Validaciona greska", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
 * )
 *
 * @OA\Post(
 *     path="/logout",
 *     operationId="logoutUser",
 *     tags={"Auth"},
 *     summary="Odjava trenutno prijavljenog korisnika",
 *     security={{"bearerAuth":{}}},
 *
 *     @OA\Response(response=200, description="Uspesna odjava", @OA\JsonContent(ref="#/components/schemas/MessageResponse")),
 *     @OA\Response(response=401, description="Korisnik nije prijavljen", @OA\JsonContent(ref="#/components/schemas/MessageResponse"))
 * )
 *
 * @OA\Get(
 *     path="/user",
 *     operationId="getCurrentUser",
 *     tags={"Users"},
 *     summary="Profil trenutno prijavljenog korisnika",
 *     security={{"bearerAuth":{}}},
 *
 *     @OA\Response(
 *         response=200,
 *         description="Korisnicki profil",
 *
 *         @OA\JsonContent(@OA\Property(property="data", ref="#/components/schemas/User"))
 *     ),
 *
 *     @OA\Response(response=401, description="Korisnik nije prijavljen", @OA\JsonContent(ref="#/components/schemas/MessageResponse"))
 * )
 *
 * @OA\Patch(
 *     path="/user",
 *     operationId="updateCurrentUser",
 *     tags={"Users"},
 *     summary="Azuriranje korisnickog profila",
 *     description="Svi korisnici mogu promeniti name. Samo artist moze poslati artist profilna polja.",
 *     security={{"bearerAuth":{}}},
 *
 *     @OA\RequestBody(required=false, @OA\JsonContent(ref="#/components/schemas/UpdateProfileRequest")),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Profil je azuriran",
 *
 *         @OA\JsonContent(
 *
 *             @OA\Property(property="message", type="string", example="Profile updated successfully."),
 *             @OA\Property(property="data", ref="#/components/schemas/User")
 *         )
 *     ),
 *
 *     @OA\Response(response=401, description="Korisnik nije prijavljen", @OA\JsonContent(ref="#/components/schemas/MessageResponse")),
 *     @OA\Response(response=422, description="Validaciona greska", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
 * )
 *
 * @OA\Get(
 *     path="/artists",
 *     operationId="listArtists",
 *     tags={"Artists"},
 *     summary="Lista umetnika sortirana po imenu",
 *
 *     @OA\Response(
 *         response=200,
 *         description="Lista umetnika",
 *
 *         @OA\JsonContent(
 *
 *             @OA\Property(property="count", type="integer", example=5),
 *             @OA\Property(property="artists", type="array", @OA\Items(ref="#/components/schemas/Artist"))
 *         )
 *     )
 * )
 *
 * @OA\Get(
 *     path="/artists/{artist}",
 *     operationId="showArtist",
 *     tags={"Artists"},
 *     summary="Pregled umetnika i njegovih dela",
 *
 *     @OA\Parameter(name="artist", in="path", required=true, description="Artist ID", @OA\Schema(type="integer", example=1)),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Detalji umetnika",
 *
 *         @OA\JsonContent(@OA\Property(property="artist", ref="#/components/schemas/ArtistDetails"))
 *     ),
 *
 *     @OA\Response(response=404, description="Umetnik nije pronadjen", @OA\JsonContent(ref="#/components/schemas/MessageResponse"))
 * )
 *
 * @OA\Get(
 *     path="/categories",
 *     operationId="listCategories",
 *     tags={"Categories"},
 *     summary="Lista kategorija sortirana po nazivu",
 *
 *     @OA\Response(
 *         response=200,
 *         description="Lista kategorija",
 *
 *         @OA\JsonContent(
 *
 *             @OA\Property(property="count", type="integer", example=6),
 *             @OA\Property(property="categories", type="array", @OA\Items(ref="#/components/schemas/Category"))
 *         )
 *     )
 * )
 *
 * @OA\Post(
 *     path="/categories",
 *     operationId="createCategory",
 *     tags={"Categories"},
 *     summary="Kreiranje kategorije",
 *     description="Samo administrator moze kreirati kategoriju.",
 *     security={{"bearerAuth":{}}},
 *
 *     @OA\RequestBody(
 *         required=true,
 *
 *         @OA\JsonContent(ref="#/components/schemas/CategoryCreateRequest")
 *     ),
 *
 *     @OA\Response(
 *         response=201,
 *         description="Kategorija je kreirana",
 *
 *         @OA\JsonContent(
 *
 *             @OA\Property(property="message", type="string", example="Category created successfully."),
 *             @OA\Property(property="category", ref="#/components/schemas/Category")
 *         )
 *     ),
 *
 *     @OA\Response(response=401, description="Korisnik nije prijavljen", @OA\JsonContent(ref="#/components/schemas/MessageResponse")),
 *     @OA\Response(response=403, description="Samo admin ima pristup", @OA\JsonContent(ref="#/components/schemas/MessageResponse")),
 *     @OA\Response(response=422, description="Validaciona greska", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
 * )
 *
 * @OA\Get(
 *     path="/categories/{category}",
 *     operationId="showCategory",
 *     tags={"Categories"},
 *     summary="Pregled jedne kategorije",
 *
 *     @OA\Parameter(name="category", in="path", required=true, description="Category ID", @OA\Schema(type="integer", example=1)),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Detalji kategorije",
 *
 *         @OA\JsonContent(@OA\Property(property="category", ref="#/components/schemas/Category"))
 *     ),
 *
 *     @OA\Response(response=404, description="Kategorija nije pronadjena", @OA\JsonContent(ref="#/components/schemas/MessageResponse"))
 * )
 *
 * @OA\Put(
 *     path="/categories/{category}",
 *     operationId="replaceCategory",
 *     tags={"Categories"},
 *     summary="Azuriranje kategorije putem PUT zahteva",
 *     description="Samo administrator moze azurirati kategoriju. Kontroler prihvata parcijalni payload.",
 *     security={{"bearerAuth":{}}},
 *
 *     @OA\Parameter(name="category", in="path", required=true, description="Category ID", @OA\Schema(type="integer", example=1)),
 *
 *     @OA\RequestBody(required=false, @OA\JsonContent(ref="#/components/schemas/CategoryWriteRequest")),
 *
 *     @OA\Response(response=200, description="Kategorija je azurirana", @OA\JsonContent(@OA\Property(property="message", type="string"), @OA\Property(property="category", ref="#/components/schemas/Category"))),
 *     @OA\Response(response=401, description="Korisnik nije prijavljen", @OA\JsonContent(ref="#/components/schemas/MessageResponse")),
 *     @OA\Response(response=403, description="Samo admin ima pristup", @OA\JsonContent(ref="#/components/schemas/MessageResponse")),
 *     @OA\Response(response=404, description="Kategorija nije pronadjena", @OA\JsonContent(ref="#/components/schemas/MessageResponse")),
 *     @OA\Response(response=422, description="Validaciona greska", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
 * )
 *
 * @OA\Patch(
 *     path="/categories/{category}",
 *     operationId="updateCategory",
 *     tags={"Categories"},
 *     summary="Parcijalno azuriranje kategorije",
 *     description="Samo administrator moze azurirati kategoriju.",
 *     security={{"bearerAuth":{}}},
 *
 *     @OA\Parameter(name="category", in="path", required=true, description="Category ID", @OA\Schema(type="integer", example=1)),
 *
 *     @OA\RequestBody(required=false, @OA\JsonContent(ref="#/components/schemas/CategoryWriteRequest")),
 *
 *     @OA\Response(response=200, description="Kategorija je azurirana", @OA\JsonContent(@OA\Property(property="message", type="string"), @OA\Property(property="category", ref="#/components/schemas/Category"))),
 *     @OA\Response(response=401, description="Korisnik nije prijavljen", @OA\JsonContent(ref="#/components/schemas/MessageResponse")),
 *     @OA\Response(response=403, description="Samo admin ima pristup", @OA\JsonContent(ref="#/components/schemas/MessageResponse")),
 *     @OA\Response(response=404, description="Kategorija nije pronadjena", @OA\JsonContent(ref="#/components/schemas/MessageResponse")),
 *     @OA\Response(response=422, description="Validaciona greska", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
 * )
 *
 * @OA\Delete(
 *     path="/categories/{category}",
 *     operationId="deleteCategory",
 *     tags={"Categories"},
 *     summary="Brisanje kategorije",
 *     description="Samo administrator moze obrisati praznu kategoriju.",
 *     security={{"bearerAuth":{}}},
 *
 *     @OA\Parameter(name="category", in="path", required=true, description="Category ID", @OA\Schema(type="integer", example=1)),
 *
 *     @OA\Response(response=200, description="Kategorija je obrisana", @OA\JsonContent(ref="#/components/schemas/MessageResponse")),
 *     @OA\Response(response=401, description="Korisnik nije prijavljen", @OA\JsonContent(ref="#/components/schemas/MessageResponse")),
 *     @OA\Response(response=403, description="Samo admin ima pristup", @OA\JsonContent(ref="#/components/schemas/MessageResponse")),
 *     @OA\Response(response=404, description="Kategorija nije pronadjena", @OA\JsonContent(ref="#/components/schemas/MessageResponse")),
 *     @OA\Response(response=409, description="Kategorija sadrzi umetnicka dela", @OA\JsonContent(ref="#/components/schemas/MessageResponse"))
 * )
 *
 * @OA\Get(
 *     path="/artworks",
 *     operationId="listArtworks",
 *     tags={"Artworks"},
 *     summary="Lista umetnickih dela",
 *     description="Javna ruta sa pretragom, filterima, sortiranjem i paginacijom.",
 *
 *     @OA\Parameter(ref="#/components/parameters/ArtworkSearch"),
 *     @OA\Parameter(ref="#/components/parameters/ArtworkCategoryId"),
 *     @OA\Parameter(ref="#/components/parameters/ArtworkArtistId"),
 *     @OA\Parameter(ref="#/components/parameters/ArtworkSortBy"),
 *     @OA\Parameter(ref="#/components/parameters/ArtworkSortDirection"),
 *     @OA\Parameter(ref="#/components/parameters/ArtworkPerPage"),
 *     @OA\Parameter(ref="#/components/parameters/Page"),
 *
 *     @OA\Response(response=200, description="Paginirana lista dela", @OA\JsonContent(ref="#/components/schemas/ArtworkListResponse")),
 *     @OA\Response(response=422, description="Validaciona greska", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
 * )
 *
 * @OA\Get(
 *     path="/categories/{category}/artworks",
 *     operationId="listCategoryArtworks",
 *     tags={"Categories","Artworks"},
 *     summary="Umetnicka dela jedne kategorije",
 *
 *     @OA\Parameter(name="category", in="path", required=true, description="Category ID", @OA\Schema(type="integer", example=1)),
 *     @OA\Parameter(ref="#/components/parameters/ArtworkSearch"),
 *     @OA\Parameter(ref="#/components/parameters/ArtworkArtistId"),
 *     @OA\Parameter(ref="#/components/parameters/ArtworkSortBy"),
 *     @OA\Parameter(ref="#/components/parameters/ArtworkSortDirection"),
 *     @OA\Parameter(ref="#/components/parameters/ArtworkPerPage"),
 *     @OA\Parameter(ref="#/components/parameters/Page"),
 *
 *     @OA\Response(response=200, description="Dela iz kategorije", @OA\JsonContent(ref="#/components/schemas/ArtworkListResponse")),
 *     @OA\Response(response=404, description="Kategorija nije pronadjena", @OA\JsonContent(ref="#/components/schemas/MessageResponse")),
 *     @OA\Response(response=422, description="Validaciona greska", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
 * )
 *
 * @OA\Get(
 *     path="/artists/{artist}/artworks",
 *     operationId="listArtistArtworks",
 *     tags={"Artists","Artworks"},
 *     summary="Umetnicka dela jednog umetnika",
 *
 *     @OA\Parameter(name="artist", in="path", required=true, description="Artist ID", @OA\Schema(type="integer", example=1)),
 *     @OA\Parameter(ref="#/components/parameters/ArtworkSearch"),
 *     @OA\Parameter(ref="#/components/parameters/ArtworkCategoryId"),
 *     @OA\Parameter(ref="#/components/parameters/ArtworkSortBy"),
 *     @OA\Parameter(ref="#/components/parameters/ArtworkSortDirection"),
 *     @OA\Parameter(ref="#/components/parameters/ArtworkPerPage"),
 *     @OA\Parameter(ref="#/components/parameters/Page"),
 *
 *     @OA\Response(response=200, description="Dela umetnika", @OA\JsonContent(ref="#/components/schemas/ArtworkListResponse")),
 *     @OA\Response(response=404, description="Umetnik nije pronadjen", @OA\JsonContent(ref="#/components/schemas/MessageResponse")),
 *     @OA\Response(response=422, description="Validaciona greska", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
 * )
 *
 * @OA\Post(
 *     path="/artworks",
 *     operationId="createArtwork",
 *     tags={"Artworks"},
 *     summary="Kreiranje umetnickog dela",
 *     description="Samo artist moze kreirati delo. Poslati image fajl ili image_url, ali ne oba. artist_id se odredjuje iz tokena.",
 *     security={{"bearerAuth":{}}},
 *
 *     @OA\RequestBody(
 *         required=true,
 *
 *         @OA\JsonContent(ref="#/components/schemas/ArtworkCreateUrlRequest"),
 *
 *         @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(ref="#/components/schemas/ArtworkCreateUploadRequest"))
 *     ),
 *
 *     @OA\Response(
 *         response=201,
 *         description="Delo je kreirano",
 *
 *         @OA\JsonContent(@OA\Property(property="message", type="string"), @OA\Property(property="artwork", ref="#/components/schemas/Artwork"))
 *     ),
 *
 *     @OA\Response(response=401, description="Korisnik nije prijavljen", @OA\JsonContent(ref="#/components/schemas/MessageResponse")),
 *     @OA\Response(response=403, description="Samo artist moze kreirati delo", @OA\JsonContent(ref="#/components/schemas/MessageResponse")),
 *     @OA\Response(response=422, description="Validaciona greska", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
 * )
 *
 * @OA\Get(
 *     path="/artworks/{artwork}",
 *     operationId="showArtwork",
 *     tags={"Artworks"},
 *     summary="Pregled jednog umetnickog dela",
 *
 *     @OA\Parameter(name="artwork", in="path", required=true, description="Artwork ID", @OA\Schema(type="integer", example=10)),
 *
 *     @OA\Response(response=200, description="Detalji dela", @OA\JsonContent(@OA\Property(property="artwork", ref="#/components/schemas/Artwork"))),
 *     @OA\Response(response=404, description="Delo nije pronadjeno", @OA\JsonContent(ref="#/components/schemas/MessageResponse"))
 * )
 *
 * @OA\Put(
 *     path="/artworks/{artwork}",
 *     operationId="replaceArtwork",
 *     tags={"Artworks"},
 *     summary="Azuriranje dela putem PUT zahteva",
 *     description="Delo moze azurirati samo artist koji je njegov vlasnik. Image i image_url se ne mogu poslati zajedno.",
 *     security={{"bearerAuth":{}}},
 *
 *     @OA\Parameter(name="artwork", in="path", required=true, description="Artwork ID", @OA\Schema(type="integer", example=10)),
 *
 *     @OA\RequestBody(
 *         required=false,
 *
 *         @OA\JsonContent(ref="#/components/schemas/ArtworkUpdateUrlRequest"),
 *
 *         @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(ref="#/components/schemas/ArtworkUpdateUploadRequest"))
 *     ),
 *
 *     @OA\Response(response=200, description="Delo je azurirano", @OA\JsonContent(@OA\Property(property="message", type="string"), @OA\Property(property="artwork", ref="#/components/schemas/Artwork"))),
 *     @OA\Response(response=401, description="Korisnik nije prijavljen", @OA\JsonContent(ref="#/components/schemas/MessageResponse")),
 *     @OA\Response(response=403, description="Korisnik nije vlasnik dela", @OA\JsonContent(ref="#/components/schemas/MessageResponse")),
 *     @OA\Response(response=404, description="Delo nije pronadjeno", @OA\JsonContent(ref="#/components/schemas/MessageResponse")),
 *     @OA\Response(response=422, description="Validaciona greska", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
 * )
 *
 * @OA\Patch(
 *     path="/artworks/{artwork}",
 *     operationId="updateArtwork",
 *     tags={"Artworks"},
 *     summary="Parcijalno azuriranje umetnickog dela",
 *     description="Delo moze azurirati samo artist koji je njegov vlasnik. Image i image_url se ne mogu poslati zajedno.",
 *     security={{"bearerAuth":{}}},
 *
 *     @OA\Parameter(name="artwork", in="path", required=true, description="Artwork ID", @OA\Schema(type="integer", example=10)),
 *
 *     @OA\RequestBody(
 *         required=false,
 *
 *         @OA\JsonContent(ref="#/components/schemas/ArtworkUpdateUrlRequest"),
 *
 *         @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(ref="#/components/schemas/ArtworkUpdateUploadRequest"))
 *     ),
 *
 *     @OA\Response(response=200, description="Delo je azurirano", @OA\JsonContent(@OA\Property(property="message", type="string"), @OA\Property(property="artwork", ref="#/components/schemas/Artwork"))),
 *     @OA\Response(response=401, description="Korisnik nije prijavljen", @OA\JsonContent(ref="#/components/schemas/MessageResponse")),
 *     @OA\Response(response=403, description="Korisnik nije vlasnik dela", @OA\JsonContent(ref="#/components/schemas/MessageResponse")),
 *     @OA\Response(response=404, description="Delo nije pronadjeno", @OA\JsonContent(ref="#/components/schemas/MessageResponse")),
 *     @OA\Response(response=422, description="Validaciona greska", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
 * )
 *
 * @OA\Delete(
 *     path="/artworks/{artwork}",
 *     operationId="deleteArtwork",
 *     tags={"Artworks"},
 *     summary="Brisanje umetnickog dela",
 *     description="Delo moze obrisati samo artist koji je njegov vlasnik. Lokalno uploadovana slika se takodje brise.",
 *     security={{"bearerAuth":{}}},
 *
 *     @OA\Parameter(name="artwork", in="path", required=true, description="Artwork ID", @OA\Schema(type="integer", example=10)),
 *
 *     @OA\Response(response=200, description="Delo je obrisano", @OA\JsonContent(ref="#/components/schemas/MessageResponse")),
 *     @OA\Response(response=401, description="Korisnik nije prijavljen", @OA\JsonContent(ref="#/components/schemas/MessageResponse")),
 *     @OA\Response(response=403, description="Korisnik nije vlasnik dela", @OA\JsonContent(ref="#/components/schemas/MessageResponse")),
 *     @OA\Response(response=404, description="Delo nije pronadjeno", @OA\JsonContent(ref="#/components/schemas/MessageResponse"))
 * )
 *
 * @OA\Get(
 *     path="/external/artworks/art-institute",
 *     operationId="listArtInstituteArtworks",
 *     tags={"External Artworks"},
 *     summary="Javna dela iz Art Institute of Chicago API-ja",
 *     description="Vraca normalizovane public-domain rezultate bez API kljuca.",
 *
 *     @OA\Parameter(ref="#/components/parameters/ExternalSearch"),
 *     @OA\Parameter(ref="#/components/parameters/Page"),
 *     @OA\Parameter(ref="#/components/parameters/ExternalPerPage"),
 *
 *     @OA\Response(response=200, description="Eksterna umetnicka dela", @OA\JsonContent(ref="#/components/schemas/ExternalArtworkListResponse")),
 *     @OA\Response(response=422, description="Validaciona greska", @OA\JsonContent(ref="#/components/schemas/ValidationError")),
 *     @OA\Response(response=502, description="Eksterni servis nije dostupan", @OA\JsonContent(ref="#/components/schemas/ExternalServiceError"))
 * )
 *
 * @OA\Get(
 *     path="/external/artworks/cleveland",
 *     operationId="listClevelandArtworks",
 *     tags={"External Artworks"},
 *     summary="Javna dela iz Cleveland Museum of Art API-ja",
 *     description="Vraca normalizovane CC0 rezultate sa slikama bez API kljuca.",
 *
 *     @OA\Parameter(ref="#/components/parameters/ExternalSearch"),
 *     @OA\Parameter(ref="#/components/parameters/Page"),
 *     @OA\Parameter(ref="#/components/parameters/ExternalPerPage"),
 *
 *     @OA\Response(response=200, description="Eksterna umetnicka dela", @OA\JsonContent(ref="#/components/schemas/ExternalArtworkListResponse")),
 *     @OA\Response(response=422, description="Validaciona greska", @OA\JsonContent(ref="#/components/schemas/ValidationError")),
 *     @OA\Response(response=502, description="Eksterni servis nije dostupan", @OA\JsonContent(ref="#/components/schemas/ExternalServiceError"))
 * )
 */
class ApiDoc extends Controller {}
