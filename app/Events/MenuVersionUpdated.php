<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento que se dispara cuando cambia la versión del menú.
 *
 * Este evento notifica a los clientes Flutter vía WebSocket que el menú
 * ha sido actualizado, permitiéndoles invalidar su caché local y
 * descargar la nueva versión cuando sea necesario.
 *
 * Canal: público (el menú es información pública)
 * Evento: menu.version.updated
 */
class MenuVersionUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $version,
        public string $reason = 'menu_changed'
    ) {}

    /**
     * Canal público para notificaciones de menú.
     * No requiere autenticación ya que el menú es público.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('menu'),
        ];
    }

    /**
     * Nombre del evento para Flutter.
     */
    public function broadcastAs(): string
    {
        return 'menu.version.updated';
    }

    /**
     * Datos enviados al cliente.
     */
    public function broadcastWith(): array
    {
        return [
            'version' => $this->version,
            'reason' => $this->reason,
            'updated_at' => now()->toIso8601String(),
        ];
    }
}
