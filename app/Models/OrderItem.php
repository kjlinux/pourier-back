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
        'license_type',
        'price',
        'photographer_amount',
        'commission',
        'download_url',
        'download_count',
        'download_expires_at',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'photographer_amount' => 'integer',
            'commission' => 'integer',
            'download_count' => 'integer',
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
}
