# Plan de Implementación: API de Motoristas

## Información del Proyecto

| Atributo | Valor |
|----------|-------|
| Fecha de creación | 2026-02-04 |
| Fecha de finalización | 2026-02-04 |
| Framework | Laravel 12 + Sanctum |
| Estado | ✅ COMPLETADO |
| Versión API | v1 |
| Tests | 83 pasando (402 assertions) |
| Documentación | [docs/API_MOTORISTAS.md](docs/API_MOTORISTAS.md) |

---

## Estado del Sistema Existente

### Componentes Disponibles
- ✅ Modelo `Driver` con relaciones y scopes básicos
- ✅ Guard `driver` configurado en Sanctum
- ✅ `DriverService` con operaciones CRUD
- ✅ Campos `driver_id`, `assigned_to_driver_at` en tabla `orders`
- ✅ Constantes de estado en `Order` model
- ✅ `OrderStatusHistory` para auditoría

### Brechas Identificadas
- ⚠️ Modelo Driver sin trait `HasApiTokens`
- ⚠️ Sin validación de orden activa única por driver
- ⚠️ Sin API endpoints para drivers
- ⚠️ Sin validación de proximidad GPS
- ⚠️ Sin middlewares específicos para API driver

---

## Arquitectura de Agentes

```
┌─────────────────────────────────────────────────────────────────────┐
│                        TECH LEAD (Coordinador)                      │
└─────────────────────────────────────────────────────────────────────┘
                                    │
         ┌──────────────────────────┼──────────────────────────┐
         │                          │                          │
         ▼                          ▼                          ▼
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   AGENTE A      │    │   AGENTE B      │    │   AGENTE C      │
│ Database/Models │    │ Business Logic  │    │   API Core      │
├─────────────────┤    ├─────────────────┤    ├─────────────────┤
│ - Migraciones   │    │ - Services      │    │ - Controllers   │
│ - Factories     │    │ - Validaciones  │    │ - Form Requests │
│ - Seeders       │    │ - GPS Logic     │    │ - Resources     │
│ - Model updates │    │ - State Machine │    │ - Routes        │
└─────────────────┘    └─────────────────┘    └─────────────────┘
         │                          │                          │
         └──────────────────────────┼──────────────────────────┘
                                    ▼
                        ┌─────────────────┐
                        │   AGENTE D      │
                        │   QA & Testing  │
                        ├─────────────────┤
                        │ - Feature Tests │
                        │ - Unit Tests    │
                        │ - Validaciones  │
                        └─────────────────┘
```

---

## FASE 1: Autenticación y Base de Datos

### Objetivo
Establecer la infraestructura base para autenticación de motoristas vía API.

### Agente A - Database & Models

#### Tareas
| ID | Tarea | Archivo | Estado |
|----|-------|---------|--------|
| A1.1 | Agregar trait `HasApiTokens` al modelo Driver | `app/Models/Driver.php` | ⬜ Pendiente |
| A1.2 | Agregar método `hasActiveOrder()` al modelo | `app/Models/Driver.php` | ⬜ Pendiente |
| A1.3 | Agregar scope `withoutActiveOrder()` | `app/Models/Driver.php` | ⬜ Pendiente |
| A1.4 | Verificar/actualizar DriverFactory | `database/factories/DriverFactory.php` | ⬜ Pendiente |
| A1.5 | Crear seeder de prueba para drivers | `database/seeders/DriverSeeder.php` | ⬜ Pendiente |

#### Archivos a Modificar/Crear
```
app/Models/Driver.php                          [MODIFICAR]
database/factories/DriverFactory.php           [VERIFICAR/MODIFICAR]
database/seeders/DriverSeeder.php              [CREAR]
```

---

### Agente B - Business Logic & Services

#### Tareas
| ID | Tarea | Archivo | Estado |
|----|-------|---------|--------|
| B1.1 | Crear DriverAuthService | `app/Services/Driver/DriverAuthService.php` | ⬜ Pendiente |
| B1.2 | Implementar login() con validaciones | `app/Services/Driver/DriverAuthService.php` | ⬜ Pendiente |
| B1.3 | Implementar logout() con revocación | `app/Services/Driver/DriverAuthService.php` | ⬜ Pendiente |
| B1.4 | Implementar refreshToken() | `app/Services/Driver/DriverAuthService.php` | ⬜ Pendiente |
| B1.5 | Agregar método canGoOffline() a DriverService | `app/Services/DriverService.php` | ⬜ Pendiente |

#### Archivos a Modificar/Crear
```
app/Services/Driver/DriverAuthService.php     [CREAR]
app/Services/DriverService.php                [MODIFICAR]
```

---

### Agente C - API Core

#### Tareas
| ID | Tarea | Archivo | Estado |
|----|-------|---------|--------|
| C1.1 | Crear DriverAuthController | `app/Http/Controllers/Api/V1/Driver/AuthController.php` | ⬜ Pendiente |
| C1.2 | Crear DriverLoginRequest | `app/Http/Requests/Api/V1/Driver/DriverLoginRequest.php` | ⬜ Pendiente |
| C1.3 | Crear DriverResource | `app/Http/Resources/Api/V1/Driver/DriverResource.php` | ⬜ Pendiente |
| C1.4 | Crear AuthenticatedDriverResource | `app/Http/Resources/Api/V1/Driver/AuthenticatedDriverResource.php` | ⬜ Pendiente |
| C1.5 | Configurar rutas driver en api.php | `routes/api.php` | ⬜ Pendiente |
| C1.6 | Crear middleware EnsureDriverIsActive | `app/Http/Middleware/EnsureDriverIsActive.php` | ⬜ Pendiente |

#### Archivos a Modificar/Crear
```
app/Http/Controllers/Api/V1/Driver/AuthController.php           [CREAR]
app/Http/Requests/Api/V1/Driver/DriverLoginRequest.php          [CREAR]
app/Http/Resources/Api/V1/Driver/DriverResource.php             [CREAR]
app/Http/Resources/Api/V1/Driver/AuthenticatedDriverResource.php [CREAR]
app/Http/Middleware/EnsureDriverIsActive.php                    [CREAR]
routes/api.php                                                  [MODIFICAR]
bootstrap/app.php                                               [MODIFICAR]
```

---

### Agente D - QA & Testing

#### Tareas
| ID | Tarea | Archivo | Estado |
|----|-------|---------|--------|
| D1.1 | Test de login exitoso | `tests/Feature/Api/V1/Driver/AuthTest.php` | ⬜ Pendiente |
| D1.2 | Test de login con credenciales inválidas | `tests/Feature/Api/V1/Driver/AuthTest.php` | ⬜ Pendiente |
| D1.3 | Test de login con cuenta inactiva | `tests/Feature/Api/V1/Driver/AuthTest.php` | ⬜ Pendiente |
| D1.4 | Test de logout | `tests/Feature/Api/V1/Driver/AuthTest.php` | ⬜ Pendiente |
| D1.5 | Test de me() endpoint | `tests/Feature/Api/V1/Driver/AuthTest.php` | ⬜ Pendiente |
| D1.6 | Test de acceso sin token | `tests/Feature/Api/V1/Driver/AuthTest.php` | ⬜ Pendiente |

#### Archivos a Crear
```
tests/Feature/Api/V1/Driver/AuthTest.php      [CREAR]
```

---

## FASE 2: Core de Entregas

### Objetivo
Implementar la gestión de órdenes asignadas y transiciones de estado.

### Agente A - Database & Models

| ID | Tarea | Archivo |
|----|-------|---------|
| A2.1 | Agregar relación `pendingOrders()` a Driver | `app/Models/Driver.php` |
| A2.2 | Agregar método `canAcceptOrder()` a Order | `app/Models/Order.php` |
| A2.3 | Agregar scope `assignedToDriver()` a Order | `app/Models/Order.php` |
| A2.4 | Crear migración para campo `accepted_by_driver_at` | `database/migrations/` |

### Agente B - Business Logic & Services

| ID | Tarea | Archivo |
|----|-------|---------|
| B2.1 | Crear DriverOrderService | `app/Services/Driver/DriverOrderService.php` |
| B2.2 | Implementar acceptOrder() | `app/Services/Driver/DriverOrderService.php` |
| B2.3 | Implementar completeDelivery() | `app/Services/Driver/DriverOrderService.php` |
| B2.4 | Implementar validateProximity() | `app/Services/Driver/DriverOrderService.php` |
| B2.5 | Crear DriverLocationService | `app/Services/Driver/DriverLocationService.php` |

### Agente C - API Core

| ID | Tarea | Archivo |
|----|-------|---------|
| C2.1 | Crear DriverOrderController | `app/Http/Controllers/Api/V1/Driver/OrderController.php` |
| C2.2 | Crear DriverLocationController | `app/Http/Controllers/Api/V1/Driver/LocationController.php` |
| C2.3 | Crear AcceptOrderRequest | `app/Http/Requests/Api/V1/Driver/AcceptOrderRequest.php` |
| C2.4 | Crear DeliverOrderRequest | `app/Http/Requests/Api/V1/Driver/DeliverOrderRequest.php` |
| C2.5 | Crear UpdateLocationRequest | `app/Http/Requests/Api/V1/Driver/UpdateLocationRequest.php` |
| C2.6 | Crear DriverOrderResource | `app/Http/Resources/Api/V1/Driver/DriverOrderResource.php` |
| C2.7 | Crear middleware EnsureOrderBelongsToDriver | `app/Http/Middleware/EnsureOrderBelongsToDriver.php` |
| C2.8 | Crear middleware EnsureDriverIsAvailable | `app/Http/Middleware/EnsureDriverIsAvailable.php` |

### Agente D - QA & Testing

| ID | Tarea | Archivo |
|----|-------|---------|
| D2.1 | Test listar órdenes pendientes | `tests/Feature/Api/V1/Driver/OrderTest.php` |
| D2.2 | Test aceptar orden | `tests/Feature/Api/V1/Driver/OrderTest.php` |
| D2.3 | Test aceptar con orden activa existente | `tests/Feature/Api/V1/Driver/OrderTest.php` |
| D2.4 | Test completar entrega | `tests/Feature/Api/V1/Driver/OrderTest.php` |
| D2.5 | Test completar entrega fuera de rango | `tests/Feature/Api/V1/Driver/OrderTest.php` |
| D2.6 | Test actualizar ubicación | `tests/Feature/Api/V1/Driver/LocationTest.php` |

---

## FASE 3: Perfil y Disponibilidad

### Objetivo
Gestión del perfil del motorista y control de disponibilidad.

### Agente A - Database & Models

| ID | Tarea | Archivo |
|----|-------|---------|
| A3.1 | Agregar accessor `rating` a Driver | `app/Models/Driver.php` |
| A3.2 | Agregar accessor `total_deliveries` a Driver | `app/Models/Driver.php` |

### Agente B - Business Logic & Services

| ID | Tarea | Archivo |
|----|-------|---------|
| B3.1 | Crear DriverProfileService | `app/Services/Driver/DriverProfileService.php` |
| B3.2 | Implementar updateProfile() | `app/Services/Driver/DriverProfileService.php` |
| B3.3 | Implementar changePassword() | `app/Services/Driver/DriverProfileService.php` |
| B3.4 | Implementar toggleAvailability() con validaciones | `app/Services/Driver/DriverProfileService.php` |

### Agente C - API Core

| ID | Tarea | Archivo |
|----|-------|---------|
| C3.1 | Crear DriverProfileController | `app/Http/Controllers/Api/V1/Driver/ProfileController.php` |
| C3.2 | Crear UpdateProfileRequest | `app/Http/Requests/Api/V1/Driver/UpdateProfileRequest.php` |
| C3.3 | Crear ToggleAvailabilityRequest | `app/Http/Requests/Api/V1/Driver/ToggleAvailabilityRequest.php` |

### Agente D - QA & Testing

| ID | Tarea | Archivo |
|----|-------|---------|
| D3.1 | Test obtener perfil | `tests/Feature/Api/V1/Driver/ProfileTest.php` |
| D3.2 | Test actualizar perfil | `tests/Feature/Api/V1/Driver/ProfileTest.php` |
| D3.3 | Test toggle disponibilidad | `tests/Feature/Api/V1/Driver/ProfileTest.php` |
| D3.4 | Test desconectarse con orden activa | `tests/Feature/Api/V1/Driver/ProfileTest.php` |

---

## FASE 4: Historial y Métricas

### Objetivo
Consulta de historial de entregas y métricas de rendimiento.

### Agente A - Database & Models

| ID | Tarea | Archivo |
|----|-------|---------|
| A4.1 | Agregar scope `completedByDriver()` a Order | `app/Models/Order.php` |
| A4.2 | Optimizar queries con índices si es necesario | `database/migrations/` |

### Agente B - Business Logic & Services

| ID | Tarea | Archivo |
|----|-------|---------|
| B4.1 | Crear DriverHistoryService | `app/Services/Driver/DriverHistoryService.php` |
| B4.2 | Crear DriverStatsService | `app/Services/Driver/DriverStatsService.php` |
| B4.3 | Implementar cálculo de métricas | `app/Services/Driver/DriverStatsService.php` |

### Agente C - API Core

| ID | Tarea | Archivo |
|----|-------|---------|
| C4.1 | Crear DriverHistoryController | `app/Http/Controllers/Api/V1/Driver/HistoryController.php` |
| C4.2 | Crear DriverStatsController | `app/Http/Controllers/Api/V1/Driver/StatsController.php` |
| C4.3 | Crear DriverHistoryResource | `app/Http/Resources/Api/V1/Driver/DriverHistoryResource.php` |
| C4.4 | Crear DriverStatsResource | `app/Http/Resources/Api/V1/Driver/DriverStatsResource.php` |

### Agente D - QA & Testing

| ID | Tarea | Archivo |
|----|-------|---------|
| D4.1 | Test historial paginado | `tests/Feature/Api/V1/Driver/HistoryTest.php` |
| D4.2 | Test historial con filtros | `tests/Feature/Api/V1/Driver/HistoryTest.php` |
| D4.3 | Test métricas diarias | `tests/Feature/Api/V1/Driver/StatsTest.php` |
| D4.4 | Test métricas mensuales | `tests/Feature/Api/V1/Driver/StatsTest.php` |

---

## Estructura de Directorios Final

```
app/
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       └── V1/
│   │           └── Driver/
│   │               ├── AuthController.php
│   │               ├── ProfileController.php
│   │               ├── OrderController.php
│   │               ├── LocationController.php
│   │               ├── HistoryController.php
│   │               └── StatsController.php
│   ├── Middleware/
│   │   ├── EnsureDriverIsActive.php
│   │   ├── EnsureDriverIsAvailable.php
│   │   └── EnsureOrderBelongsToDriver.php
│   ├── Requests/
│   │   └── Api/
│   │       └── V1/
│   │           └── Driver/
│   │               ├── DriverLoginRequest.php
│   │               ├── UpdateProfileRequest.php
│   │               ├── ToggleAvailabilityRequest.php
│   │               ├── AcceptOrderRequest.php
│   │               ├── DeliverOrderRequest.php
│   │               └── UpdateLocationRequest.php
│   └── Resources/
│       └── Api/
│           └── V1/
│               └── Driver/
│                   ├── DriverResource.php
│                   ├── AuthenticatedDriverResource.php
│                   ├── DriverOrderResource.php
│                   ├── DriverOrderDetailResource.php
│                   ├── DriverHistoryResource.php
│                   └── DriverStatsResource.php
├── Services/
│   ├── DriverService.php (existente, modificar)
│   └── Driver/
│       ├── DriverAuthService.php
│       ├── DriverProfileService.php
│       ├── DriverOrderService.php
│       ├── DriverLocationService.php
│       ├── DriverHistoryService.php
│       └── DriverStatsService.php
└── Models/
    └── Driver.php (modificar)

tests/
└── Feature/
    └── Api/
        └── V1/
            └── Driver/
                ├── AuthTest.php
                ├── ProfileTest.php
                ├── OrderTest.php
                ├── LocationTest.php
                ├── HistoryTest.php
                └── StatsTest.php
```

---

## Convenciones de Código

### Respuestas API Estándar

```php
// Éxito
return response()->json([
    'success' => true,
    'data' => $resource,
    'message' => 'Operación exitosa'
], 200);

// Error
return response()->json([
    'success' => false,
    'message' => 'Descripción del error',
    'error_code' => 'ERROR_CODE',
    'errors' => [] // Opcional, para validación
], 4xx);
```

### Nomenclatura
- Controllers: `{Entity}Controller`
- Services: `{Entity}Service` o `Driver{Feature}Service`
- Resources: `{Entity}Resource` o `Driver{Feature}Resource`
- Requests: `{Action}{Entity}Request`
- Tests: `{Feature}Test`

---

## Criterios de Aceptación por Fase

### Fase 1 ✓
- [ ] Driver puede autenticarse con email/password
- [ ] Token Sanctum se genera correctamente
- [ ] Driver puede ver su información con /me
- [ ] Driver puede hacer logout
- [ ] Cuentas inactivas no pueden autenticarse
- [ ] Todos los tests pasan

### Fase 2 ✓
- [ ] Driver puede ver órdenes asignadas pendientes
- [ ] Driver puede aceptar una orden (ready → out_for_delivery)
- [ ] Driver NO puede aceptar si ya tiene orden activa
- [ ] Driver puede completar entrega (validando proximidad GPS)
- [ ] Ubicación se actualiza correctamente
- [ ] Todos los tests pasan

### Fase 3 ✓
- [ ] Driver puede ver y editar su perfil
- [ ] Driver puede cambiar contraseña
- [ ] Driver puede toggle disponibilidad
- [ ] Driver NO puede desconectarse con orden activa
- [ ] Todos los tests pasan

### Fase 4 ✓
- [ ] Driver puede ver historial paginado
- [ ] Driver puede filtrar historial por fecha
- [ ] Driver puede ver métricas consolidadas
- [ ] Métricas calculan correctamente
- [ ] Todos los tests pasan

---

## Notas de Implementación

1. **Consistencia con API existente**: Seguir patrones de `app/Http/Controllers/Api/V1/` existentes
2. **Rate Limiting**: Ubicación tiene límite mayor (120/min vs 60/min)
3. **Proximidad GPS**: Usar fórmula Haversine, umbral de 500m
4. **Transiciones de estado**: Registrar en `OrderStatusHistory`
5. **Notificaciones**: Disparar eventos para notificar a clientes

---

*Documento generado automáticamente - Tech Lead Coordinator*
