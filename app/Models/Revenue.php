<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Revenue extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'photographer_id',
        'month',
        'total_sales',
        'commission',
        'net_revenue',
        'available_balance',
        'pending_balance',
        'withdrawn',
        'sales_count',
        'photos_sold',
    ];

    protected function casts(): array
    {
        return [
            'month' => 'date',
            'total_sales' => 'integer',
            'commission' => 'integer',
            'net_revenue' => 'integer',
            'available_balance' => 'integer',
            'pending_balance' => 'integer',
            'withdrawn' => 'integer',
            'sales_count' => 'integer',
            'photos_sold' => 'integer',
        ];
    }

    // Relationships

    public function photographer()
    {
        return $this->belongsTo(User::class, 'photographer_id');
    }
}
