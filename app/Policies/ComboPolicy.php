<?php

namespace App\Policies;

use App\Models\Menu\Combo;
use App\Models\User;

class ComboPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('menu.combos.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Combo $combo): bool
    {
        return $user->hasPermission('menu.combos.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('menu.combos.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Combo $combo): bool
    {
        return $user->hasPermission('menu.combos.update');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Combo $combo): bool
    {
        return $user->hasPermission('menu.combos.delete');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Combo $combo): bool
    {
        return $user->hasPermission('menu.combos.restore');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Combo $combo): bool
    {
        return $user->hasPermission('menu.combos.force-delete');
    }
}
