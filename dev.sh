#!/bin/bash

# Script principal para gestión del servidor de desarrollo Laravel
# Autor: Sistema
# Fecha: $(date)

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
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
    echo -e "${PURPLE}    GESTOR DE DESARROLLO LARAVEL${NC}"
    echo -e "${PURPLE}================================${NC}"
    echo ""
}

print_menu() {
    echo -e "${CYAN}Opciones disponibles:${NC}"
    echo ""
    echo -e "  ${GREEN}1)${NC} 🚀 Iniciar servidor"
    echo -e "  ${GREEN}2)${NC} 🛑 Detener servidor"
    echo -e "  ${GREEN}3)${NC} 📊 Ver estado del servidor"
    echo -e "  ${GREEN}4)${NC} 🔄 Reiniciar servidor"
    echo -e "  ${GREEN}5)${NC} 📋 Ver logs en tiempo real"
    echo -e "  ${GREEN}6)${NC} 📖 Ver documentación"
    echo -e "  ${GREEN}7)${NC} 🚪 Salir"
    echo ""
}

# Verificar que estamos en el directorio correcto
if [ ! -f "artisan" ]; then
    print_error "No se encontró el archivo artisan. Asegúrate de estar en el directorio raíz de Laravel."
    exit 1
fi

# Verificar que la carpeta scripts existe
if [ ! -d "scripts" ]; then
    print_error "No se encontró la carpeta scripts. Asegúrate de que los scripts estén en la carpeta scripts/."
    exit 1
fi

# Función para ejecutar scripts
run_script() {
    local script_name=$1
    local script_path="scripts/$script_name"
    
    if [ -f "$script_path" ]; then
        if [ -x "$script_path" ]; then
            "$script_path"
        else
            print_error "El script $script_name no tiene permisos de ejecución"
            chmod +x "$script_path"
            "$script_path"
        fi
    else
        print_error "No se encontró el script: $script_name"
        exit 1
    fi
}

# Función para reiniciar servidor
restart_server() {
    print_status "Reiniciando servidor..."
    run_script "stop-server.sh"
    sleep 2
    run_script "start-server.sh"
}

# Función para mostrar documentación
show_docs() {
    if [ -f "scripts/README-SCRIPTS.md" ]; then
        echo ""
        print_status "Documentación de los scripts:"
        echo "----------------------------------------"
        cat scripts/README-SCRIPTS.md
    else
        print_warning "No se encontró la documentación"
    fi
}

# Función principal del menú
main_menu() {
    while true; do
        clear
        print_header
        print_menu
        
        read -p "Selecciona una opción (1-7): " choice
        
        case $choice in
            1)
                echo ""
                print_status "Iniciando servidor..."
                run_script "start-server.sh"
                echo ""
                read -p "Presiona Enter para continuar..."
                ;;
            2)
                echo ""
                print_status "Deteniendo servidor..."
                run_script "stop-server.sh"
                echo ""
                read -p "Presiona Enter para continuar..."
                ;;
            3)
                echo ""
                run_script "server-status.sh"
                echo ""
                read -p "Presiona Enter para continuar..."
                ;;
            4)
                echo ""
                restart_server
                echo ""
                read -p "Presiona Enter para continuar..."
                ;;
            5)
                echo ""
                run_script "view-logs.sh"
                echo ""
                read -p "Presiona Enter para continuar..."
                ;;
            6)
                echo ""
                show_docs
                echo ""
                read -p "Presiona Enter para continuar..."
                ;;
            7)
                print_success "¡Hasta luego!"
                exit 0
                ;;
            *)
                print_error "Opción inválida. Selecciona 1-7."
                sleep 2
                ;;
        esac
    done
}

# Verificar argumentos de línea de comandos
if [ $# -eq 0 ]; then
    # Modo interactivo
    main_menu
else
    # Modo directo con argumentos
    case $1 in
        "start"|"iniciar"|"1")
            run_script "start-server.sh"
            ;;
        "stop"|"detener"|"2")
            run_script "stop-server.sh"
            ;;
        "status"|"estado"|"3")
            run_script "server-status.sh"
            ;;
        "restart"|"reiniciar"|"4")
            restart_server
            ;;
        "logs"|"log"|"5")
            run_script "view-logs.sh"
            ;;
        "docs"|"documentacion"|"6")
            show_docs
            ;;
        "help"|"ayuda"|"-h"|"--help")
            echo ""
            print_header
            echo -e "${CYAN}Uso:${NC}"
            echo "  ./dev.sh                    # Modo interactivo"
            echo "  ./dev.sh start              # Iniciar servidor"
            echo "  ./dev.sh stop               # Detener servidor"
            echo "  ./dev.sh status             # Ver estado"
            echo "  ./dev.sh restart            # Reiniciar servidor"
            echo "  ./dev.sh logs               # Ver logs en tiempo real"
            echo "  ./dev.sh docs               # Ver documentación"
            echo ""
            echo -e "${CYAN}Comandos rápidos:${NC}"
            echo "  ./dev.sh 1                  # Iniciar servidor"
            echo "  ./dev.sh 2                  # Detener servidor"
            echo "  ./dev.sh 3                  # Ver estado"
            echo "  ./dev.sh 4                  # Reiniciar servidor"
            echo "  ./dev.sh 5                  # Ver logs en tiempo real"
            echo "  ./dev.sh 6                  # Ver documentación"
            echo ""
            ;;
        *)
            print_error "Argumento inválido: $1"
            echo "Usa './dev.sh help' para ver las opciones disponibles"
            exit 1
            ;;
    esac
fi 