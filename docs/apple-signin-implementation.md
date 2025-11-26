# Implementación Sign In with Apple

## Estado Actual

### ✅ Completado

- Migración de base de datos creada
- Paquete `socialiteproviders/apple` instalado
- Tabla `customers` lista para soportar `apple_id`
- Enum `oauth_provider` actualizado para incluir 'apple'

### 🔄 Pendiente

- Configuración en Apple Developer Account
- Configuración del backend Laravel
- Implementación de controladores y rutas
- Pruebas de integración

## Requisitos de Apple Developer Account

### 1. Team ID

**Ubicación**: Apple Developer Account → Membership

**Qué es**: Identificador único de tu cuenta de desarrollador

**Ejemplo**: `A1B2C3D4E5`

**Dónde encontrarlo**:
1. Ir a https://developer.apple.com/account
2. Click en "Membership" en el menú lateral
3. Copiar el "Team ID" que aparece

### 2. App ID

**Propósito**: Identificar tu aplicación móvil

**Requisitos**:
- Bundle ID único (ej: `com.subway.app`)
- Capability "Sign In with Apple" habilitada

**Pasos para crear**:
1. Ir a https://developer.apple.com/account/resources/identifiers
2. Click en "+" para crear nuevo Identifier
3. Seleccionar "App IDs" → Continue
4. Type: App
5. Description: `Subway App`
6. Bundle ID: `com.subway.app` (usar el de tu app iOS/Android)
7. Capabilities → Scroll hasta "Sign In with Apple"
8. Check "Sign In with Apple"
9. Continue → Register

### 3. Services ID (CRÍTICO para Web/API)

**Propósito**: Identificador para autenticación web/API (diferente al App ID)

**Requisitos**:
- Identifier DIFERENTE al App ID
- Configuración de Return URLs

**Pasos para crear**:
1. Ir a https://developer.apple.com/account/resources/identifiers
2. Click en "+" para crear nuevo Identifier
3. Seleccionar "Services IDs" → Continue
4. Description: `Subway Web Sign In`
5. Identifier: `com.subway.app.signin` (DIFERENTE al App ID)
6. Continue → Register
7. En la lista, click en el Services ID recién creado
8. Check "Sign In with Apple"
9. Click en "Configure" al lado de Sign In with Apple

**Configuración de Web Authentication**:
- Primary App ID: Seleccionar `com.subway.app` (el App ID creado antes)
- Website URLs:
  - Domains and Subdomains:
    - Producción: `tudominio.com`
    - Desarrollo: `localhost`
  - Return URLs:
    - Producción: `https://tudominio.com/api/v1/auth/oauth/apple/callback`
    - Desarrollo: `http://localhost:8000/api/v1/auth/oauth/apple/callback`
- Click "Next" → "Done" → "Continue" → "Save"

### 4. Private Key (Archivo .p8)

**Propósito**: Firmar el JWT client_secret para autenticación

**IMPORTANTE**: Solo se puede descargar UNA VEZ. Si lo pierdes, debes crear uno nuevo.

**Pasos para crear**:
1. Ir a https://developer.apple.com/account/resources/authkeys
2. Click en "+" para crear nuevo Key
3. Key Name: `Subway Apple Sign In Key`
4. Check "Sign In with Apple"
5. Click "Configure" al lado
6. Primary App ID: Seleccionar `com.subway.app`
7. Save → Continue → Register
8. **DESCARGAR el archivo .p8 INMEDIATAMENTE**
9. Guardar el archivo en ubicación segura
10. Anotar el **Key ID** que aparece (ej: `XYZ123ABC9`)

### 5. Datos a Recopilar

Después de completar los pasos anteriores, debes tener:

```
Team ID:       A1B2C3D4E5
Services ID:   com.subway.app.signin
Key ID:        XYZ123ABC9
Private Key:   AuthKey_XYZ123ABC9.p8 (archivo descargado)
```

## Configuración del Backend

### 1. Variables de Entorno

Agregar al archivo `.env`:

```env
# Apple Sign In
APPLE_CLIENT_ID=com.subway.app.signin
APPLE_TEAM_ID=A1B2C3D4E5
APPLE_KEY_ID=XYZ123ABC9
APPLE_REDIRECT=http://localhost:8000/api/v1/auth/oauth/apple/callback

# Private Key: Copiar contenido del archivo .p8
# Mantener formato con saltos de línea
APPLE_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----
MIGTAgEAMBMGByqGSM49AgEGCCqGSM49AwEHBHkwdwIBAQQg...
(contenido completo del archivo .p8)
...4KzEKvM8hPbKBhQ==
-----END PRIVATE KEY-----"
```

### 2. Configuración en `config/services.php`

Agregar configuración de Apple:

```php
'apple' => [
    'client_id' => env('APPLE_CLIENT_ID'),
    'client_secret' => env('APPLE_CLIENT_SECRET'), // Se genera automáticamente
    'redirect' => env('APPLE_REDIRECT'),
    'team_id' => env('APPLE_TEAM_ID'),
    'key_id' => env('APPLE_KEY_ID'),
    'private_key' => env('APPLE_PRIVATE_KEY'),
],
```

### 3. Registrar Provider en `bootstrap/app.php`

Agregar event listener para el provider de Apple:

```php
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Apple\Provider;

->withEvents(discovery: [
    SocialiteWasCalled::class => [
        // Agregar Apple provider
        'SocialiteProviders\\Apple\\AppleExtendSocialite@handle',
    ],
])
```

### 4. Ejecutar Migración

```bash
php artisan migrate
```

## Plan de Implementación Backend

### Fase 1: Configuración Base ✅

- [x] Instalar paquete `socialiteproviders/apple`
- [x] Crear migración para `apple_id` en tabla `customers`
- [ ] Ejecutar migración
- [ ] Configurar variables de entorno
- [ ] Registrar provider en `bootstrap/app.php`

### Fase 2: Controladores y Servicios

**Archivos a modificar**:

#### `app/Http/Controllers/Api/V1/Auth/OAuthController.php`

Agregar métodos:
- `appleRedirect()` - Redirige a Apple Sign In
- `appleCallback()` - Procesa respuesta de Apple

#### `app/Services/SocialAuthService.php`

Ya está preparado, solo necesita:
- Recibir 'apple' como provider
- Manejar campo `apple_id` en lugar de `google_id`

### Fase 3: Rutas API

**Archivo**: `routes/api.php`

Agregar dentro del grupo OAuth:
```php
Route::get('/apple/redirect', [OAuthController::class, 'appleRedirect'])
    ->name('api.v1.auth.oauth.apple.redirect');

Route::get('/apple/callback', [OAuthController::class, 'appleCallback'])
    ->name('api.v1.auth.oauth.apple.callback');
```

### Fase 4: Pruebas

- Probar redirect a Apple
- Probar callback y creación de usuario
- Probar login de usuario existente
- Probar vinculación de cuenta existente
- Probar desde web y móvil

## Diferencias Críticas: Apple vs Google

### 1. Datos del Usuario

**Google**:
- Siempre devuelve: ID, email, name, avatar
- En cada login posterior también devuelve todos los datos

**Apple**:
- Primera vez: ID, email, name
- Logins posteriores: SOLO ID
- Email y nombre NO se vuelven a enviar

**Implicación**:
- Backend DEBE guardar email en primera autenticación
- No se puede "actualizar" email de Apple después
- Si pierdes el email, no hay forma de recuperarlo de Apple

### 2. Autenticación con JWT

**Google**:
- Usa Client ID y Client Secret estáticos
- Configuración simple

**Apple**:
- Requiere generar JWT firmado como client_secret
- JWT usa private key (.p8)
- JWT expira (máximo 6 meses)
- El paquete lo genera automáticamente

### 3. User ID por App

**Google**:
- Mismo Google ID para todas las apps del mismo desarrollador

**Apple**:
- User ID es diferente para cada App ID
- Mismo usuario = diferentes IDs en diferentes apps
- Imposible vincular usuarios entre apps diferentes

### 4. Avatar

**Google**:
- Siempre proporciona URL del avatar

**Apple**:
- NO proporciona avatar
- Solo email y nombre (primera vez)

### 5. Scope de Email

**Google**:
- Email siempre incluido en scope básico

**Apple**:
- Usuario puede OCULTAR su email real
- Apple genera email relay: `xyz123@privaterelay.appleid.com`
- Email relay funciona pero no es el email real del usuario

## Flujo de Autenticación Apple

### Primera Autenticación

1. Usuario hace click en "Sign In with Apple"
2. Backend redirige a Apple con Services ID
3. Usuario autoriza en página de Apple
4. Apple redirige a callback con:
   - `code` (authorization code)
   - `id_token` (JWT con user data)
   - `user` (JSON con name y email) - SOLO PRIMERA VEZ
5. Backend intercambia code por tokens
6. Backend extrae datos:
   - `sub` (Apple user ID único)
   - `email` (del id_token o del parámetro user)
   - `name` (del parámetro user, solo primera vez)
7. Backend crea cuenta con `apple_id = sub`
8. Devuelve token Sanctum al cliente

### Autenticaciones Posteriores

1. Usuario hace click en "Sign In with Apple"
2. Backend redirige a Apple
3. Apple redirige a callback con:
   - `code`
   - `id_token`
   - **NO incluye parámetro `user`**
4. Backend intercambia code por tokens
5. Backend extrae solo `sub` del id_token
6. Backend busca usuario por `apple_id`
7. Devuelve token Sanctum al cliente

## Consideraciones de Seguridad

### Private Key

- Guardar archivo .p8 en ubicación SEGURA
- NO commitear a git
- Usar variables de entorno
- Rotación recomendada cada 6 meses

### Email Relay de Apple

- Usuario puede cambiar de email real a relay
- Email relay es permanente para esa app
- NO usar email como identificador único
- Usar `apple_id` como identificador principal

### Verificación de ID Token

- El paquete valida automáticamente firma del JWT
- Verifica que `aud` coincida con Services ID
- Verifica que `iss` sea `https://appleid.apple.com`
- Verifica que token no haya expirado

## Testing

### Desarrollo Local

Para probar en desarrollo local:

1. Configurar `localhost` en Services ID (ya hecho en setup)
2. Usar `http://localhost:8000` como redirect URL
3. Apple permite localhost sin HTTPS para desarrollo

### Producción

Para producción:

1. HTTPS es OBLIGATORIO
2. Dominio debe coincidir exactamente con el configurado
3. Return URL debe coincidir exactamente
4. Certificado SSL válido requerido

## Solución de Problemas

### Error: "invalid_client"

**Causa**: Client secret JWT inválido o expirado

**Solución**:
- Verificar que Team ID, Key ID y Private Key sean correctos
- Verificar que Private Key tenga formato correcto (con BEGIN/END)
- El paquete regenera JWT automáticamente

### Error: "redirect_uri_mismatch"

**Causa**: URL de callback no coincide con la configurada

**Solución**:
- Verificar URL exacta en Services ID configuration
- Incluir protocolo (http/https)
- Verificar que dominio coincida exactamente

### No recibo email en callback

**Causa**: Apple solo envía email en primera autenticación

**Solución**:
- Revocar app de Apple ID settings
- Volver a autenticar (será "primera vez" de nuevo)
- En producción: SIEMPRE guardar email en primera auth

### Error: "User already exists"

**Causa**: Email ya registrado con otro provider

**Solución**:
- Implementar lógica de vinculación de cuentas
- Permitir que usuario con email existente vincule Apple
- Mostrar mensaje apropiado al usuario

## Endpoints Finales

### API Endpoints

```
GET  /api/v1/auth/oauth/apple/redirect
     ?action=login|register
     &platform=web|mobile
     &device_id={uuid}
     &redirect_url={url}  (opcional, desarrollo)

GET  /api/v1/auth/oauth/apple/callback
     Automático, llamado por Apple
```

### Web Success Route

```
GET  /oauth/success
     ?token={sanctum_token}
     &customer_id={id}
     &is_new={0|1}
     &message={mensaje}
```

## Checklist Final

Antes de lanzar a producción:

- [ ] Team ID configurado correctamente
- [ ] Services ID creado y configurado
- [ ] Private Key descargado y guardado seguro
- [ ] Variables de entorno configuradas
- [ ] Migración ejecutada
- [ ] Provider registrado en bootstrap/app.php
- [ ] Controladores implementados
- [ ] Rutas agregadas
- [ ] Pruebas completadas (web y móvil)
- [ ] Dominio de producción agregado a Services ID
- [ ] HTTPS configurado en producción
- [ ] Return URL de producción configurada
- [ ] Manejo de errores implementado
- [ ] Logs de debug configurados

## Recursos Adicionales

### Documentación Oficial

- Apple Sign In: https://developer.apple.com/sign-in-with-apple/
- Human Interface Guidelines: https://developer.apple.com/design/human-interface-guidelines/sign-in-with-apple
- REST API: https://developer.apple.com/documentation/sign_in_with_apple/sign_in_with_apple_rest_api

### Paquete Laravel

- Socialite Apple: https://socialiteproviders.com/Apple/
- GitHub: https://github.com/SocialiteProviders/Apple

### Testing

- Revoke Apps: https://appleid.apple.com/account/manage (para testing)
- Apple Developer Forums: https://developer.apple.com/forums/tags/sign-in-with-apple
