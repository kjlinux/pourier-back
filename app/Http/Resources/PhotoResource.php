<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PhotoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'tags' => $this->tags,
            'preview_url' => $this->preview_url,
            'thumbnail_url' => $this->thumbnail_url,
            'width' => $this->width,
            'height' => $this->height,
            'format' => $this->format,
            'color_palette' => $this->color_palette,
            'orientation' => $this->width > $this->height ? 'landscape' : ($this->height > $this->width ? 'portrait' : 'square'),

            // EXIF data
            'camera' => $this->camera,
            'lens' => $this->lens,
            'iso' => $this->iso,
            'aperture' => $this->aperture,
            'shutter_speed' => $this->shutter_speed,
            'focal_length' => $this->focal_length,
            'taken_at' => $this->taken_at?->toISOString(),
            'location' => $this->location,

            // Pricing
            'price_standard' => $this->price_standard,
            'price_extended' => $this->price_extended,

            // Stats
            'views_count' => $this->views_count,
            'downloads_count' => $this->downloads_count,
            'favorites_count' => $this->favorites_count,
            'sales_count' => $this->sales_count,

            // Status
            'status' => $this->status,
            'is_public' => $this->is_public,
            'featured' => $this->featured,
            'featured_until' => $this->featured_until?->toISOString(),

            // Relations
            'photographer' => [
                'id' => $this->photographer->id,
                'first_name' => $this->photographer->first_name,
                'last_name' => $this->photographer->last_name,
                'avatar_url' => $this->photographer->avatar_url,
            ],
            'category' => [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ],

            // Timestamps
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
