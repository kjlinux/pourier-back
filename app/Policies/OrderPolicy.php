<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    /**
     * Determine whether the user can view any orders.
     */
    public function viewAny(User $user): bool
    {
        // Admins can view all orders
        if ($user->can('view-all-orders')) {
            return true;
        }

        // Users can view their own orders
        return $user->can('view-own-orders');
    }

    /**
     * Determine whether the user can view the order.
     */
    public function view(User $user, Order $order): bool
    {
        // Admins can view any order
        if ($user->can('view-all-orders')) {
            return true;
        }

        // Users can view their own orders
        return $order->user_id === $user->id;
    }

    /**
     * Determine whether the user can create orders.
     */
    public function create(User $user): bool
    {
        // Any authenticated user can create orders
        return true;
    }

    /**
     * Determine whether the user can update the order.
     */
    public function update(User $user, Order $order): bool
    {
        // Only admins can update orders
        return $user->can('manage-orders');
    }

    /**
     * Determine whether the user can delete the order.
     */
    public function delete(User $user, Order $order): bool
    {
        // Only admins can delete orders
        return $user->can('manage-orders');
    }

    /**
     * Determine whether the user can download the order content.
     */
    public function download(User $user, Order $order): bool
    {
        // Admins can download any order
        if ($user->can('view-all-orders')) {
            return true;
        }

        // Users can only download their own paid orders
        return $order->user_id === $user->id && $order->payment_status === 'paid';
    }
}
