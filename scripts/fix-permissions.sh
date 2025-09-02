#!/bin/bash

# Script para corregir permisos del proyecto Laravel
# Autor: Sistema
# Fecha: $(date)

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
NC='\033[0m' # No Color

# Función para mostrar mensajes con colores
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

print_header() {
    echo -e "${PURPLE}================================${NC}"
    echo -e "${PURPLE}    CORRECCIÓN DE PERMISOS LARAVEL${NC}"
    echo -e "${PURPLE}================================${NC}"
    echo ""
}

# Verificar que estamos en el directorio correcto
if [ ! -f "artisan" ]; then
    print_error "No se encontró el archivo artisan. Asegúrate de estar en el directorio raíz de Laravel."
    exit 1
fi

print_header
print_status "Iniciando corrección de permisos..."

# 1. Agregar usuario ubuntu al grupo www-data
print_status "1. Agregando usuario ubuntu al grupo www-data..."
if groups ubuntu | grep -q www-data; then
    print_success "Usuario ubuntu ya está en el grupo www-data"
else
    sudo usermod -a -G www-data ubuntu
    if [ $? -eq 0 ]; then
        print_success "Usuario ubuntu agregado al grupo www-data"
    else
        print_error "Error al agregar usuario al grupo www-data"
        exit 1
    fi
fi

# 2. Cambiar propietario de todo el proyecto a www-data
print_status "2. Cambiando propietario del proyecto a www-data..."
sudo chown -R www-data:www-data /var/www/html/videra
if [ $? -eq 0 ]; then
    print_success "Propietario cambiado a www-data:www-data"
else
    print_error "Error al cambiar propietario"
    exit 1
fi

# 3. Establecer permisos del directorio raíz
print_status "3. Estableciendo permisos del directorio raíz..."
sudo chmod -R 775 /var/www/html/videra
if [ $? -eq 0 ]; then
    print_success "Permisos 775 establecidos en el directorio raíz"
else
    print_error "Error al establecer permisos del directorio raíz"
    exit 1
fi

# 4. Establecer permisos especiales para storage y bootstrap/cache
print_status "4. Estableciendo permisos especiales para storage y bootstrap/cache..."
sudo chmod -R 777 storage
sudo chmod -R 777 bootstrap/cache
if [ $? -eq 0 ]; then
    print_success "Permisos 777 establecidos en storage y bootstrap/cache"
else
    print_error "Error al establecer permisos especiales"
    exit 1
fi

# 5. Establecer permisos específicos para la base de datos
print_status "5. Estableciendo permisos de la base de datos..."
if [ -f "database/database.sqlite" ]; then
    sudo chmod 664 database/database.sqlite
    sudo chown www-data:www-data database/database.sqlite
    print_success "Permisos de base de datos corregidos"
else
    print_warning "No se encontró database.sqlite"
fi

# 6. Establecer permisos para directorio database
print_status "6. Estableciendo permisos del directorio database..."
sudo chmod 775 database/
sudo chown www-data:www-data database/
print_success "Permisos del directorio database corregidos"

# 7. Limpiar cache de Laravel
print_status "7. Limpiando cache de Laravel..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache
print_success "Cache de Laravel limpiado y regenerado"

# 8. Regenerar autoload de Composer
print_status "8. Regenerando autoload de Composer..."
composer dump-autoload
if [ $? -eq 0 ]; then
    print_success "Autoload de Composer regenerado"
else
    print_warning "Error al regenerar autoload de Composer"
fi

# 9. Verificar permisos finales
print_status "9. Verificando permisos finales..."
echo ""
echo "Permisos del directorio raíz:"
ls -la | head -5
echo ""
echo "Permisos de storage:"
ls -la storage/ | head -3
echo ""
echo "Permisos de database:"
ls -la database/ | head -3
echo ""
echo "Propietario del proyecto:"
ls -ld /var/www/html/videra

# 10. Mostrar información del usuario y grupo
print_status "10. Información del usuario y grupo:"
echo "Usuario actual: $(whoami)"
echo "Grupos del usuario ubuntu:"
groups ubuntu
echo ""
echo "Grupos del usuario www-data:"
groups www-data

print_success "¡Corrección de permisos completada!"
print_status "Recomendación: Reinicia el servidor web (Apache/Nginx) si es necesario"
print_status "También puedes reiniciar la sesión del usuario ubuntu para que los cambios de grupo surtan efecto"
