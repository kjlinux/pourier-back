<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'photo_id' => $this->photo_id,
            'license_type' => $this->license_type,
            'price' => $this->price,
            'photo' => $this->when($this->relationLoaded('photo') && $this->photo, function () {
                $photo = $this->photo;
                $photographer = $photo->photographer;
                $profile = $photographer?->photographerProfile;

                return [
                    'id' => $photo->id,
                    'title' => $photo->title,
                    'preview_url' => $photo->preview_url,
                    'thumbnail_url' => $photo->thumbnail_url,
                    'photographer' => [
                        'id' => $photographer?->id,
                        'name' => $photographer ? $photographer->first_name . ' ' . $photographer->last_name : $this->photographer_name,
                        'display_name' => $profile?->display_name ?? ($photographer ? $photographer->first_name . ' ' . $photographer->last_name : $this->photographer_name),
                    ],
                ];
            }, [
                'id' => $this->photo_id,
                'title' => $this->photo_title,
                'preview_url' => null,
                'thumbnail_url' => $this->photo_thumbnail,
                'photographer' => [
                    'id' => $this->photographer_id,
                    'name' => $this->photographer_name,
                    'display_name' => $this->photographer_name,
                ],
            ]),
        ];
    }
}
