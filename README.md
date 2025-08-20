# 🚀 Videra

**Sistema de gestión empresarial moderno y eficiente**

## 📋 Descripción

Videra es una aplicación web empresarial desarrollada con tecnologías modernas que proporciona una solución completa para la gestión de empresas, incluyendo módulos de usuarios, inventario, ventas, reportes y más.

## 🛠️ Stack Tecnológico

### Backend
- **Laravel 12** - Framework PHP moderno y robusto
- **PHP 8.3+** - Versión más reciente de PHP
- **SQLite** - Base de datos ligera y eficiente
- **Composer** - Gestor de dependencias PHP

### Frontend
- **React 19** - Biblioteca de interfaz de usuario moderna
- **TypeScript** - Tipado estático para JavaScript
- **Tailwind CSS 4.0** - Framework CSS utility-first
- **Shadcn/UI** - Componentes de interfaz reutilizables
- **Inertia.js 2.0** - Integración perfecta entre Laravel y React

### Herramientas de Desarrollo
- **Pest** - Framework de testing PHP
- **Vite** - Bundler y dev server
- **ESLint** - Linter para JavaScript/TypeScript
- **Prettier** - Formateador de código

## 🚀 Instalación Rápida

### Prerrequisitos
- **PHP 8.3** o superior
- **Composer 2.6+**
- **Node.js 18+** y NPM
- **Git**

### ⚡ Instalación en 5 Pasos

1. **Clonar el repositorio**
   ```bash
   git clone https://github.com/Diego-Landony/videra.git
   cd videra
   ```

2. **Instalar dependencias PHP y Node.js**
   ```bash
   composer install
   npm install
   ```

3. **Configurar el entorno**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Crear y poblar la base de datos**
   ```bash
   php artisan migrate:fresh --seed
   ```

5. **Compilar assets y ejecutar**
   ```bash
   npm run build
   php artisan serve
   ```

### 🔐 Acceso al Sistema

Una vez instalado, puedes acceder con:

- **URL**: `http://localhost:8000`
- **Email**: `admin@admin.com`
- **Contraseña**: `admin`

## 📁 Estructura del Proyecto

```
videra/
├── app/                    # Lógica de aplicación Laravel
│   ├── Console/           # Comandos Artisan
│   ├── Http/              # Controladores, Middleware, Requests
│   ├── Models/             # Modelos Eloquent
│   ├── Observers/          # Observadores de modelos
│   ├── Providers/          # Proveedores de servicios
│   ├── Rules/              # Reglas de validación personalizadas
│   └── Services/           # Servicios de la aplicación
├── database/               # Migraciones, seeders y factories
├── resources/              # Assets y vistas
│   ├── js/                # Componentes React
│   └── css/               # Estilos CSS
├── routes/                 # Definición de rutas
├── storage/                # Archivos de almacenamiento
├── tests/                  # Tests con Pest
└── vendor/                 # Dependencias Composer
```

## 🔧 Comandos Útiles

### Desarrollo
```bash
npm run dev          # Iniciar Vite en modo desarrollo
npm run build        # Compilar assets para producción
php artisan serve    # Iniciar servidor Laravel
```

### Base de Datos
```bash
php artisan migrate:fresh --seed    # Recrear BD y ejecutar seeders
php artisan migrate                 # Ejecutar migraciones pendientes
php artisan migrate:rollback        # Revertir última migración
php artisan db:seed                # Ejecutar seeders
```

### Testing
```bash
php artisan test                   # Ejecutar todos los tests
php artisan test --filter=User    # Ejecutar tests específicos
```

### Utilidades
```bash
php artisan permissions:sync      # Sincronizar permisos del sistema
php artisan route:list            # Listar todas las rutas
php artisan make:model User       # Crear nuevo modelo
```

## 🌟 Características Principales

### 🔐 Sistema de Autenticación
- Login/logout seguro
- Verificación de email
- Reset de contraseñas
- Sesiones persistentes

### 👥 Gestión de Usuarios
- CRUD completo de usuarios
- Roles y permisos granulares
- Tracking de actividad en tiempo real
- Estados online/offline

### 🛡️ Sistema de Permisos
- Permisos automáticos basados en páginas
- Roles del sistema protegidos
- Asignación granular de permisos
- Discovery automático de funcionalidades

### 📊 Actividad y Auditoría
- Logs de actividad del sistema
- Tracking de cambios en modelos
- Historial de acciones de usuarios
- IP y user agent tracking

### 🎨 Interfaz Moderna
- Diseño responsive mobile-first
- Tema claro/oscuro/sistema
- Componentes Shadcn/UI
- Tailwind CSS 4.0

## 🚀 Despliegue en Producción

### Configuración del Servidor
```bash
# Configurar variables de entorno
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.com

# Configurar base de datos
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=videra_prod
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_contraseña

# Configurar cache y sesiones
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

### Comandos de Despliegue
```bash
# Instalar dependencias
composer install --optimize-autoloader --no-dev
npm ci && npm run build

# Configurar base de datos
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Configurar permisos
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

## 🧪 Testing

El proyecto incluye tests completos con Pest:

```bash
# Ejecutar tests
php artisan test

# Tests con coverage
php artisan test --coverage

# Tests específicos
php artisan test --filter=UserController
```

## 🤝 Contribución

1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/NuevaFuncionalidad`)
3. Commit tus cambios (`git commit -m 'Agregar nueva funcionalidad'`)
4. Push a la rama (`git push origin feature/NuevaFuncionalidad`)
5. Abre un Pull Request

## 📝 Licencia

Este proyecto está bajo la Licencia MIT. Ver el archivo `LICENSE` para más detalles.

## 👨‍💻 Autor

**Diego Landony** - [GitHub](https://github.com/Diego-Landony)

## 🆘 Soporte

Si encuentras algún problema:

1. Revisa los logs en `storage/logs/`
2. Ejecuta `php artisan permissions:sync` para sincronizar permisos
3. Verifica que todas las migraciones se ejecutaron: `php artisan migrate:status`
4. Revisa que el seeder se ejecutó: `php artisan db:seed`

---

⭐ Si este proyecto te gusta, ¡dale una estrella!
