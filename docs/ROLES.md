# Sistema de Roles y Permisos

## Arquitectura

### Estructura
- **Configuración:** `config/permissions.php` - Define todas las páginas y acciones
- **Sincronización:** `app/Services/PermissionService.php` - Sincroniza config con BD
- **Cache:** Persistente (1 hora TTL) - Invalidación automática
- **Middleware:** `CheckUserPermissions` - Valida acceso en cada request

### Relaciones BD
```
users ←→ role_user ←→ roles ←→ permission_role ←→ permissions
```

## Formato de Permisos

### Convención
```
{página}.{acción}
```

### Ejemplos
- `users.view` - Ver usuarios
- `menu.categories.create` - Crear categorías del menú
- `roles.edit` - Editar roles

### Rol Admin
- Siempre retorna `['*']` (wildcard)
- Bypass automático en todas las verificaciones
- No usa cache

## Agregar Nueva Página

### 1. config/permissions.php
```php
'nueva-pagina' => [
    'display_name' => 'Nueva Página',
    'description' => 'Descripción',
    'group' => 'nueva-pagina',
    'actions' => ['view', 'create', 'edit', 'delete'],
],
```

Para subpáginas de menú:
```php
'menu.nueva-seccion' => [
    'display_name' => 'Nueva Sección',
    'group' => 'menu.nueva-seccion',
    'actions' => ['view', 'create', 'edit', 'delete'],
],
```

### 2. app-sidebar.tsx
```typescript
{
    name: 'nueva-pagina',
    title: 'Nueva Página',
    href: '/nueva-pagina',
    icon: Icon,
    group: 'Grupo', // opcional
    permission: 'nueva-pagina.view',
}
```

### 3. Sincronizar
```bash
php artisan permissions:sync
```

## Uso en Backend

### Verificar Permiso
```php
$user->hasPermission('users.view')
$user->hasPermission('menu.categories.edit')
```

### Verificar Rol
```php
$user->hasRole('admin')
$user->isAdmin()
```

### Invalidar Cache
```php
// Usuario individual
$user->flushPermissionsCache();

// Múltiples usuarios
$role->users()->each(fn($u) => $u->flushPermissionsCache());
```

## Uso en Frontend

### Hook usePermissions
```typescript
const { hasPermission } = usePermissions();

if (hasPermission('users.create')) {
    // Mostrar botón crear
}
```

### Validación Automática
- Sidebar filtra páginas según permisos
- Middleware bloquea rutas sin permiso
- Admin ve todo automáticamente

## Cache

### Funcionamiento
- TTL: 1 hora
- Key: `user.{id}.permissions`
- Admin: No usa cache (siempre `['*']`)

### Invalidación Automática
- Al cambiar roles de usuario
- Al modificar permisos de rol
- Al asignar/desasignar usuarios a roles

### Manual
```php
Cache::forget("user.{$userId}.permissions");
// o
$user->flushPermissionsCache();
```

## Testing

### Crear Usuario con Permisos
```php
$perm = Permission::firstOrCreate(
    ['name' => 'users.view'],
    ['display_name' => 'Ver', 'group' => 'users']
);

$role = Role::create(['name' => 'viewer', 'description' => 'Viewer']);
$role->permissions()->attach($perm);

$user = User::factory()->create();
$user->roles()->attach($role);

expect($user->hasPermission('users.view'))->toBeTrue();
```

### Admin
```php
$adminRole = Role::firstOrCreate(
    ['name' => 'admin'],
    ['description' => 'Admin', 'is_system' => true]
);

$admin = User::factory()->create();
$admin->roles()->attach($adminRole);

expect($admin->hasPermission('any.permission'))->toBeTrue();
```

## Middleware

### Rutas Protegidas
```php
Route::middleware(['auth', 'check.permissions'])->group(function () {
    Route::get('/users', [UserController::class, 'index'])
        ->name('users.index');
});
```

### Redirecciones
- Usuario sin roles → `/no-access`
- Usuario con roles pero sin permiso → `/no-access`
- Admin → Acceso completo

### AJAX
- Respuesta JSON con status 403
- Mensaje: "No tienes permisos..."

## Comandos Artisan

### Sincronizar Permisos
```bash
php artisan permissions:sync
```

Acciones:
- Crea permisos nuevos desde config
- Actualiza display_name de existentes
- Elimina permisos no definidos en config
- Retorna estadísticas (created, updated, deleted)

### Tests
```bash
php artisan test --filter=Permission
php artisan test tests/Feature/PermissionCacheTest.php
php artisan test tests/Feature/MiddlewarePermissionTest.php
```

## Estructura de Archivos

```
config/
  permissions.php                    # Configuración centralizada

app/
  Models/
    User.php                          # Métodos: hasPermission, getAllPermissions
    Role.php                          # Relación con permissions
    Permission.php                    # Modelo base
  Services/
    PermissionService.php             # Sincronización config → BD
  Http/
    Middleware/
      CheckUserPermissions.php        # Validación de acceso
      HandleInertiaRequests.php       # Eager loading de permisos

resources/js/
  components/
    app-sidebar.tsx                   # Definición de páginas del sistema
    PermissionsTable.tsx              # UI para asignar permisos
  hooks/
    use-permissions.ts                # Hook para verificar permisos

tests/Feature/
  PermissionSyncTest.php              # Tests de sincronización
  PermissionCacheTest.php             # Tests de cache
  MiddlewarePermissionTest.php        # Tests de middleware
```

## Buenas Prácticas

1. **Siempre sincronizar** después de modificar `config/permissions.php`
2. **Usar convención de nombres:** `{página}.{acción}`
3. **No hardcodear permisos** - usar config centralizado
4. **Invalidar cache** al modificar roles/permisos
5. **Tests:** Verificar acceso permitido y bloqueado
6. **Frontend:** Usar `hasPermission()` para mostrar/ocultar UI
7. **Backend:** Middleware protege todas las rutas automáticamente
