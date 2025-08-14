#!/bin/bash

echo "🔍 INICIANDO DEBUG DE AUDITORÍA..."
echo "========================================"
echo "Monitoreando logs de Laravel en tiempo real..."
echo "Presiona Ctrl+C para detener"
echo ""

# Limpiar logs anteriores
sudo truncate -s 0 storage/logs/laravel.log

# Mostrar logs en tiempo real
tail -f storage/logs/laravel.log | grep -E "(AUDIT CONTROLLER DEBUG|Filtros aplicados|Resultados de queries|Paginación calculada|URLs de paginación)" --color=always
