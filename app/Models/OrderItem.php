<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'order_id',
        'photo_id',
        'photographer_id',
        'photo_title',
        'photo_thumbnail',
        'photographer_name',
        'license_type',
        'price',
        'photographer_amount',
        'platform_commission',
        'download_url',
        'download_expires_at',
        'photographer_paid',
        'photographer_paid_at',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'photographer_amount' => 'integer',
            'photographer_paid' => 'boolean',
            'photographer_paid_at' => 'datetime',
            'platform_commission' => 'integer',
            'download_expires_at' => 'datetime',
        ];
    }

    // Relationships

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function photo()
    {
        return $this->belongsTo(Photo::class);
    }

    public function photographer()
    {
        return $this->belongsTo(User::class, 'photographer_id');
    }

    // Methods

    public function generateDownloadUrl(): string
    {
        $storageService = app(\App\Services\StorageService::class);

        // Générer une URL signée valide 24h
        $signedUrl = $storageService->generateSignedDownloadUrl(
            $this->photo->original_url,
            24 // 24 heures
        );

        $this->update([
            'download_url' => $signedUrl,
            'download_expires_at' => now()->addHours(24),
        ]);

        return $signedUrl;
    }

    public function isDownloadExpired(): bool
    {
        return $this->download_expires_at && $this->download_expires_at->isPast();
    }
}
