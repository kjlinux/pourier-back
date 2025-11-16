<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'email' => $this->email,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'avatar_url' => $this->avatar_url,
            'phone' => $this->phone,
            'bio' => $this->bio,
            'account_type' => $this->account_type,
            'is_verified' => $this->is_verified,
            'is_active' => $this->is_active,
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),
            'last_login' => $this->last_login?->toIso8601String(),

            // Roles and Permissions
            'roles' => $this->getRoleNames(),
            'permissions' => $this->getAllPermissions()->pluck('name'),

            // Photographer specific data
            'photographer_profile' => new PhotographerProfileResource($this->whenLoaded('photographerProfile')),
            'photographer_status' => $this->getPhotographerStatus(),
            'is_approved_photographer' => $this->isApprovedPhotographer(),

            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
