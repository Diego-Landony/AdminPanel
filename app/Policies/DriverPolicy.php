<?php

namespace App\Policies;

use App\Models\Driver;
use App\Models\RestaurantUser;
use App\Models\User;

class DriverPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User|RestaurantUser $user): bool
    {
        // Admin users can view all drivers
        if ($user instanceof User) {
            return $user->hasPermission('drivers.view');
        }

        // Restaurant users can view drivers from their restaurant
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User|RestaurantUser $user, Driver $driver): bool
    {
        // Admin users can view any driver
        if ($user instanceof User) {
            return $user->hasPermission('drivers.view');
        }

        // Restaurant users can only view drivers from their restaurant
        return $user->restaurant_id === $driver->restaurant_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User|RestaurantUser $user): bool
    {
        // Admin users can create drivers
        if ($user instanceof User) {
            return $user->hasPermission('drivers.create');
        }

        // Restaurant users can create drivers for their restaurant
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User|RestaurantUser $user, Driver $driver): bool
    {
        // Admin users can update any driver
        if ($user instanceof User) {
            return $user->hasPermission('drivers.update');
        }

        // Restaurant users can only update drivers from their restaurant
        return $user->restaurant_id === $driver->restaurant_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User|RestaurantUser $user, Driver $driver): bool
    {
        // Admin users can delete any driver
        if ($user instanceof User) {
            return $user->hasPermission('drivers.delete');
        }

        // Restaurant users can only delete drivers from their restaurant
        return $user->restaurant_id === $driver->restaurant_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User|RestaurantUser $user, Driver $driver): bool
    {
        // Admin users can restore any driver
        if ($user instanceof User) {
            return $user->hasPermission('drivers.restore');
        }

        // Restaurant users can only restore drivers from their restaurant
        return $user->restaurant_id === $driver->restaurant_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User|RestaurantUser $user, Driver $driver): bool
    {
        // Admin users can force delete any driver
        if ($user instanceof User) {
            return $user->hasPermission('drivers.force-delete');
        }

        // Restaurant users cannot force delete
        return false;
    }
}
