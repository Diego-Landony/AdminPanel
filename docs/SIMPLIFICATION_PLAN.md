# Plan de Simplificación: Sistema de Autenticación y Dispositivos

**Fecha de creación**: Noviembre 12, 2025
**Versión**: 1.0
**Estado**: En implementación

---

## Resumen Ejecutivo

Este documento detalla el plan para simplificar el sistema de autenticación eliminando sobre-ingeniería identificada durante auditoría exhaustiva. El objetivo es **reducir complejidad sin perder funcionalidad**.

### Métricas de Simplificación

| Métrica | Antes | Después | Mejora |
|---------|-------|---------|--------|
| Columnas en `customer_devices` | 17 | 9-10 | -47% |
| Parámetros en auth requests | 4 | 1-2 | -50% |
| Superficie de bugs | Alta | Baja | ✅ |
| Mantenibilidad | Compleja | Simple | ✅ |

---

## Problemas Identificados

### 1. ⛔ Columnas que EXISTEN pero NO se LLENAN

```sql
-- En la tabla customer_devices:
device_model   VARCHAR(255) NULL  -- ❌ Nunca se llena
app_version    VARCHAR(50)  NULL  -- ❌ Nunca se llena
os_version     VARCHAR(50)  NULL  -- ❌ Nunca se llena
```

**Impacto**: Espacio desperdiciado, confusión al leer código.

### 2. ⛔ Campos que se LLENAN pero NO se USAN

```sql
device_type        ENUM('ios','android','web')  -- ❌ Solo para nombrar tokens
device_fingerprint VARCHAR(255)                 -- ❌ Se guarda pero no se valida
trust_score        INT DEFAULT 50               -- ❌ Se calcula pero no afecta lógica
```

**Impacto**:
- `device_type`: Causa errores ENUM cuando default es 'app'
- `device_fingerprint`: Data sin propósito
- `trust_score`: Lógica de seguridad incompleta

### 3. ⛔ Parámetro `os` Redundante

```php
// Cliente debe enviar manualmente:
{
  "os": "ios",  // ❌ Puede mentir, no es confiable
  "device_identifier": "ABC-123"
}
```

**Problemas**:
- Todo pasa por la misma API web (no hay diferencias iOS vs Android vs Web)
- El cliente puede mentir
- Puede inferirse automáticamente del User-Agent
- No afecta lógica de negocio

### 4. ⛔ Nombres de Tokens Redundantes

```php
// Actual
"ios-550e8400"  // ❌ Prefijo 'ios' no aporta valor

// Propuesta
"550e8400"      // ✅ Simple, suficiente
```

---

## Fase 1: Limpieza de Base de Datos (CRÍTICO)

**Duración estimada**: 1 hora
**Prioridad**: 🔴 ALTA - Resolver errores ENUM

### 1.1 Eliminar Columnas Muertas

```sql
-- Migration: 2025_11_12_remove_unused_device_columns.php

ALTER TABLE customer_devices
  DROP COLUMN device_model,
  DROP COLUMN app_version,
  DROP COLUMN os_version;
```

**Justificación**:
- Estas columnas nunca se llenan en el código actual
- No hay validaciones que las requieran
- No se usan en queries ni reportes

### 1.2 Eliminar o Convertir `device_type`

**Opción A: Eliminar completamente** (Recomendada)

```sql
ALTER TABLE customer_devices
  DROP COLUMN device_type;
```

**Razones**:
- No afecta funcionalidad (todo es API web)
- Elimina errores ENUM
- Reduce complejidad

**Opción B: Convertir a nullable string**

```sql
ALTER TABLE customer_devices
  MODIFY COLUMN device_type VARCHAR(50) NULL;
```

**Razones**:
- Mantener para estadísticas
- Inferir automáticamente del User-Agent
- Más flexible que ENUM

### 1.3 Eliminar `device_fingerprint` (o implementar validación)

**Opción A: Eliminar** (Recomendada para simplificación)

```sql
ALTER TABLE customer_devices
  DROP COLUMN device_fingerprint;
```

**Opción B: Implementar validación real** (Requiere +8 horas desarrollo)

```php
// Middleware ValidateDeviceFingerprint
if ($existingDevice->device_fingerprint !== $newFingerprint) {
    $device->trust_score -= 20;
    // Lógica de bloqueo o 2FA
}
```

### 1.4 Eliminar `trust_score` (o implementar lógica)

**Opción A: Eliminar** (Recomendada)

```sql
ALTER TABLE customer_devices
  DROP COLUMN trust_score;
```

**Opción B: Implementar sistema de seguridad completo**

Requiere:
- Middleware de validación
- Sistema de alertas
- Lógica 2FA
- Tiempo estimado: 15-20 horas

**Decisión**: Para una app de pedidos de Subway, el costo-beneficio no justifica la complejidad.

---

## Fase 2: Simplificación de Backend (CRÍTICO)

**Duración estimada**: 2 horas
**Prioridad**: 🔴 ALTA

### 2.1 Actualizar `DeviceService`

```php
// ANTES
public function syncDeviceWithToken(
    Customer $customer,
    PersonalAccessToken $token,
    string $deviceIdentifier,
    string $deviceType,  // ❌ Eliminar
    ?string $deviceFingerprint = null
): CustomerDevice

// DESPUÉS
public function syncDeviceWithToken(
    Customer $customer,
    PersonalAccessToken $token,
    string $deviceIdentifier
): CustomerDevice
```

**Cambios**:
```php
// Eliminar lógica de device_type
// Eliminar device_fingerprint de create()
// Eliminar trust_score de calculateTrustScore()

CustomerDevice::create([
    'customer_id' => $customer->id,
    'sanctum_token_id' => $token->id,
    'device_identifier' => $deviceIdentifier,
    'device_name' => $this->generateDefaultDeviceName(),
    'is_active' => true,
    'last_used_at' => now(),
    'login_count' => 1,
]);
```

### 2.2 Actualizar Controllers

**Archivos a modificar**:
- `app/Http/Controllers/Api/V1/Auth/AuthController.php`
- `app/Http/Controllers/Api/V1/Auth/OAuthController.php`

**Cambios**:

```php
// ANTES
$validated = $request->validate([
    'os' => ['nullable', Rule::enum(OperatingSystem::class)],  // ❌
    'device_identifier' => ['nullable', 'string', 'max:255'],
    'device_fingerprint' => ['nullable', 'string', 'max:255'], // ❌
]);

// Llamada
$this->deviceService->syncDeviceWithToken(
    $customer,
    $newAccessToken->accessToken,
    $validated['device_identifier'],
    $validated['os'] ?? 'web',  // ❌
    $validated['device_fingerprint'] ?? null  // ❌
);

// DESPUÉS
$validated = $request->validate([
    'device_identifier' => ['nullable', 'string', 'max:255'],
]);

// Llamada simplificada
if (isset($validated['device_identifier'])) {
    $this->deviceService->syncDeviceWithToken(
        $customer,
        $newAccessToken->accessToken,
        $validated['device_identifier']
    );
}
```

### 2.3 Simplificar Nombres de Tokens

```php
// ANTES
protected function generateTokenName(string $os, ?string $deviceIdentifier): string
{
    if ($deviceIdentifier) {
        return $os.'-'.substr($deviceIdentifier, 0, 8);  // "ios-550e8400"
    }
    return $os;  // "ios"
}

// DESPUÉS
protected function generateTokenName(?string $deviceIdentifier): string
{
    return $deviceIdentifier
        ? substr($deviceIdentifier, 0, 8)  // "550e8400"
        : 'device-'.uniqid();               // "device-673ab123"
}
```

### 2.4 Eliminar Enum `OperatingSystem` (opcional)

Si eliminamos completamente `device_type`:

```bash
rm app/Enums/OperatingSystem.php
```

---

## Fase 3: Actualizar API y Documentación

**Duración estimada**: 1 hora
**Prioridad**: 🟡 MEDIA


### 3.2 Actualizar Swagger/OpenAPI

Actualizar anotaciones PHPDoc en controllers:

```php
/**
 * @OA\Property(property="os", ...) // ❌ ELIMINAR
 * @OA\Property(property="device_fingerprint", ...) // ❌ ELIMINAR
 */
```

### 3.3 Actualizar Mobile App

**React Native / Expo**:

```javascript
// ANTES
const response = await fetch('/api/v1/auth/login', {
    method: 'POST',
    body: JSON.stringify({
        email: email,
        password: password,
        os: Platform.OS,  // ❌ Ya no necesario
        device_identifier: await getDeviceIdentifier()
    })
});

// DESPUÉS
const response = await fetch('/api/v1/auth/login', {
    method: 'POST',
    body: JSON.stringify({
        email: email,
        password: password,
        device_identifier: await getDeviceIdentifier()  // ✅ Solo esto
    })
});
```

---

## Fase 4: Tests y Validación

**Duración estimada**: 1 hora
**Prioridad**: 🔴 ALTA

### 4.1 Actualizar Tests Existentes

**Archivos a modificar**:
- `tests/Feature/Api/V1/Auth/LoginTest.php`
- `tests/Feature/Api/V1/Auth/RegisterTest.php`
- `tests/Feature/Api/V1/Auth/OAuthTest.php`

```php
// ANTES
$response = $this->postJson('/api/v1/auth/login', [
    'email' => $customer->email,
    'password' => 'password',
    'os' => 'ios',  // ❌
    'device_identifier' => 'test-device-123',
]);

// DESPUÉS
$response = $this->postJson('/api/v1/auth/login', [
    'email' => $customer->email,
    'password' => 'password',
    'device_identifier' => 'test-device-123',  // ✅
]);
```

### 4.2 Ejecutar Suite de Tests

```bash
# Tests específicos de autenticación
php artisan test --filter=Auth

# Tests de OAuth
php artisan test --filter=OAuth

# Tests de dispositivos
php artisan test --filter=Device

# Suite completa
php artisan test
```

### 4.3 Validación Manual

**Endpoints a probar**:
1. `POST /api/v1/auth/register` - Con y sin device_identifier
2. `POST /api/v1/auth/login` - Con y sin device_identifier
3. `POST /api/v1/auth/oauth/google` - ID token flow
4. `GET /api/v1/auth/oauth/google/mobile` - WebBrowser flow
5. `GET /api/v1/devices` - Listar dispositivos
6. `DELETE /api/v1/devices/{id}` - Eliminar dispositivo

---

## Fase 5: Cleanup y Optimización

**Duración estimada**: 30 minutos
**Prioridad**: 🟢 BAJA

### 5.1 Eliminar Código Muerto

```bash
# Buscar referencias a campos eliminados
grep -r "device_type" app/
grep -r "device_model" app/
grep -r "trust_score" app/
grep -r "device_fingerprint" app/
```

### 5.2 Actualizar Factories y Seeders

```php
// database/factories/CustomerDeviceFactory.php
public function definition(): array
{
    return [
        'device_identifier' => fake()->uuid(),
        // ❌ Eliminar: 'device_type' => ...
        // ❌ Eliminar: 'device_model' => ...
        // ❌ Eliminar: 'trust_score' => ...
    ];
}
```

### 5.3 Limpiar Migraciones Antiguas

Si las migraciones ya se ejecutaron en producción, puedes:
1. Mantenerlas como historial
2. O crear una migración "squash" que consolide todo

---

## Fase 6: Deployment (IMPORTANTE)

**Duración estimada**: 30 minutos
**Prioridad**: 🔴 ALTA

### 6.1 Orden de Deployment

**ORDEN CRÍTICO** para evitar downtime:

```
1. Deploy backend (código compatible con ambas versiones)
   ├─ Backend acepta 'os' pero lo ignora
   └─ Backend funciona con y sin 'os'

2. Deploy mobile app (elimina 'os' de requests)
   └─ Apps móviles ya no envían 'os'

3. Deploy database migration (elimina columnas)
   └─ Columnas eliminadas de DB
```

### 6.2 Migration en Producción

```bash
# En servidor de producción
php artisan migrate --force

# Verificar
php artisan db:show
php artisan db:table customer_devices
```

### 6.3 Rollback Plan

Si algo sale mal:

```bash
# Rollback última migración
php artisan migrate:rollback --step=1

# O restaurar desde backup
mysql subwayapp < backup_before_simplification.sql
```

---

## Resumen de Cambios

### Base de Datos

| Acción | Campo | Razón |
|--------|-------|-------|
| ❌ ELIMINAR | `device_model` | Nunca se llena |
| ❌ ELIMINAR | `app_version` | Nunca se llena |
| ❌ ELIMINAR | `os_version` | Nunca se llena |
| ❌ ELIMINAR | `device_type` | Redundante, causa bugs ENUM |
| ❌ ELIMINAR | `device_fingerprint` | Se guarda pero no se usa |
| ❌ ELIMINAR | `trust_score` | Se calcula pero no afecta lógica |
| ✅ MANTENER | `device_identifier` | CRÍTICO - UUID único |
| ✅ MANTENER | `fcm_token` | CRÍTICO - Push notifications |
| ✅ MANTENER | `sanctum_token_id` | Útil - Tracking de token |
| ✅ MANTENER | `last_used_at` | Útil - Cleanup automático |
| ✅ MANTENER | `is_active` | Útil - Estado del device |
| ✅ MANTENER | `login_count` | Útil - Estadísticas |
| ✅ MANTENER | `device_name` | Útil - Display name |

### Backend

| Archivo | Cambio |
|---------|--------|
| `DeviceService.php` | Eliminar parámetros `deviceType`, `deviceFingerprint` |
| `AuthController.php` | Quitar validación de `os` y `device_fingerprint` |
| `OAuthController.php` | Quitar validación de `os` y `device_fingerprint` |
| Ambos controllers | Simplificar `generateTokenName()` |
| `OperatingSystem.php` | Eliminar Enum completo |

### API

| Endpoint | Cambio |
|----------|--------|
| `POST /auth/register` | Ya no requiere `os`, `device_fingerprint` |
| `POST /auth/login` | Ya no requiere `os`, `device_fingerprint` |
| `POST /auth/oauth/google` | Ya no requiere `os`, `device_fingerprint` |
| `POST /auth/oauth/google/register` | Ya no requiere `os`, `device_fingerprint` |
| `GET /auth/oauth/google/mobile` | Ya no requiere query param `os` |

---

## Métricas de Éxito

### Cuantitativas

- [ ] Reducción de columnas en DB: -7 columnas (41%)
- [ ] Reducción de parámetros API: -2 parámetros (50%)
- [ ] Reducción de líneas de código: ~200 líneas
- [ ] Tiempo de migración: <5 segundos
- [ ] Tests pasando: 100%

### Cualitativas

- [ ] Código más fácil de entender
- [ ] Menos posibilidad de bugs ENUM
- [ ] Documentación más clara
- [ ] Onboarding de nuevos devs más rápido
- [ ] Menos preguntas "¿para qué sirve esto?"

---

## Riesgos y Mitigaciones

### Riesgo 1: Apps móviles antiguas envían 'os'

**Mitigación**: Backend ignora parámetro si existe (no lo valida)

```php
// Backend acepta pero ignora
$request->input('os');  // No se valida, no se usa
```

### Riesgo 2: Queries o reportes usan campos eliminados

**Mitigación**:
1. Buscar todos los usos antes de eliminar
2. Tests cubren casos principales
3. Rollback disponible

### Riesgo 3: Downtime durante migración

**Mitigación**:
1. Migración es rápida (<5s)
2. No locks en tabla
3. Horario de bajo tráfico (2-4 AM)

---

## Checklist de Implementación

### Pre-implementación
- [ ] Backup de base de datos
- [ ] Revisar dependencias de campos a eliminar
- [ ] Notificar al equipo

### Implementación
- [ ] Crear migración
- [ ] Actualizar DeviceService
- [ ] Actualizar AuthController
- [ ] Actualizar OAuthController
- [ ] Eliminar Enum OperatingSystem
- [ ] Actualizar tests
- [ ] Ejecutar suite de tests
- [ ] Ejecutar Laravel Pint

### Post-implementación
- [ ] Deploy a staging
- [ ] Pruebas manuales en staging
- [ ] Deploy a producción
- [ ] Monitorear logs por 24h
- [ ] Actualizar documentación
- [ ] Cerrar ticket

---

## Timeline

| Fase | Duración | Puede iniciar |
|------|----------|---------------|
| Fase 1: DB Migration | 1h | Inmediato |
| Fase 2: Backend | 2h | Después Fase 1 |
| Fase 3: API Docs | 1h | Paralelo a Fase 2 |
| Fase 4: Tests | 1h | Después Fase 2 |
| Fase 5: Cleanup | 30min | Después Fase 4 |
| Fase 6: Deployment | 30min | Después Fase 5 |
| **TOTAL** | **6 horas** | 1 día laboral |

---

## Referencias

- Auditoría original: Conversación del 12 Nov 2025
- Documentación actual: `/docs/DEVICE_TOKEN_ARCHITECTURE.md`
- Schema actual: Ver con `php artisan db:table customer_devices`

---

## Aprobaciones

| Rol | Nombre | Fecha | Firma |
|-----|--------|-------|-------|
| Tech Lead | | | |
| Backend Dev | | | |
| Mobile Dev | | | |

---

## Notas de Implementación

### Comandos Útiles

```bash
# Ver estructura actual
php artisan db:table customer_devices

# Crear migración
php artisan make:migration remove_unused_device_columns

# Ejecutar migración
php artisan migrate

# Rollback
php artisan migrate:rollback --step=1

# Ver queries generados
php artisan migrate --pretend

# Tests
php artisan test --filter=Auth
vendor/bin/pest --filter=OAuth

# Formatear código
vendor/bin/pint
```

### SQL Queries de Verificación

```sql
-- Ver dispositivos activos
SELECT
    COUNT(*) as total_devices,
    COUNT(DISTINCT customer_id) as unique_customers,
    AVG(login_count) as avg_logins
FROM customer_devices
WHERE is_active = 1;

-- Ver distribución por tipo (antes de eliminar)
SELECT device_type, COUNT(*)
FROM customer_devices
GROUP BY device_type;

-- Encontrar dispositivos huérfanos
SELECT * FROM customer_devices
WHERE sanctum_token_id IS NULL;
```

---

**Documento creado por**: Claude Code
**Última actualización**: 2025-11-12
**Versión**: 1.0
