# 🚀 Videra

**Sistema de gestión empresarial moderno y eficiente**

## 📋 Descripción

Videra es una aplicación web empresarial desarrollada con tecnologías modernas que proporciona una solución completa para la gestión de empresas, incluyendo módulos de usuarios, inventario, ventas, reportes y más.

## 🛠️ Stack Tecnológico

### Backend
- **Laravel 12** - Framework PHP moderno y robusto
- **PHP 8.2+** - Versión más reciente de PHP
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

## 🚀 Instalación

### Prerrequisitos
- PHP 8.2 o superior
- Composer
- Node.js 18+ y NPM
- Git

### Pasos de Instalación

1. **Clonar el repositorio**
   ```bash
   git clone https://github.com/Diego-Landony/videra.git
   cd videra
   ```

2. **Instalar dependencias PHP**
   ```bash
   composer install
   ```

3. **Instalar dependencias Node.js**
   ```bash
   npm install
   ```

4. **Configurar variables de entorno**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Configurar base de datos**
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

6. **Compilar assets**
   ```bash
   npm run build
   ```

7. **Iniciar servidor de desarrollo**
   ```bash
   php artisan serve
   npm run dev
   ```

## 📁 Estructura del Proyecto

```
videra/
├── app/                    # Lógica de aplicación Laravel
├── config/                 # Archivos de configuración
├── database/               # Migraciones y seeders
├── public/                 # Archivos públicos
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

### Testing
```bash
php artisan test     # Ejecutar tests con Pest
```

### Base de Datos
```bash
php artisan migrate          # Ejecutar migraciones
php artisan migrate:rollback # Revertir migraciones
php artisan db:seed          # Ejecutar seeders
```

## 🤝 Contribución

1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abre un Pull Request

## 📝 Licencia

Este proyecto está bajo la Licencia MIT. Ver el archivo `LICENSE` para más detalles.

## 👨‍💻 Autor

**Diego Landony** - [GitHub](https://github.com/Diego-Landony)

## 🙏 Agradecimientos

- Laravel Team por el excelente framework
- React Team por la biblioteca de interfaz
- Tailwind CSS por el framework de utilidades
- Shadcn por los componentes de UI

---

⭐ Si este proyecto te gusta, ¡dale una estrella!
