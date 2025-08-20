# 🚀 Instalación Rápida de Videra

## ⚡ Instalación Automatizada (Recomendada)

### 1. Clonar el repositorio
```bash
git clone https://github.com/Diego-Landony/videra.git
cd videra
```

### 2. Ejecutar script de instalación
```bash
./install.sh
```

¡Eso es todo! El script se encarga de todo automáticamente.

---

## 🔧 Instalación Manual

### Prerrequisitos
- **PHP 8.3+**
- **Composer 2.6+**
- **Node.js 18+** y NPM
- **Git**

### Pasos

1. **Instalar dependencias**
   ```bash
   composer install
   npm install
   ```

2. **Configurar entorno**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **Configurar base de datos**
   ```bash
   touch database/database.sqlite
   php artisan migrate:fresh --seed
   ```

4. **Compilar assets**
   ```bash
   npm run build
   ```

5. **Iniciar servidor**
   ```bash
   php artisan serve
   ```

---

## 🔐 Acceso al Sistema

Una vez instalado:

- **URL**: `http://localhost:8000`
- **Email**: `admin@admin.com`
- **Contraseña**: `admin`

---

## 🆘 Solución de Problemas

### Error de permisos
```bash
chmod +x install.sh
```

### Base de datos no se crea
```bash
mkdir -p database
touch database/database.sqlite
```

### Assets no se compilan
```bash
rm -rf node_modules package-lock.json
npm install
npm run build
```

### Migraciones fallan
```bash
php artisan migrate:fresh --seed
```

---

## 📱 Características del Sistema

✅ **Sistema de autenticación completo**
✅ **Gestión de usuarios y roles**
✅ **Sistema de permisos automático**
✅ **Tracking de actividad en tiempo real**
✅ **Interfaz moderna con React + Tailwind**
✅ **Base de datos SQLite optimizada**
✅ **Tests automatizados con Pest**

---

## 🌟 ¡Listo para usar!

El sistema incluye:
- Usuario administrador preconfigurado
- Todos los permisos automáticamente asignados
- Base de datos optimizada con índices
- Estructura de archivos limpia y organizada

¡Disfruta usando Videra! 🎉
