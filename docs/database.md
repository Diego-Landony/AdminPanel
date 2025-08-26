# 🗄️ Documentación: Base de Datos

## 📋 Descripción General

Sistema de base de datos diseñado para soportar gestión de usuarios, roles, permisos, y seguimiento de actividad con arquitectura escalable y optimizada.

### **Motor:** SQLite (desarrollo) / Configurable para producción
### **Migraciones:** Single migration approach para simplicidad
### **Seeders:** Automáticos con PermissionDiscoveryService

---

## 📊 Tablas Principales

### **users** - Gestión de Usuarios
```sql
id                   BIGINT PRIMARY KEY
name                 VARCHAR(255) NOT NULL
email                VARCHAR(255) UNIQUE NOT NULL  
email_verified_at    TIMESTAMP NULL
password             VARCHAR(255) NOT NULL
last_login_at        TIMESTAMP NULL
last_activity_at     TIMESTAMP NULL
timezone             VARCHAR DEFAULT 'America/Guatemala'
remember_token       VARCHAR(100) NULL
created_at           TIMESTAMP
updated_at           TIMESTAMP
deleted_at           TIMESTAMP NULL  -- Soft deletes
```

### **roles** - Sistema de Roles
```sql
id                   BIGINT PRIMARY KEY
name                 VARCHAR(255) UNIQUE NOT NULL
description          TEXT NULL
is_system            BOOLEAN DEFAULT FALSE
created_at           TIMESTAMP  
updated_at           TIMESTAMP
```

### **permissions** - Sistema de Permisos
```sql
id                   BIGINT PRIMARY KEY
name                 VARCHAR(255) UNIQUE NOT NULL  -- ej: users.view
display_name         VARCHAR(255) NOT NULL         -- ej: Ver Usuarios  
description          TEXT NULL
group                VARCHAR DEFAULT 'general'     -- ej: users, roles
created_at           TIMESTAMP
updated_at           TIMESTAMP
```

---

## 🔗 Tablas Pivot (Relaciones)

### **role_user** - Usuarios ↔ Roles
```sql
id                   BIGINT PRIMARY KEY
user_id              BIGINT FK(users.id) CASCADE
role_id              BIGINT FK(roles.id) CASCADE
created_at           TIMESTAMP
updated_at           TIMESTAMP

UNIQUE(user_id, role_id)
```

### **permission_role** - Roles ↔ Permisos  
```sql
id                   BIGINT PRIMARY KEY
role_id              BIGINT FK(roles.id) CASCADE
permission_id        BIGINT FK(permissions.id) CASCADE
created_at           TIMESTAMP
updated_at           TIMESTAMP

UNIQUE(role_id, permission_id)
```

---

## 📝 Tablas de Actividad y Logs

### **user_activities** - Actividades de Usuario
```sql
id                   BIGINT PRIMARY KEY
user_id              BIGINT FK(users.id) CASCADE
activity_type        VARCHAR NOT NULL        -- login, logout, page_view
description          VARCHAR NOT NULL
user_agent           VARCHAR NULL
url                  VARCHAR NULL
method               VARCHAR NULL           -- GET, POST, etc.
metadata             JSON NULL             -- Datos adicionales
created_at           TIMESTAMP
updated_at           TIMESTAMP

INDEX(user_id, created_at)
INDEX(activity_type)
```

### **activity_logs** - Logs de Auditoría
```sql
id                   BIGINT PRIMARY KEY
user_id              BIGINT FK(users.id) SET NULL
event_type           VARCHAR(100) NOT NULL   -- user_created, role_updated
target_model         VARCHAR(255) NULL       -- User, Role, etc.
target_id            BIGINT NULL            -- ID del modelo
description          TEXT NOT NULL
old_values           JSON NULL              -- Valores anteriores
new_values           JSON NULL              -- Valores nuevos  
user_agent           TEXT NULL
created_at           TIMESTAMP
updated_at           TIMESTAMP

INDEX(user_id, created_at)
INDEX(event_type, created_at) 
INDEX(target_model, target_id)
INDEX(created_at)
```

---

## 🔧 Tablas del Sistema Laravel

### **sessions** - Gestión de Sesiones
```sql
id                   VARCHAR PRIMARY KEY
user_id              BIGINT NULL INDEX
user_agent           TEXT NULL
payload              TEXT NOT NULL
last_activity        INTEGER INDEX
ip_address           VARCHAR(45) NULL
```

### **password_reset_tokens** - Reset de Contraseñas
```sql
email                VARCHAR PRIMARY KEY
token                VARCHAR NOT NULL
created_at           TIMESTAMP NULL
```

### **cache** / **cache_locks** - Sistema de Cache
```sql
-- cache
key                  VARCHAR PRIMARY KEY
value                MEDIUMTEXT NOT NULL
expiration           INTEGER NOT NULL

-- cache_locks  
key                  VARCHAR PRIMARY KEY
owner                VARCHAR NOT NULL
expiration           INTEGER NOT NULL
```

### **jobs** / **failed_jobs** - Sistema de Colas
```sql
-- jobs
id                   BIGINT PRIMARY KEY AUTO_INCREMENT
queue                VARCHAR INDEX
payload              LONGTEXT NOT NULL
attempts             TINYINT UNSIGNED NOT NULL
reserved_at          INTEGER UNSIGNED NULL
available_at         INTEGER UNSIGNED NOT NULL
created_at           INTEGER UNSIGNED NOT NULL

-- failed_jobs
id                   BIGINT PRIMARY KEY
uuid                 VARCHAR UNIQUE NOT NULL
connection           TEXT NOT NULL
queue                TEXT NOT NULL
payload              LONGTEXT NOT NULL
exception            LONGTEXT NOT NULL
failed_at            TIMESTAMP DEFAULT CURRENT_TIMESTAMP
```

---

## 🚀 Sistema de Inicialización

### **Migración Única** (`0001_01_01_000000_create_initial_schema.php`)
- **Enfoque**: Single migration para toda la estructura
- **Usuarios por defecto**: Crea automáticamente admin@admin.com y admin@test.com
- **Permisos básicos**: Instala permisos fundamentales del sistema
- **Rol admin**: Configuración completa con todos los permisos

### **DatabaseSeeder.php** - Configuración Inteligente
```php
1. Descubre permisos automáticamente (PermissionDiscoveryService)
2. Crea/actualiza rol de administrador  
3. Asigna todos los permisos al admin
4. Verifica usuario admin@admin.com
5. Crea usuarios de prueba (solo local)
```

---

## 👤 Usuarios por Defecto

### **Administrador Principal:**
- **Email**: admin@admin.com
- **Contraseña**: admin
- **Rol**: admin (todos los permisos)
- **Verificado**: Sí

### **Administrador de Prueba:**
- **Email**: admin@test.com  
- **Contraseña**: admintest
- **Rol**: admin (todos los permisos)
- **Verificado**: Sí

### **Usuarios de Prueba** (solo local):
- **Email**: user1@test.com / user2@test.com
- **Contraseña**: password
- **Sin roles por defecto**

---

## 🔑 Sistema de Permisos

### **Patrón de Nombres:**
```
{página}.{acción}
```

### **Permisos Básicos Instalados:**
```php
dashboard.view          # Ver dashboard
home.view              # Ver página de inicio
users.view             # Ver usuarios
users.create           # Crear usuarios  
users.edit             # Editar usuarios
users.delete           # Eliminar usuarios
roles.view             # Ver roles
roles.create           # Crear roles
roles.edit             # Editar roles
roles.delete           # Eliminar roles
activity.view          # Ver actividad
settings.view          # Ver configuración
profile.view           # Ver perfil propio
profile.edit           # Editar perfil propio
```

### **Grupos de Permisos:**
- **dashboard**: Panel principal
- **home**: Página de inicio  
- **users**: Gestión de usuarios
- **roles**: Gestión de roles
- **activity**: Logs y actividad
- **settings**: Configuración
- **profile**: Perfil de usuario

---

## 📊 Índices para Performance

### **Optimizaciones Implementadas:**
```sql
-- Actividades de usuario
INDEX(user_id, created_at)     # Consultas por usuario y fecha
INDEX(activity_type)           # Filtros por tipo

-- Logs de actividad  
INDEX(user_id, created_at)     # Auditoría por usuario
INDEX(event_type, created_at)  # Filtros por evento y fecha
INDEX(target_model, target_id) # Búsqueda por modelo afectado
INDEX(created_at)              # Ordenamiento cronológico

-- Sesiones
INDEX(user_id)                 # Sesiones por usuario
INDEX(last_activity)           # Cleanup de sesiones viejas
```

---

## 🔄 Comandos de Base de Datos

### **Instalación Limpia:**
```bash
php artisan migrate:fresh --seed
```

### **Solo Migraciones:**
```bash
php artisan migrate
```

### **Solo Seeders:**
```bash  
php artisan db:seed
```

### **Sincronizar Permisos:**
```bash
php artisan tinker
$service = new App\Services\PermissionDiscoveryService;
$service->syncPermissions();
```

---

## 📈 Estadísticas de Datos

### **Capacidad de Crecimiento:**
- **Usuarios**: Sin límite práctico
- **Roles**: Escalable (típicamente 5-50)
- **Permisos**: Auto-discovered (crece con páginas)
- **Actividad**: Tablas indexadas para millones de registros

### **Retención de Datos:**
- **Actividades**: Sin límite automático
- **Logs**: Preservación completa para auditoría  
- **Sesiones**: Cleanup automático de Laravel
- **Cache**: Expiración automática

---

## ⚡ Consideraciones de Rendimiento

### **Consultas Optimizadas:**
- Eager loading en controladores
- Índices en columnas de búsqueda
- Paginación para listas grandes
- JSON para metadata flexible

### **Soft Deletes:**
- Solo en tabla `users`
- Preserva integridad referencial
- Permite auditoría de usuarios eliminados

### **Escalabilidad:**
- Estructura preparada para clustering
- Índices optimizados para consultas frecuentes
- JSON storage para flexibilidad futura