<?php

namespace App\Services;

use App\Events\MenuVersionUpdated;
use Illuminate\Support\Facades\Cache;

/**
 * Servicio de Versionado del Menú
 *
 * Maneja la generación e invalidación de versiones del menú para
 * permitir cache eficiente en aplicaciones móviles (Flutter).
 *
 * Flujo:
 * 1. Flutter guarda menú + versión en cache local
 * 2. Al abrir app, Flutter llama GET /menu/version
 * 3. Si versión cambió, Flutter descarga menú completo
 * 4. Cualquier cambio en el menú invalida la versión automáticamente
 * 5. WebSocket notifica a Flutter en tiempo real (canal: menu, evento: menu.version.updated)
 */
class MenuVersionService
{
    private const CACHE_KEY = 'menu_version';

    private const CACHE_TTL = 60 * 60 * 24 * 30; // 30 días

    /**
     * Obtiene la versión actual del menú.
     * Si no existe, genera una nueva.
     */
    public function getVersion(): string
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, fn () => $this->generateVersion());
    }

    /**
     * Obtiene información completa de la versión.
     *
     * @return array{version: string, generated_at: string}
     */
    public function getVersionInfo(): array
    {
        $version = $this->getVersion();

        // Extraer timestamp del formato: m_1706123456_a3f2
        $parts = explode('_', $version);
        $timestamp = isset($parts[1]) && is_numeric($parts[1])
            ? (int) $parts[1]
            : time();

        return [
            'version' => $version,
            'generated_at' => date('c', $timestamp),
        ];
    }

    /**
     * Invalida la versión actual y genera una nueva.
     * Llamar cuando cualquier elemento del menú cambie.
     *
     * @param  string  $reason  Razón del cambio (para debugging/analytics)
     */
    public function invalidate(string $reason = 'menu_changed'): string
    {
        $newVersion = $this->generateVersion();
        Cache::put(self::CACHE_KEY, $newVersion, self::CACHE_TTL);

        // Notificar a clientes Flutter vía WebSocket
        broadcast(new MenuVersionUpdated($newVersion, $reason))->toOthers();

        return $newVersion;
    }

    /**
     * Genera una nueva versión única.
     * Formato: m_[timestamp]_[random4]
     * Ejemplo: m_1706123456_a3f2
     */
    private function generateVersion(): string
    {
        return 'm_'.time().'_'.substr(md5(uniqid((string) mt_rand(), true)), 0, 4);
    }

    /**
     * Verifica si una versión del cliente está actualizada.
     */
    public function isUpToDate(string $clientVersion): bool
    {
        return $this->getVersion() === $clientVersion;
    }
}
