<?php

namespace App\Policies;

use App\Models\PhotographerProfile;
use App\Models\User;

class PhotographerProfilePolicy
{
    /**
     * Determine whether the user can view any photographer profiles.
     */
    public function viewAny(User $user): bool
    {
        // Admins and moderators can view all photographer profiles
        return $user->can('view-photographers');
    }

    /**
     * Determine whether the user can view the photographer profile.
     */
    public function view(User $user, PhotographerProfile $photographerProfile): bool
    {
        // Admins can view any photographer profile
        if ($user->can('view-photographers')) {
            return true;
        }

        // Photographers can view their own profile
        return $photographerProfile->user_id === $user->id;
    }

    /**
     * Determine whether the user can create photographer profiles.
     */
    public function create(User $user): bool
    {
        // Only admins can manually create photographer profiles
        // (Normal creation happens during registration)
        return $user->can('edit-users');
    }

    /**
     * Determine whether the user can update the photographer profile.
     */
    public function update(User $user, PhotographerProfile $photographerProfile): bool
    {
        // Admins can update any photographer profile
        if ($user->can('edit-users')) {
            return true;
        }

        // Photographers can update their own profile
        return $photographerProfile->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the photographer profile.
     */
    public function delete(User $user, PhotographerProfile $photographerProfile): bool
    {
        // Only admins can delete photographer profiles
        return $user->can('delete-users');
    }

    /**
     * Determine whether the user can approve the photographer profile.
     */
    public function approve(User $user, PhotographerProfile $photographerProfile): bool
    {
        // Only admins and moderators can approve photographer profiles
        return $user->can('approve-photographers') &&
               $photographerProfile->status === 'pending';
    }

    /**
     * Determine whether the user can reject the photographer profile.
     */
    public function reject(User $user, PhotographerProfile $photographerProfile): bool
    {
        // Only admins and moderators can reject photographer profiles
        return $user->can('reject-photographers') &&
               $photographerProfile->status === 'pending';
    }

    /**
     * Determine whether the user can suspend the photographer profile.
     */
    public function suspend(User $user, PhotographerProfile $photographerProfile): bool
    {
        // Only admins can suspend photographer profiles
        return $user->can('suspend-photographers') &&
               $photographerProfile->status === 'approved';
    }

    /**
     * Determine whether the user can activate the photographer profile.
     */
    public function activate(User $user, PhotographerProfile $photographerProfile): bool
    {
        // Only admins can activate suspended photographer profiles
        return $user->can('activate-photographers') &&
               $photographerProfile->status === 'suspended';
    }
}
