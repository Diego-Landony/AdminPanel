# Dashboard de Gestión AdminPanel

Dashboard web para gestión de usuarios, roles y permisos con seguimiento de actividad.

## 🛠️ Requisitos del Sistema

### Requisitos del Servidor
- PHP 8.3+
  - Extensiones requeridas:
    - php8.3-fpm
    - php8.3-sqlite3
    - php8.3-xml
    - php8.3-curl
    - php8.3-mbstring
    - php8.3-zip
- Node.js 18+ y npm
- Composer 2+

### Requisitos de Base de Datos
- SQLite 3

## ⚡ Instalación en Producción

### 1. Preparación del Servidor
```bash
# Instalar dependencias del sistema
sudo apt update
sudo apt install php8.3 php8.3-fpm php8.3-sqlite3 php8.3-xml php8.3-curl php8.3-mbstring php8.3-zip

# Instalar Node.js 18+
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs

# Verificar instalaciones
php -v
node -v
npm -v
```

### 2. Configuración del Proyecto
```bash
# Clonar repositorio
git clone <repo>
cd AdminPanel

# Instalar dependencias de producción
composer install --no-dev --optimize-autoloader
npm install
npm run build # Compila los assets para producción

# Configuración del entorno
cp .env.example .env
php artisan key:generate
```

### 3. Configuración de la Base de Datos
```bash
# Crear y configurar SQLite
touch database/database.sqlite
chmod 664 database/database.sqlite
chown www-data:www-data database/database.sqlite

# Ejecutar migraciones
php artisan migrate --force

# Compilar los assets para producción
npm run build
```

### 4. Optimizaciones para Producción
```bash
# Optimizar Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Establecer permisos correctos
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
sudo usermod -aG www-data $USER
sudo usermod -aG www-data ubuntu
```

### Acceso

## Acceso y Configuración del Servidor Web

En producción, el sistema debe ser accedido a través de la ruta `public/index.php`.

### Ejemplo de configuración para Caddy (Laravel)

```caddyfile
root * /var/www/html/AdminSubwayApp/public
php_fastcgi unix//run/php/php8.3-fpm.sock
file_server
encode gzip

# Rewrite para el index.php de Laravel
try_files {path} {path}/ /index.php?{query}
```

Esto asegura que todas las rutas sean gestionadas por Laravel y los assets públicos estén disponibles correctamente.

**URL de acceso:**  el dominio configurado
**Usuario por defecto:** admin@admin.com
**Contraseña:** admin


## ⚠️ Notas Importantes
- Asegúrate de que APP_ENV esté configurado como 'production'
- Deshabilita APP_DEBUG en producción
- Configura correctamente los permisos de archivos
- Realiza backups regulares de la base de datos
- Mantén las dependencias actualizadas

## 🔒 Seguridad
- Actualiza regularmente todas las dependencias
- Monitorea los logs de actividad
- Mantén copias de seguridad actualizadas
- Utiliza HTTPS en producción
- Configura correctamente los headers de seguridad


## 🗄️ Base de Datos

### **Tablas Principales:**
- `users` - Gestión de usuarios con soft deletes
- `roles` - Roles del sistema y personalizados  
- `permissions` - Permisos granulares auto-generados
- `user_activities` - Actividades de usuarios
- `activity_logs` - Logs de auditoría

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



## 🧪 Testing

```bash
php artisan test                    # Todos los tests
php artisan test --filter=User     # Tests específicos
composer run test                   # Con config clear
```

## 🔧 Comandos Útiles

```bash
# Sincronizar permisos tras añadir páginas
php artisan permissions:sync

# Alternativa usando Tinker
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
