#!/bin/bash

# Script para iniciar el servidor de Laravel
# Autor: Sistema
# Fecha: $(date)

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
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

# Función para limpiar al salir
cleanup() {
    print_warning "Deteniendo servidor..."
    if [ ! -z "$SERVER_PID" ]; then
        kill $SERVER_PID 2>/dev/null
    fi
    print_success "Servidor detenido correctamente"
    exit 0
}

# Capturar señal de interrupción
trap cleanup SIGINT SIGTERM

# Verificar si estamos en el directorio correcto
if [ ! -f "artisan" ]; then
    print_error "No se encontró el archivo artisan. Asegúrate de estar en el directorio raíz de Laravel."
    exit 1
fi

# Verificar si PHP está instalado
if ! command -v php &> /dev/null; then
    print_error "PHP no está instalado o no está en el PATH"
    exit 1
fi

# Verificar si Composer está instalado
if ! command -v composer &> /dev/null; then
    print_warning "Composer no está instalado. Algunas funcionalidades pueden no estar disponibles."
fi

print_status "=== Iniciando Servidor de Laravel ==="
print_status "Directorio actual: $(pwd)"
print_status "Fecha y hora: $(date)"

# Limpiar caché antes de iniciar
print_status "Limpiando caché..."
php artisan config:clear > /dev/null 2>&1
php artisan cache:clear > /dev/null 2>&1
php artisan route:clear > /dev/null 2>&1
php artisan view:clear > /dev/null 2>&1
print_success "Caché limpiada"

# Verificar si el puerto 8000 está disponible
if lsof -Pi :8000 -sTCP:LISTEN -t >/dev/null 2>&1; then
    print_warning "El puerto 8000 ya está en uso. Intentando detener proceso anterior..."
    pkill -f "php artisan serve" 2>/dev/null
    sleep 2
fi

# Mostrar información del servidor
print_status "Configuración del servidor:"
echo "  - Host: 0.0.0.0 (accesible desde cualquier IP)"
echo "  - Puerto: 8000"
echo "  - URL local: http://localhost:8000"
echo "  - URL red: http://$(hostname -I | awk '{print $1}'):8000"
echo "  - Modo: Background (se mantiene activo al cerrar terminal)"
echo ""

# Crear directorio de logs si no existe
mkdir -p logs

# Iniciar el servidor en background con nohup
print_status "Iniciando servidor de Laravel en background..."
print_status "El servidor se mantendrá activo aunque cierres la terminal"
echo ""

# Iniciar el servidor con nohup para que persista
nohup php artisan serve --host=0.0.0.0 --port=8000 > logs/laravel-server.log 2>&1 &
SERVER_PID=$!

# Esperar un momento para que el servidor inicie
sleep 3

# Verificar si el servidor está funcionando
if kill -0 $SERVER_PID 2>/dev/null; then
    print_success "Servidor iniciado correctamente!"
    print_status "Servidor ejecutándose con PID: $SERVER_PID"
    print_status "Logs guardados en: logs/laravel-server.log"
    echo ""
    print_status "URLs disponibles:"
    echo "  🌐 Local: http://localhost:8000"
    echo "  🌐 Red:  http://$(hostname -I | awk '{print $1}'):8000"
    echo "  🌐 Dominio: https://videra.subwaycardgt.com"
    echo ""
    print_status "Comandos útiles:"
    echo "  📊 Ver estado: ./dev.sh status"
    echo "  📋 Ver logs: tail -f logs/laravel-server.log"
    echo "  🛑 Detener: ./dev.sh stop"
    echo ""
    print_success "✅ El servidor continuará ejecutándose en background"
    print_status "Puedes cerrar esta terminal sin problemas"
    echo ""
    
    # Guardar PID en archivo para referencia
    echo $SERVER_PID > logs/laravel-server.pid
    print_status "PID guardado en: logs/laravel-server.pid"
    
    # Mostrar últimos logs
    echo ""
    print_status "Últimos logs del servidor:"
    echo "----------------------------------------"
    tail -n 10 logs/laravel-server.log 2>/dev/null || echo "No hay logs disponibles aún"
    
else
    print_error "Error al iniciar el servidor"
    print_status "Revisa los logs en: logs/laravel-server.log"
    exit 1
fi

# Si se ejecuta en modo interactivo, mantener abierto
if [ -t 0 ]; then
    echo ""
    print_status "Presiona Ctrl+C para salir (el servidor seguirá ejecutándose)"
    echo ""
    
    # Mostrar logs en tiempo real
    tail -f logs/laravel-server.log
else
    # Modo no interactivo, solo salir
    print_success "Servidor iniciado en background"
    exit 0
fi 