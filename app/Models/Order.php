<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'order_number',
        'user_id',
        'subtotal',
        'tax',
        'discount',
        'total',
        'payment_status',
        'payment_method',
        'payment_provider',
        'payment_id',
        'ligdicash_token',
        'billing_email',
        'billing_first_name',
        'billing_last_name',
        'billing_phone',
        'invoice_url',
        'paid_at',
        'invoice_path',
        'invoice_generated_at',
        'completed_at',
        'payment_phone',
        'otp_requested_at',
        'otp_expires_at',
        'otp_request_count',
        'payment_flow',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'integer',
            'tax' => 'integer',
            'invoice_generated_at' => 'datetime',
            'completed_at' => 'datetime',
            'discount' => 'integer',
            'total' => 'integer',
            'paid_at' => 'datetime',
            'otp_requested_at' => 'datetime',
            'otp_expires_at' => 'datetime',
        ];
    }

    // Relationships

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    // Scopes

    public function scopeCompleted($query)
    {
        return $query->where('payment_status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('payment_status', 'pending');
    }

    public function scopeFailed($query)
    {
        return $query->where('payment_status', 'failed');
    }

    public function scopeRefunded($query)
    {
        return $query->where('payment_status', 'refunded');
    }

    // Methods

    public function markAsCompleted(string $transactionId): void
    {
        $this->update([
            'payment_status' => 'completed',
            'payment_id' => $transactionId,
            'paid_at' => now(),
        ]);
    }

    public function markAsFailed(): void
    {
        $this->update([
            'payment_status' => 'failed',
        ]);
    }

    public function isPending(): bool
    {
        return $this->payment_status === 'pending';
    }

    public function isCompleted(): bool
    {
        return $this->payment_status === 'completed';
    }

    public static function generateOrderNumber(): string
    {
        $date = now()->format('Ymd');
        $random = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));

        return "ORD-{$date}-{$random}";
    }

    // OTP-related methods

    public function canRequestOtp(): bool
    {
        $maxRequests = config('services.ligdicash.max_otp_requests', 3);

        return $this->otp_request_count < $maxRequests && $this->isPending();
    }

    public function isOtpExpired(): bool
    {
        if (! $this->otp_expires_at) {
            return true;
        }

        return $this->otp_expires_at->isPast();
    }

    public function incrementOtpRequestCount(): void
    {
        $this->increment('otp_request_count');
    }

    public function resetOtpTracking(): void
    {
        $this->update([
            'otp_requested_at' => null,
            'otp_expires_at' => null,
            'otp_request_count' => 0,
        ]);
    }

    public function isOtpFlow(): bool
    {
        return $this->payment_flow === 'otp';
    }

    public function isRedirectFlow(): bool
    {
        return $this->payment_flow === 'redirect';
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = static::generateOrderNumber();
            }
        });
    }
}
