Resumen: Lo que necesitas agregar a tu .env de producción

# Cambiar esto:
BROADCAST_CONNECTION=reverb

# Agregar esto (generar keys únicas):
REVERB_APP_ID=subway-app
REVERB_APP_KEY=genera_con_php_r_echo_bin2hex_random_bytes_32
REVERB_APP_SECRET=genera_con_php_r_echo_bin2hex_random_bytes_32
REVERB_HOST=appmobile.subwaycardgt.com
REVERB_PORT=8080
REVERB_SCHEME=https
REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8080
Comandos a ejecutar en servidor:

# Generar keys
php -r "echo bin2hex(random_bytes(32));"  # Para REVERB_APP_KEY
php -r "echo bin2hex(random_bytes(32));"  # Para REVERB_APP_SECRET

# Iniciar servidor WebSocket (con supervisor en producción)
php artisan reverb:start --host=0.0.0.0 --port=8080






# ============================================
# LARAVEL REVERB - WebSocket Server
# ============================================

# Cambiar de "log" a "reverb"
BROADCAST_CONNECTION=reverb

# Credenciales de la aplicación (generar valores únicos)
REVERB_APP_ID=subway-app
REVERB_APP_KEY=your-reverb-app-key-here
REVERB_APP_SECRET=your-reverb-app-secret-here

# Host público para WebSocket (donde Flutter se conectará)
REVERB_HOST=appmobile.subwaycardgt.com
REVERB_PORT=8080
REVERB_SCHEME=https

# Servidor interno (donde corre el proceso reverb:start)
REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8080
Generar claves seguras

# En tu servidor, ejecutar:
php artisan reverb:install  # Si no lo has hecho

# O generar manualmente:
php -r "echo bin2hex(random_bytes(32));"  # Para APP_KEY
php -r "echo bin2hex(random_bytes(32));"  # Para APP_SECRET
Iniciar el servidor Reverb

# En producción (con supervisor o systemd):
php artisan reverb:start --host=0.0.0.0 --port=8080

# O en background:
nohup php artisan reverb:start --host=0.0.0.0 --port=8080 &
Configurar Nginx (proxy para WebSocket)
Si usas Nginx, necesitas agregar esto para hacer proxy del WebSocket:

# En tu configuración de Nginx para appmobile.subwaycardgt.com
location /app {
    proxy_pass http://127.0.0.1:8080;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "upgrade";
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_cache_bypass $http_upgrade;
}
¿Quieres que actualice la documentación de Flutter con los detalles de conexión a Reverb?