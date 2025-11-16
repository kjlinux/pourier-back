<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Withdrawal;

class WithdrawalPolicy
{
    /**
     * Determine whether the user can view any withdrawals.
     */
    public function viewAny(User $user): bool
    {
        // Admins can view all withdrawals
        if ($user->can('approve-withdrawals')) {
            return true;
        }

        // Photographers can view their own withdrawals
        return $user->can('request-withdrawals');
    }

    /**
     * Determine whether the user can view the withdrawal.
     */
    public function view(User $user, Withdrawal $withdrawal): bool
    {
        // Admins can view any withdrawal
        if ($user->can('approve-withdrawals')) {
            return true;
        }

        // Photographers can only view their own withdrawals
        return $withdrawal->photographer_id === $user->id;
    }

    /**
     * Determine whether the user can create withdrawals.
     */
    public function create(User $user): bool
    {
        // Only approved photographers can request withdrawals
        return $user->can('request-withdrawals') && $user->isApprovedPhotographer();
    }

    /**
     * Determine whether the user can update the withdrawal.
     */
    public function update(User $user, Withdrawal $withdrawal): bool
    {
        // Only the photographer who created the withdrawal can update it (if pending)
        return $withdrawal->photographer_id === $user->id &&
               $withdrawal->status === 'pending';
    }

    /**
     * Determine whether the user can delete the withdrawal.
     */
    public function delete(User $user, Withdrawal $withdrawal): bool
    {
        // Photographers can only cancel their own pending withdrawals
        return $withdrawal->photographer_id === $user->id &&
               $withdrawal->status === 'pending';
    }

    /**
     * Determine whether the user can approve the withdrawal.
     */
    public function approve(User $user, Withdrawal $withdrawal): bool
    {
        // Only admins can approve withdrawals
        return $user->can('approve-withdrawals') &&
               $withdrawal->status === 'pending';
    }

    /**
     * Determine whether the user can reject the withdrawal.
     */
    public function reject(User $user, Withdrawal $withdrawal): bool
    {
        // Only admins can reject withdrawals
        return $user->can('reject-withdrawals') &&
               $withdrawal->status === 'pending';
    }

    /**
     * Determine whether the user can mark withdrawal as completed.
     */
    public function complete(User $user, Withdrawal $withdrawal): bool
    {
        // Only admins can mark withdrawals as completed
        return $user->can('complete-withdrawals') &&
               $withdrawal->status === 'approved';
    }
}
