<?php

namespace App\Services\Driver;

use App\Exceptions\InvalidPasswordException;
use App\Models\Driver;
use Illuminate\Support\Facades\Hash;

class DriverProfileService
{
    /**
     * Actualiza el perfil del driver (solo campos permitidos).
     *
     * Nota: Actualmente no hay campos editables por el driver.
     * El nombre y email son manejados por el administrador.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateProfile(Driver $driver, array $data): Driver
    {
        // No hay campos editables por el driver actualmente
        // El nombre y email son gestionados por el administrador
        return $driver->fresh();
    }

    /**
     * Cambia la contraseña del driver.
     *
     * @throws InvalidPasswordException Si la contraseña actual es incorrecta
     */
    public function changePassword(
        Driver $driver,
        string $currentPassword,
        string $newPassword
    ): void {
        if (! Hash::check($currentPassword, $driver->password)) {
            throw new InvalidPasswordException;
        }

        $driver->update([
            'password' => $newPassword,
        ]);
    }
}
