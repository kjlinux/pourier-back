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
        'payment_id',
        'cinetpay_transaction_id',
        'billing_email',
        'billing_first_name',
        'billing_last_name',
        'billing_phone',
        'invoice_url',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'integer',
            'tax' => 'integer',
            'discount' => 'integer',
            'total' => 'integer',
            'paid_at' => 'datetime',
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
}
