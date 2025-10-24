# Sistema de Activity Logging

Documentación completa del sistema centralizado de registro de actividades (activity logging) implementado en la aplicación.

## Índice

1. [Introducción](#introducción)
2. [Arquitectura del Sistema](#arquitectura-del-sistema)
3. [Cómo Implementar en Nuevos Modelos](#cómo-implementar-en-nuevos-modelos)
4. [Configuración](#configuración)
5. [Frontend](#frontend)
6. [Ejemplos](#ejemplos)
7. [Troubleshooting](#troubleshooting)

---

## Introducción

El sistema de activity logging registra automáticamente todas las operaciones de **crear**, **editar** y **eliminar** realizadas por usuarios autenticados en la interfaz web.

### Características:
- ✅ Registro automático de actividades
- ✅ Traducciones centralizadas
- ✅ Configuración modular por modelo
- ✅ Solo 1 línea de código para agregar a nuevos modelos
- ✅ Detección automática de cambios con valores antes/después
- ✅ Filtrado inteligente (ignora timestamps, comandos artisan, etc.)

### Modelos con Activity Logging Activo:
- User
- Role
- Customer
- CustomerType
- Restaurant
- Category (Menú)
- Section (Menú)
- Product (Menú)
- Combo (Menú)
- Promotion (Menú)

---

## Arquitectura del Sistema

### Componentes Principales:

```
app/
├── Models/
│   ├── Concerns/
│   │   └── LogsActivity.php          # Trait para modelos
│   └── [Modelo].php                  # Usa el trait
├── Observers/
│   └── ActivityObserver.php          # Observer genérico
└── Http/Controllers/
    └── ActivityController.php        # Controlador de vista

config/
└── activity.php                      # Configuración centralizada

resources/
├── js/
│   ├── config/
│   │   └── activity-config.ts        # Config frontend
│   └── pages/
│       └── activity/
│           └── index.tsx             # Vista de actividades

database/
└── migrations/
    └── *_create_initial_schema.php   # Tabla activity_logs
```

### Flujo de Funcionamiento:

```
1. Usuario hace una acción en la web (crear/editar/eliminar)
   ↓
2. Eloquent dispara evento (created/updated/deleted)
   ↓
3. LogsActivity trait registra el ActivityObserver
   ↓
4. ActivityObserver captura el evento
   ↓
5. Verifica que sea una acción web válida (isWebUserAction)
   ↓
6. Crea registro en tabla activity_logs
   ↓
7. Frontend muestra en /activity
```

---

## Cómo Implementar en Nuevos Modelos

### Paso 1: Agregar el Trait al Modelo

Solo necesitas **1 línea de código**:

```php
<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class TuNuevoModelo extends Model
{
    use LogsActivity; // ← AGREGAR ESTA LÍNEA

    // ... resto del modelo
}
```

**¡Eso es todo!** El modelo ya registrará automáticamente todas sus actividades.

### Paso 2 (Opcional): Personalizar Configuración

Si tu modelo usa un campo diferente a `name`, `title` o `email` para identificarse:

```php
class TuNuevoModelo extends Model
{
    use LogsActivity;

    /**
     * Campo usado como identificador en los logs
     */
    protected function getActivityLabelField(): string
    {
        return 'codigo'; // Si tu modelo usa 'codigo' en vez de 'name'
    }

    /**
     * Campos adicionales a ignorar en el logging
     */
    protected function getActivityIgnoredFields(): array
    {
        return array_merge(parent::getActivityIgnoredFields(), [
            'internal_cache',
            'temp_data',
        ]);
    }
}
```

### Paso 3: Agregar Traducción al Config

Edita `config/activity.php` y agrega tu modelo:

```php
'models' => [
    // ... otros modelos

    'App\\Models\\TuNuevoModelo' => [
        'name' => 'Tu Nuevo Modelo', // Nombre en español
        'color' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300',
    ],
],
```

### Paso 4: Agregar Tipos de Evento al Frontend

Edita `resources/js/config/activity-config.ts`:

```typescript
const EVENT_TYPES = {
    // ... otros eventos

    tu_nuevo_modelo_created: 'Modelo Creado',
    tu_nuevo_modelo_updated: 'Modelo Actualizado',
    tu_nuevo_modelo_deleted: 'Modelo Eliminado',
};

const EVENT_COLORS = {
    // ... otros colores

    tu_nuevo_modelo_created: 'bg-purple-100 text-purple-800',
    tu_nuevo_modelo_updated: 'bg-blue-100 text-blue-800',
    tu_nuevo_modelo_deleted: 'bg-red-100 text-red-800',
};
```

---

## Configuración

### Backend: `config/activity.php`

```php
return [
    // Traducciones de tipos de evento
    'event_types' => [
        'created' => 'Creado',
        'updated' => 'Actualizado',
        'deleted' => 'Eliminado',
        'restored' => 'Restaurado',
        'force_deleted' => 'Eliminado Permanentemente',
    ],

    // Configuración por modelo
    'models' => [
        'App\\Models\\Menu\\Product' => [
            'name' => 'Producto',
            'color' => 'bg-blue-100 text-blue-800',
        ],
        // ... más modelos
    ],

    // Traducciones de campos
    'field_translations' => [
        'name' => 'nombre',
        'email' => 'correo',
        'is_active' => 'estado activo',
        'description' => 'descripción',
        // ... más campos
    ],

    // Colores por tipo de evento
    'event_colors' => [
        'created' => 'bg-green-100 text-green-800',
        'updated' => 'bg-blue-100 text-blue-800',
        'deleted' => 'bg-red-100 text-red-800',
        // ... más colores
    ],
];
```

### Frontend: `resources/js/config/activity-config.ts`

```typescript
const EVENT_TYPES = {
    created: 'Creado',
    updated: 'Actualizado',
    deleted: 'Eliminado',
    // ... más eventos
};

const EVENT_COLORS = {
    created: 'bg-green-100 text-green-800',
    updated: 'bg-blue-100 text-blue-800',
    deleted: 'bg-red-100 text-red-800',
    // ... más colores
};

export const ACTIVITY_CONFIG = {
    getLabel: (type: string): string => EVENT_TYPES[type] || type,
    getColor: (type: string): string => EVENT_COLORS[type] || 'bg-gray-100 text-gray-800',
};
```

---

## Frontend

### Vista Principal: `/activity`

La página de actividades muestra:
- Tabla con todas las actividades recientes
- Filtros por usuario, tipo de evento y rango de fechas
- Búsqueda por descripción, nombre de usuario o email
- Paginación
- Estadísticas (total de eventos, usuarios únicos, eventos hoy)

### Acceso:
```
http://tu-dominio/activity
```

### Permisos:
El usuario debe tener el permiso `activity.view` para acceder a esta página.

---

## Ejemplos

### Ejemplo 1: Agregar Logging a un Modelo Existente

**Antes:**
```php
class Invoice extends Model
{
    use HasFactory;

    protected $fillable = ['number', 'amount', 'customer_id'];
}
```

**Después:**
```php
use App\Models\Concerns\LogsActivity;

class Invoice extends Model
{
    use HasFactory, LogsActivity; // ← Agregar LogsActivity

    protected $fillable = ['number', 'amount', 'customer_id'];
}
```

Resultado: Ahora cada vez que se crea, edita o elimina una factura, se registra automáticamente.

### Ejemplo 2: Modelo con Campo Personalizado

```php
use App\Models\Concerns\LogsActivity;

class Order extends Model
{
    use LogsActivity;

    // Este modelo usa 'order_number' en vez de 'name'
    protected function getActivityLabelField(): string
    {
        return 'order_number';
    }
}
```

Resultado en logs:
```
"Orden '#12345' fue creado"  // Usa order_number
```

### Ejemplo 3: Ver Actividades en la Base de Datos

```php
// Obtener últimas 10 actividades
$activities = ActivityLog::with('user')
    ->latest()
    ->limit(10)
    ->get();

// Actividades de un modelo específico
$productActivities = ActivityLog::where('target_model', Product::class)
    ->where('target_id', 5)
    ->get();

// Actividades de un usuario
$userActivities = ActivityLog::where('user_id', 1)
    ->orderBy('created_at', 'desc')
    ->get();

// Actividades de hoy
$todayActivities = ActivityLog::whereDate('created_at', today())->get();
```

---

## Troubleshooting

### Problema: No se registran actividades

**Solución 1:** Verifica que el modelo tenga el trait
```php
class TuModelo extends Model
{
    use LogsActivity; // ¿Está presente?
}
```

**Solución 2:** Verifica que estés autenticado
```php
// El usuario debe estar autenticado
auth()->check(); // Debe retornar true
```

**Solución 3:** Verifica que sea una petición web HTTP
Las actividades NO se registran si:
- Es un comando artisan (`php artisan`)
- Es una petición GET (solo POST, PUT, PATCH, DELETE)
- No hay user agent (navegador)

**Solución 4:** Revisa los logs de Laravel
```bash
tail -f storage/logs/laravel.log
```

Busca mensajes de error como:
```
[ERROR] Error logging activity: ...
```

### Problema: Aparecen actividades duplicadas

**Causa:** El modelo podría tener observers registrados manualmente además del trait.

**Solución:** Verifica `app/Providers/AppServiceProvider.php` y elimina registros manuales:

```php
// ❌ ELIMINAR (el trait lo hace automáticamente)
public function boot(): void
{
    Product::observe(ActivityObserver::class);
}

// ✅ CORRECTO (dejar vacío)
public function boot(): void
{
    //
}
```

### Problema: La descripción muestra "Sin identificador"

**Causa:** El modelo no tiene los campos `name`, `title` o `email`.

**Solución:** Implementa `getActivityLabelField()` en tu modelo:

```php
protected function getActivityLabelField(): string
{
    return 'tu_campo_identificador';
}
```

### Problema: Se registran cambios en campos que quiero ignorar

**Solución:** Agrega los campos a la lista de ignorados:

```php
protected function getActivityIgnoredFields(): array
{
    return array_merge(parent::getActivityIgnoredFields(), [
        'campo_a_ignorar_1',
        'campo_a_ignorar_2',
    ]);
}
```

Por defecto se ignoran:
- `updated_at`
- `last_activity_at`
- `last_login_at`
- `remember_token`
- `password`

---

## Estructura de la Base de Datos

### Tabla: `activity_logs`

```sql
CREATE TABLE `activity_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `event_type` varchar(100) NOT NULL,
  `target_model` varchar(255) DEFAULT NULL,
  `target_id` bigint(20) unsigned DEFAULT NULL,
  `description` text NOT NULL,
  `old_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `activity_logs_user_id_foreign` (`user_id`),
  KEY `activity_logs_user_id_created_at_index` (`user_id`,`created_at`),
  KEY `activity_logs_event_type_created_at_index` (`event_type`,`created_at`),
  KEY `activity_logs_target_model_target_id_index` (`target_model`,`target_id`),
  KEY `activity_logs_created_at_index` (`created_at`)
);
```

### Campos:

- **user_id**: ID del usuario que realizó la acción
- **event_type**: Tipo de evento (`created`, `updated`, `deleted`, etc.)
- **target_model**: Clase completa del modelo (`App\Models\Product`)
- **target_id**: ID del registro afectado
- **description**: Descripción legible en español
- **old_values**: Valores anteriores (JSON) - solo para `updated`
- **new_values**: Valores nuevos (JSON) - para `created` y `updated`
- **user_agent**: Navegador/dispositivo del usuario
- **created_at**: Fecha/hora del evento

---
