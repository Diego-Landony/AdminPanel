# 📘 Guía de Uso - Infraestructura de Refactorización

> Guía rápida para usar los nuevos Form Requests, Traits y Services creados en las Fases 1-3

---

## 🎯 Form Requests

### Uso en Controllers

**Antes:**
```php
public function store(Request $request) {
    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users',
    ]);

    User::create($request->all());
}
```

**Después:**
```php
use App\Http\Requests\User\StoreUserRequest;

public function store(StoreUserRequest $request) {
    User::create($request->validated());
}
```

### Form Requests Disponibles

| Entidad | Store | Update |
|---------|-------|--------|
| **User** | `StoreUserRequest` | `UpdateUserRequest` |
| **Customer** | `StoreCustomerRequest` | `UpdateCustomerRequest` |
| **Restaurant** | `StoreRestaurantRequest` | `UpdateRestaurantRequest` |
| **Role** | `StoreRoleRequest` | `UpdateRoleRequest` |
| **CustomerType** | `StoreCustomerTypeRequest` | `UpdateCustomerTypeRequest` |

---

## 🔧 Traits para Controllers

### 1. HasDataTableFeatures

**Propósito**: Centralizar lógica de paginación, búsqueda y ordenamiento

**Uso:**
```php
use App\Http\Controllers\Concerns\HasDataTableFeatures;

class UserController extends Controller
{
    use HasDataTableFeatures;

    public function index(Request $request)
    {
        // Obtener parámetros
        $params = $this->getPaginationParams($request);

        // Construir query
        $query = User::with('roles');

        // Aplicar búsqueda
        $query = $this->applySearch($query, $params['search'], [
            'name',
            'email',
            'roles' => function($q, $search) {
                $q->where('name', 'like', "%{$search}%");
            }
        ]);

        // Aplicar ordenamiento
        if (!empty($params['multiple_sort_criteria'])) {
            $query = $this->applyMultipleSorting($query, $params['multiple_sort_criteria'], [
                'user' => 'name',
                'status' => $this->getStatusSortExpression('asc')
            ]);
        }

        $users = $query->paginate($params['per_page']);

        return Inertia::render('users/index', [
            'users' => $users,
            'filters' => $this->buildFiltersResponse($params),
        ]);
    }
}
```

**Métodos disponibles:**
- `getPaginationParams($request)` - Extrae params de paginación
- `applySearch($query, $term, $fields)` - Búsqueda en campos/relaciones
- `applyMultipleSorting($query, $criteria, $mappings)` - Ordenamiento múltiple
- `applySorting($query, $field, $direction, $mappings)` - Ordenamiento simple
- `getStatusSortExpression($direction)` - SQL para ordenar por status
- `buildFiltersResponse($params)` - Response para frontend

### 2. HandlesExceptions

**Propósito**: Manejo consistente de excepciones

**Uso:**
```php
use App\Http\Controllers\Concerns\HandlesExceptions;

class UserController extends Controller
{
    use HandlesExceptions;

    public function store(StoreUserRequest $request)
    {
        return $this->executeWithExceptionHandling(
            operation: fn() => $this->createUser($request),
            context: 'crear',
            entity: 'usuario'
        );
    }

    // O manualmente:
    public function destroy(User $user)
    {
        try {
            $user->delete();
            return $this->redirectWithSuccess('Usuario eliminado');
        } catch (QueryException $e) {
            return $this->handleDatabaseException($e, 'eliminar', 'usuario');
        }
    }
}
```

**Métodos disponibles:**
- `handleDatabaseException($e, $context, $entity)` - Maneja errores DB
- `handleGeneralException($e, $context, $entity)` - Catch-all
- `executeWithExceptionHandling($operation, $context, $entity)` - Wrapper
- `redirectWithSuccess($message)` - Helper para success
- `redirectWithError($message)` - Helper para error

---

## 🎨 Traits para Models

### TracksUserStatus

**Propósito**: Lógica compartida de status online/offline

**Uso:**
```php
use App\Models\Concerns\TracksUserStatus;

class User extends Authenticatable
{
    use TracksUserStatus;

    protected $appends = ['status', 'is_online'];
}
```

**Métodos disponibles:**
- `isOnline()` - Boolean si está online (< 5 min)
- `$model->is_online` - Accessor automático
- `$model->status` - Accessor: 'never'|'online'|'recent'|'offline'
- `updateLastActivity()` - Actualiza timestamp
- `updateLastLogin()` - Actualiza login

**Scopes disponibles:**
```php
User::online()->get(); // Usuarios online
User::withStatus('recent')->get(); // Por status específico
User::recentlyActive()->get(); // Última hora
User::inactive(30)->get(); // Sin actividad en 30 días
```

---

## 🚀 Services

### 1. ActivityLogService

**Propósito**: Logging centralizado de actividades

**Uso:**
```php
use App\Services\ActivityLogService;

class UserController extends Controller
{
    public function __construct(
        protected ActivityLogService $activityLog
    ) {}

    public function store(StoreUserRequest $request)
    {
        $user = User::create($request->validated());

        // Log automático
        $this->activityLog->logCreated($user);

        return redirect()->route('users.index');
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $oldValues = $user->only(['name', 'email']);
        $user->update($request->validated());
        $newValues = $user->only(['name', 'email']);

        // Log con detección de cambios
        $this->activityLog->logUpdated($user, $oldValues, $newValues);

        return back();
    }
}
```

**Métodos disponibles:**
- `logCreated($model, ?$description)` - Log de creación
- `logUpdated($model, $oldValues, $newValues, ?$description)` - Con cambios
- `logDeleted($model, ?$description)` - Log de eliminación
- `logRoleUsersUpdate($role, $oldIds, $newIds)` - Específico roles
- `logCustomEvent($type, $model, $id, $description, $old, $new)` - Personalizado
- `getModelActivityLog($model, $limit)` - Consultar logs
- `getUserActivityLog($userId, $limit)` - Logs de usuario

### 2. DataTableService

**Propósito**: Construcción de queries complejos para DataTables

**Uso:**
```php
use App\Services\DataTableService;

class UserController extends Controller
{
    public function __construct(
        protected DataTableService $dataTable
    ) {}

    public function index(Request $request)
    {
        $query = User::with('roles');

        $config = [
            'searchable_fields' => ['name', 'email'],
            'field_mappings' => [
                'user' => 'name',
                'status' => 'CASE WHEN last_activity_at >= NOW() - INTERVAL 5 MINUTE THEN 1 ELSE 2 END'
            ],
            'default_sort' => [
                'field' => 'created_at',
                'direction' => 'desc'
            ],
        ];

        $query = $this->dataTable->buildQuery($query, $config, $request);

        $users = $query->paginate($request->get('per_page', 10));

        return Inertia::render('users/index', [
            'users' => $users,
            'filters' => $this->dataTable->preparePaginationResponse($users, $request)['filters'],
        ]);
    }
}
```

**Métodos disponibles:**
- `buildQuery($query, $config, $request)` - Constructor completo
- `applyFilters($query, $filters)` - Filtros dinámicos
- `getStatsForEntity($modelClass, $statsConfig)` - Estadísticas
- `preparePaginationResponse($paginator, $request)` - Response completa
- `transformCollection($collection, $transformer)` - Transformación

### 3. PermissionDiscoveryService

**Uso mejorado:**
```php
use App\Services\PermissionDiscoveryService;

$service = new PermissionDiscoveryService();

// Descubrir páginas (con cache)
$pages = $service->discoverPages();

// Sincronizar permisos
$result = $service->syncPermissions(removeObsolete: false);
// Resultado: ['created' => 5, 'updated' => 2, 'deleted' => 0, ...]

// Limpiar cache manualmente
$service->clearCache();

// Sin usar cache (útil durante desarrollo)
$permissions = $service->generatePermissions(useCache: false);
```

---

## 📋 Checklist para Refactorizar un Controller

- [ ] Reemplazar validaciones inline con Form Requests
- [ ] Agregar `use HasDataTableFeatures` si tiene método `index()`
- [ ] Agregar `use HandlesExceptions` para manejo de errores
- [ ] Inyectar `ActivityLogService` si loguea actividades
- [ ] Usar `executeWithExceptionHandling()` en métodos CRUD
- [ ] Simplificar método `index()` usando métodos del trait
- [ ] Actualizar tests si es necesario

---

## 🎯 Ejemplo Completo: Refactorizar Controller

**Antes (UserController.php):**
```php
class UserController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search', '');
        $perPage = $request->get('per_page', 10);

        $query = User::with('roles');

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->paginate($perPage);

        return Inertia::render('users/index', ['users' => $users]);
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users',
            ]);

            User::create($request->all());

            return redirect()->route('users.index')->with('success', 'Usuario creado');
        } catch (QueryException $e) {
            return back()->with('error', 'Error al crear usuario');
        }
    }
}
```

**Después:**
```php
use App\Http\Controllers\Concerns\{HasDataTableFeatures, HandlesExceptions};
use App\Http\Requests\User\{StoreUserRequest, UpdateUserRequest};
use App\Services\ActivityLogService;

class UserController extends Controller
{
    use HasDataTableFeatures, HandlesExceptions;

    public function __construct(
        protected ActivityLogService $activityLog
    ) {}

    public function index(Request $request)
    {
        $params = $this->getPaginationParams($request);
        $query = User::with('roles');

        $query = $this->applySearch($query, $params['search'], ['name', 'email']);

        $users = $query->paginate($params['per_page']);

        return Inertia::render('users/index', [
            'users' => $users,
            'filters' => $this->buildFiltersResponse($params),
        ]);
    }

    public function store(StoreUserRequest $request)
    {
        return $this->executeWithExceptionHandling(
            operation: function() use ($request) {
                $user = User::create($request->validated());
                $this->activityLog->logCreated($user);

                return redirect()->route('users.index')
                    ->with('success', 'Usuario creado exitosamente');
            },
            context: 'crear',
            entity: 'usuario'
        );
    }
}
```

**Resultado:**
- ✅ ~40% menos líneas de código
- ✅ Validación centralizada
- ✅ Manejo consistente de errores
- ✅ Logging automático
- ✅ Más fácil de mantener

---

## 🔍 Testing

Los tests existentes deben seguir funcionando sin cambios, ya que la refactorización mantiene la misma funcionalidad.

```bash
# Ejecutar tests específicos
php artisan test --filter=UserControllerTest

# Ejecutar todos los tests
php artisan test
```

---

## 📝 Notas Importantes

1. **Form Requests** ya incluyen mensajes en español
2. **Traits** no tienen dependencias entre sí, úsalos independientemente
3. **Services** usan inyección de dependencias, agrégalos al constructor
4. **Cache** en PermissionDiscoveryService se limpia automáticamente en sync
5. **TracksUserStatus** debe agregarse a `$appends` en el modelo

---

**Última actualización**: 2025-09-30
