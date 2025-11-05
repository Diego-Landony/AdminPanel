# Plan de Implementación: API REST + Autenticación Multi-Canal + Notificaciones Push

**Documento**: Plan de Implementación Técnica
**Fecha**: noviembre 2025
**Versión**: 1.0
**Alcance**: API Backend (ADMIN + CLIENT) + Integración Mobile + OAuth Social

---

## Resumen Ejecutivo

### Descripción General

Implementación de una API REST completa que permita a múltiples clientes (aplicaciones móviles iOS/Android, frontends externos, integraciones) acceder al sistema mediante:
- Autenticación tradicional (email/contraseña)
- Login social (Google OAuth, Apple Sign-In)
- Sistema de tokens seguros con múltiples dispositivos simultáneos
- Notificaciones push mediante Firebase Cloud Messaging
- Documentación interactiva completa con Swagger/OpenAPI

### Diferenciación con Sistema Actual

| Aspecto | Sistema Web Actual | API REST Nueva |
|---------|-------------------|----------------|
| **Autenticación** | Sesiones (cookies) | Tokens Sanctum (stateless) |
| **Clientes** | Solo navegador web | Móvil, web, integraciones |
| **Login Social** | No implementado | Google + Apple OAuth |
| **Múltiples Dispositivos** | Una sesión por navegador | Múltiples tokens simultáneos |
| **Notificaciones** | No tiene | Firebase Cloud Messaging |
| **Documentación API** | No aplica | Swagger UI interactiva |
| **Versionado** | No aplica | API versionada (`/api/v1/`) |
| **Rate Limiting** | Básico | Por endpoint y usuario |

### Casos de Uso Principales

**Caso 1: App Móvil - Login con Email**
- Usuario abre app móvil
- Ingresa email y contraseña
- API valida credenciales → genera token Sanctum
- App guarda token + registra dispositivo para FCM
- Usuario navega en app usando token en headers
- Recibe notificaciones push en dispositivo

**Caso 2: App Móvil - Login con Google**
- Usuario toca botón "Continuar con Google"
- Google SDK obtiene `id_token`
- App envía `id_token` a API
- API verifica con Google → crea/vincula usuario
- API genera token Sanctum
- Usuario logueado sin contraseña

**Caso 3: Múltiples Dispositivos**
- Usuario tiene iPhone + Web + Android
- Inicia sesión en cada uno → obtiene 3 tokens diferentes
- Puede ver sus sesiones activas
- Puede cerrar sesión en un dispositivo específico
- Puede cerrar sesión en todos los dispositivos
- recibe notificaciones en todos los dispositivos registrados

---

## FASE 1: Estructura de Base de Datos para Tokens y Dispositivos

### Objetivos
- Crear tablas necesarias para sistema de tokens
- Agregar campos OAuth a tabla users
- Crear tabla para gestión de dispositivos FCM
- Establecer índices para performance

### Migración 1: Personal Access Tokens (Sanctum)

**Tabla**: `personal_access_tokens`

Generada automáticamente por Sanctum. Campos principales:
- `tokenable_type`, `tokenable_id`: polimórfico → User
- `name`: nombre descriptivo del dispositivo/app
- `token`: hash del token (64 caracteres)
- `abilities`: JSON con permisos (inicialmente `["*"]`)
- `last_used_at`: timestamp de última petición
- `expires_at`: fecha de expiración (nullable)

**Índices**:
- `tokenable_type` + `tokenable_id` (búsqueda rápida de tokens por usuario)
- `token` (único, para autenticación)

### Migración 2: Campos OAuth en Users

**Tabla**: `users` (modificar)

**Campos nuevos**:
- `google_id`: VARCHAR(255) nullable, unique → ID de usuario en Google
- `apple_id`: VARCHAR(255) nullable, unique → ID de usuario en Apple
- `avatar`: TEXT nullable → URL de foto de perfil del provider OAuth
- `oauth_provider`: ENUM('local', 'google', 'apple') default 'local'
- `email_verified_at`: ajustar lógica para OAuth (auto-verificar)

**Índices**:
- `google_id` (único, búsqueda rápida)
- `apple_id` (único, búsqueda rápida)

**Lógica**:
- Si usuario existe con email pero sin `google_id` → vincular cuenta
- Si usuario no existe → crear con datos de OAuth
- Password nullable cuando `oauth_provider != 'local'`

### Migración 3: Tokens de Dispositivos FCM

**Tabla**: `device_tokens`

**Campos**:
- `id`: BIGINT auto-increment
- `user_id`: BIGINT foreign key → users.id (cascade delete)
- `sanctum_token_id`: BIGINT nullable foreign key → personal_access_tokens.id
- `device_name`: VARCHAR(100) → ej: "iPhone 13 de Juan"
- `device_identifier`: VARCHAR(255) unique → UUID del dispositivo
- `fcm_token`: TEXT → token de Firebase
- `platform`: ENUM('ios', 'android', 'web')
- `app_version`: VARCHAR(20) nullable
- `os_version`: VARCHAR(20) nullable
- `last_used_at`: TIMESTAMP
- `is_active`: BOOLEAN default true
- `created_at`, `updated_at`: TIMESTAMPS

**Índices**:
- `user_id` (foreign key)
- `device_identifier` (único)
- `fcm_token` (búsqueda para envío)
- `is_active` (filtro de dispositivos activos)

**Relaciones**:
- BelongsTo → User
- BelongsTo → PersonalAccessToken (opcional)

### Verificación de Fase 1
- [ ] Migraciones ejecutan sin errores
- [ ] Rollback funciona correctamente
- [ ] Foreign keys con cascade delete configurados
- [ ] Índices únicos previenen duplicados
- [ ] Campos nullable correctos según reglas de negocio
- [ ] OAuth ids aceptan NULL (usuarios locales)

---

## FASE 2: Instalación y Configuración de Laravel Sanctum

### Objetivos
- Instalar paquete Sanctum
- Configurar guards de autenticación
- Configurar expiración de tokens
- Preparar middleware para API

### Instalación

**Paquete**: `laravel/sanctum`

Comando Artisan publish para migración y config.

### Configuración del Guard

**Archivo**: `config/auth.php`

**Guard nuevo**: `sanctum`
- Driver: `sanctum`
- Provider: `users`

**Mantener guard**: `web` (para panel admin actual)

### Configuración de Sanctum

**Archivo**: `config/sanctum.php`

**Token Expiration**:
- Valor: 525600 minutos (365 días)
- Justificación: apps móviles necesitan sesiones largas
- Usuario puede cerrar sesiones manualmente

**Stateful Domains**:
- Solo localhost para desarrollo
- Producción: solo API pura (sin cookies)

**Middleware**:
- `EnsureFrontendRequestsAreStateful` → desactivado para API
- Solo para rutas `api/*`

### Modificación del Modelo User

**Trait nuevo**: `HasApiTokens` (de Sanctum)

Métodos agregados automáticamente:
- `tokens()`: relación HasMany con personal_access_tokens
- `createToken()`: crear nuevo token
- `currentAccessToken()`: obtener token actual

### Verificación de Fase 2
- [ ] Sanctum instalado correctamente
- [ ] Guard `sanctum` configurado en auth.php
- [ ] Modelo User usa trait HasApiTokens
- [ ] Migración de Sanctum ejecutada
- [ ] Config de expiración establecida
- [ ] Middleware API funcionando

---

## FASE 3: API REST - Estructura Base y Rutas de Autenticación

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
   - Obtener datos del usuario autenticado
   - Incluye: roles, permisos, stats
   - Retorna: UserResource

9. **PUT** `/profile`
   - Actualizar datos de perfil
   - Campos: name, email, timezone, avatar
   - Email change → re-verificar
   - Retorna: UserResource actualizado

### Rate Limiting

**Grupo `auth`**: 5 requests/minuto
- Aplicado a: login, register, forgot-password
- Previene: brute force attacks

**Grupo `api`**: 60 requests/minuto
- Aplicado a: endpoints protegidos generales
- Ajustable por endpoint específico

### Verificación de Fase 3
- [ ] Archivo routes/api.php creado
- [ ] Versionado v1 implementado
- [ ] 9 rutas de autenticación definidas
- [ ] Middleware auth:sanctum en rutas protegidas
- [ ] Rate limiting configurado y funcionando
- [ ] Rutas devuelven JSON correctamente

---

## FASE 4: Controllers de Autenticación

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
- [ ] 4 controllers creados en namespace correcto
- [ ] Métodos retornan respuestas JSON consistentes
- [ ] Errores manejados con códigos HTTP apropiados
- [ ] Lógica de negocio separada de validación
- [ ] Tokens creados con nombres descriptivos
- [ ] Last login actualizado en cada login
- [ ] Passwords hasheados automáticamente

---

## FASE 5: Form Requests - Validaciones API

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

### Request 5: ChangePasswordRequest

**Reglas**:
- `current_password`: required, string
- `password`: required, string, min:6, confirmed, different:current_password

**Validación custom**: verificar current_password con Hash

### Request 6: UpdateProfileRequest

**Reglas**:
- `name`: optional, string, max:255
- `email`: optional, email, unique:users,email,{userId}, max:255
- `timezone`: optional, timezone
- `avatar`: optional, url, max:500

**Lógica unique**: ignorar email del usuario actual

### Verificación de Fase 5
- [ ] 6 Form Requests creados
- [ ] Reglas de validación completas y seguras
- [ ] Mensajes en español personalizados
- [ ] Unique rules consideran usuario actual
- [ ] Password confirmation validado
- [ ] Email format validado correctamente

---

## FASE 6: OAuth Social Login (Google + Apple)

### Objetivos
- Instalar Laravel Socialite
- Implementar login con Google OAuth
- Implementar login con Apple Sign-In
- Manejar vinculación de cuentas existentes
- Sincronizar datos del provider

### Instalación

**Paquete**: `laravel/socialite`

**Providers soportados**:
- Google (built-in)
- Apple (requiere sign-in-with-apple)

### Configuración

**Archivo**: `config/services.php`

**Google OAuth**:
- `client_id`: de Google Cloud Console
- `client_secret`: de Google Cloud Console
- `redirect`: no necesario para API móvil (client-side)

**Apple OAuth**:
- `client_id`: bundle ID de la app
- `client_secret`: JWT firmado con private key
- `redirect`: callback URL (si aplica)

**Variables de entorno** (.env):
- GOOGLE_CLIENT_ID
- GOOGLE_CLIENT_SECRET
- APPLE_CLIENT_ID
- APPLE_TEAM_ID
- APPLE_KEY_ID
- APPLE_PRIVATE_KEY (ruta al archivo .p8)

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

**Método**: `apple(AppleLoginRequest $request)`

Lógica similar a Google:
1. Recibir `authorization_code` o `id_token`
2. Verificar con Apple API
3. Extraer datos: sub (apple_id), email, name
4. Buscar/crear/vincular usuario
5. Nota: Apple solo envía name en primer login
6. Crear token y retornar

### Service: SocialAuthService

**Método**: `verifyGoogleToken(string $idToken)`
- Llamar a Google API
- Validar audiencia (client_id)
- Retornar datos del usuario o lanzar excepción

**Método**: `verifyAppleToken(string $token)`
- Decodificar JWT
- Validar firma con clave pública de Apple
- Verificar issuer y audience
- Retornar datos

**Método**: `findOrCreateUserFromProvider(array $providerData, string $provider)`
- Lógica compartida de búsqueda/creación
- Retorna usuario existente o nuevo
- Maneja vinculación automática por email

### Manejo de Casos Edge

**Caso 1**: Usuario existe con email pero sin OAuth
- Acción: vincular google_id/apple_id a usuario existente
- Notificar al usuario por email (seguridad)

**Caso 2**: Usuario usa Google y luego Apple con mismo email
- Acción: vincular ambos providers al mismo usuario
- Permitir login por cualquiera

**Caso 3**: Email no proporcionado por Apple
- Acción: generar email temporal (uuid@privaterelay.appleid.com)
- O solicitar email al usuario

**Caso 4**: Password NULL en OAuth users
- Acción: permitir, password es nullable
- Usuario puede establecer password después para login tradicional

### Verificación de Fase 6
- [ ] Socialite instalado y configurado
- [ ] Google OAuth funcionando con id_token
- [ ] Apple Sign-In funcionando con authorization_code
- [ ] Vinculación automática por email funciona
- [ ] Avatar sincronizado desde provider
- [ ] Email verificado automáticamente en OAuth
- [ ] Casos edge manejados correctamente
- [ ] Service de verificación de tokens implementado

---

## FASE 7: API Resources - Respuestas Consistentes

### Objetivos
- Crear API Resources para serialización
- Formato de respuesta estandarizado
- Ocultar campos sensibles
- Incluir relaciones según contexto

### Resource 1: UserResource

**Namespace**: `App\Http\Resources\Api\V1`

**Campos incluidos**:
- id
- name
- email
- avatar (URL)
- oauth_provider
- timezone
- email_verified_at
- last_login_at
- is_online (computed)
- status (computed)
- created_at

**Campos excluidos**:
- password
- remember_token
- google_id, apple_id (sensibles)
- deleted_at

**Relaciones condicionales**:
- roles (when loaded)
- permissions (when loaded)
- device_tokens_count (whenCounted)

### Resource 2: AuthResource

**Campos**:
- access_token (string)
- token_type: "Bearer"
- expires_in: minutos hasta expiración (o null)
- user: UserResource

**Uso**: Respuesta de login y register

### Resource 3: DeviceResource

**Campos**:
- id
- device_name
- platform
- app_version
- os_version
- last_used_at
- is_active
- created_at

**Campos excluidos**:
- fcm_token (sensible)
- device_identifier (sensible)
- sanctum_token_id

**Relaciones**:
- is_current_device (boolean) → comparar con token actual

### Resource 4: ErrorResource

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
- [ ] 4 Resources creados
- [ ] Campos sensibles ocultos
- [ ] Formato JSON consistente
- [ ] Relaciones incluidas condicionalmente
- [ ] Computed attributes funcionando
- [ ] AuthResource incluye token + user

---

## FASE 8: Firebase Cloud Messaging (FCM) - Notificaciones Push

### Objetivos
- Configurar Firebase en backend
- Crear endpoints para registro de dispositivos
- Implementar servicio de envío de notificaciones
- Asociar tokens FCM con usuarios

### Instalación

**Paquete**: `kreait/firebase-php`

Proporciona SDK de Firebase para PHP.

### Configuración

**Archivo de credenciales**: `storage/app/firebase-credentials.json`

Obtenido desde Firebase Console → Project Settings → Service Accounts.

**Variable de entorno** (.env):
```
FIREBASE_CREDENTIALS=storage/app/firebase-credentials.json
```

**Service Provider**: registrar singleton de Firebase\Factory

### Model: DeviceToken

**Namespace**: `App\Models`

**Relaciones**:
- BelongsTo User
- BelongsTo PersonalAccessToken (opcional)

**Scopes**:
- `active()`: where is_active = true
- `platform($platform)`: filter por iOS/Android
- `forUser($userId)`: dispositivos de un usuario

**Métodos**:
- `markAsUsed()`: actualizar last_used_at
- `deactivate()`: marcar is_active = false

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
- [ ] Firebase SDK instalado y configurado
- [ ] Credenciales JSON en storage
- [ ] DeviceToken model con relaciones
- [ ] Endpoints de registro funcionando
- [ ] FCMService puede enviar notificaciones
- [ ] Tokens inválidos se desactivan automáticamente
- [ ] Múltiples dispositivos por usuario soportados
- [ ] Notificaciones llegan a dispositivos reales

---

## FASE 9: Swagger/OpenAPI - Documentación Interactiva

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
4. POST /apple
5. POST /logout
6. POST /logout-all
7. POST /forgot-password
8. POST /reset-password
9. POST /change-password
10. GET /me
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
- AppleLoginRequest

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
- [ ] L5-Swagger instalado y configurado
- [ ] Todos los endpoints API documentados
- [ ] Esquemas de datos definidos
- [ ] Ejemplos de requests/responses incluidos
- [ ] Autenticación Bearer configurada
- [ ] UI Swagger accesible y funcional
- [ ] Documentación se regenera correctamente
- [ ] Testing desde UI funciona para endpoints

---

## FASE 10: Middleware Personalizado y Manejo de Errores

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
- [ ] ForceJsonResponse aplicado al grupo api
- [ ] Todas las excepciones retornan JSON
- [ ] Códigos HTTP apropiados por tipo de error
- [ ] Rate limiting funciona por grupo
- [ ] CORS configurado correctamente
- [ ] Errores de validación tienen formato consistente
- [ ] Errores 500 no exponen stack traces en producción

---

## FASE 11: Testing de API

### Objetivos
- Cobertura completa de endpoints
- Tests de autenticación tradicional
- Tests de OAuth mocking
- Tests de dispositivos FCM
- Tests de edge cases

### Test Suite 1: Authentication

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

### Test Suite 2: Registration

**Archivo**: `tests/Feature/Api/V1/Auth/RegisterTest.php`

**Tests**:
1. `puede_registrarse_con_datos_validos()`
2. `rechaza_email_duplicado()`
3. `requiere_password_confirmacion()`
4. `hashea_password_automaticamente()`
5. `crea_token_sanctum_al_registrarse()`

### Test Suite 3: Social Auth (Google)

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

3. `permite_login_con_google_y_apple_mismo_usuario()`
   - Vincular ambos providers

4. `rechaza_token_invalido()`
   - Mock devuelve error
   - Assert 401

### Test Suite 4: Password Management

**Archivo**: `tests/Feature/Api/V1/Auth/PasswordTest.php`

**Tests**:
1. `puede_solicitar_reset_de_password()`
2. `puede_cambiar_password_con_token_valido()`
3. `rechaza_token_expirado()`
4. `puede_cambiar_password_estando_autenticado()`
5. `requiere_password_actual_correcto()`

### Test Suite 5: Devices

**Archivo**: `tests/Feature/Api/V1/DeviceControllerTest.php`

**Tests**:
1. `puede_registrar_dispositivo_con_fcm_token()`
2. `actualiza_dispositivo_existente()`
3. `lista_dispositivos_del_usuario()`
4. `indica_dispositivo_actual()`
5. `puede_eliminar_dispositivo()`
6. `no_puede_eliminar_dispositivo_de_otro_usuario()`

### Test Suite 6: Authorization

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
- [ ] 30+ tests de API funcionando
- [ ] Cobertura de happy paths
- [ ] Cobertura de error cases
- [ ] OAuth tests con mocking
- [ ] Rate limiting testeado
- [ ] Validaciones testeadas
- [ ] Todas las assertions pasando

---

## FASE 12: Seeders de Desarrollo

### Objetivos
- Crear usuarios de prueba con diferentes roles
- Generar tokens de ejemplo
- Registrar dispositivos simulados
- Datos realistas para testing manual

### Seeder: ApiDevelopmentSeeder

**Namespace**: `Database\Seeders`

**Usuarios a crear**:

1. **Usuario Admin API**:
   - Email: `api-admin@example.com`
   - Password: `password`
   - Roles: admin
   - Tokens: 2 (iPhone, Android)
   - Dispositivos FCM: 2 registros

2. **Usuario Normal API**:
   - Email: `api-user@example.com`
   - Password: `password`
   - Roles: user
   - Tokens: 1 (iPhone)

3. **Usuario OAuth Google**:
   - Email: `google@example.com`
   - google_id: "123456789"
   - oauth_provider: "google"
   - Avatar: URL de Google
   - Token: 1

4. **Usuario OAuth Apple**:
   - Email: `apple@example.com`
   - apple_id: "001234.abcd..."
   - oauth_provider: "apple"
   - Token: 1

5. **Usuario con múltiples dispositivos**:
   - Email: `multi@example.com`
   - Tokens: 5 (iPhone, iPad, Android Phone, Android Tablet, Web)
   - Dispositivos FCM: 5

**Lógica del Seeder**:
1. Verificar entorno (solo development/local)
2. Crear usuarios con User::factory()
3. Para cada usuario, crear tokens con createToken()
4. Para cada token, crear DeviceToken asociado
5. Generar FCM tokens falsos (string aleatorio)
6. Output: mostrar emails y tokens generados

**Comando**: `php artisan db:seed --class=ApiDevelopmentSeeder`

### Verificación de Fase 12
- [ ] Seeder crea 5 usuarios de prueba
- [ ] Tokens Sanctum generados correctamente
- [ ] Dispositivos FCM asociados
- [ ] Datos variados (OAuth + tradicional)
- [ ] Output muestra credenciales para testing
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
   - Tipos soportados (email/password, Google, Apple)
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

2. **Apple Sign-In**:
   - Configurar en Apple Developer Portal
   - Crear Service ID
   - Generar private key (.p8)
   - Configurar bundle ID
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
   - Login con Apple Sign-In funciona
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
**Tiempo estimado**: 3-4 días de desarrollo full-time
**Archivos nuevos**: ~30
**Archivos modificados**: ~8
**Dependencias nuevas**: 4 paquetes
**Tests**: 30+ tests

**Arquitectura reutilizada**:
- Sistema de usuarios y roles existente
- Middleware de permisos actual
- Sistema de validaciones Laravel

**Arquitectura nueva**:
- API REST con Sanctum
- OAuth social login
- FCM notificaciones push
- Swagger documentación
- API Resources
- Versioning de API
