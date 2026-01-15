<?php

namespace App\Policies;

use App\Models\RestaurantUser;
use App\Models\User;

class RestaurantUserPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User|RestaurantUser $user): bool
    {
        // Admin users can view all restaurant users
        if ($user instanceof User) {
            return $user->hasPermission('restaurant-users.view');
        }

        // Restaurant users can view users from their restaurant
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User|RestaurantUser $user, RestaurantUser $restaurantUser): bool
    {
        // Admin users can view any restaurant user
        if ($user instanceof User) {
            return $user->hasPermission('restaurant-users.view');
        }

        // Restaurant users can only view users from their restaurant
        return $user->restaurant_id === $restaurantUser->restaurant_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User|RestaurantUser $user): bool
    {
        // Admin users can create restaurant users
        if ($user instanceof User) {
            return $user->hasPermission('restaurant-users.create');
        }

        // Restaurant users can create users for their restaurant (if they have admin role)
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User|RestaurantUser $user, RestaurantUser $restaurantUser): bool
    {
        // Admin users can update any restaurant user
        if ($user instanceof User) {
            return $user->hasPermission('restaurant-users.update');
        }

        // Restaurant users can only update users from their restaurant (if they have admin role)
        if ($user->restaurant_id !== $restaurantUser->restaurant_id) {
            return false;
        }

        return $user->role === 'admin' || $user->id === $restaurantUser->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User|RestaurantUser $user, RestaurantUser $restaurantUser): bool
    {
        // Admin users can delete any restaurant user
        if ($user instanceof User) {
            return $user->hasPermission('restaurant-users.delete');
        }

        // Restaurant users can only delete users from their restaurant (if they have admin role)
        if ($user->restaurant_id !== $restaurantUser->restaurant_id) {
            return false;
        }

        // Cannot delete yourself
        if ($user->id === $restaurantUser->id) {
            return false;
        }

        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User|RestaurantUser $user, RestaurantUser $restaurantUser): bool
    {
        // Admin users can restore any restaurant user
        if ($user instanceof User) {
            return $user->hasPermission('restaurant-users.restore');
        }

        // Restaurant users can only restore users from their restaurant (if they have admin role)
        if ($user->restaurant_id !== $restaurantUser->restaurant_id) {
            return false;
        }

        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User|RestaurantUser $user, RestaurantUser $restaurantUser): bool
    {
        // Admin users can force delete any restaurant user
        if ($user instanceof User) {
            return $user->hasPermission('restaurant-users.force-delete');
        }

        // Restaurant users cannot force delete
        return false;
    }
}
