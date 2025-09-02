#!/bin/bash

# Script para verificar el estado del servidor de Laravel
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

print_status "=== Estado del Servidor de Laravel ==="
echo ""

# Verificar archivo PID guardado
PID_FILE="logs/laravel-server.pid"
if [ -f "$PID_FILE" ]; then
    SAVED_PID=$(cat "$PID_FILE")
    print_status "PID guardado: $SAVED_PID"
    
    if kill -0 $SAVED_PID 2>/dev/null; then
        print_success "✅ Proceso guardado activo"
    else
        print_warning "⚠️  Proceso guardado no existe (posible crash)"
        rm -f "$PID_FILE"
    fi
fi

# Verificar si el puerto 8000 está en uso
if lsof -ti:8000 >/dev/null 2>&1; then
    print_success "✅ Servidor ejecutándose en puerto 8000"
    
    # Obtener información del proceso
    LARAVEL_PIDS=$(lsof -ti:8000)
    print_status "Procesos activos: $LARAVEL_PIDS"
    
    # Mostrar información detallada del proceso
    for pid in $LARAVEL_PIDS; do
        echo ""
        print_status "Detalles del proceso PID: $pid"
        ps -p $pid -o pid,ppid,cmd,etime,pcpu,pmem --no-headers 2>/dev/null || echo "No se pudo obtener información del proceso"
    done
    
    # Verificar conectividad
    echo ""
    print_status "Probando conectividad..."
    if curl -s http://localhost:8000 >/dev/null 2>&1; then
        print_success "✅ Servidor responde correctamente"
    else
        print_warning "⚠️  El servidor no responde a las peticiones HTTP"
    fi
    
    # Mostrar URLs disponibles
    echo ""
    print_status "URLs disponibles:"
    echo "  🌐 Local: http://localhost:8000"
    echo "  🌐 Red:  http://$(hostname -I | awk '{print $1}'):8000"
    echo "  🌐 Dominio: https://videra.subwaycardgt.com"
    
else
    print_error "❌ No hay servidor ejecutándose en el puerto 8000"
    echo ""
    print_status "Para iniciar el servidor ejecuta: ./dev.sh start"
fi

# Verificar logs del servidor
echo ""
print_status "=== Logs del Servidor ==="
if [ -f "logs/laravel-server.log" ]; then
    print_success "✅ Archivo de logs encontrado"
    LOG_SIZE=$(du -h logs/laravel-server.log | cut -f1)
    print_status "Tamaño del log: $LOG_SIZE"
    
    echo ""
    print_status "Últimas 5 líneas del log:"
    echo "----------------------------------------"
    tail -n 5 logs/laravel-server.log 2>/dev/null || echo "No hay contenido en el log"
    
    echo ""
    print_status "Para ver logs en tiempo real: tail -f logs/laravel-server.log"
else
    print_warning "⚠️  No se encontró archivo de logs"
fi

echo ""
print_status "=== Información del Sistema ==="
echo "  📅 Fecha: $(date)"
echo "  💻 Hostname: $(hostname)"
echo "  🌐 IP: $(hostname -I | awk '{print $1}')"
echo "  📁 Directorio: $(pwd)"

# Verificar espacio en disco
echo ""
print_status "=== Espacio en Disco ==="
df -h . | tail -1 | awk '{print "  💾 Espacio disponible: " $4 " de " $2}'

# Verificar memoria del sistema
echo ""
print_status "=== Memoria del Sistema ==="
free -h | grep Mem | awk '{print "  🧠 Memoria: " $3 " usado de " $2}'

# Información adicional
echo ""
print_status "=== Comandos Útiles ==="
echo "  🚀 Iniciar: ./dev.sh start"
echo "  🛑 Detener: ./dev.sh stop"
echo "  📊 Estado: ./dev.sh status"
echo "  📋 Logs: tail -f logs/laravel-server.log" 