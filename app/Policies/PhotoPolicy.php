<?php

namespace App\Policies;

use App\Models\Photo;
use App\Models\User;

class PhotoPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Photo $photo): bool
    {
        // Les photos publiques et approuvées sont visibles par tous
        if ($photo->is_public && $photo->status === 'approved') {
            return true;
        }

        // Le photographe peut voir ses propres photos
        if ($user && $user->id === $photo->photographer_id) {
            return true;
        }

        // Les admins peuvent voir toutes les photos
        if ($user && $user->account_type === 'admin') {
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->account_type === 'photographer';
    }

    public function update(User $user, Photo $photo): bool
    {
        return $user->id === $photo->photographer_id;
    }

    public function delete(User $user, Photo $photo): bool
    {
        return $user->id === $photo->photographer_id || $user->account_type === 'admin';
    }

    public function approve(User $user, Photo $photo): bool
    {
        return $user->account_type === 'admin';
    }

    public function reject(User $user, Photo $photo): bool
    {
        return $user->account_type === 'admin';
    }
}
