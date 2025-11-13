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
            'order_id' => $this->order_id,
            'photo_id' => $this->photo_id,
            'photo_title' => $this->photo_title,
            'photo_thumbnail' => $this->photo_thumbnail,
            'photographer_id' => $this->photographer_id,
            'photographer_name' => $this->photographer_name,
            'license_type' => $this->license_type,
            'price' => $this->price,
            'photographer_amount' => $this->photographer_amount,
            'platform_commission' => $this->platform_commission,
            'download_url' => $this->download_url,
            'download_expires_at' => $this->download_expires_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
