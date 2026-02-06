<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro de dispositivos Apple Wallet para Push Notifications.
 *
 * Cuando un usuario agrega un pase a Apple Wallet, el dispositivo
 * se registra aquí para recibir notificaciones de actualización.
 */
class AppleWalletRegistration extends Model
{
    protected $fillable = [
        'customer_id',
        'device_library_identifier',
        'push_token',
        'pass_type_identifier',
        'serial_number',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Obtiene todos los registros para un serial number específico.
     * Útil para enviar push a todos los dispositivos de un cliente.
     */
    public static function getBySerialNumber(string $serialNumber): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('serial_number', $serialNumber)->get();
    }

    /**
     * Obtiene todos los registros para un cliente específico.
     */
    public static function getByCustomerId(int $customerId): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('customer_id', $customerId)->get();
    }

    /**
     * Extrae el customer ID del serial number (formato: subway-{id}).
     */
    public static function extractCustomerIdFromSerial(string $serialNumber): ?int
    {
        if (preg_match('/^subway-(\d+)$/', $serialNumber, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }
}
