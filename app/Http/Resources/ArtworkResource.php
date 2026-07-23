<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArtworkResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'artist_id' => $this->artist_id,
            'category_id' => $this->category_id,
            'title' => $this->title,
            'description' => $this->description,
            'image_url' => $this->image_url,
            'creation_date' => $this->creation_date,
            'dimensions' => $this->dimensions,
            'artist' => ArtistResource::make($this->whenLoaded('artist')),
            'category' => CategoryResource::make($this->whenLoaded('category')),
        ];
    }
}
