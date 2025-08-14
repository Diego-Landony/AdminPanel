<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Modelo para los permisos del sistema
 */
class Permission extends Model
{
    protected $fillable = [
        'name',
        'display_name',
        'description',
        'group',
    ];

    /**
     * Relación con roles que tienen este permiso
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * Obtiene permisos agrupados por categoría
     */
    public static function getGrouped(): array
    {
        return static::orderBy('group')
            ->orderBy('display_name')
            ->get()
            ->groupBy('group')
            ->map(function ($permissions) {
                return $permissions->map(function ($permission) {
                    return [
                        'id' => $permission->id,
                        'name' => $permission->name,
                        'display_name' => $permission->display_name,
                        'description' => $permission->description,
                        'group' => $permission->group,
                    ];
                });
            })
            ->toArray();
    }

    /**
     * Obtiene el nombre legible del grupo usando el servicio de descubrimiento
     */
    public function getGroupDisplayName(): string
    {
        $discoveryService = app(\App\Services\PermissionDiscoveryService::class);
        return $discoveryService->getGroupDisplayName($this->group);
    }
}
