<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, HasUuids, SoftDeletes, HasRoles;

    /**
     * Guard name for Spatie Permission
     */
    protected $guard_name = 'api';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'email',
        'password',
        'first_name',
        'last_name',
        'avatar_url',
        'phone',
        'bio',
        'account_type',
        'is_verified',
        'is_active',
        'email_verified_at',
        'last_login',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login' => 'datetime',
            'is_verified' => 'boolean',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    // JWT Methods

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     */
    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     */
    public function getJWTCustomClaims(): array
    {
        return [
            'account_type' => $this->account_type,
            'is_verified' => $this->is_verified,
        ];
    }

    // Relationships

    /**
     * Get the photographer profile for the user.
     */
    public function photographerProfile()
    {
        return $this->hasOne(PhotographerProfile::class);
    }

    /**
     * Get the photos uploaded by the photographer.
     */
    public function photos()
    {
        return $this->hasMany(Photo::class, 'photographer_id');
    }

    /**
     * Get the orders made by the user.
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get the withdrawals requested by the photographer.
     */
    public function withdrawals()
    {
        return $this->hasMany(Withdrawal::class, 'photographer_id');
    }

    /**
     * Get the notifications for the user.
     */
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Get the photos favorited by the user.
     */
    public function favorites()
    {
        return $this->belongsToMany(Photo::class, 'favorites')->withTimestamps();
    }

    /**
     * Get the user's shopping cart.
     */
    public function cart()
    {
        return $this->hasOne(Cart::class);
    }

    /**
     * Get the photographers that the user is following.
     */
    public function following()
    {
        return $this->belongsToMany(User::class, 'follows', 'follower_id', 'following_id')->withTimestamps();
    }

    /**
     * Get the users following this user (photographer).
     */
    public function followers()
    {
        return $this->belongsToMany(User::class, 'follows', 'following_id', 'follower_id')->withTimestamps();
    }

    // Scopes

    /**
     * Scope a query to only include active users.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include verified users.
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope a query to only include photographers.
     */
    public function scopePhotographers($query)
    {
        return $query->where('account_type', 'photographer');
    }

    /**
     * Scope a query to only include buyers.
     */
    public function scopeBuyers($query)
    {
        return $query->where('account_type', 'buyer');
    }

    /**
     * Scope a query to only include admins.
     */
    public function scopeAdmins($query)
    {
        return $query->where('account_type', 'admin');
    }

    // Helper Methods

    /**
     * Check if user is a photographer.
     */
    public function isPhotographer(): bool
    {
        return $this->account_type === 'photographer';
    }

    /**
     * Check if user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->account_type === 'admin';
    }

    /**
     * Check if user is a buyer.
     */
    public function isBuyer(): bool
    {
        return $this->account_type === 'buyer';
    }

    /**
     * Get the user's full name.
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Check if photographer is approved and can perform photographer actions.
     */
    public function isApprovedPhotographer(): bool
    {
        return $this->isPhotographer() &&
               $this->photographerProfile &&
               $this->photographerProfile->status === 'approved';
    }

    /**
     * Get photographer approval status if user is a photographer.
     */
    public function getPhotographerStatus(): ?string
    {
        if (!$this->isPhotographer()) {
            return null;
        }

        return $this->photographerProfile?->status;
    }
}
