# 🔐 Documentación: Roles Page

## 📋 Índice
- [Descripción General](#descripción-general)
- [Arquitectura del Sistema](#arquitectura-del-sistema)
- [Páginas y Funcionalidades](#páginas-y-funcionalidades)
- [Sistema de Permisos Automático](#sistema-de-permisos-automático)
- [Componentes UI](#componentes-ui)
- [Flujo de Trabajo](#flujo-de-trabajo)
- [API y Controladores](#api-y-controladores)
- [Base de Datos](#base-de-datos)
- [Seguridad](#seguridad)
- [Mantenimiento](#mantenimiento)

---

## 🎯 Descripción General

La **Roles Page** es un sistema completo de gestión de roles y permisos que permite:
- ✅ Crear, editar y eliminar roles
- ✅ Asignar permisos granulares por página y acción
- ✅ Gestionar usuarios asignados a cada rol
- ✅ Detección automática de nuevas páginas del sistema
- ✅ Sincronización automática de permisos
- ✅ Interfaz moderna y responsive

---

## 🏗️ Arquitectura del Sistema

### **Frontend (React + TypeScript + Inertia.js)**
```
resources/js/pages/roles/
├── index.tsx           # Lista de roles con filtros y estadísticas
├── create.tsx          # Formulario de creación de roles
└── edit.tsx            # Formulario de edición con gestión de usuarios
```

### **Backend (Laravel + PHP 8.2+)**
```
app/
├── Http/Controllers/
│   └── RoleController.php              # Controlador principal
├── Models/
│   ├── Role.php                        # Modelo de roles
│   ├── Permission.php                  # Modelo de permisos
│   └── User.php                        # Modelo de usuarios
├── Services/
│   └── PermissionDiscoveryService.php  # Detección automática
├── Observers/
│   └── RoleObserver.php                # Observer para logs
└── Console/Commands/
    └── SyncPermissions.php             # Comando de sincronización
```

---

## 📄 Páginas y Funcionalidades

### **1. 📊 Página Index (`/roles`)**

#### **Características:**
- **Estadísticas en tiempo real**: Total, roles del sistema, roles personalizados
- **Filtros inteligentes**: Búsqueda con debounce, paginación configurable
- **Tabla responsive**: Información completa con badges y estados
- **Acciones contextuales**: Editar, eliminar, gestionar usuarios

#### **Componentes Principales:**
```tsx
// Estadísticas
<Card>
  <CardHeader>Total de Roles</CardHeader>
  <CardContent>{roles.total}</CardContent>
</Card>

// Filtros con debounce
<Input
  value={searchValue}
  onChange={(e) => setSearchValue(e.target.value)}
  placeholder="Buscar por nombre o descripción..."
/>

// Tabla con acciones
<Table>
  <TableRow>
    <TableCell>
      <Badge variant={role.is_system ? "secondary" : "default"}>
        {role.is_system ? "Sistema" : "Personalizado"}
      </Badge>
    </TableCell>
  </TableRow>
</Table>
```

#### **Funcionalidades:**
- ✅ **Búsqueda en tiempo real** con debounce de 500ms
- ✅ **Paginación configurable** (10, 25, 50, 100 registros)
- ✅ **Eliminación con confirmación** y feedback visual
- ✅ **Modal de usuarios** para ver asignaciones
- ✅ **Notificaciones toast** para todas las acciones

---

### **2. ➕ Página Create (`/roles/create`)**

#### **Características:**
- **Formulario limpio**: Validación en tiempo real
- **Permisos automáticos**: Detecta y muestra todas las páginas del sistema
- **Tabla de permisos**: Organizada por página y acción (Ver, Crear, Editar, Eliminar)
- **Redirección inteligente**: Vuelve a la lista después de crear

#### **Estructura del Formulario:**
```tsx
<form onSubmit={handleSubmit}>
  {/* Información básica */}
  <Input name="name" placeholder="Nombre del rol" />
  <Textarea name="description" placeholder="Descripción del rol" />
  
  {/* Tabla de permisos automática */}
  <Table>
    <TableHeader>
      <TableRow>
        <TableHead>Página</TableHead>
        <TableHead>Ver</TableHead>
        <TableHead>Crear</TableHead>
        <TableHead>Editar</TableHead>
        <TableHead>Eliminar</TableHead>
      </TableRow>
    </TableHeader>
    <TableBody>
      {Object.entries(permissions).map(([group, groupPermissions]) => (
        <TableRow key={group}>
          <TableCell>{getGroupDisplayName(group)}</TableCell>
          {/* Checkboxes por acción */}
        </TableRow>
      ))}
    </TableBody>
  </Table>
</form>
```

#### **Funcionalidades:**
- ✅ **Validación frontend y backend**
- ✅ **Permisos dinámicos** (se actualizan automáticamente)
- ✅ **Notificaciones de éxito/error**
- ✅ **Redirección automática** a `/roles` después de crear

---

### **3. ✏️ Página Edit (`/roles/{role}/edit`)**

#### **Características:**
- **Información del rol**: Pre-cargada en formulario
- **Gestión de permisos**: Tabla interactiva con estado actual
- **Gestión de usuarios**: Sheet lateral con búsqueda
- **Protecciones especiales**: Para rol admin y roles del sistema

#### **Gestión de Usuarios:**
```tsx
<Sheet open={isUserSheetOpen}>
  <SheetContent>
    <SheetHeader>
      <SheetTitle>Gestionar Usuarios del Rol</SheetTitle>
    </SheetHeader>
    
    {/* Buscador de usuarios */}
    <Input
      placeholder="Buscar usuarios..."
      value={searchTerm}
      onChange={(e) => setSearchTerm(e.target.value)}
    />
    
    {/* Lista con checkboxes */}
    <ScrollArea className="h-[400px]">
      {filteredUsers.map(user => (
        <div key={user.id}>
          <Checkbox
            checked={selectedUsers.includes(user.id)}
            onCheckedChange={(checked) => handleUserChange(user.id, checked)}
          />
          <Label>{user.name}</Label>
        </div>
      ))}
    </ScrollArea>
  </SheetContent>
</Sheet>
```

#### **Funcionalidades:**
- ✅ **Edición de permisos** con estado visual
- ✅ **Gestión de usuarios** con guardado automático
- ✅ **Protección de rol admin** (siempre todos los permisos)
- ✅ **Notificaciones en tiempo real**

---

## 🤖 Sistema de Permisos Automático

### **Detección Automática de Páginas**

#### **PermissionDiscoveryService:**
```php
class PermissionDiscoveryService
{
    public function discoverPages(): array
    {
        $pagesPath = resource_path('js/pages');
        $discoveredPages = [];

        // Escanear directorios
        foreach (File::directories($pagesPath) as $directory) {
            $pageName = basename($directory);
            
            // Saltar páginas excluidas (auth, settings)
            if (in_array($pageName, $this->excludedPages)) {
                continue;
            }

            // Detectar acciones automáticamente
            $discoveredPages[$pageName] = $this->autoDetectPageConfig($pageName, $directory);
        }

        return $discoveredPages;
    }

    private function autoDetectPageConfig(string $pageName, string $directory): array
    {
        $config = [
            'display_name' => Str::title($pageName),
            'description' => "Gestión de {$pageName}",
            'actions' => ['view'] // Por defecto solo ver
        ];

        $files = collect(File::files($directory))
            ->map(fn($file) => pathinfo($file->getFilename(), PATHINFO_FILENAME));
        
        // Detectar acciones basado en archivos existentes
        if ($files->contains('create')) $config['actions'][] = 'create';
        if ($files->contains('edit')) $config['actions'][] = 'edit';
        if ($files->contains('index') && ($files->contains('create') || $files->contains('edit'))) {
            $config['actions'][] = 'delete';
        }

        return $config;
    }
}
```

### **Sincronización Automática:**

#### **En RoleController:**
```php
private function syncPermissionsIfNeeded(): void
{
    $discoveryService = new PermissionDiscoveryService();
    
    // Verificar si hay páginas nuevas
    $currentPermissionNames = collect($discoveryService->generatePermissions())->pluck('name');
    $existingPermissionNames = Permission::pluck('name');
    
    $newPermissions = $currentPermissionNames->diff($existingPermissionNames);
    
    if ($newPermissions->count() > 0) {
        \Log::info('Auto-sincronizando permisos: ' . $newPermissions->join(', '));
        $discoveryService->syncPermissions();
        
        // Actualizar rol admin
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $allPermissionIds = Permission::pluck('id');
            $adminRole->permissions()->sync($allPermissionIds);
        }
    }
}
```

#### **Comando Manual:**
```bash
# Ver qué se detectaría
php artisan permissions:sync --show-only

# Sincronizar permisos
php artisan permissions:sync --force

# Sincronizar y limpiar obsoletos
php artisan permissions:sync --clean --force
```

---

## 🎨 Componentes UI

### **Componentes Utilizados:**
- **shadcn/ui**: `Card`, `Button`, `Input`, `Select`, `Table`, `Badge`, `Dialog`, `Sheet`
- **Lucide Icons**: `Shield`, `Plus`, `Edit`, `Trash2`, `Users`, `ArrowLeft`, `Save`
- **Sonner**: Para notificaciones toast

### **Patrones de Diseño:**
```tsx
// Patrón de Card para secciones
<Card>
  <CardHeader>
    <CardTitle>Título</CardTitle>
    <CardDescription>Descripción</CardDescription>
  </CardHeader>
  <CardContent>
    {/* Contenido */}
  </CardContent>
</Card>

// Patrón de filtros
<div className="grid gap-4 md:grid-cols-2">
  <div className="space-y-2">
    <Label>Filtro</Label>
    <Input placeholder="Buscar..." />
  </div>
</div>

// Patrón de acciones
<div className="flex items-center justify-end space-x-2">
  <Button variant="outline">Cancelar</Button>
  <Button type="submit">Guardar</Button>
</div>
```

---

## 🔄 Flujo de Trabajo

### **Crear Nuevo Rol:**
1. Usuario va a `/roles/create`
2. Sistema detecta automáticamente nuevas páginas
3. Sincroniza permisos si es necesario
4. Muestra formulario con permisos actualizados
5. Usuario completa formulario
6. Se crea rol y se redirige a `/roles`
7. Notificación de éxito

### **Editar Rol Existente:**
1. Usuario va a `/roles/{role}/edit`
2. Sistema carga rol con relaciones
3. Detecta y sincroniza nuevos permisos
4. Muestra formulario pre-poblado
5. Usuario modifica permisos/usuarios
6. Se guarda automáticamente
7. Notificaciones en tiempo real

### **Eliminar Rol:**
1. Usuario hace clic en eliminar
2. Aparece dialog de confirmación
3. Si confirma, se ejecuta eliminación
4. Observer registra actividad automáticamente
5. Notificación de éxito
6. Lista se actualiza

---

## 🔌 API y Controladores

### **RoleController Endpoints:**

#### **GET `/roles`** - Lista de roles
```php
public function index(Request $request): Response
{
    $perPage = $request->get('per_page', 10);
    $search = $request->get('search', '');
    
    $query = Role::with(['permissions', 'users:id,name,email']);
    
    if ($search) {
        $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        });
    }
    
    $roles = $query->orderBy('is_system', 'desc')
        ->orderBy('name')
        ->paginate($perPage)
        ->appends($request->all());

    return Inertia::render('roles/index', [
        'roles' => $roles,
        'permissions' => Permission::getGrouped(),
        'filters' => [
            'search' => $search,
            'per_page' => $perPage,
        ],
    ]);
}
```

#### **POST `/roles`** - Crear rol
```php
public function store(Request $request)
{
    $request->validate([
        'name' => 'required|string|max:255|unique:roles,name',
        'description' => 'nullable|string',
        'permissions' => 'array',
        'permissions.*' => 'exists:permissions,name',
    ]);

    $role = Role::create([
        'name' => $request->name,
        'description' => $request->description,
        'is_system' => false,
    ]);

    if ($request->has('permissions')) {
        $permissionIds = Permission::whereIn('name', $request->permissions)->pluck('id');
        $role->permissions()->sync($permissionIds);
    }

    return redirect()->route('roles.index')->with('success', 'Rol creado exitosamente');
}
```

#### **PATCH `/roles/{role}`** - Actualizar rol
```php
public function update(Request $request, Role $role): RedirectResponse
{
    $request->validate([
        'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
        'description' => 'nullable|string|max:500',
        'permissions' => 'array',
    ]);

    $oldValues = $role->toArray();
    $newValues = $request->only(['name', 'description']);

    // Rol admin mantiene todos los permisos automáticamente
    if ($role->name === 'admin') {
        $allPermissions = Permission::pluck('id')->toArray();
        $request->merge(['permissions' => $allPermissions]);
    }

    $role->update($newValues);

    // Convertir nombres a IDs para sincronización
    $permissionNames = $request->input('permissions', []);
    $permissionIds = Permission::whereIn('name', $permissionNames)->pluck('id')->toArray();
    $role->permissions()->sync($permissionIds);

    return back()->with('success', 'Rol actualizado exitosamente');
}
```

#### **PATCH `/roles/{role}/users`** - Actualizar usuarios
```php
public function updateUsers(Request $request, Role $role): JsonResponse
{
    $request->validate([
        'users' => 'array',
        'users.*' => 'exists:users,id'
    ]);

    $oldUserIds = $role->users()->pluck('users.id')->toArray();
    $newUserIds = $request->input('users', []);

    // Protección especial para rol admin
    if ($role->name === 'admin') {
        $adminUser = User::where('email', 'admin@admin.com')->first();
        if ($adminUser && !in_array($adminUser->id, $newUserIds)) {
            $newUserIds[] = $adminUser->id;
        }
    }

    $role->users()->sync($newUserIds);

    return response()->json(['success' => 'Usuarios del rol actualizados exitosamente']);
}
```

---

## 🗄️ Base de Datos

### **Estructura de Tablas:**

#### **roles**
```sql
CREATE TABLE roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    description TEXT NULL,
    is_system BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

#### **permissions**
```sql
CREATE TABLE permissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    display_name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    group VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

#### **role_user** (Tabla Pivot)
```sql
CREATE TABLE role_user (
    role_id BIGINT UNSIGNED,
    user_id BIGINT UNSIGNED,
    PRIMARY KEY (role_id, user_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

#### **permission_role** (Tabla Pivot)
```sql
CREATE TABLE permission_role (
    permission_id BIGINT UNSIGNED,
    role_id BIGINT UNSIGNED,
    PRIMARY KEY (permission_id, role_id),
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
);
```

### **Relaciones Eloquent:**
```php
// Role.php
public function users(): BelongsToMany
{
    return $this->belongsToMany(User::class);
}

public function permissions(): BelongsToMany
{
    return $this->belongsToMany(Permission::class);
}

// User.php
public function roles(): BelongsToMany
{
    return $this->belongsToMany(Role::class);
}

public function hasPermission(string $permission): bool
{
    return $this->roles()
        ->whereHas('permissions', function ($query) use ($permission) {
            $query->where('name', $permission);
        })
        ->exists();
}
```

---

## 🔒 Seguridad

### **Middleware de Permisos:**
```php
// CheckUserPermissions.php
public function handle(Request $request, Closure $next, string $permission = null): Response
{
    $user = auth()->user();
    
    if (!$user || !$user->hasPermission($permission)) {
        if ($request->expectsJson()) {
            return response()->json(['error' => 'No tienes permisos'], 403);
        }
        
        return redirect()->route('dashboard')
            ->with('error', 'No tienes permisos para acceder a esta página.');
    }

    return $next($request);
}
```

### **Protecciones en Rutas:**
```php
// routes/web.php
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('roles', [RoleController::class, 'index'])
        ->middleware('permission:roles.view');
    Route::get('roles/create', [RoleController::class, 'create'])
        ->middleware('permission:roles.create');
    Route::post('roles', [RoleController::class, 'store'])
        ->middleware('permission:roles.create');
    Route::get('roles/{role}/edit', [RoleController::class, 'edit'])
        ->middleware('permission:roles.edit');
    Route::patch('roles/{role}', [RoleController::class, 'update'])
        ->middleware('permission:roles.edit');
    Route::delete('roles/{role}', [RoleController::class, 'destroy'])
        ->middleware('permission:roles.delete');
});
```

### **Validaciones Especiales:**
- **Rol Admin**: No se puede eliminar, siempre tiene todos los permisos
- **Roles del Sistema**: Solo admin puede editarlos
- **Usuario admin@admin.com**: Siempre debe estar en rol admin
- **CSRF Protection**: En todas las peticiones POST/PATCH/DELETE

---

## 🛠️ Mantenimiento

### **Logs de Actividad:**
```php
// RoleObserver.php
public function created(Role $role): void
{
    ActivityLog::create([
        'user_id' => auth()->id(),
        'event_type' => 'role_created',
        'target_model' => 'Role',
        'target_id' => $role->id,
        'description' => "Rol '{$role->name}' fue creado",
        'old_values' => null,
        'new_values' => $role->toArray(),
        'ip_address' => request()->ip(),
    ]);
}
```

### **Comandos de Mantenimiento:**
```bash
# Sincronizar permisos automáticamente
php artisan permissions:sync --force

# Ver qué permisos se detectarían sin ejecutar
php artisan permissions:sync --show-only

# Limpiar permisos obsoletos
php artisan permissions:sync --clean --force

# Verificar integridad del sistema
php artisan tinker
> $service = new App\Services\PermissionDiscoveryService();
> $pages = $service->discoverPages();
> echo "Páginas detectadas: " . count($pages);
```

### **Troubleshooting:**

#### **Problema**: Permisos no aparecen en formulario
```bash
# Solución: Sincronizar manualmente
php artisan permissions:sync --force
```

#### **Problema**: Rol admin sin permisos
```bash
# Solución: Ejecutar sincronización que actualiza admin
php artisan permissions:sync --force
```

#### **Problema**: Páginas nuevas no detectadas
```bash
# Verificar estructura de archivos
ls -la resources/js/pages/nueva-pagina/
# Debe tener al menos index.tsx

# Verificar exclusiones
php artisan tinker
> $service = new App\Services\PermissionDiscoveryService();
> $pages = $service->discoverPages();
> print_r($pages);
```

---

## 📈 Métricas y Performance

### **Optimizaciones Implementadas:**
- ✅ **Eager Loading**: `with(['permissions', 'users'])`
- ✅ **Debounce**: Búsqueda con 500ms de delay
- ✅ **Paginación**: Configurable (10, 25, 50, 100)
- ✅ **Caching de Permisos**: Se evita regenerar en cada request
- ✅ **Índices de BD**: En columnas de búsqueda frecuente

### **Monitoreo:**
```php
// Logs automáticos en sincronización
\Log::info('Auto-sincronizando permisos: ' . $newPermissions->join(', '));

// Métricas disponibles en dashboard
- Total de roles en sistema
- Roles del sistema vs personalizados  
- Usuarios sin roles asignados
- Permisos más utilizados
```

---

## 🚀 Escalabilidad

### **Diseño Escalable:**
- **Detección Automática**: Nuevas páginas se integran automáticamente
- **Permisos Granulares**: Por página y acción específica
- **Roles Ilimitados**: Sin límite en cantidad de roles
- **Usuarios Múltiples**: Un usuario puede tener múltiples roles

### **Futuras Mejoras:**
- [ ] **Cache de Permisos**: Redis para high-performance
- [ ] **Permisos Condicionales**: Basados en contexto o datos
- [ ] **Auditoría Avanzada**: Tracking detallado de cambios
- [ ] **API REST**: Para integración con sistemas externos
- [ ] **Bulk Operations**: Asignación masiva de permisos

---

## 🎯 Conclusión

La **Roles Page** es un sistema completo, automático y escalable que:

1. **✅ Gestiona roles y permisos** de forma intuitiva
2. **✅ Se adapta automáticamente** a nuevas páginas del sistema  
3. **✅ Mantiene seguridad** con validaciones y protecciones
4. **✅ Ofrece UX/UI moderna** siguiendo las mejores prácticas
5. **✅ Es completamente sostenible** con mínimo mantenimiento

El sistema está diseñado para crecer con el proyecto sin requerir configuración manual adicional, cumpliendo perfectamente con los requisitos de escalabilidad y sostenibilidad.

