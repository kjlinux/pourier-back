<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'session_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * Get the total price of all items in the cart
     */
    public function getSubtotalAttribute(): float
    {
        return $this->items->sum('price');
    }

    /**
     * Get the total price including taxes (currently same as subtotal)
     */
    public function getTotalAttribute(): float
    {
        return $this->subtotal;
    }

    /**
     * Get the number of items in the cart
     */
    public function getItemsCountAttribute(): int
    {
        return $this->items->count();
    }
}
