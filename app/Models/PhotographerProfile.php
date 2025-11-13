<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhotographerProfile extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'username',
        'display_name',
        'cover_photo_url',
        'location',
        'website',
        'instagram',
        'portfolio_url',
        'specialties',
        'status',
        'commission_rate',
        'total_sales',
        'total_revenue',
        'followers_count',
        'rejection_reason',
        'approved_at',
        'approved_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'specialties' => 'array',
            'commission_rate' => 'decimal:2',
            'total_sales' => 'integer',
            'total_revenue' => 'integer',
            'followers_count' => 'integer',
            'approved_at' => 'datetime',
        ];
    }

    // Relationships

    /**
     * Get the user that owns the photographer profile.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the admin who approved the profile.
     */
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Scopes

    /**
     * Scope a query to only include pending profiles.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include approved profiles.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope a query to only include rejected profiles.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope a query to only include suspended profiles.
     */
    public function scopeSuspended($query)
    {
        return $query->where('status', 'suspended');
    }

    // Helper Methods

    /**
     * Approve the photographer profile.
     */
    public function approve(User $admin): void
    {
        $this->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => $admin->id,
            'rejection_reason' => null,
        ]);
    }

    /**
     * Reject the photographer profile.
     */
    public function reject(User $admin, string $reason): void
    {
        $this->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
            'approved_at' => null,
            'approved_by' => null,
        ]);
    }

    /**
     * Suspend the photographer profile.
     */
    public function suspend(User $admin, string $reason): void
    {
        $this->update([
            'status' => 'suspended',
            'rejection_reason' => $reason,
        ]);
    }

    /**
     * Check if the profile is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if the profile is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
