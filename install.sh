#!/bin/bash

# Script de instalación automatizada para Videra
# Este script configura el sistema desde cero

set -e

echo "🚀 Iniciando instalación de Videra..."
echo "======================================"

# Verificar prerrequisitos
echo "🔍 Verificando prerrequisitos..."

if ! command -v php &> /dev/null; then
    echo "❌ PHP no está instalado. Por favor instala PHP 8.3+"
    exit 1
fi

if ! command -v composer &> /dev/null; then
    echo "❌ Composer no está instalado. Por favor instala Composer"
    exit 1
fi

if ! command -v node &> /dev/null; then
    echo "❌ Node.js no está instalado. Por favor instala Node.js 18+"
    exit 1
fi

if ! command -v npm &> /dev/null; then
    echo "❌ NPM no está instalado. Por favor instala NPM"
    exit 1
fi

echo "✅ Prerrequisitos verificados"

# Verificar versión de PHP
PHP_VERSION=$(php -r "echo PHP_VERSION;")
echo "📋 Versión de PHP: $PHP_VERSION"

# Instalar dependencias PHP
echo "📦 Instalando dependencias PHP..."
composer install --no-interaction --optimize-autoloader

# Instalar dependencias Node.js
echo "📦 Instalando dependencias Node.js..."
npm install

# Configurar entorno
echo "⚙️  Configurando entorno..."
if [ ! -f .env ]; then
    if [ -f .env.example ]; then
        cp .env.example .env
        echo "✅ Archivo .env creado desde .env.example"
    else
        echo "⚠️  Archivo .env.example no encontrado. Creando configuración básica..."
        cat > .env << EOF
APP_NAME="Videra"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000
LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120
EOF
        echo "✅ Archivo .env creado con configuración básica"
    fi
else
    echo "ℹ️  Archivo .env ya existe"
fi

# Generar clave de aplicación
echo "🔑 Generando clave de aplicación..."
php artisan key:generate

# Crear base de datos SQLite si no existe
echo "🗄️  Configurando base de datos..."
if [ ! -f database/database.sqlite ]; then
    touch database/database.sqlite
    echo "✅ Base de datos SQLite creada"
fi

# Ejecutar migraciones y seeders
echo "🔄 Configurando base de datos..."
php artisan migrate:fresh --seed

# Compilar assets
echo "🎨 Compilando assets..."
npm run build

echo ""
echo "🎉 ¡Instalación completada exitosamente!"
echo "======================================"
echo ""
echo "🔐 Credenciales de acceso:"
echo "   URL: http://localhost:8000"
echo "   Email: admin@admin.com"
echo "   Contraseña: admin"
echo ""
echo "🚀 Para iniciar el servidor:"
echo "   php artisan serve"
echo ""
echo "✨ ¡Bienvenido a Videra!"
