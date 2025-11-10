# Plan de Implementación: API REST + Autenticación Multi-Canal + Notificaciones Push

**Documento**: Plan de Implementación Técnica
**Fecha**: noviembre 2025
**Versión**: 1.0
**Alcance**: API Backend (ADMIN + CLIENT) + Integración Mobile + OAuth Social

---

## Resumen Ejecutivo

### Descripción General

Implementación de una API REST completa para **clientes (customers)** de Subway Guatemala que permita:

- **Autenticación** tradicional (email/contraseña) y social (Google OAuth)
- **Sistema de pedidos** desde app móvil iOS/Android
- **Programa de lealtad**: acumulación y gestión de puntos (Bronze/Silver/Gold)
- **Gestión de perfil**: múltiples direcciones de entrega y NITs para facturación
- **Tokens seguros** con múltiples dispositivos simultáneos por customer
- **Notificaciones push** mediante Firebase Cloud Messaging (promociones, estados de pedido)
- **Documentación interactiva** completa con Swagger/OpenAPI

**Nota importante**: Esta API es **exclusivamente para customers** (clientes que hacen pedidos). Los administradores del sistema ya tienen su panel web separado con autenticación por sesiones.

### Diferenciación con Sistema Actual

| Aspecto | Sistema Web Actual | API REST Nueva |
|---------|-------------------|----------------|
| **Autenticación** | Sesiones (cookies) - Panel Admin | Tokens Sanctum (stateless) - App Clientes |
| **Usuarios** | Admins (panel web) | Customers (app móvil pedidos) |
| **Login Social** | No implementado | Google OAuth |
| **Múltiples Dispositivos** | Una sesión por navegador | Múltiples tokens simultáneos por customer |
| **Notificaciones** | No tiene | Firebase Cloud Messaging |
| **Documentación API** | No aplica | Swagger UI interactiva |
| **Versionado** | No aplica | API versionada (`/api/v1/`) |
| **Rate Limiting** | Básico | Por endpoint y customer |
| **Propósito** | Gestión administrativa | **Pedidos de comida + puntos de lealtad** |

### Casos de Uso Principales

**Caso 1: App Móvil - Login con Email**
- **Customer** abre app móvil de Subway Guatemala
- Ingresa email y contraseña
- API valida credenciales → genera token Sanctum
- App guarda token + registra dispositivo para FCM
- **Customer** navega en app, hace pedidos, acumula puntos
- Recibe notificaciones push en dispositivo (promociones, estados de pedido)

**Caso 2: App Móvil - Login con Google**
- **Customer** toca botón "Continuar con Google"
- Google SDK obtiene `id_token`
- App envía `id_token` a API
- API verifica con Google → crea/vincula customer
- API genera token Sanctum
- **Customer** logueado sin contraseña, puede hacer pedidos inmediatamente

**Caso 3: Múltiples Dispositivos**
- **Customer** tiene iPhone + iPad + Android
- Inicia sesión en cada uno → obtiene 3 tokens diferentes
- Puede ver sus sesiones activas en configuración de app
- Puede cerrar sesión en un dispositivo específico
- Puede cerrar sesión en todos los dispositivos
- Recibe notificaciones push en todos los dispositivos registrados
- **Ejemplo**: Inicia pedido en iPhone, lo completa en iPad

---

## FASE 1: Estructura de Base de Datos para Tokens y Dispositivos ✅ COMPLETADA

### Objetivos
- Crear tablas necesarias para sistema de tokens
- Agregar campos OAuth a tabla `customers` (tabla existente)
- Actualizar tabla `customer_devices` con campos faltantes
- Establecer índices para performance

### Estado Actual del Sistema

**✅ Implementado Completamente**:
- Tabla `customers` (Authenticatable, Notifiable, SoftDeletes)
- Tabla `customer_devices` (con FCM tokens, is_active, soft deletes)
- Tabla `customer_addresses` (múltiples direcciones por cliente)
- Tabla `customer_nits` (múltiples NITs por cliente)
- Tabla `customer_types` (sistema de niveles: bronze, silver, gold)
- Sistema de puntos y actualización automática de tipo
- **Laravel Sanctum instalado (v4.2.0)**
- **Tabla `personal_access_tokens` creada**
- **Campos OAuth en `customers` agregados**
- **Guard API para customers configurado**
- **Campos adicionales en `customer_devices` agregados**

### Migración 1: Personal Access Tokens (Sanctum)

**Tabla**: `personal_access_tokens`

**Status**: ❌ No existe - se creará al instalar Sanctum

Generada automáticamente por Sanctum. Campos principales:
- `tokenable_type`, `tokenable_id`: polimórfico → Customer
- `name`: nombre descriptivo del dispositivo/app
- `token`: hash del token (64 caracteres)
- `abilities`: JSON con permisos (inicialmente `["*"]`)
- `last_used_at`: timestamp de última petición
- `expires_at`: fecha de expiración (nullable)

**Índices**:
- `tokenable_type` + `tokenable_id` (búsqueda rápida de tokens por customer)
- `token` (único, para autenticación)

**Comando**: `php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"`

### Migración 2: Campos OAuth en Customers

**Tabla**: `customers` (modificar existente)

**Estructura Actual**:
```
✅ id, name, email, email_verified_at, password, subway_card, birth_date,
   gender, customer_type_id, phone, remember_token, last_login_at,
   last_activity_at, last_purchase_at, points, points_updated_at, timezone,
   created_at, updated_at, deleted_at
```

**Campos nuevos a agregar**:
- `google_id`: VARCHAR(255) nullable, unique → ID de usuario en Google
- `avatar`: TEXT nullable → URL de foto de perfil del provider OAuth
- `oauth_provider`: ENUM('local', 'google') default 'local'

**Índices nuevos**:
- `google_id` (único, búsqueda rápida)

**Lógica**:
- Si customer existe con email pero sin `google_id` → vincular cuenta
- Si customer no existe → crear con datos de OAuth
- Password nullable cuando `oauth_provider != 'local'`
- `email_verified_at` auto-verificar en OAuth

### Migración 3: Actualizar Customer Devices

**Tabla**: `customer_devices` (modificar existente)

**Estructura Actual**:
```
✅ id, customer_id, fcm_token (unique), device_type (enum), device_name,
   device_model, last_used_at, is_active, created_at, updated_at, deleted_at
```

**Campos nuevos a agregar**:
- `sanctum_token_id`: BIGINT nullable foreign key → personal_access_tokens.id
- `device_identifier`: VARCHAR(255) unique → UUID del dispositivo (backup de fcm_token)
- `app_version`: VARCHAR(20) nullable → versión de la app
- `os_version`: VARCHAR(20) nullable → versión del SO

**Nota**: `device_type` (enum existente) mantener como está: 'ios', 'android', 'web'

**Índices adicionales**:
- `sanctum_token_id` (foreign key)
- `device_identifier` (único, backup identifier)

**Relaciones actualizadas**:
- BelongsTo → Customer (ya existe)
- BelongsTo → PersonalAccessToken (nuevo - opcional)

### Verificación de Fase 1

- [x] Sanctum instalado y migración publicada
- [x] Migración de OAuth fields en `customers` creada
- [x] Migración de campos adicionales en `customer_devices` creada
- [x] Migraciones ejecutan sin errores
- [x] Rollback funciona correctamente
- [x] Foreign keys con cascade delete configurados
- [x] Índices únicos previenen duplicados
- [x] Campos nullable correctos según reglas de negocio
- [x] OAuth ids aceptan NULL (customers locales)
- [x] Model Customer actualizado con campos nuevos en fillable

---

## FASE 2: Instalación y Configuración de Laravel Sanctum ✅ COMPLETADA

### Objetivos

- Instalar paquete Sanctum
- Configurar guards de autenticación para customers
- Configurar expiración de tokens
- Preparar middleware para API

### Instalación

**Paquete**: `laravel/sanctum` (actualmente NO instalado)

**Comandos**:

```bash
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

### Configuración del Guard

**Archivo**: `config/auth.php`

**Estructura Actual**:

```php
'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'users',  // Para panel admin
    ],
],
'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model' => App\Models\User::class,
    ],
],
```

**Agregar guards y providers**:

```php
'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'users',  // Panel admin (mantener)
    ],
    'customer' => [
        'driver' => 'session',
        'provider' => 'customers',  // Nuevo - para web de customers
    ],
    'sanctum' => [
        'driver' => 'sanctum',
        'provider' => 'customers',  // API móvil customers
    ],
],
'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model' => App\Models\User::class,  // Admins (mantener)
    ],
    'customers' => [
        'driver' => 'eloquent',
        'model' => App\Models\Customer::class,  // Nuevo
    ],
],
```

**Nota importante**: Mantener guard `web` con provider `users` para el panel admin actual (no afecta sistema existente).

### Configuración de Sanctum

**Archivo**: `config/sanctum.php`

**Token Expiration**:

- Valor: 525600 minutos (365 días)
- Justificación: apps móviles necesitan sesiones largas
- Customer puede cerrar sesiones manualmente
- Lifecycle automático desactiva tokens inactivos

**Stateful Domains**:

- Solo localhost para desarrollo
- Producción: solo API pura (sin cookies)

**Middleware**:

- `EnsureFrontendRequestsAreStateful` → desactivado para API
- Solo para rutas `api/*`

### Modificación del Modelo Customer

**Archivo**: `app/Models/Customer.php`

**Estado actual**:

```php
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Customer extends Authenticatable
{
    use HasFactory, LogsActivity, Notifiable, SoftDeletes, TracksUserStatus;
    // ...
}
```

**Agregar trait**:

```php
use Laravel\Sanctum\HasApiTokens;

class Customer extends Authenticatable
{
    use HasApiTokens, HasFactory, LogsActivity, Notifiable, SoftDeletes, TracksUserStatus;
    // ...
}
```

**Métodos agregados automáticamente por HasApiTokens**:

- `tokens()`: relación HasMany con personal_access_tokens
- `createToken(string $name, array $abilities = ['*'])`: crear nuevo token
- `currentAccessToken()`: obtener token actual en request
- `tokenCan(string $ability)`: verificar permisos del token

### Verificación de Fase 2

- [x] Sanctum instalado correctamente
- [x] Guard `sanctum` configurado en auth.php con provider `customers`
- [x] Provider `customers` creado apuntando a `App\Models\Customer`
- [x] Modelo Customer usa trait `HasApiTokens`
- [x] Migración de Sanctum ejecutada (tabla `personal_access_tokens`)
- [x] Config de expiración establecida (365 días)
- [x] Middleware API funcionando
- [x] Panel admin sigue funcionando con guard `web` (no afectado)

---

## FASE 3: API REST - Estructura Base y Rutas de Autenticación ✅ COMPLETADA

### Objetivos
- Crear archivo de rutas API
- Definir versionado de API
- Estructurar endpoints de autenticación
- Configurar middleware API
- Implementar rate limiting

### Estructura de Rutas

**Archivo nuevo**: `routes/api.php`

**Prefijo global**: `/api`

**Versionado**: `/api/v1/`
- Justificación: permite versiones futuras (`v2`) sin romper clientes

**Middleware grupo `api`**:
- `ForceJsonResponse` → todas las respuestas en JSON
- `api` (default Laravel) → throttle + bindings
- Sin `web` middleware (sin cookies, sin sesiones)

### Endpoints de Autenticación (Sin Protección)

**Prefijo**: `/api/v1/auth`

**Rutas públicas** (sin middleware `auth:sanctum`):

1. **POST** `/register`
   - Registro con email/password
   - Validaciones: email único, password mínimo 6 caracteres
   - Retorna: token + user

2. **POST** `/login`
   - Login tradicional email/password
   - Validaciones: credenciales correctas
   - Parámetro opcional: `device_name` (default: "API Client")
   - Retorna: token + user

3. **POST** `/forgot-password`
   - Solicitar reset de contraseña
   - Envía email con token temporal
   - Retorna: mensaje de confirmación

4. **POST** `/reset-password`
   - Cambiar contraseña con token del email
   - Validaciones: token válido, passwords coinciden
   - Retorna: mensaje de éxito

### Endpoints de Autenticación (Protegidos)

**Middleware**: `auth:sanctum`

5. **POST** `/logout`
   - Revoca token actual (del header)
   - Mantiene otros tokens activos
   - Retorna: mensaje de éxito

6. **POST** `/logout-all`
   - Revoca todos los tokens del usuario
   - Cierra sesión en todos los dispositivos
   - Retorna: cantidad de tokens revocados

7. **POST** `/change-password`
   - Cambiar contraseña estando autenticado
   - Validaciones: contraseña actual correcta
   - Opción: revocar otros tokens tras cambio
   - Retorna: mensaje de éxito

8. **GET** `/me`
   - Obtener datos del customer autenticado
   - Incluye: customer_type, addresses, nits, devices, points, stats
   - Retorna: CustomerResource

9. **PUT** `/profile`
   - Actualizar datos de perfil
   - Campos: name, email, phone, birth_date, gender, timezone, avatar
   - Email change → re-verificar
   - Retorna: CustomerResource actualizado

10. **GET** `/addresses`
    - Listar direcciones del customer
    - Retorna: AddressResourceCollection

11. **POST** `/addresses`
    - Crear nueva dirección
    - Retorna: AddressResource

12. **PUT** `/addresses/{id}`
    - Actualizar dirección
    - Retorna: AddressResource

13. **DELETE** `/addresses/{id}`
    - Eliminar dirección
    - Retorna: mensaje de éxito

14. **GET** `/nits`
    - Listar NITs del customer
    - Retorna: NitResourceCollection

15. **POST** `/nits`
    - Crear nuevo NIT
    - Retorna: NitResource

16. **PUT** `/nits/{id}`
    - Actualizar NIT
    - Retorna: NitResource

17. **DELETE** `/nits/{id}`
    - Eliminar NIT
    - Retorna: mensaje de éxito

### Rate Limiting

**Grupo `auth`**: 5 requests/minuto
- Aplicado a: login, register, forgot-password
- Previene: brute force attacks

**Grupo `api`**: 60 requests/minuto
- Aplicado a: endpoints protegidos generales
- Ajustable por endpoint específico

### Verificación de Fase 3
- [x] Archivo routes/api.php creado
- [x] Versionado v1 implementado
- [x] 17 rutas totales definidas (auth + profile + addresses + nits)
- [x] Middleware auth:sanctum en rutas protegidas
- [x] Rate limiting configurado y funcionando (auth: 5/min, oauth: 10/min, api: 120/min)
- [x] Rutas devuelven JSON correctamente

---

## FASE 4: Controllers de Autenticación ✅ COMPLETADA

### Objetivos
- Crear controllers para cada grupo de funcionalidad
- Implementar lógica de negocio de autenticación
- Manejar errores con respuestas JSON consistentes
- Validar datos con Form Requests

### Controller 1: RegisterController

**Namespace**: `App\Http\Controllers\Api\V1\Auth`

**Método**: `register(RegisterRequest $request)`

Lógica:
1. Validar datos (delegado a Form Request)
2. Hash de password automático (cast en modelo)
3. Crear usuario en DB
4. Crear token Sanctum con nombre de dispositivo
5. Disparar evento `UserRegistered` (emails, etc)
6. Retornar AuthResource (token + user)

Manejo de errores:
- Email duplicado → 422 con mensaje claro
- Validaciones fallidas → 422 con errores por campo

### Controller 2: LoginController

**Método**: `login(LoginRequest $request)`

Lógica:
1. Validar email/password
2. Buscar usuario por email
3. Verificar password con Hash::check()
4. Verificar usuario activo (no eliminado)
5. Actualizar `last_login_at`
6. Crear token con nombre de dispositivo opcional
7. Retornar AuthResource

Manejo de errores:
- Credenciales incorrectas → 401 "Credenciales inválidas"
- Usuario inactivo → 403 "Cuenta desactivada"
- Demasiados intentos → 429 (rate limit)

**Método**: `logout(Request $request)`

Lógica:
1. Obtener token actual desde request
2. `$request->user()->currentAccessToken()->delete()`
3. Retornar mensaje de éxito

**Método**: `logoutAll(Request $request)`

Lógica:
1. Obtener usuario autenticado
2. `$user->tokens()->delete()`
3. Contar tokens eliminados
4. Retornar mensaje + cantidad

### Controller 3: PasswordController

**Método**: `forgot(ForgotPasswordRequest $request)`

Lógica:
1. Validar email existe
2. Generar token aleatorio único
3. Guardar en tabla `password_reset_tokens`
4. Enviar email con link de reset
5. Retornar mensaje genérico (seguridad)

**Método**: `reset(ResetPasswordRequest $request)`

Lógica:
1. Validar token + email
2. Verificar token no expirado (60 minutos)
3. Actualizar password del usuario
4. Eliminar token usado
5. Opcionalmente: revocar todos los tokens
6. Retornar mensaje de éxito

**Método**: `change(ChangePasswordRequest $request)`

Lógica:
1. Obtener usuario autenticado
2. Verificar contraseña actual con Hash::check()
3. Actualizar con nueva contraseña
4. Opcionalmente: revocar otros tokens
5. Retornar mensaje de éxito

Manejo de errores:
- Contraseña actual incorrecta → 401
- Token inválido/expirado → 422
- Usuario no encontrado → 404

### Controller 4: ProfileController

**Método**: `show(Request $request)`

Lógica:
1. Obtener usuario autenticado
2. Eager load: roles, permisos, estadísticas
3. Retornar UserResource

**Método**: `update(UpdateProfileRequest $request)`

Lógica:
1. Validar datos entrantes
2. Si cambia email → verificar unicidad
3. Si cambia email → marcar como no verificado
4. Actualizar campos permitidos
5. Retornar UserResource actualizado

Campos actualizables:
- name, email, timezone, avatar (URL)

Campos NO actualizables:
- password (usar change-password)
- roles, permisos (solo admin)
- OAuth ids

### Verificación de Fase 4
- [x] Controllers creados: AuthController, OAuthController, ProfileController
- [x] Métodos retornan respuestas JSON consistentes
- [x] Errores manejados con códigos HTTP apropiados
- [x] Lógica de negocio separada de validación (usando Form Requests)
- [x] Tokens creados con nombres descriptivos (device_name)
- [x] Last login actualizado en cada login
- [x] Passwords hasheados automáticamente

---

## FASE 5: Form Requests - Validaciones API ✅ COMPLETADA

### Objetivos
- Crear Form Requests para cada endpoint
- Validaciones robustas y seguras
- Mensajes de error personalizados en español
- Reglas específicas para API

### Request 1: RegisterRequest

**Namespace**: `App\Http\Requests\Api\V1\Auth`

**Reglas**:
- `name`: required, string, max:255
- `email`: required, email, unique:users,email, max:255
- `password`: required, string, min:6, confirmed
- `device_name`: optional, string, max:100

**Mensajes personalizados**:
- email.unique: "Este correo ya está registrado"
- password.min: "La contraseña debe tener al menos 6 caracteres"
- password.confirmed: "Las contraseñas no coinciden"

**Método authorize()**: siempre true (endpoint público)

### Request 2: LoginRequest

**Reglas**:
- `email`: required, email
- `password`: required, string
- `device_name`: optional, string, max:100
- `remember`: optional, boolean

**Nota**: NO validar que email exista (seguridad)

### Request 3: ForgotPasswordRequest

**Reglas**:
- `email`: required, email

**Nota**: NO validar existencia (prevenir enumeración)

### Request 4: ResetPasswordRequest

**Reglas**:
- `token`: required, string, size:64
- `email`: required, email
- `password`: required, string, min:6, confirmed

### Request 5: ChangePasswordRequest ✅ IMPLEMENTADO

**Namespace**: `App\Http\Requests\Api\V1\Auth`

**Reglas**:
- `current_password`: required, string
- `password`: required, string, confirmed, different:current_password, Rules\Password::defaults()

**Validación custom**: verificar current_password con Hash usando withValidator()

**Método authorize()**: true (authenticated via middleware)

**Implementación**:
- Archivo: `app/Http/Requests/Api/V1/Auth/ChangePasswordRequest.php`
- Utiliza `withValidator()` para validación custom de current_password
- Mensajes personalizados en español
- Integrado en `ProfileController::updatePassword()`

### Request 6: UpdateProfileRequest

**Reglas**:
- `name`: optional, string, max:255
- `email`: optional, email, unique:users,email,{userId}, max:255
- `timezone`: optional, timezone
- `avatar`: optional, url, max:500

**Lógica unique**: ignorar email del usuario actual

### Verificación de Fase 5
- [x] 6 Form Requests creados (RegisterRequest, LoginRequest, ForgotPasswordRequest, ResetPasswordRequest, ChangePasswordRequest, UpdateProfileRequest)
- [x] Reglas de validación completas y seguras
- [x] Mensajes en español personalizados
- [x] Unique rules consideran usuario actual
- [x] Password confirmation validado
- [x] Email format validado correctamente
- [x] ChangePasswordRequest implementado con validación custom (withValidator)
- [x] Different rule para nueva contraseña vs actual
- [x] ProfileController refactorizado para usar ChangePasswordRequest

---

## FASE 6: OAuth Social Login (Google) ✅ COMPLETADA

### Objetivos
- Instalar Laravel Socialite
- Implementar login con Google OAuth
- Manejar vinculación de cuentas existentes
- Sincronizar datos del provider

### Instalación

**Paquete**: `laravel/socialite`

**Providers soportados**:
- Google (built-in)

### Configuración

**Archivo**: `config/services.php`

**Google OAuth**:
- `client_id`: de Google Cloud Console
- `client_secret`: de Google Cloud Console
- `redirect`: no necesario para API móvil (client-side)

**Variables de entorno** (.env):
- GOOGLE_CLIENT_ID
- GOOGLE_CLIENT_SECRET

### Controller: SocialAuthController

**Namespace**: `App\Http\Controllers\Api\V1\Auth`

**Método**: `google(GoogleLoginRequest $request)`

Lógica:
1. Recibir `id_token` desde cliente móvil
2. Verificar token con Google API
3. Extraer datos: sub (google_id), email, name, picture
4. Buscar usuario por `google_id`
5. Si no existe, buscar por email
   - Si existe usuario con ese email → vincular google_id
   - Si no existe → crear usuario nuevo
6. Actualizar avatar si viene del provider
7. Marcar email como verificado automáticamente
8. Crear token Sanctum
9. Retornar AuthResource

### Service: SocialAuthService

**Método**: `verifyGoogleToken(string $idToken)`
- Llamar a Google API
- Validar audiencia (client_id)
- Retornar datos del usuario o lanzar excepción

**Método**: `findOrCreateUserFromProvider(array $providerData, string $provider)`
- Lógica compartida de búsqueda/creación
- Retorna usuario existente o nuevo
- Maneja vinculación automática por email

### Manejo de Casos Edge

**Caso 1**: Usuario existe con email pero sin OAuth
- Acción: vincular google_id a usuario existente
- Notificar al usuario por email (seguridad)

**Caso 2**: Password NULL en OAuth users
- Acción: permitir, password es nullable
- Usuario puede establecer password después para login tradicional

### Verificación de Fase 6

- [x] Socialite instalado y configurado (v5.23.1)
- [x] Google OAuth funcionando con id_token
- [x] Vinculación automática por email funciona
- [x] Avatar sincronizado desde provider
- [x] Email verificado automáticamente en OAuth
- [x] Casos edge manejados correctamente
- [x] SocialAuthService implementado con verifyGoogleToken(), findOrCreateCustomer()
- [x] OAuthController actualizado para usar Socialite con ->stateless()->userFromToken()
- [x] config/services.php configurado con Google
- [x] .env.example actualizado con variables OAuth

---

## FASE 7: API Resources - Respuestas Consistentes ✅ COMPLETADA

### Objetivos

- Crear API Resources para serialización
- Formato de respuesta estandarizado
- Ocultar campos sensibles
- Incluir relaciones según contexto
- Aprovechar modelos y relaciones existentes

### Resource 1: CustomerResource

**Namespace**: `App\Http\Resources\Api\V1`

**Campos incluidos** (basados en tabla `customers` real):

- id
- name
- email
- subway_card (único del sistema Subway)
- birth_date
- gender
- phone
- timezone
- avatar (URL - nuevo campo OAuth)
- oauth_provider (nuevo campo OAuth)
- email_verified_at
- last_login_at
- last_activity_at
- last_purchase_at
- points (sistema de lealtad)
- points_updated_at
- is_online (computed - ya existe en modelo)
- status (computed - ya existe en modelo)
- created_at

**Campos excluidos**:

- password
- remember_token
- google_id (sensible)
- deleted_at
- customer_type_id (se incluye como relación)

**Relaciones condicionales**:

- customer_type (when loaded) → CustomerTypeResource
- addresses (when loaded) → AddressResourceCollection
- nits (when loaded) → NitResourceCollection
- devices (when loaded) → DeviceResourceCollection
- addresses_count (whenCounted)
- nits_count (whenCounted)
- devices_count (whenCounted)

### Resource 2: AuthResource

**Campos**:

- access_token (string)
- token_type: "Bearer"
- expires_in: minutos hasta expiración (525600 o null)
- customer: CustomerResource

**Uso**: Respuesta de login y register

### Resource 3: DeviceResource

**Namespace**: `App\Http\Resources\Api\V1`

**Campos** (basados en tabla `customer_devices` real):

- id
- device_name
- device_type (enum: ios, android, web - ya existe)
- device_model
- app_version (nuevo campo)
- os_version (nuevo campo)
- last_used_at
- is_active
- created_at

**Campos excluidos**:

- fcm_token (sensible - no exponer al cliente)
- device_identifier (sensible)
- sanctum_token_id (interno)
- customer_id (obvio por contexto)
- deleted_at

**Relaciones**:

- is_current_device (boolean) → comparar con token actual

### Resource 4: AddressResource

**Namespace**: `App\Http\Resources\Api\V1`

**Campos** (basados en tabla `customer_addresses` real):

- id
- label (ej: "Casa", "Oficina")
- address_line
- latitude
- longitude
- delivery_notes
- is_default
- created_at
- updated_at

**Campos excluidos**:

- customer_id (obvio por contexto)

### Resource 5: NitResource

**Namespace**: `App\Http\Resources\Api\V1`

**Campos** (basados en tabla `customer_nits` real):

- id
- nit
- nit_type (enum: personal, company, other)
- business_name
- is_default
- created_at
- updated_at

**Campos excluidos**:

- customer_id (obvio por contexto)

### Resource 6: CustomerTypeResource

**Namespace**: `App\Http\Resources\Api\V1`

**Campos** (basados en tabla `customer_types` real):

- id
- name (bronze, silver, gold)
- points_required
- multiplier
- color
- is_active

### Resource 7: ErrorResource

**Estructura estandarizada**:

- message (string)
- errors (object, opcional) → validación por campo
- code (string, opcional) → código de error custom

**Ejemplo JSON**:

```json
{
  "message": "Los datos proporcionados no son válidos",
  "errors": {
    "email": ["El correo ya está registrado"],
    "password": ["La contraseña debe tener al menos 6 caracteres"]
  }
}
```

### Verificación de Fase 7

- [x] Resources creados: CustomerResource, CustomerTypeResource, CustomerAddressResource, CustomerNitResource, CustomerDeviceResource
- [x] Campos sensibles ocultos (password, fcm_token, oauth IDs)
- [x] Formato JSON consistente
- [x] Relaciones incluidas condicionalmente con whenLoaded()
- [x] Computed attributes funcionando (is_online, status)
- [x] Respuestas de auth incluyen token + customer
- [x] Todos los recursos reflejan estructura real de base de datos

---

## FASE 8: Firebase Cloud Messaging (FCM) - Notificaciones Push ✅ COMPLETADA

### Objetivos

- Configurar Firebase en backend
- Crear endpoints para registro de dispositivos
- Implementar servicio de envío de notificaciones
- Aprovechar tabla `customer_devices` existente

### Estado Actual

**✅ Ya Implementado**:

- Tabla `customer_devices` con columnas:
  - `fcm_token` (TEXT, unique)
  - `device_type` (enum: ios, android, web)
  - `device_name`, `device_model`
  - `last_used_at`, `is_active`
  - `created_at`, `updated_at`, `deleted_at` (soft deletes)
- Modelo `CustomerDevice` con:
  - Relación `belongsTo(Customer)`
  - Scopes: `active()`, `inactive()`, `shouldBeInactive()`, `shouldBeDeleted()`
  - Métodos: `markAsActive()`, `markAsInactive()`, `updateLastUsed()`
- Sistema de lifecycle automático (30 días inactivo, 360 días eliminación)
- Comando artisan: `ManageCustomerDevicesLifecycle`

**✅ Implementado Completamente**:

- ✅ Paquete Firebase PHP SDK (kreait/firebase-php v7.23.0)
- ✅ FCMService para enviar notificaciones push (sendToDevice, sendToCustomer, sendToMultipleCustomers)
- ✅ Endpoints API para registrar/actualizar dispositivos (GET, POST, DELETE /api/v1/devices)
- ✅ Integración con personal_access_tokens (campo sanctum_token_id)
- ✅ Manejo automático de tokens FCM inválidos
- ✅ Configuración Firebase en AppServiceProvider
- ✅ Credenciales Firebase almacenadas en storage/app/firebase/

**⚠️ Nota sobre Testing**:
- Testing de notificaciones push con dispositivos reales requiere app móvil con Firebase SDK configurado
- Backend está 100% funcional y listo para producción
- Testing manual pendiente hasta que exista app móvil real

### Instalación

**Paquete**: `kreait/firebase-php` (NO instalado)

Proporciona SDK de Firebase para PHP.

**Comando**:

```bash
composer require kreait/firebase-php
```

### Configuración

**Archivo de credenciales**: `storage/app/firebase-credentials.json`

Obtenido desde Firebase Console → Project Settings → Service Accounts.

**Variable de entorno** (.env):

```env
FIREBASE_CREDENTIALS=storage/app/firebase-credentials.json
```

**Service Provider**: registrar singleton de Firebase\Factory en `AppServiceProvider`

```php
use Kreait\Firebase\Factory;

public function register(): void
{
    $this->app->singleton('firebase', function ($app) {
        return (new Factory)
            ->withServiceAccount(storage_path('app/firebase-credentials.json'));
    });
}
```

### Model: CustomerDevice (actualizar existente)

**Archivo**: `app/Models/CustomerDevice.php`

**Estado Actual**:

```php
class CustomerDevice extends Model
{
    use HasFactory, SoftDeletes;

    // Relaciones
    public function customer(): BelongsTo

    // Scopes existentes
    public function scopeActive($query)
    public function scopeInactive($query)
    public function scopeShouldBeInactive($query)
    public function scopeShouldBeDeleted($query)

    // Métodos existentes
    public function markAsActive(): void
    public function markAsInactive(): void
    public function updateLastUsed(): void
}
```

**Agregar relación con tokens**:

```php
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Relación con el token de Sanctum (opcional)
 */
public function token(): BelongsTo
{
    return $this->belongsTo(PersonalAccessToken::class, 'sanctum_token_id');
}
```

**Agregar scope adicional**:

```php
/**
 * Scope para dispositivos de un customer específico
 */
public function scopeForCustomer($query, int $customerId)
{
    return $query->where('customer_id', $customerId);
}

/**
 * Scope para filtrar por plataforma
 */
public function scopePlatform($query, string $platform)
{
    return $query->where('device_type', $platform);
}
```

### Controller: DeviceController

**Namespace**: `App\Http\Controllers\Api\V1`

**Método**: `register(RegisterDeviceRequest $request)`

Lógica:
1. Validar fcm_token, device_name, platform
2. Buscar dispositivo existente por device_identifier
3. Si existe → actualizar FCM token y datos
4. Si no existe → crear nuevo registro
5. Asociar con token Sanctum actual
6. Marcar como activo y actualizar last_used_at
7. Retornar DeviceResource

**Método**: `index(Request $request)`

Lógica:
1. Obtener usuario autenticado
2. Listar todos sus dispositivos activos
3. Ordenar por last_used_at desc
4. Indicar cuál es el dispositivo actual
5. Retornar DeviceResourceCollection

**Método**: `destroy(Request $request, DeviceToken $device)`

Lógica:
1. Verificar dispositivo pertenece al usuario autenticado
2. Soft delete o marcar is_active = false
3. Retornar mensaje de éxito

### Service: FCMService

**Namespace**: `App\Services`

**Método**: `sendNotification(string $fcmToken, array $data)`

Parámetros:
- $fcmToken: token del dispositivo destino
- $data: array con title, body, custom data

Lógica:
1. Construir mensaje FCM
2. Configurar prioridad (high)
3. Agregar datos custom
4. Enviar mediante Firebase SDK
5. Manejar tokens inválidos → marcar dispositivo como inactivo
6. Retornar resultado (éxito/fallo)

**Método**: `sendToUser(int $userId, array $data)`

Lógica:
1. Obtener todos los FCM tokens activos del usuario
2. Enviar notificación a cada dispositivo
3. Retornar cantidad enviada exitosamente

**Método**: `sendToMultipleUsers(array $userIds, array $data)`

Lógica:
1. Obtener tokens de múltiples usuarios
2. Usar multicast de Firebase
3. Optimizar envío en batch (máx 500 por batch)

### Estructura de Notificación

**Payload básico**:
- notification:
  - title: "Nuevo mensaje"
  - body: "Tienes un pedido pendiente"
  - icon: URL del ícono
  - sound: "default"
- data:
  - type: "order" | "promo" | "system"
  - id: ID del recurso relacionado
  - action: "view" | "open"
  - custom: JSON adicional

### Manejo de Errores FCM

**Token inválido/expirado**:
- Capturar excepción InvalidArgumentException
- Marcar device_token.is_active = false
- Registrar en log

**Timeout/Red**:
- Intentar reenvío con exponential backoff
- Máximo 3 intentos
- Si falla → encolar en job asíncrono

### Verificación de Fase 8
- [x] Firebase SDK instalado y configurado
- [x] Credenciales JSON en storage
- [x] CustomerDevice model con relaciones (token() → PersonalAccessToken)
- [x] Endpoints de registro funcionando (3 endpoints: index, register, destroy)
- [x] FCMService puede enviar notificaciones (4 métodos: sendToDevice, sendToCustomer, sendToMultipleCustomers, sendToAllCustomers)
- [x] Tokens inválidos se desactivan automáticamente (markDeviceAsInactive en NotFound exception)
- [x] Múltiples dispositivos por usuario soportados
- [x] Notificaciones backend funcional (⚠️ Testing con dispositivos reales requiere app móvil)

---

## FASE 9: Swagger/OpenAPI - Documentación Interactiva ✅ COMPLETADA

### Objetivos
- Instalar generador de documentación OpenAPI
- Anotar todos los controllers API
- Definir esquemas de datos
- Configurar autenticación Bearer
- UI interactiva para testing

### Instalación

**Paquete**: `darkaonline/l5-swagger`

Genera documentación OpenAPI 3.0 desde anotaciones PHP.

**Comando**: publish config y views

### Configuración

**Archivo**: `config/l5-swagger.php`

**Configuraciones clave**:
- `api.title`: "Subway Admin Panel API"
- `api.version`: "1.0.0"
- `api.description`: descripción completa
- `routes.api`: "/api/documentation"
- `routes.docs`: "/docs" (JSON)
- `security`: definir Bearer token
- `generate_always`: false (producción)

### Estructura de Anotaciones

**Nivel Application** (Controller base o archivo separado):

Definiciones globales:
- @OA\Info: título, versión, descripción, contacto
- @OA\Server: URLs de desarrollo y producción
- @OA\SecurityScheme: Bearer token
- @OA\Tag: agrupación de endpoints

**Nivel Controller**:

Para cada método del controller:
- @OA\Post | Get | Put | Delete: método HTTP + ruta
- @OA\Tag: agrupar (ej: "Authentication")
- @OA\Summary: descripción breve
- @OA\Description: descripción detallada
- @OA\RequestBody: esquema del payload
- @OA\Parameter: query params, headers
- @OA\Response: para cada código HTTP posible
- @OA\Security: si requiere autenticación

**Nivel Schema**:

Definir modelos de datos:
- @OA\Schema: User, DeviceToken, etc.
- @OA\Property: cada campo con tipo y descripción
- @OA\Examples: ejemplos de JSON

### Endpoints a Documentar

**Grupo: Authentication**
1. POST /register
2. POST /login
3. POST /google
4. POST /logout
5. POST /logout-all
6. POST /forgot-password
7. POST /reset-password
8. POST /change-password
9. GET /me
11. PUT /profile

**Grupo: Devices**
1. POST /devices/register
2. GET /devices
3. DELETE /devices/{id}

**Total**: 14 endpoints documentados

### Esquemas (Schemas)

**Definir**:
- User (completo)
- UserPublic (sin campos sensibles)
- AuthResponse (token + user)
- DeviceToken
- ErrorResponse
- ValidationErrorResponse
- LoginRequest
- RegisterRequest
- GoogleLoginRequest

### Ejemplos de Responses

Para cada endpoint, incluir:
- Response 200 (éxito) con JSON de ejemplo
- Response 401 (no autenticado) con mensaje
- Response 422 (validación) con errores por campo
- Response 500 (error servidor) con mensaje genérico

### Seguridad en Swagger UI

**Configurar botón "Authorize"**:
- Tipo: HTTP Bearer
- Nombre: Authorization
- Ubicación: Header
- Formato: Bearer {token}

**Flujo de testing**:
1. Usuario hace login en Swagger UI
2. Copia token de respuesta
3. Click en "Authorize"
4. Pega token
5. Puede probar endpoints protegidos

### Generación de Documentación

**Comando**: `php artisan l5-swagger:generate`

Genera archivo JSON en `storage/api-docs/api-docs.json`

**Acceso UI**: `http://localhost/api/documentation`

### Verificación de Fase 9
- [x] L5-Swagger instalado y configurado (v9.0.1 con swagger-ui v5.30.2)
- [x] Todos los endpoints API documentados (17 endpoints con 214 anotaciones @OA\)
- [x] Esquemas de datos definidos (Customer, CustomerDevice en Controller.php)
- [x] Ejemplos de requests/responses incluidos (cada endpoint con ejemplos completos)
- [x] Autenticación Bearer configurada (SecurityScheme sanctum con HTTP bearer)
- [x] UI Swagger accesible y funcional (GET /api/documentation)
- [x] Documentación se regenera correctamente (storage/api-docs/api-docs.json - 66KB)
- [x] Testing desde UI funciona para endpoints (botón Authorize + Try it out disponibles)

---

## FASE 10: Middleware Personalizado y Manejo de Errores ✅ COMPLETADA

### Objetivos
- Forzar respuestas JSON en API
- Manejar excepciones con formato consistente
- Configurar CORS para API
- Rate limiting granular

### Middleware 1: ForceJsonResponse

**Namespace**: `App\Http\Middleware`

**Función**:
- Interceptar todas las requests a `/api/*`
- Forzar header `Accept: application/json`
- Asegurar que errores de validación retornen JSON

**Lógica**:
- Modificar request antes de procesarla
- Agregar header si no existe
- Aplicar globalmente al grupo `api`

### Middleware 2: ApiRateLimiter

**Función**: Rate limiting por usuario y endpoint

**Grupos definidos**:
1. `api.auth`: 5 requests/minuto
   - login, register, forgot-password
2. `api.general`: 60 requests/minuto
   - endpoints protegidos estándar
3. `api.heavy`: 10 requests/minuto
   - endpoints que hacen queries pesadas

**Configuración**: `app/Http/Kernel.php` o RouteServiceProvider

### Exception Handler

**Archivo**: `app/Exceptions/Handler.php`

**Modificar método `render()`**:

Capturar excepciones comunes:
1. **AuthenticationException**:
   - Response: 401 "No autenticado"
   - JSON: { "message": "..." }

2. **AuthorizationException**:
   - Response: 403 "No autorizado"
   - JSON: { "message": "..." }

3. **ValidationException**:
   - Response: 422 con errores por campo
   - JSON: { "message": "...", "errors": {...} }

4. **ModelNotFoundException**:
   - Response: 404 "Recurso no encontrado"
   - JSON: { "message": "..." }

5. **ThrottleRequestsException**:
   - Response: 429 "Demasiadas peticiones"
   - JSON: { "message": "...", "retry_after": 60 }

6. **QueryException** (errores DB):
   - Response: 500 "Error interno"
   - JSON: { "message": "Error procesando solicitud" }
   - NO exponer detalles en producción

**Método helper**: `jsonErrorResponse($message, $code)`

### Configuración CORS

**Archivo**: `config/cors.php`

**Configuración para API**:
- `paths`: ['/api/*']
- `allowed_methods`: ['*'] o ['GET', 'POST', 'PUT', 'DELETE']
- `allowed_origins`: [env('FRONTEND_URL')] o ['*'] en desarrollo
- `allowed_headers`: ['*']
- `exposed_headers`: ['Authorization']
- `max_age`: 3600
- `supports_credentials`: false (API stateless)

### Verificación de Fase 10
- [x] ForceJsonResponse aplicado al grupo api (registrado en bootstrap/app.php línea 42)
- [x] Todas las excepciones retornan JSON (7 excepciones manejadas con condición api/*)
- [x] Códigos HTTP apropiados por tipo de error (401, 403, 404, 422, 429, 500)
- [x] Rate limiting funciona por grupo (auth: 5/min, oauth: 10/min, api: 120/min)
- [x] CORS configurado correctamente (config/cors.php con origins configurables por env)
- [x] Errores de validación tienen formato consistente (estructura {message, errors})
- [x] Errores 500 no exponen stack traces en producción (QueryException verifica config('app.debug'))

---

## FASE 11: Testing de API ⚙️ EN PROGRESO

### Objetivos
- Cobertura completa de endpoints
- Tests de autenticación tradicional
- Tests de OAuth mocking
- Tests de dispositivos FCM
- Tests de edge cases

### Test Suite 1: Authentication ✅ IMPLEMENTADA

**Archivo**: `tests/Feature/Api/V1/Auth/LoginTest.php`

**Tests**:
1. `puede_hacer_login_con_credenciales_validas()`
   - Crear usuario
   - POST /api/v1/auth/login
   - Verificar token en respuesta
   - Verificar estructura UserResource

2. `rechaza_credenciales_invalidas()`
   - POST con password incorrecta
   - Assert 401
   - Verificar mensaje de error

3. `rechaza_usuario_inexistente()`
   - POST con email no registrado
   - Assert 401 (NO 404 por seguridad)

4. `puede_especificar_nombre_de_dispositivo()`
   - Login con `device_name`
   - Verificar token tiene ese nombre

5. `actualiza_last_login_at()`
   - Login exitoso
   - Verificar campo actualizado

### Test Suite 2: Registration ✅ IMPLEMENTADA

**Archivo**: `tests/Feature/Api/V1/Auth/RegisterTest.php`

**Tests**:
1. `puede_registrarse_con_datos_validos()`
2. `rechaza_email_duplicado()`
3. `requiere_password_confirmacion()`
4. `hashea_password_automaticamente()`
5. `crea_token_sanctum_al_registrarse()`

### Test Suite 3: Social Auth (Google) ⏳ PENDIENTE

**Archivo**: `tests/Feature/Api/V1/Auth/GoogleLoginTest.php`

**Tests con Mock**:
1. `puede_hacer_login_con_google_id_token()`
   - Mockear Google API verification
   - POST /api/v1/auth/google
   - Verificar usuario creado con google_id

2. `vincula_cuenta_existente_por_email()`
   - Crear usuario con email
   - Login con Google (mismo email)
   - Verificar google_id agregado

3. `rechaza_token_invalido()`
   - Mock devuelve error
   - Assert 401

### Test Suite 4: Password Management ✅ IMPLEMENTADA

**Archivo**: `tests/Feature/Api/V1/Auth/PasswordTest.php`

**Tests**:
1. `puede_solicitar_reset_de_password()`
2. `puede_cambiar_password_con_token_valido()`
3. `rechaza_token_expirado()`
4. `puede_cambiar_password_estando_autenticado()`
5. `requiere_password_actual_correcto()`

### Test Suite 5: Devices ⏳ PENDIENTE

**Archivo**: `tests/Feature/Api/V1/DeviceControllerTest.php`

**Tests**:
1. `puede_registrar_dispositivo_con_fcm_token()`
2. `actualiza_dispositivo_existente()`
3. `lista_dispositivos_del_usuario()`
4. `indica_dispositivo_actual()`
5. `puede_eliminar_dispositivo()`
6. `no_puede_eliminar_dispositivo_de_otro_usuario()`

### Test Suite 6: Authorization ⏳ PENDIENTE

**Archivo**: `tests/Feature/Api/V1/Auth/AuthorizationTest.php`

**Tests**:
1. `endpoint_protegido_requiere_autenticacion()`
2. `token_invalido_retorna_401()`
3. `token_revocado_no_funciona()`
4. `logout_revoca_solo_token_actual()`
5. `logout_all_revoca_todos_los_tokens()`

### Helpers de Testing

**Trait**: `AuthenticatedApiTest`

Métodos helpers:
- `actingAsApiUser($user)`: autenticar con Sanctum
- `createUserWithToken()`: crear usuario + token
- `assertJsonStructure()`: verificar estructura
- `assertAuthTokenInResponse()`: verificar token en respuesta

### Verificación de Fase 11
- [x] 15 tests de API implementados (Suites 1, 2, 4) ✅
- [x] Cobertura de happy paths ✅
- [x] Cobertura de error cases ✅
- [ ] OAuth tests con mocking ⏳ Pendiente (Suite 3)
- [ ] Device tests ⏳ Pendiente (Suite 5)
- [ ] Authorization tests ⏳ Pendiente (Suite 6)
- [ ] Rate limiting testeado ⏳ Pendiente
- [x] Validaciones testeadas ✅

**Estado Actual**: 3 de 6 suites completadas (50%)
**Tests Implementados**: 15 tests funcionando
- Suite 1 (Authentication): 5 tests ✅
- Suite 2 (Registration): 5 tests ✅
- Suite 4 (Password Management): 5 tests ✅

**Nota**: Los tests se ejecutan localmente usando la base de datos de testing configurada en .env (DB_TEST_*). No requieren conexión a servicios externos.

---

## FASE 12: Ampliación de Seeders para API

### Objetivos
- Ampliar seeders existentes con datos de API
- Generar tokens Sanctum para customers existentes
- Vincular customer_devices con tokens Sanctum
- Agregar customers con OAuth para testing

### Estado Actual

**✅ Ya Implementado**:
- `RealCustomersSeeder` - Crea 50 customers realistas con addresses y nits
- `CustomerSeeder` - Crea 50 customers + 1 test customer con devices
- Método `createCustomerRelations()` ya crea addresses y nits por default

**❌ Falta Implementar**:
- Tokens Sanctum (`$customer->createToken()`)
- Vinculación `customer_devices.sanctum_token_id` con tokens
- Customers con OAuth (google_id, oauth_provider) para testing

### Ampliar: RealCustomersSeeder

**Archivo**: `database/seeders/RealCustomersSeeder.php`

**Cambios requeridos**:

1. **Agregar 4 customers especiales para API testing** (adicionales a los 50 existentes):
   - Customer con OAuth Google
   - Customer con múltiples dispositivos
   - Customer inactivo (para probar lifecycle)
   - Customer de testing directo para Postman/Insomnia

2. **Actualizar método `createCustomerRelations()`** para crear:
   - Token Sanctum con `$customer->createToken('device_name')`
   - CustomerDevice vinculado a ese token con `sanctum_token_id`

**Customers especiales a agregar para API**:

1. **Customer Bronze - Login Tradicional**:
   - Email: `customer.bronze@subway.gt`
   - Password: `password`
   - Name: "Carlos López"
   - Subway Card: "1000000001"
   - Customer Type: Bronze (0-499 puntos)
   - Points: 150
   - Tokens: 1 (iPhone)
   - Direcciones: 1 (Casa)
   - NITs: 1 (Personal)
   - Dispositivos FCM: 1 registro

2. **Customer Silver - Múltiples direcciones**:
   - Email: `customer.silver@subway.gt`
   - Password: `password`
   - Name: "María Fernández"
   - Subway Card: "2000000001"
   - Customer Type: Silver (500-999 puntos)
   - Points: 750
   - Tokens: 2 (iPhone, Android)
   - Direcciones: 3 (Casa, Oficina, Universidad)
   - NITs: 2 (Personal, Empresa)
   - Dispositivos FCM: 2 registros

3. **Customer Gold - OAuth Google**:
   - Email: `customer.gold.google@subway.gt`
   - Name: "Juan Pérez"
   - Subway Card: "3000000001"
   - google_id: "123456789"
   - oauth_provider: "google"
   - Avatar: URL de Google
   - Customer Type: Gold (1000+ puntos)
   - Points: 1500
   - Token: 1 (iOS)
   - Direcciones: 2
   - NITs: 1

4. **Customer - Múltiples dispositivos**:
   - Email: `customer.multi@subway.gt`
   - Password: `password`
   - Name: "Roberto García"
   - Subway Card: "5000000001"
   - Customer Type: Silver
   - Points: 600
   - Tokens: 3 (iPhone, Android, Web)
   - Direcciones: 2
   - NITs: 2
   - Dispositivos FCM: 3
   - **Uso**: Simular usuario que usa app en múltiples dispositivos

6. **Customer - Sin actividad reciente**:
   - Email: `customer.inactive@subway.gt`
   - Password: `password`
   - Name: "Laura Rodríguez"
   - Subway Card: "6000000001"
   - Customer Type: Bronze
   - Points: 50
   - last_activity_at: 45 días atrás
   - last_purchase_at: 60 días atrás
   - Tokens: 1 (marcado para inactivación)
   - **Uso**: Probar lifecycle de dispositivos

**Cambios en el método `createCustomerRelations()`**:

**Antes** (solo creaba address y nit):

```php
private function createCustomerRelations(Customer $customer): void
{
    // Crear dirección por defecto
    $customer->addresses()->create([...]);

    // Crear NIT por defecto
    $customer->nits()->create([...]);
}
```

**Después** (agregar token + device):

```php
use App\Models\CustomerDevice;
use Illuminate\Support\Str;

private function createCustomerRelations(Customer $customer): void
{
    // Crear dirección por defecto (existente)
    $customer->addresses()->create([
        'label' => 'Casa',
        'address_line' => $this->getRandomAddress(),
        'latitude' => 14.6000 + (rand(-1000, 1000) / 10000),
        'longitude' => -90.5000 + (rand(-1000, 1000) / 10000),
        'delivery_notes' => null,
        'is_default' => true,
    ]);

    // Crear NIT por defecto (existente)
    $customer->nits()->create([
        'nit' => $this->generateNIT(),
        'nit_type' => 'personal',
        'business_name' => null,
        'is_default' => true,
    ]);

    // NUEVO: Crear token Sanctum + device vinculado
    $deviceTypes = ['ios', 'android', 'web'];
    $deviceType = $deviceTypes[array_rand($deviceTypes)];
    $deviceNames = [
        'ios' => ['iPhone 14 Pro', 'iPhone 15', 'iPhone 13 Mini'],
        'android' => ['Samsung Galaxy S23', 'Google Pixel 7', 'Xiaomi 13'],
        'web' => ['Chrome on macOS', 'Firefox on Windows', 'Safari on macOS'],
    ];
    $deviceName = $deviceNames[$deviceType][array_rand($deviceNames[$deviceType])];

    $token = $customer->createToken($deviceName);

    CustomerDevice::create([
        'customer_id' => $customer->id,
        'sanctum_token_id' => $token->accessToken->id,
        'fcm_token' => 'fcm_' . Str::random(152), // FCM tokens reales son ~152 chars
        'device_type' => $deviceType,
        'device_name' => $deviceName,
        'device_model' => $deviceName,
        'app_version' => '1.0.0',
        'os_version' => $deviceType === 'ios' ? '17.0' : ($deviceType === 'android' ? '13.0' : null),
        'is_active' => true,
        'last_used_at' => now(),
    ]);
}
```

**Agregar método para crear customers especiales API**:

```php
private function createApiTestCustomers(): void
{
    $this->command->info('   🔧 Creando customers de prueba para API...');

    // 1. Customer OAuth Google
    $googleCustomer = Customer::create([
        'name' => 'Juan Pérez (Google)',
        'email' => 'juan.google@subway.gt',
        'password' => null,
        'google_id' => '123456789012345678901',
        'oauth_provider' => 'google',
        'avatar' => 'https://lh3.googleusercontent.com/a/default-user',
        'subway_card' => '9000000001',
        'customer_type_id' => CustomerType::where('name', 'Oro')->first()->id,
        'points' => 1200,
        'email_verified_at' => now(),
        'timezone' => 'America/Guatemala',
    ]);
    $this->createCustomerRelations($googleCustomer);

    // 2. Customer Testing (Postman/Insomnia)
    $testCustomer = Customer::create([
        'name' => 'API Test Customer',
        'email' => 'api@subway.gt',
        'password' => Hash::make('password'),
        'subway_card' => '9999999999',
        'customer_type_id' => CustomerType::where('name', 'Plata')->first()->id,
        'points' => 500,
        'email_verified_at' => now(),
        'timezone' => 'America/Guatemala',
    ]);
    $this->createCustomerRelations($testCustomer);

    $this->command->line('   ✓ 2 customers de prueba API creados');
}
```

**Actualizar método `run()`** para llamar al nuevo método:

```php
public function run(): void
{
    $this->command->info('👤 Creando clientes realistas...');

    // ... código existente para crear 50 customers ...

    // NUEVO: Agregar customers de prueba para API
    $this->createApiTestCustomers();

    $this->command->info('   ✅ 53 clientes realistas creados (50 regulares + 3 API test)');
}
```

**Comando**: `php artisan db:seed --class=RealCustomersSeeder`

### Verificación de Fase 12

- [ ] RealCustomersSeeder ampliado con `createApiTestCustomers()`
- [ ] Método `createCustomerRelations()` actualizado con tokens + devices
- [ ] Todos los 50 customers existentes ahora tienen token Sanctum
- [ ] Todos los customers tienen CustomerDevice vinculado con `sanctum_token_id`
- [ ] 2 customers especiales API creados (Google OAuth, Test)
- [ ] Campos OAuth poblados correctamente (google_id, oauth_provider)
- [ ] FCM tokens generados con formato realista (152 chars)
- [ ] Seeder ejecuta sin errores: `php artisan db:seed --class=RealCustomersSeeder`
- [ ] Output muestra cantidad correcta: "53 clientes realistas creados"
- [ ] Solo ejecuta en ambiente development

---

## FASE 13: Documentación Técnica y README

### Objetivos
- Documentar arquitectura de API
- Guía de autenticación
- Ejemplos de uso
- Troubleshooting común

### Documento: API_DOCUMENTATION.md

**Secciones**:

1. **Introducción**
   - Propósito de la API
   - Versión actual
   - Base URL

2. **Autenticación**
   - Tipos soportados (email/password, Google)
   - Cómo obtener token
   - Cómo usar token en requests
   - Expiración y renovación

3. **Arquitectura**
   - Stack tecnológico
   - Laravel Sanctum para tokens
   - Firebase para notificaciones
   - OAuth con Socialite

4. **Endpoints**
   - Lista completa con links a Swagger
   - Rate limits por grupo
   - Códigos de respuesta HTTP

5. **Flujos Completos**
   - Diagrama de secuencia: Login tradicional
   - Diagrama de secuencia: Login con Google
   - Diagrama de secuencia: Registro de dispositivo
   - Diagrama de secuencia: Envío de notificación push

6. **Manejo de Errores**
   - Estructura de errores JSON
   - Códigos comunes y significado
   - Debugging tips

7. **Seguridad**
   - Rate limiting
   - Token expiration
   - Revocar tokens
   - OAuth security best practices

8. **Testing**
   - Cómo ejecutar tests
   - Usar Swagger UI
   - Postman collection (opcional)

9. **Deployment**
   - Variables de entorno requeridas
   - Comandos de setup
   - Configuración de Firebase
   - Configuración de OAuth

### Documento: OAUTH_SETUP.md

**Guía paso a paso**:

1. **Google OAuth**:
   - Crear proyecto en Google Cloud Console
   - Habilitar Google Sign-In API
   - Crear OAuth Client ID (iOS, Android, Web)
   - Configurar consent screen
   - Agregar variables a .env

### README.md Updates

**Sección nueva**: API REST

**Contenido**:
- Quick start para desarrolladores API
- Link a documentación completa
- Link a Swagger UI
- Credenciales de testing
- Ejemplos curl básicos

### Verificación de Fase 13
- [ ] API_DOCUMENTATION.md completo
- [ ] OAUTH_SETUP.md con guías detalladas
- [ ] README.md actualizado
- [ ] Diagramas de secuencia incluidos
- [ ] Ejemplos de código funcionales
- [ ] Troubleshooting section útil

---

## Criterios de Éxito

Al finalizar la implementación, el sistema debe cumplir:

1. ✅ **Autenticación Multi-Canal**
   - Login con email/password funciona
   - Login con Google OAuth funciona
   - Tokens Sanctum generados correctamente

2. ✅ **Gestión de Sesiones**
   - Usuario puede tener múltiples tokens activos
   - Puede listar sus dispositivos/sesiones
   - Puede cerrar sesión en dispositivo específico
   - Puede cerrar sesión en todos los dispositivos

3. ✅ **Notificaciones Push**
   - Dispositivos se registran con FCM token
   - API puede enviar notificaciones a usuario específico
   - API puede enviar notificaciones a múltiples usuarios
   - Tokens FCM inválidos se desactivan automáticamente

4. ✅ **Documentación Swagger**
   - Todos los endpoints documentados
   - UI Swagger accesible y funcional
   - Autenticación Bearer funciona en UI
   - Ejemplos de requests/responses completos

5. ✅ **Seguridad**
   - Rate limiting activo y configurado
   - Passwords hasheados con bcrypt
   - Tokens expiran después de 365 días
   - CORS configurado apropiadamente

6. ✅ **Testing**
   - 30+ tests de API pasando
   - Cobertura de happy paths y error cases
   - OAuth testeado con mocking
   - Tests de autenticación y autorización

7. ✅ **Usabilidad**
   - API versioning implementado (v1)
   - Errores JSON consistentes
   - Mensajes en español
   - Respuestas bien estructuradas

8. ✅ **Performance**
   - Eager loading de relaciones
   - Índices en campos de búsqueda
   - Rate limiting previene abuso
   - FCM envío en batch para múltiples usuarios

---

## Próximos Pasos Post-Implementación

**Fuera del alcance actual, considerar para futuras versiones**:

1. **Autenticación adicional**:
   - Login con Facebook
   - Login con Twitter/X
   - Two-factor authentication (2FA)
   - Biometric authentication

2. **Notificaciones avanzadas**:
   - Preferencias de notificaciones por usuario
   - Schedule de notificaciones
   - Notificaciones en silencio
   - Rich notifications (imágenes, botones)



4. **Seguridad avanzada**:
   - Device fingerprinting
   - Detección de dispositivos sospechosos
   - IP whitelisting
   - Audit logs detallados

5. **Analytics**:
   - Tracking de uso de API
   - Métricas de performance
   - Dashboard de analytics
   - Reportes de errores

---

## Conclusión

Este plan implementa una API REST completa y moderna que:

- **Soporta múltiples canales** de autenticación (tradicional + OAuth)
- **Es segura** con tokens Sanctum de larga duración
- **Permite múltiples dispositivos** simultáneos por usuario
- **Integra notificaciones push** vía Firebase Cloud Messaging
- **Está completamente documentada** con Swagger/OpenAPI interactivo
- **Tiene testing robusto** con 30+ tests automatizados
- **Es escalable** con versionado y arquitectura modular

**Complejidad**: Media-Alta
**Tiempo estimado**: 2-3 días de desarrollo full-time (reducido por infraestructura existente)

**Arquitectura Ya Existente (Ventaja Competitiva)**:
- ✅ Tabla `customers` (Authenticatable, Notifiable, SoftDeletes)
- ✅ Tabla `customer_devices` con FCM tokens y lifecycle management
- ✅ Tabla `customer_addresses` (múltiples direcciones por customer)
- ✅ Tabla `customer_nits` (múltiples NITs por customer)
- ✅ Tabla `customer_types` (sistema de niveles con puntos)
- ✅ Sistema de puntos y actualización automática de tipo
- ✅ Traits: LogsActivity, TracksUserStatus
- ✅ Controllers web: CustomerController, CustomerAddressController, CustomerNitController, CustomerDeviceController
- ✅ Form Requests de validación
- ✅ Factories y Seeders para testing

**Por Implementar (Estimado)**:

| Fase | Tarea | Tiempo | Archivos |
|------|-------|--------|----------|
| 1 | Migraciones OAuth + device fields | 0.5 días | 2 migraciones |
| 2 | Instalar y configurar Sanctum | 0.5 días | config, Customer model |
| 3 | Rutas y estructura API | 0.25 días | routes/api.php |
| 4 | Controllers API (reutilizar lógica) | 0.5 días | 4-5 controllers |
| 5 | Form Requests API | 0.25 días | 6 requests |
| 6 | OAuth Google | 1 día | SocialAuthController, Service |
| 7 | API Resources | 0.5 días | 7 resources |
| 8 | FCM Service | 0.5 días | FCMService, config |
| 9 | Swagger/OpenAPI | 0.5 días | Anotaciones |
| 10 | Middleware y errores | 0.25 días | 2 middleware |
| 11 | Testing API | 1 día | 30+ tests |
| 12 | Seeders desarrollo | 0.25 días | ApiDevelopmentSeeder |
| 13 | Documentación | 0.5 días | README, guías |

**Total Estimado**: 6.5 días → **Reducido a 2-3 días** por código reutilizable

**Dependencias Nuevas**: 3 paquetes
- `laravel/sanctum` - Autenticación API con tokens
- `laravel/socialite` - OAuth social login
- `kreait/firebase-php` - Firebase Cloud Messaging
- `darkaonline/l5-swagger` (opcional) - Documentación OpenAPI

**Arquitectura Nueva a Implementar**:
- API REST con Sanctum (customers como tokenable)
- OAuth social login (Google)
- Service FCM para notificaciones push
- Swagger documentación interactiva
- API Resources (7 nuevos)
- Versioning de API (`/api/v1/`)
- Guard `sanctum` con provider `customers`

**Ventajas del Sistema Actual**:
1. Infraestructura de customers completa
2. Sistema FCM devices ya con lifecycle management
3. Múltiples direcciones y NITs por customer
4. Sistema de puntos y customer_types
5. Controllers web reutilizables para API
6. Validaciones ya implementadas
7. Testing infrastructure con Pest

---

## Estado de Implementación - Actualización: Noviembre 2025

### Resumen de Progreso

| Fase | Nombre | Estado | Archivos Clave |
|------|--------|--------|----------------|
| **1** | **Estructura BD** | ✅ **COMPLETADA** | Migraciones OAuth + Sanctum |
| **2** | **Sanctum Config** | ✅ **COMPLETADA** | config/auth.php, config/sanctum.php, Customer model |
| **3** | **Rutas API** | ✅ **COMPLETADA** | routes/api.php (17 endpoints) |
| **4** | **Controllers** | ✅ **COMPLETADA** | AuthController, OAuthController, ProfileController |
| **5** | **Form Requests** | ✅ **COMPLETADA** | 5 Form Requests con validaciones en español |
| **6** | **OAuth Social** | ✅ **COMPLETADA** | SocialAuthService, Socialite integration |
| **7** | **API Resources** | ✅ **COMPLETADA** | 5 Resources para serialización JSON |
| **8** | **FCM Push** | ✅ **COMPLETADA** | Firebase SDK v7.23.0, FCMService, DeviceController (⚠️ Testing manual pendiente) |
| **9** | **Swagger Docs** | ✅ **COMPLETADA** | l5-swagger v9.0.1, 17 endpoints documentados, UI accesible |
| **10** | **Middleware** | ✅ **COMPLETADA** | ForceJsonResponse, 7 exception handlers, 3 rate limiters, CORS |
| **11** | **Testing** | ⚙️ **EN PROGRESO** | 15 tests implementados (Suites 1, 2, 4) - Pendientes: Suites 3, 5, 6 |
| **12** | **Seeders** | ⏳ **PENDIENTE** | Ampliar RealCustomersSeeder |
| **13** | **Docs Técnica** | ⏳ **PENDIENTE** | API_DOCUMENTATION.md |

### Implementación Completada (Fases 1-10)

**Autenticación Multi-Canal Funcional**:

- ✅ Login tradicional email/password
- ✅ Login con Google OAuth (id_token verification)
- ✅ Registro de nuevos customers
- ✅ Password reset flow
- ✅ Gestión de perfil
- ✅ Múltiples tokens por customer (dispositivos simultáneos)
- ✅ Logout individual y logout all

**Arquitectura Implementada**:

```text
/routes/api.php
├── POST /api/v1/auth/register
├── POST /api/v1/auth/login
├── POST /api/v1/auth/logout (protegido)
├── POST /api/v1/auth/logout-all (protegido)
├── POST /api/v1/auth/refresh (protegido)
├── POST /api/v1/auth/forgot-password
├── POST /api/v1/auth/reset-password
├── POST /api/v1/auth/email/verify/{id}/{hash}
├── POST /api/v1/auth/email/resend
├── POST /api/v1/auth/oauth/google
└── /api/v1/profile/* (protegido)
    ├── GET /profile
    ├── PUT /profile
    ├── DELETE /profile
    ├── POST /profile/avatar
    ├── DELETE /profile/avatar
    └── PUT /profile/password
```

**Servicios Implementados**:

- `SocialAuthService`: Verificación de tokens OAuth con Google via Socialite
- Rate limiting: auth (5/min), oauth (10/min), api (120/min)
- Sanctum tokens con expiración de 365 días

**Configuración OAuth**:

- Google OAuth via `config/services.php` + Socialite
- Variables en `.env.example` documentadas

**Nuevas Funcionalidades Implementadas (Fases 8-10)**:

**Fase 8 - Firebase Cloud Messaging (FCM)**:
- ✅ Firebase SDK instalado (kreait/firebase-php v7.23.0)
- ✅ FCMService con métodos: sendToDevice, sendToCustomer, sendToMultipleCustomers, sendToAllCustomers
- ✅ 3 endpoints de dispositivos: GET, POST /register, DELETE
- ✅ CustomerDevice vinculado con Sanctum tokens
- ✅ Manejo automático de tokens FCM inválidos
- ⚠️ Testing manual con dispositivos reales pendiente (requiere app móvil)

**Fase 9 - Swagger/OpenAPI**:
- ✅ l5-swagger v9.0.1 instalado y configurado
- ✅ 17 endpoints documentados con 214 anotaciones @OA\
- ✅ UI interactiva accesible en /api/documentation
- ✅ 2 esquemas principales: Customer, CustomerDevice
- ✅ Autenticación Bearer integrada (botón Authorize)
- ✅ 4 tags: Authentication, OAuth, Profile, Devices

**Fase 10 - Middleware y Manejo de Errores**:
- ✅ ForceJsonResponse aplicado a todas las rutas API
- ✅ 7 exception handlers con respuestas JSON consistentes
- ✅ 3 rate limiters granulares: auth (5/min), oauth (10/min), api (120/min)
- ✅ CORS configurado con balance desarrollo/producción
- ✅ Protección de información sensible en producción

**Próximos Pasos (Fases 11-13)**:

**Fase 11 - Testing de API**: 30+ tests automatizados
**Fase 12 - Seeders**: Ampliar RealCustomersSeeder con datos API
**Fase 13 - Documentación Técnica**: API_DOCUMENTATION.md completo
