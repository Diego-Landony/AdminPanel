#!/bin/bash

# Script para detener el servidor de Laravel
# Autor: Sistema

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

print_status "=== Deteniendo Servidor de Laravel ==="

# Buscar PID guardado
PID_FILE="logs/laravel-server.pid"
if [ -f "$PID_FILE" ]; then
    SAVED_PID=$(cat "$PID_FILE")
    print_status "PID guardado encontrado: $SAVED_PID"
    
    # Verificar si el proceso aún existe
    if kill -0 $SAVED_PID 2>/dev/null; then
        print_status "Deteniendo proceso guardado PID: $SAVED_PID"
        kill -TERM $SAVED_PID 2>/dev/null
        
        # Esperar un momento y verificar si se detuvo
        sleep 2
        if kill -0 $SAVED_PID 2>/dev/null; then
            print_warning "Proceso $SAVED_PID no se detuvo, forzando terminación..."
            kill -KILL $SAVED_PID 2>/dev/null
        fi
        
        # Limpiar archivo PID
        rm -f "$PID_FILE"
        print_success "Proceso guardado detenido"
    else
        print_warning "El proceso guardado ya no existe"
        rm -f "$PID_FILE"
    fi
fi

# Buscar procesos de Laravel en el puerto 8000
LARAVEL_PIDS=$(lsof -ti:8000 2>/dev/null)

if [ -z "$LARAVEL_PIDS" ]; then
    print_success "✅ No hay servidor ejecutándose en el puerto 8000"
    exit 0
fi

print_status "Procesos encontrados en puerto 8000: $LARAVEL_PIDS"

# Detener procesos de Laravel
for pid in $LARAVEL_PIDS; do
    print_status "Deteniendo proceso PID: $pid"
    kill -TERM $pid 2>/dev/null
    
    # Esperar un momento y verificar si se detuvo
    sleep 2
    if kill -0 $pid 2>/dev/null; then
        print_warning "Proceso $pid no se detuvo, forzando terminación..."
        kill -KILL $pid 2>/dev/null
    fi
done

# Verificar que el puerto esté libre
sleep 1
if lsof -ti:8000 >/dev/null 2>&1; then
    print_error "El puerto 8000 aún está en uso"
    exit 1
else
    print_success "✅ Servidor de Laravel detenido correctamente"
    print_status "Puerto 8000 liberado"
    
    # Limpiar archivos de PID si existen
    rm -f "$PID_FILE"
    
    # Mostrar información de logs
    if [ -f "logs/laravel-server.log" ]; then
        echo ""
        print_status "Logs del servidor disponibles en: logs/laravel-server.log"
        print_status "Para ver logs: tail -f logs/laravel-server.log"
    fi
fi 