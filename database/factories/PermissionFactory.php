<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Permission>
 */
class PermissionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $actions = ['view', 'create', 'edit', 'delete'];
        $modules = ['users', 'customers', 'roles', 'activity', 'settings'];
        
        $module = fake()->randomElement($modules);
        $action = fake()->randomElement($actions);
        
        return [
            'name' => "{$module}.{$action}",
            'display_name' => ucfirst($action) . ' ' . ucfirst($module),
            'description' => "Permission to {$action} {$module}",
            'group' => $module,
        ];
    }

    /**
     * Create a dashboard permission.
     */
    public function dashboard(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'dashboard.view',
            'display_name' => 'Dashboard',
            'description' => 'Ver dashboard del sistema',
            'group' => 'dashboard',
        ]);
    }

    /**
     * Create a users permission.
     */
    public function users(string $action = 'view'): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => "users.{$action}",
            'display_name' => ucfirst($action) . ' Users',
            'description' => "Permission to {$action} users",
            'group' => 'users',
        ]);
    }

    /**
     * Create a customers permission.
     */
    public function customers(string $action = 'view'): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => "customers.{$action}",
            'display_name' => ucfirst($action) . ' Customers',
            'description' => "Permission to {$action} customers",
            'group' => 'customers',
        ]);
    }

    /**
     * Create a roles permission.
     */
    public function roles(string $action = 'view'): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => "roles.{$action}",
            'display_name' => ucfirst($action) . ' Roles',
            'description' => "Permission to {$action} roles",
            'group' => 'roles',
        ]);
    }
}