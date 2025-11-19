<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Photo extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'photographer_id',
        'category_id',
        'title',
        'description',
        'tags',
        'original_url',
        'preview_url',
        'thumbnail_url',
        'width',
        'height',
        'file_size',
        'format',
        'color_palette',
        'camera',
        'lens',
        'iso',
        'aperture',
        'shutter_speed',
        'focal_length',
        'taken_at',
        'location',
        'price_standard',
        'price_extended',
        'views_count',
        'downloads_count',
        'favorites_count',
        'sales_count',
        'is_public',
        'status',
        'rejection_reason',
        'moderated_at',
        'moderated_by',
        'featured',
        'featured_until',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'color_palette' => 'array',
            'taken_at' => 'datetime',
            'moderated_at' => 'datetime',
            'featured_until' => 'datetime',
            'width' => 'integer',
            'height' => 'integer',
            'file_size' => 'integer',
            'price_standard' => 'integer',
            'price_extended' => 'integer',
            'views_count' => 'integer',
            'downloads_count' => 'integer',
            'favorites_count' => 'integer',
            'sales_count' => 'integer',
            'is_public' => 'boolean',
            'featured' => 'boolean',
        ];
    }

    // Relationships

    public function photographer()
    {
        return $this->belongsTo(User::class, 'photographer_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function moderatedBy()
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    public function favoritedBy()
    {
        return $this->belongsToMany(User::class, 'favorites')->withTimestamps();
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    // Scopes

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeFeatured($query)
    {
        return $query->where('featured', true)
            ->where(function($q) {
                $q->whereNull('featured_until')
                  ->orWhere('featured_until', '>', now());
            });
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    // Methods

    public function approve($moderator)
    {
        $this->update([
            'status' => 'approved',
            'is_public' => true,
            'moderated_at' => now(),
            'moderated_by' => $moderator->id,
            'rejection_reason' => null,
        ]);
    }

    public function reject($moderator, string $reason)
    {
        $this->update([
            'status' => 'rejected',
            'is_public' => false,
            'moderated_at' => now(),
            'moderated_by' => $moderator->id,
            'rejection_reason' => $reason,
        ]);
    }

    public function incrementViews()
    {
        $this->increment('views_count');
    }

    public function incrementSales()
    {
        $this->increment('sales_count');
    }

    public function incrementDownloads()
    {
        $this->increment('downloads_count');
    }

    public function incrementFavorites()
    {
        $this->increment('favorites_count');
    }

    public function decrementFavorites()
    {
        $this->decrement('favorites_count');
    }
}
