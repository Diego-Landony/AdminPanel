#!/bin/bash

# Colores para mensajes
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

# Función para mostrar mensajes
log() {
    local level=$1
    local msg=$2
    case $level in
        "info") echo -e "${BLUE}ℹ ${NC}$msg";;
        "success") echo -e "${GREEN}✓ ${NC}$msg";;
        "warning") echo -e "${YELLOW}⚠ ${NC}$msg";;
        "error") echo -e "${RED}✖ ${NC}$msg";;
    esac
}

# Función para preguntar si/no
confirm() {
    local msg=$1
    local default=${2:-"n"}
    
    if [ "$default" = "y" ]; then
        local prompt="Y/n"
        local default="y"
    else
        local prompt="y/N"
        local default="n"
    fi
    
    while true; do
        read -p "$msg [$prompt]: " response
        response=${response:-$default}
        case $response in
            [yY]) return 0 ;;
            [nN]) return 1 ;;
            *) echo "Por favor responde y o n";;
        esac
    done
}

# Función para verificar requisitos del sistema
check_system_requirements() {
    log "info" "Verificando requisitos del sistema..."
    
    # Verificar PHP y su versión
    if ! command -v php &> /dev/null; then
        log "error" "PHP no está instalado. Se requiere PHP 8.3+"
        exit 1
    fi

    PHP_VERSION=$(php -v | head -n 1 | cut -d " " -f 2 | cut -d "." -f 1,2)
    if (( $(echo "$PHP_VERSION < 8.3" | bc -l) )); then
        log "error" "Se requiere PHP 8.3 o superior. Versión actual: $PHP_VERSION"
        exit 1
    fi
    log "success" "PHP $PHP_VERSION"

    # Verificar Composer
    if ! command -v composer &> /dev/null; then
        log "error" "Composer no está instalado"
        exit 1
    fi
    COMPOSER_VERSION=$(composer --version | cut -d " " -f 3)
    log "success" "Composer $COMPOSER_VERSION"

    # Verificar Node.js
    if ! command -v node &> /dev/null; then
        log "error" "Node.js no está instalado. Se requiere Node.js 18+"
        exit 1
    fi
    NODE_VERSION=$(node -v | cut -d "v" -f 2)
    NODE_MAJOR_VERSION=$(echo $NODE_VERSION | cut -d. -f1)
    if [ "$NODE_MAJOR_VERSION" -lt 18 ]; then
        log "error" "Se requiere Node.js 18 o superior. Versión actual: $NODE_VERSION"
        exit 1
    fi
    log "success" "Node.js $NODE_VERSION"
}

# Función para configurar la base de datos
setup_database() {
    local db_type=$1
    local required_extensions=()
    
    case $db_type in
        "sqlite")
            required_extensions=("pdo" "pdo_sqlite" "sqlite3")
            mkdir -p database
            touch database/database.sqlite
            chmod 666 database/database.sqlite
            log "success" "Base de datos SQLite creada en database/database.sqlite"
            ;;
        "mysql")
            required_extensions=("pdo" "pdo_mysql")
            ;;
    esac
    
    # Verificar extensiones necesarias
    local missing_extensions=()
    for ext in "${required_extensions[@]}"; do
        if ! php -m | grep -q "^$ext$"; then
            missing_extensions+=($ext)
        fi
    done
    
    if [ ${#missing_extensions[@]} -ne 0 ]; then
        log "error" "Faltan las siguientes extensiones de PHP para $db_type:"
        for ext in "${missing_extensions[@]}"; do
            echo "   - $ext"
        done
        exit 1
    fi
}

# Función para configurar el entorno
setup_environment() {
    local app_name=$1
    local app_url=$2
    local db_type=$3
    
    log "info" "Configurando archivo .env..."
    if [ ! -f .env ]; then
        cp .env.example .env
    fi
    
    # Configurar variables básicas
    sed -i "s/APP_NAME=.*/APP_NAME=\"$app_name\"/" .env
    sed -i "s#APP_URL=.*#APP_URL=$app_url#" .env
    sed -i "s/DB_CONNECTION=.*/DB_CONNECTION=$db_type/" .env
    
    # Generar clave de aplicación
    php artisan key:generate
    
    log "success" "Archivo .env configurado"
}

# Función para instalar dependencias
install_dependencies() {
    log "info" "Instalando dependencias..."
    
    if [ "$EUID" -eq 0 ]; then
        log "warning" "Ejecutando como root. Las dependencias se instalarán con --no-bin-links"
        npm install --no-bin-links
    else
        npm install
    fi
    
    composer install --no-interaction
    
    log "success" "Dependencias instaladas"
}

# Función para configurar la aplicación
setup_application() {
    log "info" "Configurando la aplicación..."
    
    # Migrar base de datos
    php artisan migrate:fresh --force
    
    # Compilar assets
    if [ "$EUID" -eq 0 ]; then
        npm run build --no-bin-links
    else
        npm run build
    fi
    
    # Optimizar la aplicación
    php artisan optimize:clear
    php artisan optimize
    
    # Configurar permisos
    chmod -R 775 storage bootstrap/cache
    chown -R $USER:www-data storage bootstrap/cache
    
    log "success" "Aplicación configurada"
}

# Iniciar instalación
log "info" "=== Iniciando instalación ==="

# Verificar requisitos
check_system_requirements

# Configuración de la aplicación
log "info" "Configuración de la aplicación"
read -p "Nombre de la aplicación [Laravel]: " app_name
app_name=${app_name:-Laravel}

read -p "URL de la aplicación [http://localhost]: " app_url
app_url=${app_url:-http://localhost}

# Configuración de la base de datos
log "info" "Configuración de la base de datos"
echo "1) SQLite (recomendado para desarrollo)"
echo "2) MySQL"
read -p "Seleccione el tipo de base de datos [1]: " db_choice
db_choice=${db_choice:-1}

# Configurar la base de datos según la elección
case $db_choice in
    1)
        db_type="sqlite"
        setup_database "sqlite"
        ;;
    2)
        if confirm "¿Está seguro que desea usar MySQL? SQLite es más simple para desarrollo" "n"; then
            db_type="mysql"
            setup_database "mysql"
        else
            log "info" "Cambiando a SQLite..."
            db_type="sqlite"
            setup_database "sqlite"
        fi
        ;;
    *)
        log "error" "Opción no válida"
        exit 1
        ;;
esac

# Instalar dependencias
install_dependencies

# Configurar entorno
setup_environment "$app_name" "$app_url" "$db_type"

# Configurar aplicación
setup_application

log "success" "¡Instalación completada con éxito!"
log "info" "Puede acceder a su aplicación en: $app_url"
