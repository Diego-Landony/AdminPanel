# API Móvil - Documentación Swagger/OpenAPI

## Resumen de la Actualización

La documentación Swagger/OpenAPI para la API móvil de Subway Guatemala ha sido completada y actualizada. El proyecto utiliza **L5-Swagger** (OpenAPI 3.0) con anotaciones PHP en los controladores.

## Acceso a la Documentación

- **URL Local**: `http://localhost:8000/api/documentation`
- **JSON**: `http://localhost:8000/docs/api-docs.json`
- **YAML**: `http://localhost:8000/docs/api-docs.yaml`

## Endpoints Documentados

### Authentication (11 endpoints)

| Método | Endpoint | Descripción | Autenticación |
|--------|----------|-------------|---------------|
| POST | `/api/v1/auth/register` | Registro con email, password, phone (8 dígitos), birth_date, gender, device_identifier | No |
| POST | `/api/v1/auth/login` | Login con email, password, device_identifier. Retorna token Sanctum | No |
| POST | `/api/v1/auth/logout` | Logout dispositivo actual | Sanctum |
| POST | `/api/v1/auth/logout-all` | Logout todos los dispositivos | Sanctum |
| POST | `/api/v1/auth/refresh` | Rotar token Sanctum | Sanctum |
| POST | `/api/v1/auth/forgot-password` | Solicitar reset de contraseña | No |
| POST | `/api/v1/auth/reset-password` | Resetear contraseña con token | No |
| POST | `/api/v1/auth/email/verify/{id}/{hash}` | Verificar email (signed URL) | No |
| POST | `/api/v1/auth/email/resend` | Reenviar verificación de email | No |
| POST | `/api/v1/auth/reactivate` | Reactivar cuenta eliminada (30 días) | No |

### OAuth (4 endpoints)

| Método | Endpoint | Descripción | Autenticación |
|--------|----------|-------------|---------------|
| GET | `/api/v1/auth/oauth/google/redirect` | Iniciar OAuth Google (web/mobile) | No |
| GET | `/api/v1/auth/oauth/google/callback` | Callback OAuth Google | No |
| GET | `/api/v1/auth/oauth/apple/redirect` | Iniciar OAuth Apple (web/mobile) | No |
| GET | `/api/v1/auth/oauth/apple/callback` | Callback OAuth Apple | No |

**Nota OAuth**: Ambos proveedores (Google y Apple) soportan flujo unificado web/mobile usando parámetro `state` de OAuth 2.0.

### Profile (6 endpoints)

| Método | Endpoint | Descripción | Autenticación |
|--------|----------|-------------|---------------|
| GET | `/api/v1/profile` | Ver perfil del cliente autenticado | Sanctum |
| PUT | `/api/v1/profile` | Actualizar perfil (first_name, last_name, email, phone, birth_date, gender) | Sanctum |
| DELETE | `/api/v1/profile` | Eliminar cuenta (soft delete 30 días). Requiere password para cuentas locales, no requiere para OAuth | Sanctum |
| POST | `/api/v1/profile/avatar` | Subir/actualizar avatar URL | Sanctum |
| DELETE | `/api/v1/profile/avatar` | Eliminar avatar | Sanctum |
| PUT | `/api/v1/profile/password` | Cambiar contraseña (cuentas locales) o crear primera contraseña (cuentas OAuth) | Sanctum |

### Devices (3 endpoints)

| Método | Endpoint | Descripción | Autenticación |
|--------|----------|-------------|---------------|
| GET | `/api/v1/devices` | Listar dispositivos activos del cliente | Sanctum |
| POST | `/api/v1/devices/register` | Registrar/actualizar FCM token y nombre de dispositivo | Sanctum |
| DELETE | `/api/v1/devices/{device}` | Eliminar/desactivar dispositivo (soft delete) | Sanctum |

## Detalles de Validación

### POST /api/v1/auth/register

**Request Body (JSON):**
```json
{
  "first_name": "Juan",
  "last_name": "Pérez",
  "email": "juan@example.com",
  "password": "Pass123",
  "password_confirmation": "Pass123",
  "phone": "12345678",
  "birth_date": "1990-05-15",
  "gender": "male",
  "device_identifier": "550e8400-e29b-41d4-a716-446655440000"
}
```

**Validaciones:**
- `first_name`: requerido, string, max:255
- `last_name`: requerido, string, max:255
- `email`: requerido, email, único en tabla `customers`
- `password`: requerido, confirmado, mínimo 6 caracteres, 1 letra, 1 número
- `phone`: requerido, exactamente 8 dígitos numéricos (formato Guatemala)
- `birth_date`: requerido, fecha, anterior a hoy
- `gender`: requerido, enum: male|female|other
- `device_identifier`: requerido, string, max:255 (UUID del dispositivo)

**Response 201:**
```json
{
  "message": "Registro exitoso. Por favor verifica tu email.",
  "data": {
    "customer": {
      "id": 1,
      "first_name": "Juan",
      "last_name": "Pérez",
      "email": "juan@example.com",
      "subway_card": "802056895224",
      "points": 0,
      ...
    },
    "token": "1|abc123xyz..."
  }
}
```

### POST /api/v1/auth/login

**Request Body (JSON):**
```json
{
  "email": "juan@example.com",
  "password": "SecurePass123!",
  "device_identifier": "550e8400-e29b-41d4-a716-446655440000"
}
```

**Response 200 (Éxito):**
```json
{
  "message": "Inicio de sesión exitoso.",
  "data": {
    "customer": { ... },
    "token": "1|abc123xyz..."
  }
}
```

**Response 409 (Cuenta OAuth):**
```json
{
  "message": "Esta cuenta usa autenticación con google. Por favor inicia sesión con google.",
  "code": "oauth_account_required",
  "data": {
    "oauth_provider": "google",
    "email": "juan@example.com"
  }
}
```

**Response 409 (Cuenta Eliminada Recuperable):**
```json
{
  "message": "Encontramos una cuenta eliminada con este correo.",
  "code": "account_deleted_recoverable",
  "data": {
    "deleted_at": "2025-11-15T10:30:00Z",
    "days_until_permanent_deletion": 15,
    "points": 150,
    "can_reactivate": true,
    "oauth_provider": "local"
  }
}
```

### PUT /api/v1/profile/password

**Caso 1: Cuenta Local (cambiar contraseña existente)**
```json
{
  "current_password": "OldPass123!",
  "password": "NuevaPass123!",
  "password_confirmation": "NuevaPass123!"
}
```

**Caso 2: Cuenta OAuth (crear primera contraseña)**
```json
{
  "password": "NuevaPass123!",
  "password_confirmation": "NuevaPass123!"
}
```

**Response 200:**
```json
{
  "message": "Contraseña creada exitosamente. Ahora puedes iniciar sesión con tu correo y contraseña.",
  "data": {
    "password_created": true,
    "can_use_password_login": true
  }
}
```

**Comportamiento importante:**
- Cuentas locales: Requieren `current_password` para verificar identidad
- Cuentas OAuth (Google/Apple): NO requieren `current_password` (están creando su primera contraseña)
- Después de crear contraseña, usuarios OAuth pueden usar AMBOS métodos: OAuth O email+contraseña
- El campo `oauth_provider` cambia de `google`/`apple` a `local` al crear contraseña
- Mantienen vinculados sus IDs de Google/Apple para seguir usando OAuth si desean

### POST /api/v1/devices/register

**Request Body (JSON):**
```json
{
  "fcm_token": "fKw8h4Xj...",
  "device_identifier": "550e8400-e29b-41d4-a716-446655440000",
  "device_name": "iPhone 14 Pro de Juan"
}
```

**Validaciones:**
- `fcm_token`: requerido, string, max:255 (Firebase Cloud Messaging token)
- `device_identifier`: requerido, string, max:255 (UUID del dispositivo, DEBE coincidir con el usado en auth)
- `device_name`: requerido, string, max:255 (nombre personalizado que verá el usuario en lista de dispositivos)

**Nota importante sobre device_name:**
La app móvil DEBE enviar un nombre personalizado (ej: "iPhone 14 Pro de Juan", "Samsung Galaxy S23 de María") que permita al usuario identificar sus dispositivos. Este nombre se muestra cuando el usuario gestiona sus sesiones activas.

## Schemas Documentados

Los siguientes schemas están definidos en `/app/Http/Controllers/Controller.php`:

1. **Customer**: Modelo de cliente con datos de perfil y lealtad
2. **CustomerDevice**: Dispositivo registrado para notificaciones push
3. **Category**: Categoría de menú
4. **Product**: Producto del menú
5. **Combo**: Combo del menú
6. **Promotion**: Promoción del menú
7. **CustomerAddress**: Dirección de entrega del cliente
8. **Restaurant**: Ubicación de restaurante

## Tags de la API

- **Authentication**: Endpoints para registro, login, recuperación de contraseña
- **OAuth**: Autenticación con Google OAuth y Apple
- **Profile**: Gestión del perfil del cliente
- **Devices**: Gestión de dispositivos y tokens FCM
- **Addresses**: Gestión de direcciones de entrega
- **NITs**: Gestión de NITs para facturación
- **Favorites**: Gestión de favoritos
- **Points**: Sistema de puntos de lealtad
- **Menu**: Menú de productos y combos
- **Cart**: Gestión del carrito de compras
- **Orders**: Creación y gestión de órdenes

## Seguridad (Authentication)

**Scheme:** Bearer Token (Laravel Sanctum)

**Header:**
```
Authorization: Bearer 1|abc123xyz...
```

**Cómo obtener el token:**
1. POST `/api/v1/auth/register` → Retorna token en response
2. POST `/api/v1/auth/login` → Retorna token en response
3. GET `/api/v1/auth/oauth/google/callback` → Retorna token via redirect
4. GET `/api/v1/auth/oauth/apple/callback` → Retorna token via redirect

**Endpoints públicos (sin token):**
- POST `/api/v1/auth/register`
- POST `/api/v1/auth/login`
- POST `/api/v1/auth/forgot-password`
- POST `/api/v1/auth/reset-password`
- POST `/api/v1/auth/email/verify/{id}/{hash}`
- POST `/api/v1/auth/email/resend`
- POST `/api/v1/auth/reactivate`
- GET `/api/v1/auth/oauth/google/redirect`
- GET `/api/v1/auth/oauth/google/callback`
- GET `/api/v1/auth/oauth/apple/redirect`
- GET `/api/v1/auth/oauth/apple/callback`

**Endpoints protegidos (requieren token):**
- Todos los demás endpoints

## Flujo OAuth (Google y Apple)

### Web (Desktop/Browser)
1. Cliente inicia: `GET /api/v1/auth/oauth/google/redirect?action=login&platform=web`
2. Usuario autoriza en Google/Apple
3. Google/Apple redirige a: `/api/v1/auth/oauth/google/callback?code=...&state=...`
4. API procesa y redirige a: `/oauth/success?token=xxx&customer_id=123&is_new_customer=0`

### Mobile (iOS/Android)
1. Cliente inicia: `GET /api/v1/auth/oauth/google/redirect?action=login&platform=mobile&device_id=550e8400...`
2. Usuario autoriza en Google/Apple
3. Google/Apple redirige a: `/api/v1/auth/oauth/google/callback?code=...&state=...`
4. API procesa y redirige a deep link: `subwayapp://oauth/callback?token=xxx&customer_id=123&is_new_customer=0`

**Parámetros importantes:**
- `action`: `login` (usuario existente) o `register` (nuevo usuario)
- `platform`: `web` o `mobile`
- `device_id`: UUID del dispositivo (REQUERIDO si platform=mobile)
- `redirect_url`: URL personalizada para desarrollo local (opcional)

**Parámetro `is_new_customer`:**
- `1`: Usuario recién registrado (app debe mostrar pantalla de bienvenida/onboarding)
- `0`: Usuario existente (login normal)

## Códigos de Error Comunes

### 422 Unprocessable Entity (Validación)
```json
{
  "message": "Este correo ya está registrado.",
  "errors": {
    "email": ["Este correo ya está registrado."]
  }
}
```

### 401 Unauthorized
```json
{
  "message": "Unauthenticated."
}
```

### 403 Forbidden
```json
{
  "message": "No tienes permiso para acceder a este recurso."
}
```

### 404 Not Found
```json
{
  "message": "Recurso no encontrado."
}
```

### 429 Too Many Requests (Rate Limiting)
```json
{
  "message": "Demasiados intentos de acceso. Intenta de nuevo en 60 segundos."
}
```

### 409 Conflict (Cuenta OAuth o Eliminada)
```json
{
  "message": "Esta cuenta usa autenticación con google.",
  "code": "oauth_account_required",
  "data": { ... }
}
```

## Rate Limiting

- **Login**: 5 intentos por minuto por IP+email
- **Password Reset**: 1 solicitud cada 60 segundos por email
- **Email Verification**: Limitación estándar de Laravel

## Regenerar Documentación

```bash
php artisan l5-swagger:generate
```

La documentación se genera en:
- JSON: `storage/api-docs/api-docs.json`
- YAML: `storage/api-docs/api-docs.yaml`

## Configuración L5-Swagger

Ubicación: `config/l5-swagger.php`

**Configuración actual:**
- Título: "Subway Guatemala Customer API"
- Versión: 1.0.2
- Servidor Local: http://localhost:8000
- Formato: JSON (configurable a YAML)
- Ruta UI: /api/documentation
- Anotaciones desde: `app/` (escaneo automático)

## Archivos Modificados

### Controladores con Documentación Completa

1. `/app/Http/Controllers/Controller.php`
   - Info de la API
   - Security schemes (Sanctum)
   - Tags
   - Schemas: Customer, CustomerDevice, Category, Product, Combo, Promotion, CustomerAddress, Restaurant

2. `/app/Http/Controllers/Api/V1/Auth/AuthController.php`
   - POST /auth/register
   - POST /auth/login
   - POST /auth/logout
   - POST /auth/logout-all
   - POST /auth/refresh
   - POST /auth/forgot-password
   - POST /auth/reset-password
   - POST /auth/email/verify/{id}/{hash}
   - POST /auth/email/resend
   - POST /auth/reactivate

3. `/app/Http/Controllers/Api/V1/Auth/OAuthController.php`
   - GET /auth/oauth/google/redirect
   - GET /auth/oauth/google/callback
   - GET /auth/oauth/apple/redirect
   - GET /auth/oauth/apple/callback

4. `/app/Http/Controllers/Api/V1/ProfileController.php`
   - GET /profile
   - PUT /profile
   - DELETE /profile
   - POST /profile/avatar
   - DELETE /profile/avatar
   - PUT /profile/password

5. `/app/Http/Controllers/Api/V1/DeviceController.php`
   - GET /devices
   - POST /devices/register
   - DELETE /devices/{device}

### Form Requests con Validaciones

- `/app/Http/Requests/Api/V1/Auth/RegisterRequest.php`
- `/app/Http/Requests/Api/V1/Auth/LoginRequest.php`
- `/app/Http/Requests/Api/V1/Auth/ForgotPasswordRequest.php`
- `/app/Http/Requests/Api/V1/Auth/ResetPasswordRequest.php`
- `/app/Http/Requests/Api/V1/Auth/ReactivateAccountRequest.php`
- `/app/Http/Requests/Api/V1/Auth/ChangePasswordRequest.php`
- `/app/Http/Requests/Api/V1/UpdateProfileRequest.php`

## Notas Importantes

### Eliminación de Cuenta (Soft Delete)
- Período de gracia: 30 días
- Durante este período, el usuario puede reactivar usando POST `/api/v1/auth/reactivate`
- Los puntos se preservan durante el período de gracia
- Después de 30 días, la cuenta se elimina permanentemente (force delete)

### OAuth vs Local Authentication
- Usuarios OAuth pueden crear contraseña usando PUT `/api/v1/profile/password`
- Después de crear contraseña, pueden usar ambos métodos de autenticación
- El campo `oauth_provider` cambia a `local` al crear contraseña
- Mantienen vinculados sus IDs de Google/Apple

### Dispositivos
- Dispositivos se crean automáticamente durante auth usando `device_identifier`
- POST `/api/v1/devices/register` enriquece el dispositivo con FCM token y nombre
- El nombre del dispositivo DEBE ser personalizado por la app
- Los dispositivos se desactivan (soft delete) al eliminar, no se borran físicamente

### Validación de Teléfono
- Formato Guatemala: Exactamente 8 dígitos numéricos
- Ejemplo válido: "12345678"
- NO incluir código de país ni caracteres especiales

## Estado de Documentación

✅ **Completado al 100%**
- 14 endpoints de Authentication y OAuth
- 6 endpoints de Profile
- 3 endpoints de Devices
- Todos los schemas necesarios
- Ejemplos de request/response
- Validaciones detalladas
- Códigos de error documentados
- Security scheme configurado

Total: **23 endpoints documentados** de Auth, Profile y Devices

---

**Versión de API**: 1.0.2
**Última actualización**: Diciembre 2025
**Desarrollado para**: Subway Guatemala - App Móvil de Fidelidad
