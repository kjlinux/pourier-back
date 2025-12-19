<?php

namespace App\Http\Resources;

use App\Services\LigdicashOtpService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'user_id' => $this->user_id,

            // Items
            'items' => OrderItemResource::collection($this->whenLoaded('items')),

            // Pricing
            'subtotal' => $this->subtotal,
            'tax' => $this->tax,
            'discount' => $this->discount,
            'total' => $this->total,

            // Payment
            'payment_method' => $this->payment_method,
            'payment_provider' => $this->payment_provider,
            'payment_status' => $this->payment_status,
            'payment_id' => $this->payment_id,
            'ligdicash_token' => $this->ligdicash_token,

            // OTP Flow Fields
            'payment_flow' => $this->payment_flow,
            'payment_phone' => $this->payment_phone,
            'otp_requested_at' => $this->otp_requested_at?->toISOString(),
            'otp_expires_at' => $this->otp_expires_at?->toISOString(),
            'otp_request_count' => $this->otp_request_count,
            'can_request_otp' => $this->canRequestOtp(),
            'otp_expired' => $this->isOtpExpired(),
            'supports_otp' => $this->payment_provider
                ? LigdicashOtpService::supportsOtp($this->payment_provider)
                : null,

            // Billing
            'billing_email' => $this->billing_email,
            'billing_first_name' => $this->billing_first_name,
            'billing_last_name' => $this->billing_last_name,
            'billing_phone' => $this->billing_phone,

            // Invoice
            'invoice_url' => $this->invoice_url,

            // Timestamps
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
