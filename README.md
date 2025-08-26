# Dashboard de Gestión

Dashboard web para gestión de usuarios, roles y permisos con seguimiento de actividad.

## 🛠️ Stack Tecnológico

- **Laravel 12.22** + **PHP 8.3**
- **React 19** + **TypeScript** + **Inertia.js 2.0**
- **Tailwind CSS 4.0** + **shadcn/ui**
- **SQLite** (desarrollo) / **MySQL/PostgreSQL** (producción)
- **Pest** (testing)

## ⚡ Instalación

### Prerrequisitos
- PHP 8.2+ con SQLite
- Node.js 18+ y npm
- Composer

### Comandos
```bash
# Clonar e instalar
git clone <repo>
cd dashboard
composer install
npm install

# Configurar
cp .env.example .env
php artisan key:generate

# Base de datos
php artisan migrate:fresh --seed

# Compilar y ejecutar
npm run build
php artisan serve
```

### Acceso
- **URL**: http://localhost:8000
- **Usuario**: admin@admin.com
- **Contraseña**: admin

## 📄 Funcionalidades

### **Gestión de Usuarios**
- Lista con búsqueda y paginación
- Crear, editar, eliminar usuarios
- Estados en tiempo real (online/offline)
- Gestión de contraseñas opcional

### **Sistema de Roles y Permisos**
- Roles del sistema y personalizados
- Permisos automáticos por página
- Asignación granular de permisos
- Auto-discovery de nuevas páginas

### **Seguimiento de Actividad**
- Logs de auditoría completos
- Tracking de cambios en tiempo real
- Filtros por usuario, tipo y fecha
- Vista unificada de actividades

### **Autenticación**
- Login/logout seguro
- Verificación de email
- Reset de contraseñas
- Sesiones persistentes

### **Configuración Personal**
- Perfil de usuario editable
- Cambio de contraseña
- Tema claro/oscuro/sistema

## 🚀 Comandos de Desarrollo

```bash
# Desarrollo
npm run dev              # Vite dev server
composer run dev         # Laravel + Vite + Queue + Logs

# Base de datos
php artisan migrate:fresh --seed
php artisan db:seed

# Testing
php artisan test
php artisan test --filter=User

# Producción
npm run build
composer install --no-dev --optimize-autoloader
```

## 📊 Estructura del Proyecto

```
dashboard/
├── app/
│   ├── Http/Controllers/    # UserController, RoleController, etc.
│   ├── Models/              # User, Role, Permission, ActivityLog
│   └── Services/            # PermissionDiscoveryService
├── resources/js/
│   ├── pages/              # Páginas React (users, roles, activity)
│   ├── components/ui/      # Componentes shadcn/ui
│   └── layouts/           # Layouts de la app
├── database/
│   ├── migrations/        # Schema completo
│   └── seeders/          # Usuarios y permisos por defecto
└── docs/                 # Documentación técnica
```

## 🗄️ Base de Datos

### **Tablas Principales:**
- `users` - Gestión de usuarios con soft deletes
- `roles` - Roles del sistema y personalizados  
- `permissions` - Permisos granulares auto-generados
- `user_activities` - Actividades de usuarios
- `activity_logs` - Logs de auditoría con old/new values

### **Usuarios por Defecto:**
- **admin@admin.com** / **admin** (acceso completo)
- **admin@test.com** / **admintest** (acceso completo)

## 🔐 Sistema de Permisos

### **Auto-Discovery:**
El sistema detecta automáticamente nuevas páginas en `resources/js/pages/` y genera permisos con patrón `{página}.{acción}`:

```
users.view, users.create, users.edit, users.delete
roles.view, roles.create, roles.edit, roles.delete  
activity.view, dashboard.view, etc.
```

### **Protecciones:**
- Rol `admin` siempre tiene todos los permisos
- Roles del sistema protegidos contra eliminación
- Usuario admin@admin.com no se puede eliminar

## 📱 Responsive Design

- **Desktop**: Tablas completas con todas las funcionalidades
- **Mobile/Tablet**: Vista de cards optimizada
- **Componentes**: shadcn/ui + Tailwind CSS 4.0
- **Tema**: Claro/Oscuro/Sistema automático

## 🧪 Testing

```bash
php artisan test                    # Todos los tests
php artisan test --filter=User     # Tests específicos
composer run test                   # Con config clear
```

## 🔧 Comandos Útiles

```bash
# Sincronizar permisos tras añadir páginas
php artisan tinker
$service = new App\Services\PermissionDiscoveryService;
$service->syncPermissions();

# Ver todas las rutas
php artisan route:list

# Limpiar cache
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

## 📈 Producción

```bash
# Variables de entorno
APP_ENV=production
APP_DEBUG=false  
DB_CONNECTION=mysql

# Deploy
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
```

---

Sistema simple y directo para gestión de usuarios con roles y seguimiento completo de actividad.