# 📊 Esquema de Base de Datos - Videra

**Versión del Esquema:** 2.1.0 - Sistema de Roles Dinámicos y Simplificados  
**Última Actualización:** Agosto 2025  
**Base de Datos:** SQLite (Desarrollo) / MySQL (Producción)  
**Características Principales:** Sistema RBAC con descubrimiento automático e interfaz simplificada

## 🎯 Resumen General

Este documento describe el esquema completo de la base de datos SQLite del proyecto **Videra**, incluyendo todas las tablas, relaciones, índices y funcionalidades implementadas. El sistema incluye un **sistema de roles y permisos dinámicos** que se adapta automáticamente a las páginas del sistema.

---

## 📋 Tablas del Sistema

### 🧑‍💼 **1. users**
Tabla principal para almacenar información de usuarios del sistema.

| Campo | Tipo | Descripción | Índices |
|-------|------|-------------|---------|
| `id` | INTEGER PRIMARY KEY | Identificador único auto-incremental | PRIMARY |
| `name` | VARCHAR(255) | Nombre completo del usuario | - |
| `email` | VARCHAR(255) | Correo electrónico único | UNIQUE |
| `email_verified_at` | TIMESTAMP NULL | Fecha de verificación del email | - |
| `password` | VARCHAR(255) | Contraseña hasheada | - |
| `remember_token` | VARCHAR(100) NULL | Token para recordar sesión | - |
| `last_login_at` | TIMESTAMP NULL | Último inicio de sesión real | - |
| `last_activity_at` | TIMESTAMP NULL | Última actividad/heartbeat | - |
| `timezone` | VARCHAR(50) | Zona horaria del usuario | DEFAULT: 'America/Guatemala' |
| `created_at` | TIMESTAMP | Fecha de creación | - |
| `updated_at` | TIMESTAMP | Fecha de última actualización | - |

**Relaciones:**
- 1:N con `user_activities`
- 1:N con `audit_logs`
- 1:N con `sessions`
- N:M con `roles` (a través de `role_user`)

---

### 📈 **2. user_activities**
Tabla para tracking detallado de todas las actividades de usuarios.

| Campo | Tipo | Descripción | Índices |
|-------|------|-------------|---------|
| `id` | INTEGER PRIMARY KEY | Identificador único auto-incremental | PRIMARY |
| `user_id` | INTEGER | FK a tabla users | INDEX, FK |
| `activity_type` | VARCHAR(50) | Tipo de actividad (login, page_view, etc.) | INDEX |
| `description` | VARCHAR(255) NULL | Descripción de la actividad | - |
| `ip_address` | VARCHAR(45) NULL | Dirección IP del usuario | - |
| `user_agent` | TEXT NULL | User Agent del navegador | - |
| `url` | VARCHAR(255) NULL | URL visitada | - |
| `method` | VARCHAR(10) NULL | Método HTTP (GET, POST, etc.) | - |
| `metadata` | JSON NULL | Metadatos adicionales en formato JSON | - |
| `created_at` | TIMESTAMP | Fecha de creación (con DEFAULT CURRENT_TIMESTAMP) | INDEX |

**Índices Compuestos:**
- `(user_id, created_at)` - Para consultas de actividad por usuario
- `(activity_type, created_at)` - Para filtros por tipo de actividad

**Tipos de Actividad:**
- `login` - Inicio de sesión
- `logout` - Cierre de sesión  
- `page_view` - Vista de página
- `action` - Acción del usuario
- `api_call` - Llamada a API
- `file_upload` - Subida de archivo
- `file_download` - Descarga de archivo
- `settings_change` - Cambio de configuración
- `password_change` - Cambio de contraseña
- `profile_update` - Actualización de perfil
- `heartbeat` - Pulso de actividad

---

### 🔍 **3. audit_logs**
Tabla para logs de auditoría del sistema estilo Netbird.

| Campo | Tipo | Descripción | Índices |
|-------|------|-------------|---------|
| `id` | INTEGER PRIMARY KEY | Identificador único auto-incremental | PRIMARY |
| `user_id` | INTEGER NULL | FK a tabla users (puede ser NULL) | INDEX, FK |
| `event_type` | VARCHAR(100) | Tipo de evento de auditoría | INDEX |
| `target_model` | VARCHAR(255) NULL | Modelo afectado (User, Role, etc.) | INDEX |
| `target_id` | INTEGER NULL | ID del modelo afectado | INDEX |
| `description` | TEXT | Descripción del evento | - |
| `old_values` | JSON NULL | Valores anteriores al cambio | - |
| `new_values` | JSON NULL | Valores nuevos después del cambio | - |
| `ip_address` | VARCHAR(45) NULL | Dirección IP del usuario | - |
| `user_agent` | TEXT NULL | User Agent del navegador | - |
| `created_at` | TIMESTAMP | Fecha de creación (con DEFAULT CURRENT_TIMESTAMP) | INDEX |

**Índices Compuestos:**
- `(user_id, created_at)` - Para consultas de auditoría por usuario
- `(event_type, created_at)` - Para filtros por tipo de evento
- `(target_model, target_id)` - Para buscar cambios en modelos específicos

**Tipos de Eventos:**
- `user_created` - Usuario creado
- `user_updated` - Usuario actualizado
- `user_deleted` - Usuario eliminado
- `login` - Inicio de sesión
- `logout` - Cierre de sesión
- `password_changed` - Contraseña cambiada
- `profile_updated` - Perfil actualizado
- `settings_changed` - Configuración cambiada
- `file_uploaded` - Archivo subido
- `file_deleted` - Archivo eliminado
- `permission_granted` - Permiso otorgado
- `permission_revoked` - Permiso revocado

---

### 🔑 **4. password_reset_tokens**
Tabla para tokens de reseteo de contraseña.

| Campo | Tipo | Descripción | Índices |
|-------|------|-------------|---------|
| `email` | VARCHAR(255) | Email del usuario | PRIMARY |
| `token` | VARCHAR(255) | Token de reseteo | - |
| `created_at` | TIMESTAMP NULL | Fecha de creación del token | - |

---

### 🏃‍♂️ **5. sessions**
Tabla para gestión de sesiones de Laravel.

| Campo | Tipo | Descripción | Índices |
|-------|------|-------------|---------|
| `id` | VARCHAR(255) | Identificador único de sesión | PRIMARY |
| `user_id` | INTEGER NULL | FK a tabla users | INDEX, FK |
| `ip_address` | VARCHAR(45) NULL | Dirección IP de la sesión | - |
| `user_agent` | TEXT NULL | User Agent del navegador | - |
| `payload` | LONGTEXT | Datos de la sesión | - |
| `last_activity` | INTEGER | Timestamp de última actividad | INDEX |

---

### 📦 **6. cache**
Tabla para el sistema de caché de Laravel.

| Campo | Tipo | Descripción | Índices |
|-------|------|-------------|---------|
| `key` | VARCHAR(255) | Clave del cache | PRIMARY |
| `value` | MEDIUMTEXT | Valor almacenado | - |
| `expiration` | INTEGER | Timestamp de expiración | - |

---

### 🔒 **7. cache_locks**
Tabla para locks del sistema de caché.

| Campo | Tipo | Descripción | Índices |
|-------|------|-------------|---------|
| `key` | VARCHAR(255) | Clave del lock | PRIMARY |
| `owner` | VARCHAR(255) | Propietario del lock | - |
| `expiration` | INTEGER | Timestamp de expiración | - |

---

### 🚀 **8. jobs**
Tabla para el sistema de colas de Laravel.

| Campo | Tipo | Descripción | Índices |
|-------|------|-------------|---------|
| `id` | INTEGER PRIMARY KEY | Identificador único auto-incremental | PRIMARY |
| `queue` | VARCHAR(255) | Nombre de la cola | INDEX |
| `payload` | LONGTEXT | Datos del trabajo | - |
| `attempts` | TINYINT UNSIGNED | Número de intentos | - |
| `reserved_at` | INTEGER UNSIGNED NULL | Timestamp de reserva | - |
| `available_at` | INTEGER UNSIGNED | Timestamp de disponibilidad | - |
| `created_at` | INTEGER UNSIGNED | Timestamp de creación | - |

---

### 📊 **9. job_batches**
Tabla para lotes de trabajos en cola.

| Campo | Tipo | Descripción | Índices |
|-------|------|-------------|---------|
| `id` | VARCHAR(255) | Identificador único del lote | PRIMARY |
| `name` | VARCHAR(255) | Nombre del lote | - |
| `total_jobs` | INTEGER | Total de trabajos | - |
| `pending_jobs` | INTEGER | Trabajos pendientes | - |
| `failed_jobs` | INTEGER | Trabajos fallidos | - |
| `failed_job_ids` | LONGTEXT | IDs de trabajos fallidos | - |
| `options` | MEDIUMTEXT NULL | Opciones del lote | - |
| `cancelled_at` | INTEGER NULL | Timestamp de cancelación | - |
| `created_at` | INTEGER | Timestamp de creación | - |
| `finished_at` | INTEGER NULL | Timestamp de finalización | - |

---

### ❌ **10. failed_jobs**
Tabla para trabajos fallidos en cola.

| Campo | Tipo | Descripción | Índices |
|-------|------|-------------|---------|
| `id` | INTEGER PRIMARY KEY | Identificador único auto-incremental | PRIMARY |
| `uuid` | VARCHAR(255) | UUID único del trabajo | UNIQUE |
| `connection` | TEXT | Conexión utilizada | - |
| `queue` | TEXT | Cola utilizada | - |
| `payload` | LONGTEXT | Datos del trabajo | - |
| `exception` | LONGTEXT | Excepción ocurrida | - |
| `failed_at` | TIMESTAMP | Fecha de fallo | DEFAULT: CURRENT_TIMESTAMP |

---

### 🛡️ **11. roles**
Tabla para almacenar los roles del sistema de permisos.

| Campo | Tipo | Descripción | Índices |
|-------|------|-------------|---------|
| `id` | INTEGER PRIMARY KEY | Identificador único auto-incremental | PRIMARY |
| `name` | VARCHAR(255) | Nombre único del rol (slug) | UNIQUE |
| `display_name` | VARCHAR(255) | Nombre visible del rol | - |
| `description` | TEXT NULL | Descripción del rol | - |
| `is_system` | BOOLEAN | Si es un rol del sistema (no eliminable) | DEFAULT: false |
| `created_at` | TIMESTAMP | Fecha de creación | - |
| `updated_at` | TIMESTAMP | Fecha de última actualización | - |

**Relaciones:**
- N:M con `users` (a través de `role_user`)
- N:M con `permissions` (a través de `permission_role`)

**Roles del Sistema (v2.1.0 - Simplificado):**
- `Administrador` - Único rol del sistema protegido con acceso completo
- Roles personalizados - Creados por administradores con nombres legibles únicos

**Mejoras de Simplificación:**
- **Campo único**: Solo `name` (eliminado `display_name` duplicado)
- **Nombres legibles**: Roles usan nombres directos como "Administrador", "Editor"
- **Interfaz limpia**: Formularios simplificados sin campos redundantes
- **Usuarios sin roles**: Solo pueden acceder al dashboard
- **Actualización automática**: El rol Administrador se actualiza con nuevos permisos

---

### 🔑 **12. permissions**
Tabla para almacenar los permisos del sistema.

| Campo | Tipo | Descripción | Índices |
|-------|------|-------------|---------|
| `id` | INTEGER PRIMARY KEY | Identificador único auto-incremental | PRIMARY |
| `name` | VARCHAR(255) | Nombre único del permiso | UNIQUE |
| `display_name` | VARCHAR(255) | Nombre visible del permiso | - |
| `description` | TEXT NULL | Descripción del permiso | - |
| `group` | VARCHAR(100) | Grupo al que pertenece el permiso | INDEX |
| `created_at` | TIMESTAMP | Fecha de creación | - |
| `updated_at` | TIMESTAMP | Fecha de última actualización | - |

**Relaciones:**
- N:M con `roles` (a través de `permission_role`)

**Grupos de Permisos (Dinámicos v2.0.0):**
- `dashboard` - Panel principal (solo view)
- `users` - Gestión de usuarios (view, create, edit, delete)
- `audit` - Logs de actividad (solo view)
- `roles` - Gestión de roles y permisos (view, create, edit, delete)
- Grupos futuros - Se generan automáticamente al detectar nuevas páginas

**Acciones Estándar:**
- `view` - Ver/listar elementos (siempre presente)
- `create` - Crear nuevos elementos
- `edit` - Modificar elementos existentes  
- `delete` - Eliminar elementos

**Nomenclatura:** Los permisos siguen el patrón `{página}.{acción}` (ej: `users.view`, `roles.create`)

---

### 🔗 **13. role_user**
Tabla pivote para la relación muchos-a-muchos entre usuarios y roles.

| Campo | Tipo | Descripción | Índices |
|-------|------|-------------|---------|
| `id` | INTEGER PRIMARY KEY | Identificador único auto-incremental | PRIMARY |
| `user_id` | INTEGER | FK a tabla users | INDEX, FK |
| `role_id` | INTEGER | FK a tabla roles | INDEX, FK |
| `created_at` | TIMESTAMP | Fecha de asignación | - |
| `updated_at` | TIMESTAMP | Fecha de última actualización | - |

**Índices Únicos:**
- `(user_id, role_id)` - Evita roles duplicados por usuario

**Constraints:**
- FK: `user_id` → `users.id` (ON DELETE CASCADE)
- FK: `role_id` → `roles.id` (ON DELETE CASCADE)

---

### 🔐 **14. permission_role**
Tabla pivote para la relación muchos-a-muchos entre roles y permisos.

| Campo | Tipo | Descripción | Índices |
|-------|------|-------------|---------|
| `id` | INTEGER PRIMARY KEY | Identificador único auto-incremental | PRIMARY |
| `permission_id` | INTEGER | FK a tabla permissions | INDEX, FK |
| `role_id` | INTEGER | FK a tabla roles | INDEX, FK |
| `created_at` | TIMESTAMP | Fecha de asignación | - |
| `updated_at` | TIMESTAMP | Fecha de última actualización | - |

**Índices Únicos:**
- `(permission_id, role_id)` - Evita permisos duplicados por rol

**Constraints:**
- FK: `permission_id` → `permissions.id` (ON DELETE CASCADE)
- FK: `role_id` → `roles.id` (ON DELETE CASCADE)

---

## 🔗 Relaciones Entre Tablas

### **users → user_activities (1:N)**
- Un usuario puede tener múltiples actividades
- FK: `user_activities.user_id` → `users.id`
- ON DELETE: CASCADE

### **users → audit_logs (1:N)**
- Un usuario puede tener múltiples logs de auditoría
- FK: `audit_logs.user_id` → `users.id`
- ON DELETE: SET NULL (preservar logs aunque se elimine el usuario)

### **users → sessions (1:N)**
- Un usuario puede tener múltiples sesiones
- FK: `sessions.user_id` → `users.id`
- ON DELETE: CASCADE

### **users ↔ roles (N:M)**
- Un usuario puede tener múltiples roles
- Un rol puede ser asignado a múltiples usuarios
- Tabla pivote: `role_user`
- FK: `role_user.user_id` → `users.id` (ON DELETE CASCADE)
- FK: `role_user.role_id` → `roles.id` (ON DELETE CASCADE)

### **roles ↔ permissions (N:M)**
- Un rol puede tener múltiples permisos
- Un permiso puede ser asignado a múltiples roles
- Tabla pivote: `permission_role`
- FK: `permission_role.role_id` → `roles.id` (ON DELETE CASCADE)
- FK: `permission_role.permission_id` → `permissions.id` (ON DELETE CASCADE)

---

## 📈 Estrategia de Índices

### **Índices de Rendimiento:**
1. **user_activities**: 
   - `(user_id, created_at)` - Optimiza consultas de actividad por usuario
   - `(activity_type, created_at)` - Optimiza filtros por tipo
   - `created_at` - Para ordenamiento temporal

2. **audit_logs**:
   - `(user_id, created_at)` - Optimiza consultas de auditoría por usuario
   - `(event_type, created_at)` - Optimiza filtros por tipo de evento
   - `(target_model, target_id)` - Para buscar cambios en modelos específicos

3. **sessions**:
   - `user_id` - Para consultas por usuario
   - `last_activity` - Para limpiar sesiones expiradas

4. **roles**:
   - `name` - Nombre único del rol
   - `is_system` - Para filtrar roles del sistema

5. **permissions**:
   - `name` - Nombre único del permiso
   - `group` - Para agrupar permisos por funcionalidad

6. **role_user**:
   - `(user_id, role_id)` - Evita duplicados y optimiza consultas
   - `user_id` - Para consultas por usuario
   - `role_id` - Para consultas por rol

7. **permission_role**:
   - `(permission_id, role_id)` - Evita duplicados y optimiza consultas
   - `permission_id` - Para consultas por permiso
   - `role_id` - Para consultas por rol

---

## ⚡ Funcionalidades Implementadas

### **🔄 Sistema de Tracking de Actividad**
- **Heartbeat**: Actualización automática cada 30 segundos
- **Separación de responsabilidades**:
  - `last_login_at`: Para logins reales
  - `last_activity_at`: Para actividad continua
- **Estados de usuario**: Basados en `last_activity_at`
  - En línea: < 5 minutos
  - Reciente: < 15 minutos
  - Desconectado: > 15 minutos
  - Nunca: Sin registro

### **📊 Sistema de Auditoría**
- **Registro automático** de todas las actividades importantes
- **Metadatos JSON** para información adicional
- **Preservación de datos** históricos
- **Filtros avanzados** por usuario, tipo, fecha
- **Búsqueda de texto** en descripciones y metadatos

### **🕐 Manejo de Tiempo**
- **UTC en base de datos**: Todos los timestamps en UTC
- **Conversión local**: Solo en frontend para display
- **Zona horaria por usuario**: Campo `timezone` en users
- **Formato Guatemala**: Conversión automática a `America/Guatemala`

### **🛡️ Sistema de Roles y Permisos (v2.0.0 - Dinámico)**
- **Arquitectura RBAC**: Role-Based Access Control con descubrimiento automático
- **Detección automática**: Escanea páginas y genera permisos dinámicamente
- **Escalabilidad**: Se adapta automáticamente a nuevas páginas del sistema
- **Roles del sistema**: Solo "Administrador" como rol protegido
- **Permisos granulares**: 4 acciones base (view, create, edit, delete)
- **Sincronización automática**: Comando `permissions:sync` para actualizar
- **Asignación múltiple**: Un usuario puede tener múltiples roles
- **Herencia de permisos**: Los permisos se heredan de todos los roles asignados
- **Gestión completa**: CRUD completo para roles personalizados
- **Usuario por defecto**: admin@admin.com (contraseña: admin) con acceso completo

**Sistema de Descubrimiento:**
- **Servicio**: `PermissionDiscoveryService` - Escanea `/resources/js/pages/`
- **Comando**: `php artisan permissions:sync` - Sincroniza permisos automáticamente
- **Seeder dinámico**: `RolesAndPermissionsSeeder` - Usa descubrimiento automático
- **Middleware**: `CheckUserPermissions` - Valida permisos dinámicamente
- **Frontend**: Hook `usePermissions` - Gestión de permisos en React

**Permisos Generados Automáticamente:**
- **Dashboard**: `dashboard.view` (solo lectura)
- **Usuarios**: `users.view`, `users.create`, `users.edit`, `users.delete`
- **Actividad**: `audit.view` (solo lectura) 
- **Roles**: `roles.view`, `roles.create`, `roles.edit`, `roles.delete`
- **Páginas futuras**: Se detectan y generan automáticamente

**Configuración de Acciones por Página:**
```php
dashboard: [view]                    // Solo lectura
users:     [view, create, edit, delete]  // CRUD completo  
audit:     [view]                    // Solo lectura
roles:     [view, create, edit, delete]  // CRUD completo
```

**Flujo de Escalabilidad:**
1. **Desarrollador** crea nueva página en `/resources/js/pages/nueva-pagina/`
2. **Sistema** detecta automáticamente la página y archivos
3. **Comando** `permissions:sync` genera permisos correspondientes
4. **Administrador** obtiene automáticamente todos los nuevos permisos
5. **Sidebar** se actualiza dinámicamente para mostrar nueva sección

---

## 🔧 Migraciones Ejecutadas

1. **`0001_01_01_000000_create_users_table.php`** - Tabla base de usuarios
2. **`0001_01_01_000001_create_cache_table.php`** - Sistema de caché
3. **`0001_01_01_000002_create_jobs_table.php`** - Sistema de colas
4. **`2025_08_12_193838_add_last_login_at_to_users_table.php`** - Campo last_login_at
5. **`2025_08_13_173244_add_last_activity_at_to_users_table.php`** - Campos last_activity_at y timezone
6. **`2025_08_13_173253_create_user_activities_table.php`** - Tabla de actividades
7. **`2025_08_13_173301_create_audit_logs_table.php`** - Tabla de auditoría
8. **`2025_08_13_205211_create_roles_table.php`** - Tabla de roles del sistema
9. **`2025_08_13_205216_create_permissions_table.php`** - Tabla de permisos
10. **`2025_08_13_205222_create_role_user_table.php`** - Tabla pivote usuarios-roles
11. **`2025_08_13_205228_create_permission_role_table.php`** - Tabla pivote roles-permisos

---

## 📋 Tamaño Estimado de Datos

### **Cálculos de Crecimiento:**
- **user_activities**: ~100-500 registros/usuario/día
- **audit_logs**: ~10-50 registros/usuario/día
- **sessions**: 1-3 registros activos/usuario

### **Recomendaciones de Mantenimiento:**
- **Limpieza de actividades**: Mantener últimos 90 días
- **Limpieza de sesiones**: Automática por Laravel
- **Backup de audit_logs**: Mantener histórico completo
- **Índices**: Monitorear rendimiento con crecimiento

---

## 🛠️ Comandos Útiles

```bash
# Ejecutar migraciones
php artisan migrate

# Ver estado de migraciones
php artisan migrate:status

# Rollback de migración
php artisan migrate:rollback

# Limpiar cache
php artisan cache:clear

# Ver rutas
php artisan route:list

# Generar modelo
php artisan make:model ModelName

# Generar migración
php artisan make:migration create_table_name
```

---

## 📝 Notas de Desarrollo

### **Convenciones Utilizadas:**
- **Nombres de tabla**: `snake_case` en plural
- **Nombres de campo**: `snake_case`
- **FK**: Siempre `table_name_id`
- **Timestamps**: Laravel standard (`created_at`, `updated_at`)
- **JSON**: Para metadatos complejos
- **Índices**: Nombres descriptivos con prefijo de tabla

### **Buenas Prácticas Implementadas:**
- ✅ **Foreign Keys** con acciones ON DELETE apropiadas
- ✅ **Índices compuestos** para consultas comunes
- ✅ **Campos nullable** donde corresponde
- ✅ **Valores por defecto** sensatos
- ✅ **Separación de concerns** (actividad vs auditoría)
- ✅ **Preservación de datos** históricos importantes

---

**📅 Última actualización**: 13 de agosto de 2025  
**🔢 Versión del esquema**: 2.0.0 - Sistema de Roles y Permisos  
**👥 Mantenido por**: Equipo de Desarrollo Videra

### **🚀 Nuevas Funcionalidades v2.0.0**
- ✅ **Sistema RBAC completo** con roles y permisos
- ✅ **Gestión visual de roles** en la interfaz de usuarios
- ✅ **Roles del sistema protegidos** contra eliminación accidental
- ✅ **Permisos granulares** agrupados por funcionalidad
- ✅ **Usuario administrador predeterminado** (admin@admin.com)
- ✅ **Interfaz de creación/edición** de roles personalizados
- ✅ **Validaciones de integridad** para roles y permisos
