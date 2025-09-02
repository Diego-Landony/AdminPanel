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

# Verificar si el script tiene permisos de ejecución
if [ ! -x "$(command -v $0)" ]; then
    echo -e "${RED}Error: El script no tiene permisos de ejecución${NC}"
    echo -e "Por favor, ejecute: ${BLUE}chmod +x $0${NC}"
    exit 1
fi

# Verificar si se está ejecutando como root cuando es necesario
if [ "$EUID" -eq 0 ]; then
    echo -e "${BLUE}Ejecutando con privilegios de superusuario${NC}"
elif [ -w "/var/www/html/AdminSubwayApp" ]; then
    echo -e "${BLUE}Ejecutando con permisos de usuario${NC}"
else
    echo -e "${RED}Error: Se requieren privilegios de superusuario para algunas operaciones${NC}"
    echo -e "Por favor, ejecute: ${BLUE}sudo -E $0${NC}"
    exit 1
fi

echo -e "${BLUE}=== Script de Instalación ===${NC}\n"

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

# Función para instalar dependencias
install_dependencies() {
    log "info" "Instalando dependencias..."
    
    log "info" "Instalando dependencias de PHP..."
    if ! composer install --no-interaction; then
        log "error" "Error al instalar dependencias de PHP"
        exit 1
    fi
    
    log "info" "Instalando dependencias de Node.js..."
    if ! npm install; then
        log "error" "Error al instalar dependencias de Node.js"
        exit 1
    fi
}

# Iniciar instalación
log "info" "=== Iniciando instalación ==="

# Verificar requisitos
check_system_requirements
if ! command -v php &> /dev/null; then
    echo -e "${RED}❌ PHP no está instalado. Por favor instala PHP 8.3+${NC}"
    exit 1
fi

PHP_VERSION=$(php -v | head -n 1 | cut -d " " -f 2 | cut -d "." -f 1,2)
if (( $(echo "$PHP_VERSION < 8.3" | bc -l) )); then
    echo -e "${RED}❌ Se requiere PHP 8.3 o superior. Versión actual: $PHP_VERSION${NC}"
    exit 1
else
    echo -e "${GREEN}✓ PHP $PHP_VERSION${NC}"
fi

# Verificar Composer y su versión
if ! command -v composer &> /dev/null; then
    echo -e "${RED}❌ Composer no está instalado. Por favor instala Composer${NC}"
    exit 1
fi

COMPOSER_VERSION=$(composer --version | cut -d " " -f 3)
echo -e "${GREEN}✓ Composer $COMPOSER_VERSION${NC}"

# Verificar extensiones de PHP requeridas
REQUIRED_EXTENSIONS=("pdo" "pdo_mysql" "pdo_sqlite" "json" "fileinfo" "tokenizer" "mbstring" "xml" "curl")
MISSING_EXTENSIONS=()

for ext in "${REQUIRED_EXTENSIONS[@]}"; do
    if ! php -m | grep -q "^$ext$"; then
        MISSING_EXTENSIONS+=($ext)
    fi
done

if [ ${#MISSING_EXTENSIONS[@]} -ne 0 ]; then
    echo -e "${RED}❌ Faltan las siguientes extensiones de PHP:${NC}"
    for ext in "${MISSING_EXTENSIONS[@]}"; do
        echo -e "${RED}   - $ext${NC}"
    done
    
    # Verificar si estamos en un sistema Debian/Ubuntu
    if command -v apt-get &> /dev/null; then
        echo -e "\n${BLUE}Se detectó sistema Debian/Ubuntu${NC}"
        echo -e "${BLUE}Intentando instalar las extensiones faltantes...${NC}"
        
        # Construir lista de paquetes
        PACKAGES_TO_INSTALL=()
        for ext in "${MISSING_EXTENSIONS[@]}"; do
            case $ext in
                "pdo") PACKAGES_TO_INSTALL+=("php8.3-common") ;;
                "pdo_mysql") PACKAGES_TO_INSTALL+=("php8.3-mysql") ;;
                "pdo_sqlite") PACKAGES_TO_INSTALL+=("php8.3-sqlite3") ;;
                "json") PACKAGES_TO_INSTALL+=("php8.3-json") ;;
                "fileinfo") PACKAGES_TO_INSTALL+=("php8.3-common") ;;
                "tokenizer") PACKAGES_TO_INSTALL+=("php8.3-common") ;;
                "mbstring") PACKAGES_TO_INSTALL+=("php8.3-mbstring") ;;
                "xml") PACKAGES_TO_INSTALL+=("php8.3-xml") ;;
                "curl") PACKAGES_TO_INSTALL+=("php8.3-curl") ;;
            esac
        done
        
        if [ ${#PACKAGES_TO_INSTALL[@]} -ne 0 ]; then
            echo -e "${BLUE}Instalando paquetes: ${PACKAGES_TO_INSTALL[*]}${NC}"
            if apt-get update && apt-get install -y "${PACKAGES_TO_INSTALL[@]}"; then
                echo -e "${GREEN}✓ Extensiones instaladas correctamente${NC}"
                echo -e "${BLUE}Reiniciando PHP-FPM...${NC}"
                systemctl restart php8.3-fpm || true
                
                # Verificar nuevamente las extensiones
                MISSING_EXTENSIONS=()
                for ext in "${REQUIRED_EXTENSIONS[@]}"; do
                    if ! php -m | grep -q "^$ext$"; then
                        MISSING_EXTENSIONS+=($ext)
                    fi
                done
                
                if [ ${#MISSING_EXTENSIONS[@]} -eq 0 ]; then
                    echo -e "${GREEN}✓ Todas las extensiones están ahora instaladas${NC}"
                else
                    echo -e "${RED}❌ Algunas extensiones aún faltan. Por favor, instálalas manualmente:${NC}"
                    for ext in "${MISSING_EXTENSIONS[@]}"; do
                        echo -e "${RED}   - $ext${NC}"
                    done
                    exit 1
                fi
            else
                echo -e "${RED}❌ Error al instalar las extensiones${NC}"
                echo -e "${BLUE}Por favor, instala las extensiones manualmente:${NC}"
                echo -e "sudo apt-get install ${PACKAGES_TO_INSTALL[*]}"
                exit 1
            fi
        fi
    else
        echo -e "${RED}Este script no puede instalar automáticamente las extensiones en tu sistema.${NC}"
        echo -e "${BLUE}Por favor, instala las extensiones manualmente según tu sistema operativo:${NC}"
        for ext in "${MISSING_EXTENSIONS[@]}"; do
            echo -e "   - $ext"
        done
        exit 1
    fi
fi

echo -e "${GREEN}✓ Todas las extensiones de PHP requeridas están instaladas${NC}"

# Verificar Node.js y su versión
if ! command -v node &> /dev/null; then
    echo -e "${RED}❌ Node.js no está instalado. Por favor instala Node.js 18+${NC}"
    exit 1
fi

# Verificar versión de Node.js
NODE_VERSION=$(node -v | cut -d "v" -f 2)
NODE_MAJOR_VERSION=$(echo $NODE_VERSION | cut -d. -f1)
if [ "$NODE_MAJOR_VERSION" -lt 18 ]; then
    echo -e "${RED}❌ Se requiere Node.js 18 o superior. Versión actual: $NODE_VERSION${NC}"
    exit 1
else
    echo -e "${GREEN}✓ Node.js $NODE_VERSION${NC}"
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

# Solicitar información básica
echo -e "\n${BLUE}Configuración de la aplicación${NC}"
read -p "Nombre de la aplicación [Laravel]: " app_name
app_name=${app_name:-Laravel}

read -p "URL de la aplicación [http://localhost]: " app_url
app_url=${app_url:-http://localhost}

# Función para convertir bytes a formato legible
format_size() {
    local size=$1
    local units=("B" "KB" "MB" "GB" "TB")
    local unit=0
    
    while [ $size -gt 1024 ] && [ $unit -lt 4 ]; do
        size=$(($size/1024))
        unit=$((unit+1))
    done
    
    echo "$size${units[$unit]}"
}

# Función para verificar espacio disponible
check_disk_space() {
    local path=$1
    local min_space=$2  # en MB
    
    # Obtener espacio libre en bytes
    local free_space=$(df --output=avail "$path" | tail -n1)
    free_space=$((free_space * 1024))  # Convertir KB a bytes
    local free_space_mb=$((free_space / 1024 / 1024))
    
    local formatted_free=$(format_size $free_space)
    local formatted_min=$(format_size $((min_space * 1024 * 1024)))
    
    echo -e "${BLUE}Espacio disponible en $path: $formatted_free${NC}"
    
    if [ $free_space_mb -lt $min_space ]; then
        echo -e "${RED}Error: Se requiere al menos $formatted_min de espacio libre${NC}"
        return 1
    fi
    return 0
}

# Preguntar por el tipo de base de datos
echo -e "\n${BLUE}Seleccione el tipo de base de datos:${NC}"
echo "1) SQLite (recomendado para desarrollo)"
echo "2) MySQL"
read -p "Selección [1]: " db_choice
db_choice=${db_choice:-1}

# Verificar espacio en disco antes de continuar
echo -e "\n${BLUE}Verificando espacio en disco...${NC}"
if [ "$db_choice" = "1" ]; then
    # Para SQLite, verificar espacio en el directorio del proyecto
    if ! check_disk_space "./database" 100; then  # Requiere 100MB mínimo
        echo -e "${RED}No hay suficiente espacio para la base de datos SQLite${NC}"
        exit 1
    fi
else
    # Para MySQL, verificar espacio en /var/lib/mysql
    if ! check_disk_space "/var/lib/mysql" 500; then  # Requiere 500MB mínimo
        echo -e "${RED}No hay suficiente espacio para la base de datos MySQL${NC}"
        exit 1
    fi
fi

# Configurar variables de base de datos
if [ "$db_choice" = "1" ]; then
    db_connection="sqlite"
    mkdir -p database
    touch database/database.sqlite
    echo -e "${GREEN}Base de datos SQLite creada en database/database.sqlite${NC}"
else
    db_connection="mysql"
    read -p "Host de MySQL [127.0.0.1]: " db_host
    db_host=${db_host:-127.0.0.1}
    
    read -p "Puerto de MySQL [3306]: " db_port
    db_port=${db_port:-3306}
    
    read -p "Nombre de la base de datos: " db_name
    while [ -z "$db_name" ]; do
        echo -e "${RED}El nombre de la base de datos es requerido${NC}"
        read -p "Nombre de la base de datos: " db_name
    done
    
    read -p "Usuario de MySQL [root]: " db_user
    db_user=${db_user:-root}
    
    read -s -p "Contraseña de MySQL: " db_password
    echo ""
    
    # Verificar conexión a MySQL
    echo -e "\n${BLUE}Verificando conexión a MySQL...${NC}"
    if ! mysql -h "$db_host" -P "$db_port" -u "$db_user" -p"$db_password" -e ";" 2>/dev/null; then
        echo -e "${RED}No se pudo conectar a MySQL. Verifique las credenciales e intente nuevamente.${NC}"
        exit 1
    fi
    
    # Crear base de datos si no existe
    echo -e "${BLUE}Creando base de datos si no existe...${NC}"
    mysql -h "$db_host" -P "$db_port" -u "$db_user" -p"$db_password" -e "CREATE DATABASE IF NOT EXISTS \`$db_name\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    
    if [ $? -ne 0 ]; then
        echo -e "${RED}Error al crear la base de datos${NC}"
        exit 1
    fi
    
    echo -e "${GREEN}Base de datos MySQL configurada correctamente${NC}"
fi

# Crear archivo .env
echo -e "\n${BLUE}Creando archivo .env...${NC}"
cp .env.example .env

# Configurar .env
sed -i "s/APP_NAME=.*/APP_NAME=\"$app_name\"/" .env
sed -i "s#APP_URL=.*#APP_URL=$app_url#" .env
sed -i "s/DB_CONNECTION=.*/DB_CONNECTION=$db_connection/" .env

if [ "$db_choice" = "2" ]; then
    sed -i "s/DB_HOST=.*/DB_HOST=$db_host/" .env
    sed -i "s/DB_PORT=.*/DB_PORT=$db_port/" .env
    sed -i "s/DB_DATABASE=.*/DB_DATABASE=$db_name/" .env
    sed -i "s/DB_USERNAME=.*/DB_USERNAME=$db_user/" .env
    sed -i "s/DB_PASSWORD=.*/DB_PASSWORD=$db_password/" .env
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
echo -e "${BLUE}Configurando base de datos...${NC}"

if [ "$db_choice" = "2" ]; then
    # Para MySQL, asegurarse de que las tablas usen el motor InnoDB
    echo -e "${BLUE}Configurando el motor de base de datos...${NC}"
    php artisan config:clear
    
    # Ejecutar migraciones con reintentos
    MAX_RETRIES=3
    RETRY_COUNT=0
    
    while [ $RETRY_COUNT -lt $MAX_RETRIES ]; do
        if php artisan migrate:fresh --force; then
            break
        else
            RETRY_COUNT=$((RETRY_COUNT+1))
            if [ $RETRY_COUNT -eq $MAX_RETRIES ]; then
                echo -e "${RED}Error al ejecutar las migraciones después de $MAX_RETRIES intentos${NC}"
                exit 1
            fi
            echo -e "${BLUE}Reintentando migración ($RETRY_COUNT de $MAX_RETRIES)...${NC}"
            sleep 5
        fi
    done
else
    # Para SQLite, ejecutar migraciones normalmente
    php artisan migrate:fresh --force
fi

# Ejecutar seeders
echo -e "${BLUE}Poblando la base de datos con datos iniciales...${NC}"
php artisan db:seed --force

# Compilar assets
echo -e "\n${BLUE}Compilando assets...${NC}"
npm run build

# Optimizar la aplicación
echo -e "\n${BLUE}Optimizando la aplicación...${NC}"
php artisan optimize:clear
php artisan optimize

# Configurar permisos
echo -e "\n${BLUE}Configurando permisos...${NC}"
chmod -R 775 storage bootstrap/cache
chown -R $USER:www-data storage bootstrap/cache

echo -e "\n${GREEN}¡Instalación completada con éxito!${NC}"
echo -e "\nPuede acceder a su aplicación en: ${BLUE}$app_url${NC}"
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
