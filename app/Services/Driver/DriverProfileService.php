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
     * Campos editables: name
     * El email es manejado por el administrador.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateProfile(Driver $driver, array $data): Driver
    {
        $allowedFields = ['name'];
        $updateData = array_intersect_key($data, array_flip($allowedFields));

        if (! empty($updateData)) {
            $driver->update($updateData);
        }

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
            'password' => Hash::make($newPassword),
        ]);
    }
}
