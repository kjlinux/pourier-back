<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PhotographerProfileResource extends JsonResource
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
            'username' => $this->username,
            'display_name' => $this->display_name,
            'cover_photo_url' => $this->cover_photo_url,
            'location' => $this->location,
            'website' => $this->website,
            'instagram' => $this->instagram,
            'portfolio_url' => $this->portfolio_url,
            'specialties' => $this->specialties,
            'status' => $this->status,
            'commission_rate' => (float) $this->commission_rate,
            'total_sales' => $this->total_sales,
            'total_revenue' => $this->total_revenue,
            'followers_count' => $this->followers_count,
            'approved_at' => $this->approved_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
