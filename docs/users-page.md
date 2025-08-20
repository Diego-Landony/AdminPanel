# 👥 Documentación: Users Page

## 📋 Índice
- [Descripción General](#descripción-general)
- [Arquitectura del Sistema](#arquitectura-del-sistema)
- [Funcionalidades CRUD](#funcionalidades-crud)
- [Páginas y Componentes](#páginas-y-componentes)
- [Controlador y API](#controlador-y-api)
- [Sistema de Logging](#sistema-de-logging)
- [Seguridad y Validaciones](#seguridad-y-validaciones)
- [UX/UI y Diseño](#uxui-y-diseño)
- [Base de Datos](#base-de-datos)
- [Ejemplos de Uso](#ejemplos-de-uso)
- [Troubleshooting](#troubleshooting)

---

## 🎯 Descripción General

La **Users Page** es un sistema completo de gestión de usuarios que permite administrar todos los aspectos de los usuarios del sistema, incluyendo:

- ✅ **CRUD completo**: Crear, leer, actualizar y eliminar usuarios
- ✅ **Gestión de roles**: Asignar y modificar roles por usuario
- ✅ **Gestión de contraseñas**: Cambio opcional de contraseñas
- ✅ **Seguimiento de actividad**: Monitoreo en tiempo real del estado de usuarios
- ✅ **Logging automático**: Registro de todas las operaciones en activity logs
- ✅ **Interfaz moderna**: UI responsive con componentes shadcn/ui
- ✅ **Búsqueda y filtros**: Sistema de búsqueda con paginación

---

## 🏗️ Arquitectura del Sistema

### **Frontend (React + TypeScript + Inertia.js)**
```
resources/js/pages/users/
├── index.tsx           # Lista de usuarios con CRUD y búsqueda
├── create.tsx          # Formulario de creación de usuarios
└── edit.tsx            # Formulario de edición con gestión de contraseñas
```

### **Backend (Laravel + PHP 8.2+)**
```
app/
├── Http/Controllers/
│   └── UserController.php              # Controlador CRUD completo
├── Models/
│   ├── User.php                        # Modelo con relaciones y métodos
│   ├── Role.php                        # Modelo de roles
│   └── UserActivity.php                # Modelo de actividad
├── Observers/
│   └── UserObserver.php                # Observer para logging automático
└── Providers/
    └── AppServiceProvider.php          # Configuración de observers
```

### **Rutas (routes/web.php)**
```php
// Gestión completa de usuarios con middlewares de permisos
Route::get('users', [UserController::class, 'index'])->middleware('permission:users.view');
Route::get('users/create', [UserController::class, 'create'])->middleware('permission:users.create');
Route::post('users', [UserController::class, 'store'])->middleware('permission:users.create');
Route::get('users/{user}/edit', [UserController::class, 'edit'])->middleware('permission:users.edit');
Route::patch('users/{user}', [UserController::class, 'update'])->middleware('permission:users.edit');
Route::delete('users/{user}', [UserController::class, 'destroy'])->middleware('permission:users.delete');
```

---

## ⚙️ Funcionalidades CRUD

### **1. 📊 Listar Usuarios (`GET /users`)**

#### **Características:**
- **Estadísticas en tiempo real**: Total usuarios, verificados, en línea
- **Búsqueda inteligente**: Por nombre y email con botón aplicar filtros
- **Paginación configurable**: 10, 25, 50, 100 registros por página
- **Estado de usuarios**: En línea, reciente, desconectado, nunca
- **Roles visualizados**: Badges con diferenciación sistema/personalizado
- **Acciones por fila**: Editar y eliminar con confirmación

#### **Interfaz de Datos:**
```typescript
interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    last_activity: string | null;
    is_online: boolean;
    status: 'online' | 'recent' | 'offline' | 'never';
    roles: Role[];
}
```

#### **Implementación de Búsqueda:**
```tsx
const applyFilters = () => {
    const filterParams = {
        search: searchValue,
        per_page: perPage,
    };

    router.get('/users', filterParams, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: (page) => {
            if (activities && activities.total === 0 && searchValue.trim() !== '') {
                toast.info(`No se encontraron usuarios para: "${searchValue}"`);
            }
        }
    });
};
```

---

### **2. ➕ Crear Usuario (`POST /users`)**

#### **Formulario de Creación:**
```tsx
// Campos del formulario
const { data, setData, post, processing, errors } = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    roles: [] as number[],
});
```

#### **Validaciones del Servidor:**
```php
$request->validate([
    'name' => 'required|string|max:255',
    'email' => 'required|string|lowercase|email|max:255|unique:users',
    'password' => ['required', 'confirmed', Rules\Password::defaults()],
    'roles' => 'array',
    'roles.*' => 'exists:roles,id',
]);
```

#### **Características Especiales:**
- **Auto-verificación**: Los usuarios creados por admin se marcan como verificados automáticamente
- **Selección de roles**: Interface de checkboxes con diferenciación visual
- **Validación en tiempo real**: Frontend y backend sincronizados
- **Notificaciones**: Toast success/error con redirección automática

#### **Layout Responsive:**
```tsx
<div className="grid gap-6 lg:grid-cols-2">
    {/* Información del Usuario */}
    <Card>
        <CardHeader>
            <CardTitle className="flex items-center gap-2">
                <User className="h-5 w-5" />
                Información del Usuario
            </CardTitle>
        </CardHeader>
        {/* Formulario de datos básicos */}
    </Card>

    {/* Roles y Permisos */}
    <Card>
        <CardHeader>
            <CardTitle className="flex items-center gap-2">
                <Shield className="h-5 w-5" />
                Roles y Permisos
            </CardTitle>
        </CardHeader>
        {/* Selección de roles */}
    </Card>
</div>
```

---

### **3. ✏️ Editar Usuario (`PATCH /users/{user}`)**

#### **Características Avanzadas:**
- **Edición de datos básicos**: Nombre y email con validación
- **Cambio opcional de contraseña**: Con toggle y confirmación
- **Gestión de roles**: Modificación en tiempo real
- **Información del sistema**: Sidebar con metadatos del usuario
- **Mostrar/ocultar contraseña**: Toggle de visibilidad

#### **Gestión de Contraseñas:**
```tsx
const [changePassword, setChangePassword] = useState(false);
const [showPassword, setShowPassword] = useState(false);

// Solo enviar campos de contraseña si el usuario decide cambiarla
const handleSubmit = (e: React.FormEvent) => {
    const submitData = { ...data };
    if (!changePassword) {
        delete submitData.password;
        delete submitData.password_confirmation;
    }
    
    patch(route('users.update', user.id), { data: submitData });
};
```

#### **Validación Condicional del Backend:**
```php
$rules = [
    'name' => 'required|string|max:255',
    'email' => 'required|string|lowercase|email|max:255|unique:users,email,' . $user->id,
    'roles' => 'array',
];

// Solo validar contraseña si se proporciona
if ($request->filled('password')) {
    $rules['password'] = ['confirmed', Rules\Password::defaults()];
}
```

#### **Sidebar de Información:**
```tsx
<Card>
    <CardHeader>
        <CardTitle className="text-sm">Información del Sistema</CardTitle>
    </CardHeader>
    <CardContent className="space-y-3">
        <div>
            <Label className="text-xs text-muted-foreground">ID</Label>
            <p className="text-sm font-mono">#{user.id}</p>
        </div>
        <div>
            <Label className="text-xs text-muted-foreground">Email Verificado</Label>
            <Badge variant={user.email_verified_at ? "default" : "destructive"}>
                {user.email_verified_at ? "Verificado" : "No verificado"}
            </Badge>
        </div>
        {/* Más información del sistema */}
    </CardContent>
</Card>
```

---

### **4. 🗑️ Eliminar Usuario (`DELETE /users/{user}`)**

#### **Protecciones de Seguridad:**
```php
// Proteger al usuario admin principal
if ($user->email === 'admin@admin.com') {
    return back()->with('error', 'No se puede eliminar el usuario administrador principal');
}

// Verificar que el usuario no se elimine a sí mismo
if ($user->id === auth()->id()) {
    return back()->with('error', 'No puedes eliminar tu propia cuenta');
}
```

#### **Confirmación con Dialog:**
```tsx
<Dialog>
    <DialogTrigger asChild>
        <Button 
            variant="outline" 
            size="sm"
            className="text-red-600 hover:text-red-700 hover:bg-red-50"
            disabled={deletingUser === user.id}
        >
            <Trash2 className="h-4 w-4" />
        </Button>
    </DialogTrigger>
    <DialogContent>
        <DialogHeader>
            <DialogTitle>Eliminar Usuario</DialogTitle>
            <DialogDescription>
                ¿Estás seguro de que deseas eliminar al usuario <strong>{user.name}</strong>? 
                Esta acción no se puede deshacer.
            </DialogDescription>
        </DialogHeader>
        <DialogFooter>
            <Button variant="outline">Cancelar</Button>
            <Button 
                variant="destructive"
                onClick={() => handleDeleteUser(user)}
                disabled={deletingUser === user.id}
            >
                {deletingUser === user.id ? 'Eliminando...' : 'Eliminar'}
            </Button>
        </DialogFooter>
    </DialogContent>
</Dialog>
```

---

## 🔌 Controlador y API

### **UserController - Métodos Principales**

#### **index() - Lista paginada**
```php
public function index(Request $request): Response
{
    $perPage = $request->get('per_page', 10);
    $search = $request->get('search', '');
    
    $query = User::with('roles')
        ->select(['id', 'name', 'email', 'email_verified_at', 'created_at', 'updated_at', 'last_activity_at']);
    
    // Aplicar búsqueda si existe
    if ($search) {
        $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%");
        });
    }
    
    $users = $query->orderBy('created_at', 'desc')
        ->paginate($perPage)
        ->appends($request->all());

    return Inertia::render('users/index', [
        'users' => $users,
        'total_users' => $totalStats->count(),
        'verified_users' => $totalStats->where('email_verified_at', '!=', null)->count(),
        'online_users' => $totalStats->filter(fn($user) => $this->isUserOnline($user->last_activity_at))->count(),
        'filters' => ['search' => $search, 'per_page' => $perPage],
    ]);
}
```

#### **store() - Crear usuario**
```php
public function store(Request $request): RedirectResponse
{
    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|string|lowercase|email|max:255|unique:users',
        'password' => ['required', 'confirmed', Rules\Password::defaults()],
        'roles' => 'array',
    ]);

    $user = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => Hash::make($request->password),
        'email_verified_at' => now(), // Auto-verificar
    ]);

    // Asignar roles
    if ($request->has('roles')) {
        $user->roles()->sync($request->roles);
    }

    return redirect()->route('users.index')->with('success', 'Usuario creado exitosamente');
}
```

#### **update() - Actualizar usuario**
```php
public function update(Request $request, User $user): RedirectResponse
{
    $rules = [
        'name' => 'required|string|max:255',
        'email' => 'required|string|lowercase|email|max:255|unique:users,email,' . $user->id,
        'roles' => 'array',
    ];

    // Validación condicional de contraseña
    if ($request->filled('password')) {
        $rules['password'] = ['confirmed', Rules\Password::defaults()];
    }

    $request->validate($rules);

    $userData = ['name' => $request->name, 'email' => $request->email];

    // Actualizar contraseña solo si se proporciona
    if ($request->filled('password')) {
        $userData['password'] = Hash::make($request->password);
    }

    // Marcar email como no verificado si cambió
    if ($user->email !== $request->email) {
        $userData['email_verified_at'] = null;
    }

    $user->update($userData);
    $user->roles()->sync($request->input('roles', []));

    return back()->with('success', 'Usuario actualizado exitosamente');
}
```

---

## 📝 Sistema de Logging

### **UserObserver - Logging Automático**

Todas las operaciones CRUD se registran automáticamente en la actividad del sistema:

```php
class UserObserver
{
    public function created(User $user): void
    {
        $this->logActivityEvent('user_created', $user, null, $user->toArray());
    }

    public function updated(User $user): void
    {
        $oldValues = $user->getOriginal();
        $newValues = $user->getChanges();
        
        $this->logActivityEvent('user_updated', $user, $oldValues, $newValues);
    }

    public function deleted(User $user): void
    {
        $this->logActivityEvent('user_deleted', $user, $user->toArray(), null);
    }

    private function logActivityEvent(string $eventType, User $user, ?array $oldValues, ?array $newValues): void
    {
        ActivityLog::create([
            'user_id' => auth()->id(),
            'event_type' => $eventType,
            'target_model' => 'User',
            'target_id' => $user->id,
            'description' => "Usuario '{$user->name}' ({$user->email}) fue " . $this->getEventDescription($eventType),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
        ]);
    }
}
```

### **Tipos de Eventos Registrados:**
- `user_created` - Usuario creado
- `user_updated` - Usuario actualizado (datos, roles, contraseña)
- `user_deleted` - Usuario eliminado
- `user_restored` - Usuario restaurado (soft deletes)
- `user_force_deleted` - Usuario eliminado permanentemente

---

## 🔒 Seguridad y Validaciones

### **Middleware de Permisos:**
```php
// Verificación granular de permisos por acción
Route::get('users', [UserController::class, 'index'])->middleware('permission:users.view');
Route::post('users', [UserController::class, 'store'])->middleware('permission:users.create');
Route::patch('users/{user}', [UserController::class, 'update'])->middleware('permission:users.edit');
Route::delete('users/{user}', [UserController::class, 'destroy'])->middleware('permission:users.delete');
```

### **Protecciones Especiales:**
- **Usuario admin principal**: No se puede eliminar (`admin@admin.com`)
- **Auto-eliminación**: Los usuarios no pueden eliminarse a sí mismos
- **Validación de email único**: Con exclusión del propio registro en edición
- **Contraseñas seguras**: Usando `Rules\Password::defaults()`
- **Sanitización automática**: Email en minúsculas, trim de espacios

### **Validaciones Frontend:**
```tsx
// Validación en tiempo real con estados de error
<Input
    id="email"
    type="email"
    value={data.email}
    onChange={(e) => setData('email', e.target.value)}
    className={errors.email ? 'border-red-500' : ''}
    required
/>
{errors.email && (
    <p className="text-sm text-red-600">{errors.email}</p>
)}
```

---

## 🎨 UX/UI y Diseño

### **Principios de Diseño Aplicados:**

#### **1. Mobile-First Responsive:**
```tsx
// Grid adaptativo según tamaño de pantalla
<div className="grid gap-4 md:grid-cols-3">          // Estadísticas
<div className="grid gap-6 lg:grid-cols-2">          // Formularios
<div className="grid gap-4 md:grid-cols-2">          // Filtros
```

#### **2. Componentes Consistentes:**
- **shadcn/ui**: Card, Button, Input, Select, Dialog, Badge, Table
- **Lucide Icons**: User, Mail, Lock, Shield, Edit, Trash2, Plus, Search
- **Toast Notifications**: sonner para feedback inmediato
- **Patrones de Layout**: CardHeader + CardContent consistente

#### **3. Estados Visuales:**
```tsx
// Estados de usuarios con códigos de color
const getStatusColor = (status: string): string => {
    switch (status) {
        case 'online':
            return 'bg-green-100 text-green-800 border-green-200';
        case 'recent':
            return 'bg-blue-100 text-blue-800 border-blue-200';
        case 'offline':
            return 'bg-gray-100 text-gray-700 border-gray-200';
        case 'never':
            return 'bg-red-100 text-red-800 border-red-200';
    }
};
```

#### **4. Microinteracciones:**
- **Loading states**: Botones con spinner durante operaciones
- **Hover effects**: Cambios sutiles en botones y filas
- **Focus management**: Outline visible en navegación por teclado
- **Disabled states**: Visual feedback para acciones no disponibles

#### **5. Información Contextual:**
```tsx
// Breadcrumbs para navegación
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Usuarios', href: '/users' },
    { title: 'Crear Usuario', href: '/users/create' },
];

// Descripciones informativas
<CardDescription>
    Administra los usuarios del sistema, sus roles y permisos
</CardDescription>
```

---

## 🗄️ Base de Datos

### **Estructura de la Tabla Users:**
```sql
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    last_login_at TIMESTAMP NULL,
    last_activity_at TIMESTAMP NULL,
    timezone VARCHAR(50) DEFAULT 'America/Guatemala',
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    INDEX idx_email (email),
    INDEX idx_last_activity (last_activity_at),
    INDEX idx_created_at (created_at)
);
```

### **Relaciones Eloquent:**
```php
// User.php
public function roles(): BelongsToMany
{
    return $this->belongsToMany(Role::class);
}

public function activities(): HasMany
{
    return $this->hasMany(UserActivity::class);
}

public function activityLogs(): HasMany
{
    return $this->hasMany(ActivityLog::class);
}

// Métodos de utilidad
public function hasRole(string $role): bool
{
    return $this->roles()->where('name', $role)->exists();
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

### **Tabla Pivot role_user:**
```sql
CREATE TABLE role_user (
    role_id BIGINT UNSIGNED,
    user_id BIGINT UNSIGNED,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (role_id, user_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

---

## 💡 Ejemplos de Uso

### **Crear un Usuario Administrador:**
```bash
# 1. Ir a /users/create
# 2. Llenar formulario:
#    - Nombre: "Juan Administrador"
#    - Email: "juan@empresa.com"
#    - Contraseña: "password123"
#    - Confirmar contraseña: "password123"
#    - Roles: [✓] admin
# 3. Hacer clic en "Crear Usuario"
# ✅ Usuario creado con rol admin y email auto-verificado
```

### **Buscar Usuarios por Email:**
```bash
# 1. Ir a /users
# 2. En el campo de búsqueda escribir: "@gmail.com"
# 3. Hacer clic en "Buscar"
# ✅ Muestra todos los usuarios con emails de Gmail
```

### **Cambiar Contraseña de Usuario:**
```bash
# 1. Ir a /users/{id}/edit
# 2. Marcar checkbox "Cambiar contraseña"
# 3. Llenar nueva contraseña y confirmación
# 4. Hacer clic en "Guardar Cambios"
# ✅ Solo la contraseña se actualiza, otros datos permanecen igual
```

### **Asignar Roles a Usuario Existente:**
```bash
# 1. Ir a /users/{id}/edit
# 2. En la sección "Roles", marcar/desmarcar roles deseados
# 3. Hacer clic en "Guardar Cambios"
# ✅ Roles se sincronizan automáticamente
```

---

## 🔧 Troubleshooting

### **Problema: Error de validación "email already exists"**
```bash
# Causa: Intentar actualizar usuario con email de otro usuario
# Solución: La validación excluye el propio registro automáticamente
'email' => 'required|email|unique:users,email,' . $user->id
```

### **Problema: Usuario no puede eliminar su propia cuenta**
```bash
# Comportamiento esperado por seguridad
if ($user->id === auth()->id()) {
    return back()->with('error', 'No puedes eliminar tu propia cuenta');
}
```

### **Problema: No aparecen nuevos roles en formulario**
```bash
# Solución: Los roles se cargan dinámicamente desde la base de datos
$roles = Role::orderBy('name')->get();
```

### **Problema: Contraseña no se actualiza**
```bash
# Verificar que el checkbox "Cambiar contraseña" esté marcado
# El frontend solo envía campos de password si changePassword === true
if (!changePassword) {
    delete submitData.password;
    delete submitData.password_confirmation;
}
```

### **Problema: Activity logs no se registran**
```bash
# Verificar que el UserObserver esté registrado en AppServiceProvider
User::observe(UserObserver::class);
```

---

## 📈 Métricas y Performance

### **Optimizaciones Implementadas:**
- ✅ **Eager Loading**: `with('roles')` para evitar N+1 queries
- ✅ **Búsqueda Eficiente**: Índices en columnas de búsqueda
- ✅ **Paginación**: Configurable para manejar grandes volúmenes
- ✅ **Preservar Estado**: `preserveState` y `preserveScroll` en navegación
- ✅ **Validación Condicional**: Solo validar contraseña cuando se proporciona

### **Monitoreo Disponible:**
```php
// Estadísticas automáticas en dashboard
- Total de usuarios en sistema
- Usuarios con email verificado
- Usuarios activos (últimos 5 minutos)
- Distribución de roles por usuario
- Actividad de creación/modificación de usuarios
```

---

## 🚀 Escalabilidad y Futuras Mejoras

### **Diseño Escalable Actual:**
- **Validación granular**: Por campo y acción específica
- **Logging completo**: Auditoría de todos los cambios
- **Permisos flexibles**: Sistema de roles configurable
- **UI modular**: Componentes reutilizables

### **Mejoras Futuras Sugeridas:**
- [ ] **Importación masiva**: CSV/Excel upload para usuarios
- [ ] **Autenticación 2FA**: Google Authenticator integration
- [ ] **Roles temporales**: Asignación con fecha de expiración
- [ ] **Dashboard de usuario**: Panel individual por usuario
- [ ] **Integración LDAP**: Sincronización con Active Directory
- [ ] **Campos personalizados**: Perfil extendido por usuario

---

## 🎯 Conclusión

La **Users Page** es un sistema completo y robusto que:

1. **✅ Proporciona CRUD completo** para gestión de usuarios
2. **✅ Integra automáticamente** con el sistema de activity logging
3. **✅ Mantiene seguridad** con validaciones y protecciones
4. **✅ Ofrece UX/UI moderna** siguiendo las mejores prácticas
5. **✅ Es escalable** y está preparada para crecer con el sistema

El sistema está diseñado para manejar desde pequeños equipos hasta organizaciones grandes, manteniendo siempre la seguridad, usabilidad y rendimiento como prioridades principales.

