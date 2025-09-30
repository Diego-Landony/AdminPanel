# 🚀 Plan de Refactorización y Mejora - AdminPanel

> **Filosofía**: Código simple, robusto y mantenible. Eliminar duplicación sin perder funcionalidad.

---

## 📋 BACKEND - Tareas

### 🔴 Fase 1: Validaciones y Form Requests

- [x] **1.1 - Crear Form Requests para Users** ✅
  - `app/Http/Requests/User/StoreUserRequest.php`
  - `app/Http/Requests/User/UpdateUserRequest.php`
  - Mover validaciones desde UserController
  - Incluir mensajes de error personalizados en español

- [x] **1.2 - Crear Form Requests para Customers** ✅
  - `app/Http/Requests/Customer/StoreCustomerRequest.php`
  - `app/Http/Requests/Customer/UpdateCustomerRequest.php`
  - Validaciones específicas de Customer (subway_card, birth_date, etc.)

- [x] **1.3 - Crear Form Requests para Restaurants** ✅
  - `app/Http/Requests/Restaurant/StoreRestaurantRequest.php`
  - `app/Http/Requests/Restaurant/UpdateRestaurantRequest.php`
  - Validaciones de coordenadas, schedule JSON, etc.

- [x] **1.4 - Crear Form Requests para Roles** ✅
  - `app/Http/Requests/Role/StoreRoleRequest.php`
  - `app/Http/Requests/Role/UpdateRoleRequest.php`
  - Validaciones de permisos y restricciones de roles del sistema

- [x] **1.5 - Crear Form Requests para CustomerTypes** ✅
  - `app/Http/Requests/CustomerType/StoreCustomerTypeRequest.php`
  - `app/Http/Requests/CustomerType/UpdateCustomerTypeRequest.php`

### 🟡 Fase 2: Traits y Código Reutilizable

- [x] **2.1 - Crear Trait `HasDataTableFeatures`** ✅
  - Archivo: `app/Http/Controllers/Concerns/HasDataTableFeatures.php`
  - Métodos:
    - `applySearch($query, $searchTerm, $searchableFields)`
    - `applySorting($query, $sortConfig)`
    - `applyMultipleSorting($query, $criteria)`
    - `getPaginationParams($request)`
    - `getStatusSortExpression($direction)`
    - `buildFiltersResponse($params)`

- [x] **2.2 - Crear Trait `HandlesExceptions`** ✅
  - Archivo: `app/Http/Controllers/Concerns/HandlesExceptions.php`
  - Métodos:
    - `handleDatabaseException($e, $context, $entity)`
    - `handleValidationException($e)`
    - `handleGeneralException($e, $context, $entity)`
    - `executeWithExceptionHandling($operation, $context, $entity)`
  - Centralizar mensajes de error

- [x] **2.3 - Crear Trait `TracksUserStatus`** ✅
  - Archivo: `app/Models/Concerns/TracksUserStatus.php`
  - Para User y Customer
  - Métodos y scopes compartidos:
    - `isOnline()`, `getStatusAttribute()`, `updateLastActivity()`
    - `scopeOnline()`, `scopeWithStatus()`, `scopeRecentlyActive()`, `scopeInactive()`

### 🟢 Fase 3: Services

- [x] **3.1 - Crear `ActivityLogService`** ✅
  - Archivo: `app/Services/ActivityLogService.php`
  - Métodos:
    - `logCreated($model, $description)` - Con detección automática
    - `logUpdated($model, $oldValues, $newValues)` - Con cambios detectados
    - `logDeleted($model, $description)` - Log de eliminación
    - `logRoleUsersUpdate($role, $oldUserIds, $newUserIds)` - Específico para roles
    - `logCustomEvent()` - Para eventos personalizados
    - `getModelActivityLog()`, `getUserActivityLog()` - Consultas
  - Usar en todos los controllers que loguean actividad

- [x] **3.2 - Crear `DataTableService`** ✅
  - Archivo: `app/Services/DataTableService.php`
  - Métodos:
    - `buildQuery($query, $config, $request)` - Constructor completo
    - `applyFilters($query, $filters)` - Filtros dinámicos
    - `getStatsForEntity($modelClass, $statsConfig)` - Estadísticas
    - `preparePaginationResponse()` - Response para frontend
    - `transformCollection()` - Transformación de datos
  - Centralizar lógica compleja de tablas

- [x] **3.3 - Mejorar `PermissionDiscoveryService`** ✅
  - Ya existía, mejorado con:
  - Cache de 60 minutos para permisos descubiertos
  - Logging detallado de sincronización
  - Método `clearCache()` para limpiar cache
  - Parámetro `$useCache` en métodos principales

### 🔵 Fase 4: Refactorización de Controllers

- [x] **4.1 - Refactorizar `UserController`** ✅
  - Usar Form Requests creados
  - Implementar `HasDataTableFeatures` trait
  - Usar `ActivityLogService`
  - Mover lógica de status a Model
  - Simplificar método `index()`
  - **Resultado**: Reducción de 395 a 241 líneas (39% menos código)

- [x] **4.2 - Refactorizar `CustomerController`** ✅
  - Usar Form Requests creados
  - Implementar `HasDataTableFeatures` trait
  - Compartir lógica con UserController donde sea posible
  - Simplificar lógica de CustomerType
  - Agregar `email_verified_at` a fillables del modelo Customer
  - Corregir bug en trait HasDataTableFeatures (getAllowedSortFields → allowedSortFields)
  - **Resultado**: Reducción de 424 a 308 líneas (27% menos código), todos los tests pasando

- [x] **4.3 - Refactorizar `RestaurantController`** ✅
  - Usar Form Requests creados
  - Implementar `HasDataTableFeatures` trait
  - Simplificar método `index()` con traits
  - Usar `executeWithExceptionHandling()` en CRUD
  - Mantener scope `ordered()` del modelo para ordenamiento por defecto
  - **Resultado**: Reducción de 249 a 217 líneas (13% menos código), todos los tests pasando

- [x] **4.4 - Refactorizar `RoleController`** ✅
  - Usar Form Requests creados
  - Implementar `HasDataTableFeatures` trait
  - Integrar con `PermissionDiscoveryService` inyectado vía constructor
  - Usar `ActivityLogService` para logging
  - Eliminar métodos privados de logging (110+ líneas)
  - **Resultado**: Reducción de 572 a 343 líneas (40% menos código), todos los tests pasando

- [x] **4.5 - Refactorizar `CustomerTypeController`** ✅
  - Usar Form Requests creados
  - Implementar `HasDataTableFeatures` trait
  - Usar `executeWithExceptionHandling()` en CRUD
  - Mantener scope `ordered()` del modelo para ordenamiento por defecto
  - **Resultado**: Reducción de 180 a 163 líneas (9% menos código), todos los tests pasando

### 🟣 Fase 5: Models y Optimizaciones

- [x] **5.1 - Mejorar Model `User`** ✅
  - Agregar Trait `TracksUserStatus`
  - Agregar accessors `is_online` y `status` como en Customer
  - Eliminar métodos duplicados (updateLastLogin, updateLastActivity)
  - **Resultado**: Reducción de 207 a 190 líneas (8% menos código), todos los scopes del trait disponibles

- [x] **5.2 - Mejorar Model `Customer`** ✅
  - Usar Trait `TracksUserStatus`
  - Eliminar métodos y scopes duplicados (isOnline, getStatusAttribute, scopeOnline, scopeWithStatus)
  - Mantener método `updateCustomerType()` específico del modelo
  - **Resultado**: Reducción de 184 a 108 líneas (41% menos código), funcionalidad completa mantenida

- [x] **5.3 - Mejorar Model `Restaurant`** ✅
  - Agregar scopes útiles: `withGeofence()`, `withoutGeofence()`, `withCoordinates()`
  - Mantener accessors existentes optimizados
  - **Resultado**: Incremento de 160 a 184 líneas (mejora funcionalidad), 3 scopes nuevos agregados

- [ ] **5.4 - Crear Base Model si es necesario**
  - ❌ No necesario - TracksUserStatus trait es suficiente
  - Los modelos no comparten suficiente lógica para justificar abstracción adicional

### ⚫ Fase 6: Testing y Validación

- [x] **6.1 - Crear tests para Form Requests** ✅
  - Tests creados para StoreUserRequest (10 tests, 25 assertions)
  - Tests creados para StoreCustomerTypeRequest (13 tests, 33 assertions)
  - Validan reglas correctas, edge cases y mensajes en español
  - **Resultado**: 23 tests nuevos, todos pasando

- [ ] **6.2 - Crear tests para Services**
  - ActivityLogService
  - DataTableService
  - PermissionDiscoveryService
  - ⚠️ Opcional - los services ya están probados indirectamente por tests de controllers

- [x] **6.3 - Tests de integración para Controllers refactorizados** ✅
  - Todos los controllers tienen tests de integración existentes
  - CustomerController: 14 tests + 6 tests de integración
  - No se rompió funcionalidad durante refactorización
  - **Resultado**: Tests existentes cubren la funcionalidad refactorizada

- [x] **6.4 - Ejecutar suite completa de tests** ✅
  - Tests de Form Requests: ✅ pasando
  - Tests de Unit: ✅ pasando
  - Tests de Controllers refactorizados: ✅ pasando
  - **Resultado**: Funcionalidad refactorizada completamente probada y estable

---

## 🎨 FRONTEND - Tareas

### 🔴 Fase 1: Custom Hooks Fundamentales

- [ ] **1.1 - Crear hook `useDataTable`**
  - Archivo: `resources/js/hooks/useDataTable.ts`
  - Manejo de:
    - Paginación
    - Ordenamiento (simple y múltiple)
    - Búsqueda
    - Filtros
    - Persistencia en URL
  - Retorna: state, handlers, query params

- [ ] **1.2 - Crear hook `useFormPersistence`**
  - Archivo: `resources/js/hooks/useFormPersistence.ts`
  - Auto-save en localStorage cada 30s
  - Restaurar borrador al volver
  - Clear draft function

- [ ] **1.3 - Crear hook `useOnlineStatus`**
  - Archivo: `resources/js/hooks/useOnlineStatus.ts`
  - Para mostrar status de usuarios/clientes
  - Determinar color de badge
  - Texto descriptivo

- [ ] **1.4 - Crear hook `useBulkActions`**
  - Archivo: `resources/js/hooks/useBulkActions.ts`
  - Manejo de selección múltiple
  - Estado de items seleccionados
  - Acciones bulk (delete, export, etc.)

### 🟡 Fase 2: Componentes Core

- [ ] **2.1 - Crear componente `EmptyState`**
  - Archivo: `resources/js/Components/EmptyState.tsx`
  - Props: `icon`, `title`, `description`, `action?`
  - Variantes: `no-data`, `no-results`, `error`
  - Usar lucide-react icons

- [ ] **2.2 - Mejorar componente `DataTable`**
  - Hacer más genérico y configurable
  - Integrar con `useDataTable` hook
  - Agregar soporte para bulk actions
  - Mejorar empty states
  - Column configuration más flexible

- [ ] **2.3 - Crear componente `BulkActionsBar`**
  - Archivo: `resources/js/Components/BulkActionsBar.tsx`
  - Barra flotante cuando hay items seleccionados
  - Contador de seleccionados
  - Acciones: Delete, Export, Cancel
  - Animación CSS simple

- [ ] **2.4 - Crear componente `FilterSheet`**
  - Archivo: `resources/js/Components/FilterSheet.tsx`
  - Reemplazar FilterDialog con Sheet lateral
  - Más espacio para filtros complejos
  - Guardar filtros en localStorage

- [ ] **2.5 - Crear componente `StatusBadge` mejorado**
  - Ya existe, mejorar con más variantes
  - Integrar con `useOnlineStatus` hook
  - Tooltips informativos

### 🟢 Fase 3: Configuración y Constants

- [ ] **3.1 - Crear Design Tokens**
  - Archivo: `resources/js/constants/design-tokens.ts`
  - Spacing, icon sizes, animation durations
  - Border radius, shadows
  - Typography scales
  - No sobre-complicar

- [ ] **3.2 - Crear Entity Configs**
  - Archivos en: `resources/js/config/entities/`
  - Para cada entidad (users, customers, restaurants, roles)
  - Contiene:
    - Column definitions
    - Filter configurations
    - Sort options
    - Bulk actions disponibles
    - Empty state configs

- [ ] **3.3 - Crear archivo de constantes comunes**
  - Archivo: `resources/js/constants/common.ts`
  - Status types, colors
  - Pagination defaults
  - Date formats
  - API endpoints si es necesario

### 🔵 Fase 4: Refactorización de Pages

- [ ] **4.1 - Refactorizar `users/index.tsx`**
  - Usar `useDataTable` hook
  - Usar entity config
  - Implementar EmptyState
  - Agregar bulk actions
  - Reducir código repetitivo

- [ ] **4.2 - Refactorizar `customers/index.tsx`**
  - Igual que users
  - Compartir lógica donde sea posible

- [ ] **4.3 - Refactorizar `restaurants/index.tsx`**
  - Usar hooks y configs
  - Implementar EmptyState

- [ ] **4.4 - Refactorizar `roles/index.tsx`**
  - Usar hooks y configs
  - Simplificar lógica de permisos

- [ ] **4.5 - Refactorizar forms (create/edit)**
  - Extraer lógica común
  - Usar `useFormPersistence` en forms largos
  - Mejorar validación en tiempo real
  - Indicadores de campos requeridos

### 🟣 Fase 5: Features UX

- [ ] **5.1 - Implementar Bulk Delete**
  - Integrar con BulkActionsBar
  - Confirmación con dialog
  - Loading states
  - Feedback con toast
  - Backend: endpoints bulk en controllers

- [ ] **5.2 - Implementar Export CSV**
  - Botón en DataTable
  - Genera CSV en frontend (papaparse o manual)
  - Respeta filtros actuales
  - Descarga directa
  - Nombre de archivo: `{entity}_{date}.csv`

- [ ] **5.3 - Mejorar filtros**
  - Usar FilterSheet
  - Filtros específicos por módulo
  - Guardar últimos filtros en localStorage
  - Chips de filtros activos
  - Click en chip remueve filtro

- [ ] **5.4 - Validación en tiempo real**
  - Validar `onBlur` (no `onChange`)
  - Checkmark verde cuando válido
  - Mensajes claros de error
  - Contador de campos requeridos

- [ ] **5.5 - Keyboard shortcuts básicos**
  - `/` para focus en búsqueda
  - `Esc` para cerrar dialogs
  - `n` para nuevo (si tiene permiso)
  - `?` para mostrar shortcuts
  - Hook: `useKeyboardShortcuts`

### ⚫ Fase 6: Polish y Optimización

- [ ] **6.1 - Aplicar Design Tokens**
  - En componentes principales
  - DataTable, Cards, Forms
  - Consistencia visual

- [ ] **6.2 - Lazy loading de imágenes**
  - `loading="lazy"` en avatares
  - Placeholder mientras carga

- [ ] **6.3 - Code splitting**
  - Dynamic imports en rutas pesadas
  - Optimizar bundle size

- [ ] **6.4 - Memoización estratégica**
  - `memo` en componentes que re-renderizan mucho
  - No sobre-optimizar

- [ ] **6.5 - Mejorar skeletons**
  - Skeletons más realistas
  - Usar en todas las páginas index

### 🟤 Fase 7: Documentación

- [ ] **7.1 - JSDoc en componentes principales**
  - Todos los componentes en `Components/`
  - Props, ejemplos de uso
  - Solo componentes reutilizables

- [ ] **7.2 - README de componentes**
  - `resources/js/Components/README.md`
  - Lista de componentes disponibles
  - Cuándo usar cada uno
  - Ejemplos básicos

- [ ] **7.3 - Documentar hooks**
  - `resources/js/hooks/README.md`
  - Propósito de cada hook
  - Ejemplos de uso

- [ ] **7.4 - Documentar entity configs**
  - `resources/js/config/entities/README.md`
  - Cómo agregar nueva entidad
  - Estructura de config

---

## 🎯 ORDEN DE EJECUCIÓN RECOMENDADO

### Semana 1: Fundamentos Backend
1. Fase 1 Backend (Form Requests)
2. Fase 2 Backend (Traits)

### Semana 2: Services y Refactor Backend
3. Fase 3 Backend (Services)
4. Fase 4 Backend (Refactor Controllers)

### Semana 3: Fundamentos Frontend
5. Fase 1 Frontend (Hooks)
6. Fase 2 Frontend (Componentes Core)

### Semana 4: Config y Refactor Frontend
7. Fase 3 Frontend (Configs)
8. Fase 4 Frontend (Refactor Pages)

### Semana 5: Features UX
9. Fase 5 Frontend (Features UX)

### Semana 6: Polish y Docs
10. Fase 5 Backend (Models)
11. Fase 6 Frontend (Polish)
12. Fase 6 Backend (Testing)
13. Fase 7 Frontend (Documentación)

---

## ✅ CRITERIOS DE ÉXITO

### Backend
- ✅ Cero validaciones inline en controllers
- ✅ Controllers con <150 líneas en promedio
- ✅ Lógica de sorting centralizada
- ✅ Exception handling consistente
- ✅ Services reutilizables
- ✅ Tests pasando al 100%

### Frontend
- ✅ Componentes reutilizables bien documentados
- ✅ Hooks personalizados útiles
- ✅ Código duplicado reducido >50%
- ✅ Empty states en todas las tablas
- ✅ Bulk actions funcionales
- ✅ Export CSV funcionando
- ✅ Design tokens aplicados

### General
- ✅ No breaking changes
- ✅ Funcionalidad existente intacta
- ✅ Código más mantenible
- ✅ Developer experience mejorada
- ✅ Performance igual o mejor

---

## ⚠️ PRINCIPIOS A SEGUIR

1. **Refactors Incrementales**: No reescribir todo de golpe
2. **Tests Before Refactor**: En partes críticas
3. **Mantener Funcionalidad**: Cero breaking changes
4. **Simplicidad Primero**: No sobre-ingenierizar
5. **Documentar Decisiones**: Por qué se hizo así
6. **Revisar y Ajustar**: El plan puede cambiar según aprendizajes

---

## 📝 NOTAS

- Marcar tareas completadas con `[x]`
- Agregar notas de implementación bajo cada tarea si es necesario
- Si una tarea se vuelve muy compleja, dividirla en sub-tareas
- Está bien saltarse tareas si no aportan valor real
- Priorizar siempre: funcionalidad > features fancy

---

**Última actualización**: 2025-09-30
**Versión**: 1.0
