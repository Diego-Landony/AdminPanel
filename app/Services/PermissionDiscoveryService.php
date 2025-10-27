<?php

namespace App\Services;

/**
 * Alias para PermissionService para mantener compatibilidad con comandos existentes
 *
 * Este servicio es simplemente una extensión de PermissionService
 * para mantener la compatibilidad con código que referencia PermissionDiscoveryService
 */
class PermissionDiscoveryService extends PermissionService
{
    /**
     * Obtiene el número de páginas descubiertas
     */
    public function getDiscoveredPagesCount(): int
    {
        return count(config('permissions.pages', []));
    }

    /**
     * Sincroniza los permisos con la base de datos
     * Sobrescribe el método padre para agregar discovered_pages al resultado
     *
     * @param  bool  $removeObsolete  Si debe eliminar permisos obsoletos
     * @return array Resultado de la sincronización
     */
    public function syncPermissions(bool $removeObsolete = false): array
    {
        $result = parent::syncPermissions($removeObsolete);

        // Agregar discovered_pages para compatibilidad con comandos
        $result['discovered_pages'] = $result['total_pages'];

        return $result;
    }
}
