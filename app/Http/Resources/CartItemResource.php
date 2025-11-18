<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
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
            'photo_id' => $this->photo_id,
            'photo_title' => $this->photo->title,
            'photo_thumbnail' => $this->photo->thumbnail_url,
            'photographer_id' => $this->photo->photographer_id,
            'photographer_name' => $this->photo->photographer->first_name . ' ' . $this->photo->photographer->last_name,
            'license_type' => $this->license_type,
            'price' => (float) $this->price,
            'created_at' => $this->created_at,
        ];
    }
}
