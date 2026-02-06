<?php

namespace App\Services;

use App\Models\Driver;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;

class DriverService
{
    /**
     * Crear un nuevo motorista.
     *
     * @param  array{
     *     restaurant_id: int,
     *     name: string,
     *     email: string,
     *     password: string
     * }  $data
     */
    public function create(array $data): Driver
    {
        $data['password'] = Hash::make($data['password']);

        return Driver::create($data);
    }

    /**
     * Actualizar un motorista existente.
     *
     * @param  array{
     *     restaurant_id?: int,
     *     name?: string,
     *     email?: string,
     *     password?: string|null,
     *     is_active?: bool
     * }  $data
     */
    public function update(Driver $driver, array $data): Driver
    {
        if (isset($data['password']) && $data['password'] !== null) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $driver->update($data);

        return $driver->fresh();
    }

    /**
     * Eliminar un motorista.
     */
    public function delete(Driver $driver): bool
    {
        return $driver->delete();
    }

    /**
     * Cambiar la disponibilidad de un motorista.
     */
    public function toggleAvailability(Driver $driver): Driver
    {
        $driver->update([
            'is_available' => ! $driver->is_available,
            'last_activity_at' => now(),
        ]);

        return $driver->fresh();
    }

    /**
     * Obtener todos los motoristas disponibles para un restaurante.
     */
    public function getAvailableDriversForRestaurant(int $restaurantId): Collection
    {
        return Driver::query()
            ->forRestaurant($restaurantId)
            ->available()
            ->orderBy('name')
            ->get();
    }

    /**
     * Obtener todos los motoristas de un restaurante.
     */
    public function getDriversForRestaurant(int $restaurantId): Collection
    {
        return Driver::query()
            ->forRestaurant($restaurantId)
            ->orderBy('name')
            ->get();
    }

    /**
     * Activar un motorista.
     */
    public function activate(Driver $driver): Driver
    {
        $driver->update(['is_active' => true]);

        return $driver->fresh();
    }

    /**
     * Desactivar un motorista.
     */
    public function deactivate(Driver $driver): Driver
    {
        $driver->update([
            'is_active' => false,
            'is_available' => false,
        ]);

        return $driver->fresh();
    }

    /**
     * Verifica si el driver puede desconectarse (no tiene orden activa).
     */
    public function canGoOffline(Driver $driver): bool
    {
        return $driver->activeOrder()->doesntExist();
    }
}
