#!/bin/bash

# Script para ver logs del servidor de Laravel en tiempo real
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

LOG_FILE="logs/laravel-server.log"

print_status "=== Visualizador de Logs del Servidor ==="
echo ""

# Verificar si el archivo de logs existe
if [ ! -f "$LOG_FILE" ]; then
    print_error "❌ No se encontró el archivo de logs: $LOG_FILE"
    print_status "El servidor debe estar ejecutándose para generar logs"
    echo ""
    print_status "Para iniciar el servidor: ./dev.sh start"
    exit 1
fi

# Mostrar información del archivo de logs
LOG_SIZE=$(du -h "$LOG_FILE" | cut -f1)
LOG_LINES=$(wc -l < "$LOG_FILE")
print_success "✅ Archivo de logs encontrado"
print_status "Tamaño: $LOG_SIZE"
print_status "Líneas: $LOG_LINES"
echo ""

# Verificar si el servidor está ejecutándose
if lsof -ti:8000 >/dev/null 2>&1; then
    print_success "✅ Servidor ejecutándose - Logs en tiempo real"
else
    print_warning "⚠️  Servidor no ejecutándose - Mostrando logs estáticos"
fi

echo ""
print_status "Mostrando logs (Ctrl+C para salir):"
echo "----------------------------------------"

# Mostrar logs en tiempo real
if [ -t 0 ]; then
    # Terminal interactiva
    tail -f "$LOG_FILE"
else
    # No interactivo, mostrar últimas líneas
    tail -n 20 "$LOG_FILE"
fi 