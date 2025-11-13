<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Withdrawal extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'photographer_id',
        'amount',
        'status',
        'payment_method',
        'payment_details',
        'rejection_reason',
        'processed_at',
        'processed_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'payment_details' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    // Relationships

    public function photographer()
    {
        return $this->belongsTo(User::class, 'photographer_id');
    }

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    // Scopes

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}
