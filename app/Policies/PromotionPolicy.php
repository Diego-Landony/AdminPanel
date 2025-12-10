<?php

namespace App\Policies;

use App\Models\Menu\Promotion;
use App\Models\User;

class PromotionPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('menu.promotions.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Promotion $promotion): bool
    {
        return $user->hasPermission('menu.promotions.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('menu.promotions.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Promotion $promotion): bool
    {
        return $user->hasPermission('menu.promotions.update');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Promotion $promotion): bool
    {
        return $user->hasPermission('menu.promotions.delete');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Promotion $promotion): bool
    {
        return $user->hasPermission('menu.promotions.restore');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Promotion $promotion): bool
    {
        return $user->hasPermission('menu.promotions.force-delete');
    }
}
