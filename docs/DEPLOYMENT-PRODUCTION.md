# Guía de Despliegue a Producción - API REST Subway Guatemala

**Documento**: Guía de Deployment
**Fecha**: Noviembre 2025
**Versión**: 1.0
**Proyecto**: Subway Admin Panel - API REST

---

## Tabla de Contenidos

1. [Pre-requisitos](#pre-requisitos)
2. [Configuración del Entorno (.env)](#configuración-del-entorno-env)
3. [Comandos de Despliegue](#comandos-de-despliegue)
4. [Verificación Post-Deploy](#verificación-post-deploy)
5. [Troubleshooting](#troubleshooting)
6. [Rollback](#rollback)

---

## Pre-requisitos

### Servidor

- PHP 8.4 o superior
- Composer instalado
- MySQL/MariaDB 10.3+
- Nginx o Apache
- Acceso SSH al servidor
- Certificado SSL (HTTPS obligatorio para OAuth)

### Archivos Necesarios

- Firebase credentials: `storage/app/firebase/credentials.json`
- Google OAuth credentials (si se usa)

### Backup

```bash
# SIEMPRE hacer backup antes de desplegar
mysqldump -u usuario -p nombre_base_datos > backup_$(date +%Y%m%d_%H%M%S).sql
```

---

## Configuración del Entorno (.env)

### Variables Críticas a Cambiar

```bash
# ═════════════════════════════════════════════════════════════
# ENTORNO - CRÍTICO
# ═════════════════════════════════════════════════════════════
APP_ENV=production
APP_DEBUG=false  # ⚠️ NUNCA true en producción
APP_URL=https://tu-dominio-produccion.com

# ═════════════════════════════════════════════════════════════
# SWAGGER
# ═════════════════════════════════════════════════════════════
L5_SWAGGER_CONST_HOST=https://tu-dominio-produccion.com

# ═════════════════════════════════════════════════════════════
# CORS - Frontend URL
# ═════════════════════════════════════════════════════════════
# Si tienes app web/móvil separada:
FRONTEND_URL=https://app.subwayguatemala.com
# Si no tienes frontend separado, déjala vacía o usa *

# ═════════════════════════════════════════════════════════════
# LOGS
# ═════════════════════════════════════════════════════════════
LOG_LEVEL=error  # En producción: error o critical

# ═════════════════════════════════════════════════════════════
# BASE DE DATOS
# ═════════════════════════════════════════════════════════════
DB_CONNECTION=mariadb
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=subwayapp_produccion
DB_USERNAME=usuario_produccion
DB_PASSWORD=password_seguro_produccion

# ═════════════════════════════════════════════════════════════
# FIREBASE (Notificaciones Push)
# ═════════════════════════════════════════════════════════════
# Usar ruta relativa (recomendado):
FIREBASE_CREDENTIALS=storage/app/firebase/credentials.json

# O ruta absoluta del servidor:
# FIREBASE_CREDENTIALS=/var/www/html/subway-admin/storage/app/firebase/credentials.json

# ═════════════════════════════════════════════════════════════
# EMAIL (para reset password)
# ═════════════════════════════════════════════════════════════
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=tu-email@gmail.com
MAIL_PASSWORD=tu-app-password  # Usar App Password de Gmail
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@subwayguatemala.com
MAIL_FROM_NAME="Subway Guatemala"

# ═════════════════════════════════════════════════════════════
# OAUTH (opcional - si usas login social)
# ═════════════════════════════════════════════════════════════

# Google OAuth
# Obtener de: https://console.cloud.google.com/apis/credentials
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
```

### Diferencias Local vs Producción

| Variable | Local | Producción |
|----------|-------|------------|
| `APP_ENV` | `local` | `production` ⚠️ |
| `APP_DEBUG` | `true` | `false` ⚠️ |
| `APP_URL` | `http://localhost` | `https://dominio.com` |
| `LOG_LEVEL` | `debug` | `error` |
| `DB_DATABASE` | `subwayapp` | `subwayapp_produccion` |
| `MAIL_MAILER` | `log` | `smtp` |
| `FIREBASE_CREDENTIALS` | Ruta absoluta local | Ruta relativa |

---

## Comandos de Despliegue

### Orden de Ejecución (Copia y Pega)

```bash
# ═════════════════════════════════════════════════════════════
# 1. NAVEGACIÓN Y BACKUP
# ═════════════════════════════════════════════════════════════

cd /var/www/html/tu-proyecto

# Backup de base de datos (IMPORTANTE)
mysqldump -u usuario -p subwayapp_produccion > backup_$(date +%Y%m%d_%H%M%S).sql

# ═════════════════════════════════════════════════════════════
# 2. ACTUALIZAR CÓDIGO
# ═════════════════════════════════════════════════════════════

git pull origin main

# ═════════════════════════════════════════════════════════════
# 3. INSTALAR DEPENDENCIAS (sin dev)
# ═════════════════════════════════════════════════════════════

composer install --no-dev --optimize-autoloader

# ═════════════════════════════════════════════════════════════
# 4. VERIFICAR FIREBASE CREDENTIALS
# ═════════════════════════════════════════════════════════════

ls -la storage/app/firebase/credentials.json

# Si no existe, subirlo manualmente

# ═════════════════════════════════════════════════════════════
# 5. EJECUTAR MIGRACIONES
# ═════════════════════════════════════════════════════════════

php artisan migrate --force

# ═════════════════════════════════════════════════════════════
# 6. LIMPIAR CACHES VIEJOS (IMPORTANTE)
# ═════════════════════════════════════════════════════════════

php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# ═════════════════════════════════════════════════════════════
# 7. CACHEAR PARA PRODUCCIÓN (CRÍTICO)
# ═════════════════════════════════════════════════════════════

php artisan config:cache
php artisan route:cache
php artisan view:cache

# ═════════════════════════════════════════════════════════════
# 8. REGENERAR SWAGGER (después de cachear)
# ═════════════════════════════════════════════════════════════

php artisan l5-swagger:generate

# ═════════════════════════════════════════════════════════════
# 9. PERMISOS CORRECTOS
# ═════════════════════════════════════════════════════════════

sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# ═════════════════════════════════════════════════════════════
# 10. REINICIAR SERVICIOS
# ═════════════════════════════════════════════════════════════

sudo systemctl restart php8.4-fpm
sudo systemctl reload nginx
```

### Script de Deploy Automatizado

Crea un archivo `deploy.sh` en la raíz del proyecto:

```bash
#!/bin/bash

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}═══════════════════════════════════════════════${NC}"
echo -e "${GREEN}  SUBWAY ADMIN - Deploy to Production${NC}"
echo -e "${GREEN}═══════════════════════════════════════════════${NC}"

# 1. Backup
echo -e "\n${YELLOW}[1/10] Creando backup de base de datos...${NC}"
mysqldump -u $DB_USERNAME -p$DB_PASSWORD $DB_DATABASE > backup_$(date +%Y%m%d_%H%M%S).sql
echo -e "${GREEN}✓ Backup creado${NC}"

# 2. Git Pull
echo -e "\n${YELLOW}[2/10] Actualizando código desde Git...${NC}"
git pull origin main
echo -e "${GREEN}✓ Código actualizado${NC}"

# 3. Composer
echo -e "\n${YELLOW}[3/10] Instalando dependencias de Composer...${NC}"
composer install --no-dev --optimize-autoloader
echo -e "${GREEN}✓ Dependencias instaladas${NC}"

# 4. Verificar Firebase
echo -e "\n${YELLOW}[4/10] Verificando Firebase credentials...${NC}"
if [ -f "storage/app/firebase/credentials.json" ]; then
    echo -e "${GREEN}✓ Firebase credentials encontrado${NC}"
else
    echo -e "${RED}✗ Firebase credentials NO encontrado${NC}"
    exit 1
fi

# 5. Migraciones
echo -e "\n${YELLOW}[5/10] Ejecutando migraciones...${NC}"
php artisan migrate --force
echo -e "${GREEN}✓ Migraciones completadas${NC}"

# 6. Limpiar caches
echo -e "\n${YELLOW}[6/10] Limpiando caches viejos...${NC}"
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
echo -e "${GREEN}✓ Caches limpiados${NC}"

# 7. Cachear
echo -e "\n${YELLOW}[7/10] Cacheando configuración para producción...${NC}"
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo -e "${GREEN}✓ Configuración cacheada${NC}"

# 8. Swagger
echo -e "\n${YELLOW}[8/10] Regenerando Swagger...${NC}"
php artisan l5-swagger:generate
echo -e "${GREEN}✓ Swagger regenerado${NC}"

# 9. Permisos
echo -e "\n${YELLOW}[9/10] Configurando permisos...${NC}"
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
echo -e "${GREEN}✓ Permisos configurados${NC}"

# 10. Reiniciar servicios
echo -e "\n${YELLOW}[10/10] Reiniciando servicios...${NC}"
sudo systemctl restart php8.4-fpm
sudo systemctl reload nginx
echo -e "${GREEN}✓ Servicios reiniciados${NC}"

echo -e "\n${GREEN}═══════════════════════════════════════════════${NC}"
echo -e "${GREEN}  Deploy completado exitosamente ✓${NC}"
echo -e "${GREEN}═══════════════════════════════════════════════${NC}"
```

Dar permisos de ejecución:

```bash
chmod +x deploy.sh
```

Ejecutar:

```bash
./deploy.sh
```

---

## Verificación Post-Deploy

### 1. Verificar Configuración

```bash
# Verificar APP_ENV
php artisan config:show app.env
# Debe mostrar: production

# Verificar APP_DEBUG
php artisan config:show app.debug
# Debe mostrar: false

# Verificar APP_URL
php artisan config:show app.url
# Debe mostrar: https://tu-dominio.com
```

### 2. Verificar Base de Datos

```bash
# Ver migraciones ejecutadas
php artisan migrate:status

# Verificar conexión
php artisan tinker
>>> DB::connection()->getPdo();
```

### 3. Verificar API

```bash
# Listar rutas API
php artisan route:list --path=api

# Test de login
curl -X POST https://tu-dominio.com/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@subway.gt",
    "password": "password"
  }'

# Verificar Swagger UI
curl https://tu-dominio.com/api/documentation
```

### 4. Verificar Firebase

```bash
# Verificar que existe
ls -la storage/app/firebase/credentials.json

# Verificar permisos
stat storage/app/firebase/credentials.json
# Debe ser readable por www-data
```

### 5. Revisar Logs

```bash
# Ver últimas líneas del log
tail -f storage/logs/laravel.log

# Buscar errores recientes
grep -i "error" storage/logs/laravel.log | tail -20
```

### 6. Test desde Swagger UI

1. Acceder a: `https://tu-dominio.com/api/documentation`
2. Probar endpoint `POST /api/v1/auth/login`
3. Copiar token de respuesta
4. Click en botón "Authorize"
5. Pegar token en formato: `Bearer {token}`
6. Probar endpoint protegido como `GET /api/v1/profile`

---

## Troubleshooting

### Error: "Route [login] not defined"

**Causa**: Cache de rutas desactualizado

**Solución**:

```bash
php artisan route:clear
php artisan route:cache
```

### Error: "Class config does not exist"

**Causa**: Cache de config corrupto

**Solución**:

```bash
php artisan config:clear
composer dump-autoload
php artisan config:cache
```

### Error: "Unable to locate file in Vite manifest"

**Causa**: Assets no compilados

**Solución**:

```bash
npm run build
```

### Error: "SQLSTATE[HY000] [2002] Connection refused"

**Causa**: Base de datos no accesible

**Solución**:

```bash
# Verificar servicio MySQL
sudo systemctl status mysql

# Verificar credenciales en .env
php artisan config:show database.connections.mysql
```

### Error 500 en /api/documentation

**Causa**: Swagger no regenerado o permisos incorrectos

**Solución**:

```bash
# Regenerar Swagger
php artisan l5-swagger:generate

# Verificar permisos
sudo chmod -R 775 storage/api-docs
sudo chown -R www-data:www-data storage/api-docs
```

### Error: "Firebase credentials not found"

**Causa**: Archivo no existe o ruta incorrecta

**Solución**:

```bash
# Verificar archivo
ls -la storage/app/firebase/credentials.json

# Verificar variable en .env
grep FIREBASE_CREDENTIALS .env

# Si usas ruta relativa, debe ser:
FIREBASE_CREDENTIALS=storage/app/firebase/credentials.json
```

### Rate Limiting muy agresivo

**Causa**: Cache de rutas con limiters viejos

**Solución**:

```bash
php artisan route:clear
php artisan cache:clear
php artisan route:cache
```

### CORS errors desde frontend

**Causa**: FRONTEND_URL no configurado

**Solución**:

```bash
# Agregar a .env
FRONTEND_URL=https://tu-frontend.com

# Limpiar y cachear
php artisan config:clear
php artisan config:cache
```

### Logs muy grandes

**Solución**:

```bash
# Rotar logs manualmente
mv storage/logs/laravel.log storage/logs/laravel-$(date +%Y%m%d).log

# O vaciar
> storage/logs/laravel.log
```

---

## Rollback

### Rollback de Base de Datos

```bash
# Restaurar desde backup
mysql -u usuario -p nombre_base_datos < backup_20251107_153000.sql

# O hacer rollback de última migración
php artisan migrate:rollback --step=1
```

### Rollback de Código

```bash
# Ver últimos commits
git log --oneline -5

# Rollback a commit anterior
git reset --hard <commit-hash>

# Forzar push (si ya hiciste push)
git push --force origin main

# Redeployar
./deploy.sh
```

### Rollback Total (Emergencia)

```bash
# 1. Restaurar base de datos
mysql -u usuario -p nombre_base_datos < backup_ultima_version.sql

# 2. Rollback código
git reset --hard <commit-hash-estable>
git push --force origin main

# 3. Limpiar todo
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 4. Reinstalar
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan l5-swagger:generate

# 5. Reiniciar
sudo systemctl restart php8.4-fpm
sudo systemctl reload nginx
```

---

## Checklist Final

Antes de dar por terminado el deploy, verificar:

- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`
- [ ] `APP_URL` correcto con HTTPS
- [ ] Base de datos de producción configurada
- [ ] Firebase credentials existe y es accesible
- [ ] Migraciones ejecutadas sin errores
- [ ] Caches regenerados (config, route, view)
- [ ] Swagger regenerado y accesible
- [ ] Permisos de storage correctos (775)
- [ ] Owner de storage es www-data
- [ ] PHP-FPM reiniciado
- [ ] Nginx reloaded
- [ ] Test de login funciona
- [ ] Swagger UI accesible
- [ ] Endpoints protegidos requieren token
- [ ] CORS permite requests desde frontend
- [ ] Logs no muestran errores críticos
- [ ] Email de reset password funciona (si configurado)
- [ ] Rate limiting funciona correctamente

---

## Mantenimiento Periódico

### Diario

```bash
# Revisar logs por errores
grep -i "error" storage/logs/laravel.log | tail -50
```

### Semanal

```bash
# Limpiar logs viejos
find storage/logs -name "*.log" -mtime +7 -delete

# Limpiar cache si hay cambios
php artisan cache:clear
```

### Mensual

```bash
# Backup de base de datos
mysqldump -u usuario -p nombre_base_datos > monthly_backup_$(date +%Y%m).sql

# Actualizar dependencias de seguridad
composer update --with-dependencies
```

---

## Contacto y Soporte

**Documentación adicional**:
- [API-REST-AUTH-PLAN.md](./API-REST-AUTH-PLAN.md) - Plan completo de implementación
- [Swagger UI](https://tu-dominio.com/api/documentation) - Documentación interactiva

**En caso de problemas**:
1. Revisar `storage/logs/laravel.log`
2. Verificar configuración con `php artisan config:show`
3. Verificar rutas con `php artisan route:list`
4. Contactar al equipo de desarrollo
