<?php

namespace App\Http\Resources;

use App\Services\S3Service;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminPhotoResource extends JsonResource
{
    private static ?S3Service $s3Service = null;

    public function toArray(Request $request): array
    {
        if (self::$s3Service === null) {
            self::$s3Service = app(S3Service::class);
        }

        return [
            'id' => $this->id,
            'photographer_id' => $this->photographer_id,
            'category_id' => $this->category_id,
            'title' => $this->title,
            'description' => $this->description,
            'tags' => $this->tags,

            // URLs with S3 signed URLs
            'original_url' => $this->original_url ? self::$s3Service->getSignedUrl($this->original_url, 60) : null,
            'preview_url' => $this->preview_url ? self::$s3Service->getSignedUrl($this->preview_url, 60) : null,
            'thumbnail_url' => $this->thumbnail_url ? self::$s3Service->getSignedUrl($this->thumbnail_url, 60) : null,

            // Image metadata
            'width' => $this->width,
            'height' => $this->height,
            'file_size' => $this->file_size,
            'format' => $this->format,
            'color_palette' => $this->color_palette,

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

            // Status & moderation
            'is_public' => $this->is_public,
            'status' => $this->status,
            'rejection_reason' => $this->rejection_reason,
            'moderated_at' => $this->moderated_at?->toISOString(),
            'moderated_by' => $this->moderated_by,
            'featured' => $this->featured,
            'featured_until' => $this->featured_until?->toISOString(),

            // Relations
            'photographer' => $this->whenLoaded('photographer', fn () => [
                'id' => $this->photographer->id,
                'first_name' => $this->photographer->first_name,
                'last_name' => $this->photographer->last_name,
                'email' => $this->photographer->email,
            ]),
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ]),

            // Timestamps
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            'deleted_at' => $this->deleted_at?->toISOString(),
        ];
    }
}
