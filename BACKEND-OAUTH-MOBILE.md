# OAuth de Google - Backend Laravel (Web + Mobile)
## Guía de Implementación Completa

> **✅ ESTADO: IMPLEMENTACIÓN COMPLETADA Y VALIDADA**
>
> Última actualización: 2025-01-18
> Versión: 4.0 - Guía Web + Mobile

---

## 📋 Resumen Ejecutivo

**Solución OAuth unificada que funciona para web y mobile:**

- ✅ Backend maneja TODO el flujo OAuth (más seguro)
- ✅ OAuth vía navegador (NO requiere Google SDK)
- ✅ OAuth 2.0 state parameter (estándar, sin sesión)
- ✅ Compatible con aplicaciones web (React/Vue/Inertia)
- ✅ Compatible con aplicaciones mobile (Expo React Native)
- ✅ Cliente tipo "Aplicación web" en Google Cloud Console
- ✅ Callback seguro (JSON para web, deep link para mobile)

---

## 🎯 Arquitectura

### Backend (Laravel)
- Socialite + Laravel Sanctum
- OAuth 2.0 Authorization Code Grant
- State parameter para mantener contexto (no usa sesión)
- Genera tokens Sanctum para autenticación API
- Gestiona vinculación de dispositivos (mobile)

### Frontend Web
- Redirige al endpoint de OAuth con `platform=web`
- Recibe respuesta JSON con token y datos del usuario
- Guarda token en almacenamiento local (localStorage, cookies, etc.)
- NO requiere Google SDK

### Frontend Mobile
- Abre OAuth en navegador del sistema
- Configura deep link para recibir callback
- Guarda token en almacenamiento persistente
- NO requiere Google SDK nativo

### Google Cloud Console
- Tipo de cliente: **"Aplicación web"** ✅
- 1 solo redirect URI necesario
- Backend hace todo el OAuth

---

## 🔧 Configuración Backend (Ya Implementado ✅)

### 1. Rutas API

```php
// routes/api.php

Route::middleware(['throttle:oauth'])->prefix('auth/oauth')->group(function () {
    // OAuth redirect flow (unified for web & mobile)
    // Only uses browser-based OAuth, no Google SDK required
    Route::middleware(['web'])->group(function () {
        Route::get('/google/redirect', [OAuthController::class, 'googleRedirect'])
            ->name('api.v1.auth.oauth.google.redirect');

        Route::get('/google/callback', [OAuthController::class, 'googleCallback'])
            ->name('api.v1.auth.oauth.google.callback');
    });
});
```

**Nota:** Este proyecto solo usa OAuth via navegador. No requiere Google SDK nativo.

### 2. Validaciones (Corregidas ✅)

```php
// GET /api/v1/auth/oauth/google/redirect
$validated = $request->validate([
    'action' => 'required|in:login,register',
    'platform' => 'required|in:web,mobile',
    'device_id' => 'required_if:platform,mobile|string|max:255', // ✅ Requerido para mobile
]);
```

### 3. Variables de Entorno

```env
# .env

# Production
APP_URL=https://admin.subwaycardgt.com

# Google OAuth
GOOGLE_CLIENT_ID=your-google-client-id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=your-google-client-secret
# GOOGLE_REDIRECT_URI=  # Optional: defaults to APP_URL/api/v1/auth/oauth/google/callback

# Mobile App Deep Link
MOBILE_APP_SCHEME=subwayapp
```

### 4. Google Cloud Console

**URIs Autorizados de JavaScript:**
```
https://admin.subwaycardgt.com
```

**URIs de Redireccionamiento Autorizados:**
```
https://admin.subwaycardgt.com/api/v1/auth/oauth/google/callback
```

⚠️ **Importante:** Este es el ÚNICO URI necesario en Google Cloud Console.

---

## 🔄 Flujo Completo OAuth

### Flujo Web (Inertia/React/Vue)

```
┌─────────────────────────────────────────────────────────────────┐
│ 1. Usuario hace clic en "Continuar con Google"                 │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│ 2. Frontend redirige a Backend                                 │
│    window.location.href = /api/v1/auth/oauth/google/redirect   │
│    ?action=login&platform=web                                  │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│ 3. Backend codifica parámetros en OAuth state                  │
│    state = base64({platform: "web", action, nonce})            │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│ 4. Backend redirige a Google con state                         │
│    https://accounts.google.com/o/oauth2/v2/auth?state=...      │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│ 5. Usuario autoriza en Google (pantalla de consentimiento)     │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│ 6. Google redirige a backend con code y state                  │
│    GET /api/v1/auth/oauth/google/callback?code=xxx&state=xxx   │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│ 7. Backend decodifica state y procesa autenticación            │
│    - Obtiene datos de Google                                   │
│    - Login: vincula cuenta existente                           │
│    - Register: crea cuenta nueva                               │
│    - Genera token Sanctum                                      │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│ 8. Backend guarda en sesión y redirige a /oauth/success        │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│ 9. Vista HTML oauth-success.blade.php se carga                 │
│    - JavaScript lee datos de la sesión (token, customer_id)    │
│    - Guarda token en localStorage automáticamente              │
│    - Emite evento 'oauth-success' para Livewire                │
│    - Redirige a /home después de 1 segundo                     │
└─────────────────────────────────────────────────────────────────┘
```

### Flujo Mobile (Expo React Native)

```
┌─────────────────────────────────────────────────────────────────┐
│ 1. Usuario toca "Continuar con Google" en la app               │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│ 2. App abre navegador → Backend                                │
│    WebBrowser.openAuthSessionAsync(...)                        │
│    GET /api/v1/auth/oauth/google/redirect                      │
│    ?action=login&platform=mobile&device_id=uuid                │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│ 3. Backend codifica parámetros en OAuth state                  │
│    state = base64({platform: "mobile", action, device_id})     │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│ 4. Backend redirige a Google con state                         │
│    https://accounts.google.com/o/oauth2/v2/auth?state=...      │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│ 5. Usuario autoriza en Google (pantalla de consentimiento)     │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│ 6. Google redirige a backend con code y state                  │
│    GET /api/v1/auth/oauth/google/callback?code=xxx&state=xxx   │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│ 7. Backend decodifica state y procesa autenticación            │
│    - Obtiene datos de Google                                   │
│    - Login: vincula cuenta existente                           │
│    - Register: crea cuenta nueva                               │
│    - Genera token Sanctum                                      │
│    - Vincula dispositivo con token (mobile)                    │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│ 8. Backend redirige a app con deep link (platform=mobile)      │
│    subwayapp://oauth/callback?token=xxx&customer_id=xxx        │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│ 9. App recibe callback, guarda token, navega a home            │
└─────────────────────────────────────────────────────────────────┘
```

---

## 🌐 Guía para Desarrolladores Web

### 🎯 Implementación Plataforma Web

#### 1. Iniciar Flujo OAuth

Para iniciar el proceso de autenticación con Google, redirige al usuario al endpoint del backend:

**URL de inicio:**
```
GET /api/v1/auth/oauth/google/redirect?action={login|register}&platform=web
```

**Parámetros:**
- `action`: `login` (cuenta existente) o `register` (crear cuenta nueva)
- `platform`: **Debe ser `web`**
- `device_id`: Opcional para web

**Ejemplo:**
```
https://admin.subwaycardgt.com/api/v1/auth/oauth/google/redirect?action=login&platform=web
```

#### 2. Respuesta del Backend

Después de que el usuario autorice en Google, el backend **redirigirá a una página HTML** (`/oauth/success`) que procesará el token automáticamente.

**⚠️ IMPORTANTE: El backend NO retorna JSON directamente. Retorna una vista HTML con JavaScript.**

**Flujo de respuesta (automático):**

1. Backend redirige a → `/oauth/success?token=xxx&customer_id=xxx&is_new=x&message=xxx`
2. Laravel renderiza la vista → `resources/views/auth/oauth-success.blade.php`
3. La vista recibe los datos de los query parameters
4. La vista contiene JavaScript que:
   - Lee los datos de las variables Blade (`@json($token)`, etc.)
   - Guarda `auth_token` en `localStorage`
   - Guarda `customer_id` en `localStorage`
   - Emite evento `oauth-success` (para Livewire/Alpine.js)
   - Redirige automáticamente a `/home` después de 1 segundo

**Datos pasados en la URL:**
- `token`: Token de acceso Sanctum
- `customer_id`: ID del cliente
- `is_new`: 1 si es cuenta nueva, 0 si ya existía
- `message`: Mensaje de éxito traducido

**Por qué usamos URL en lugar de sesión:**
La sesión se puede perder en redirects cross-origin (desde Google OAuth). Pasar datos en URL es más confiable y funciona igual que el flujo mobile.

**Todo esto sucede automáticamente - no necesitas hacer nada en el frontend, excepto iniciar el flujo.**

#### 3. Escuchar Evento OAuth (Opcional - Para Livewire)

Si usas Livewire, puedes escuchar el evento `oauth-success`:

```javascript
window.addEventListener('oauth-success', (event) => {
    const { token, customerId, isNewCustomer, message } = event.detail;

    // Actualizar estado de Livewire
    Livewire.emit('userAuthenticated', { token, customerId });

    // O hacer lo que necesites con los datos
    console.log('Usuario autenticado:', customerId);
});
```

#### 4. Usar Token en Peticiones API

El token ya está guardado en `localStorage` automáticamente. Para usarlo en peticiones API:

```
Authorization: Bearer {auth_token desde localStorage}
```

**Ejemplo de petición:**
```
GET /api/v1/profile
Headers:
  Accept: application/json
  Authorization: Bearer 12|SUisABC123xyz...
```

### ✅ Consideraciones Web

**✅ Hacer:**
- Redirigir completamente al endpoint OAuth con `window.location.href` (redirección de página completa)
- Usar `platform=web` en todos los casos
- El token se guarda automáticamente en localStorage (nada que hacer)
- La redirección a `/home` es automática (personalizar en la vista si necesitas)
- Incluir token en todas las peticiones autenticadas
- (Opcional) Escuchar evento `oauth-success` si usas Livewire/Alpine.js
- Personalizar la ruta de redirección en `resources/views/auth/oauth-success.blade.php` si necesitas

**❌ NO Hacer:**
- NO instalar o usar Google Sign-In SDK/JavaScript
- NO usar popups + postMessage (innecesario)
- NO hacer peticiones AJAX/fetch al endpoint OAuth
- NO exponer tokens en URLs públicas
- NO usar `platform=mobile` para aplicaciones web
- NO intentar parsear deep links
- NO cambiar el backend - ya funciona correctamente

**🎨 Personalización:**

Si necesitas cambiar la ruta de redirección después del OAuth, edita:
```
resources/views/auth/oauth-success.blade.php
```

Busca esta línea:
```javascript
window.location.href = '/home';
```

Y cámbiala por tu ruta preferida.

---

## 📱 Guía para Desarrolladores Mobile

### 🎯 Implementación Plataforma Mobile

#### 1. Configurar Deep Link

Tu aplicación mobile debe estar configurada para recibir deep links con el scheme:

```
subwayapp://
```

**⚠️ El scheme debe ser exactamente `subwayapp`**

Este deep link permite que el backend redirija a tu app después de completar el OAuth.

---

#### 2. Generar Device ID Único

Genera un identificador único para el dispositivo y guárdalo en almacenamiento persistente:

- **Formato recomendado:** UUID v4
- **Ejemplo:** `550e8400-e29b-41d4-a716-446655440000`
- **Persistencia:** Debe mantenerse entre sesiones
- **Uso:** Enviar en todas las peticiones OAuth

**⚠️ Este `device_id` es OBLIGATORIO para mobile**

---

#### 3. Iniciar Flujo OAuth

Abre el navegador del sistema con la URL del backend:

**URL de inicio:**
```
GET /api/v1/auth/oauth/google/redirect?action={login|register}&platform=mobile&device_id={uuid}
```

**Parámetros:**
- `action`: `login` (cuenta existente) o `register` (crear cuenta nueva)
- `platform`: **Debe ser `mobile`**
- `device_id`: **Requerido** - UUID del dispositivo

**Ejemplo:**
```
https://admin.subwaycardgt.com/api/v1/auth/oauth/google/redirect?action=login&platform=mobile&device_id=550e8400-e29b-41d4-a716-446655440000
```

**Configuración del navegador:**
- Especifica la URL de callback esperada: `subwayapp://oauth/callback`
- El navegador se cerrará automáticamente al recibir el callback

---

#### 4. Recibir Callback

Después de que el usuario autorice en Google, el backend redirigirá a tu app mediante deep link:

**Éxito:**
```
subwayapp://oauth/callback?token={access_token}&customer_id={id}&is_new_customer={0|1}
```

**Ejemplo:**
```
subwayapp://oauth/callback?token=12|SUisABC123xyz&customer_id=81&is_new_customer=0
```

**Parámetros del callback:**
- `token`: Token de acceso Sanctum para autenticación API
- `customer_id`: ID del cliente (para referencia)
- `is_new_customer`: `1` si es cuenta nueva, `0` si ya existía

**Error:**
```
subwayapp://oauth/callback?error={error_code}&message={error_message}
```

**Ejemplo:**
```
subwayapp://oauth/callback?error=user_not_found&message=No%20existe%20una%20cuenta
```

---

#### 5. Parsear Callback

Extrae los parámetros del deep link:

1. Obtén la URL del deep link recibido
2. Parsea los query parameters
3. Extrae `token`, `customer_id`, `is_new_customer`
4. Verifica si hay `error`

---

#### 6. Guardar Token

Guarda el token en almacenamiento persistente seguro:

- **Android:** SharedPreferences (modo privado) o EncryptedSharedPreferences
- **iOS:** Keychain
- **Persistencia:** Debe sobrevivir cierres de app

---

#### 7. Obtener Perfil Completo

**⚠️ Importante:** El callback solo envía `customer_id` (no el objeto completo) por seguridad.

Usa el token para obtener el perfil completo del usuario:

```
GET /api/v1/profile
Headers:
  Accept: application/json
  Authorization: Bearer {access_token}
```

**Respuesta:**
```json
{
  "data": {
    "id": 81,
    "first_name": "Juan",
    "last_name": "Pérez",
    "email": "juan@example.com",
    "phone": null,
    "avatar": "https://lh3.googleusercontent.com/a/...",
    "loyalty_points": 0,
    "customer_type": {
      "id": 1,
      "name": "Regular"
    }
  }
}
```

---

#### 8. Usar Token en Peticiones API

Para todas las peticiones autenticadas, incluye el token en el header `Authorization`:

```
Authorization: Bearer {access_token}
```

---

### ✅ Consideraciones Mobile

**✅ Hacer:**
- Abrir OAuth en navegador del sistema (NO WebView embebida)
- Generar y persistir `device_id` único
- Usar `platform=mobile` en todos los casos
- Incluir `device_id` en todas las peticiones OAuth
- Configurar deep link con scheme `subwayapp://`
- Manejar callback (éxito y errores)
- Obtener perfil completo con `GET /api/v1/profile` después del callback
- Guardar token de forma segura

**❌ NO Hacer:**
- NO instalar o usar Google Sign-In SDK nativo
- NO usar WebView embebida para OAuth
- NO exponer token en logs
- NO usar `platform=web` para apps mobile
- NO asumir que el callback contiene el perfil completo

---

## 📊 Endpoint de la API

### GET /api/v1/auth/oauth/google/redirect

**Descripción:** Inicia el flujo OAuth unificado para web y mobile

**Parámetros Query:**

| Parámetro | Tipo | Requerido | Valores | Descripción |
|-----------|------|-----------|---------|-------------|
| `action` | string | ✅ Sí | `login`, `register` | Tipo de acción |
| `platform` | string | ✅ Sí | `web`, `mobile` | Plataforma del cliente |
| `device_id` | string | ⚠️ Si platform=mobile | UUID | Identificador único del dispositivo (requerido solo para mobile) |

**Respuestas:**
- **302:** Redirige a Google OAuth
- **422:** Error de validación

**Callback (automático):**

Después de la autorización en Google, el backend:

- **Web (`platform=web`):** Redirige a `/oauth/success` (vista HTML que procesa el token automáticamente)
- **Mobile (`platform=mobile`):** Redirige a `subwayapp://oauth/callback?token=xxx&customer_id=xxx`

### Respuesta del Callback

#### Web (HTML View con JavaScript)

**⚠️ El backend NO retorna JSON para web. Retorna una redirección a `/oauth/success`**

El navegador carga `resources/views/auth/oauth-success.blade.php` que contiene:

```html
<!-- La vista tiene acceso a estas variables Blade: -->
@if($token)
    <script>
        const authData = {
            token: @json($token),              // "12|SUisABC123xyz..."
            customerId: @json($customerId),    // 81
            isNewCustomer: @json($isNewCustomer), // false
            message: @json($message)           // "Inicio de sesión exitoso"
        };

        // Automáticamente guarda en localStorage
        localStorage.setItem('auth_token', authData.token);
        localStorage.setItem('customer_id', authData.customerId);

        // Emite evento para Livewire
        window.dispatchEvent(new CustomEvent('oauth-success', { detail: authData }));

        // Redirige automáticamente
        setTimeout(() => {
            window.location.href = '/home';
        }, 1000);
    </script>
@endif
```

**El frontend web NO necesita parsear JSON - todo se maneja automáticamente.**

#### Mobile (Deep Link Redirect)

```
subwayapp://oauth/callback?token=12|SUisABC123xyz&customer_id=81&is_new_customer=0
```

**Nota:** Mobile solo recibe `customer_id` por seguridad. Usa `GET /api/v1/profile` para obtener datos completos.

---

## 📊 Resumen de URIs

| Contexto | URI | Notas |
|----------|-----|-------|
| **Google Cloud Console** | `https://admin.subwaycardgt.com/api/v1/auth/oauth/google/callback` | Único URI necesario |
| **App inicia OAuth** | `https://admin.subwaycardgt.com/api/v1/auth/oauth/google/redirect` | Con params: action, platform, device_id |
| **Deep Link Scheme** | `subwayapp://` | Configurar en app.json |
| **Callback a app** | `subwayapp://oauth/callback` | Backend redirige aquí con token |

---

## 🧪 Testing

### 1. Test Web desde Navegador

**Login (redirige a vista HTML):**
```
https://admin.subwaycardgt.com/api/v1/auth/oauth/google/redirect?action=login&platform=web
```

**Register (redirige a vista HTML):**
```
https://admin.subwaycardgt.com/api/v1/auth/oauth/google/redirect?action=register&platform=web
```

**Resultado esperado:**
1. Autorización en Google
2. Redirección a `/oauth/success`
3. Vista HTML con spinner de carga
4. Token guardado en localStorage automáticamente
5. Redirección automática a `/home`

**Verificar localStorage en DevTools:**
```javascript
localStorage.getItem('auth_token')  // debe tener el token Sanctum
localStorage.getItem('customer_id') // debe tener el ID del cliente
```

### 2. Test Mobile desde Navegador

**Login (redirige a deep link):**
```
https://admin.subwaycardgt.com/api/v1/auth/oauth/google/redirect?action=login&platform=mobile&device_id=550e8400-e29b-41d4-a716-446655440000
```

**Register (redirige a deep link):**
```
https://admin.subwaycardgt.com/api/v1/auth/oauth/google/redirect?action=register&platform=mobile&device_id=550e8400-e29b-41d4-a716-446655440000
```

**Resultado esperado:** Redirección a `subwayapp://oauth/callback?token=xxx&customer_id=xxx`

### 3. Test desde React Native

```typescript
const deviceId = await getOrCreateDeviceId();

const authUrl = `https://admin.subwaycardgt.com/api/v1/auth/oauth/google/redirect?action=login&platform=mobile&device_id=${deviceId}`;

const result = await WebBrowser.openAuthSessionAsync(
  authUrl,
  'subwayapp://oauth/callback'
);
```

### 4. Test desde Web App (JavaScript)

```javascript
// Test Login
window.location.href = '/api/v1/auth/oauth/google/redirect?action=login&platform=web';

// Test Register
window.location.href = '/api/v1/auth/oauth/google/redirect?action=register&platform=web';
```

### 5. Verificar Logs

```bash
tail -f storage/logs/laravel.log | grep "OAuth"
```

**Logs esperados (Web):**
```
[info] OAuth Redirect Initiated {"platform":"web","action":"login","device_id":"none"}
[info] OAuth Callback {"platform":"web","action":"login","email":"user@example.com"}
```

**Logs esperados (Mobile):**
```
[info] OAuth Redirect Initiated {"platform":"mobile","action":"login","device_id":"550e8400..."}
[info] OAuth Callback {"platform":"mobile","action":"login","email":"user@example.com"}
[info] Device synced with token {"customer_id":81,"device_id":"550e8400..."}
```

### 6. Test Page HTML

Existe una página de testing en:
```
https://admin.subwaycardgt.com/test-auth-redirect.html
```

Permite probar:
- Login con Google (Web) → Redirige a `/oauth/success` → guarda token → redirige a `/home`
- Login con Google (Mobile) → Redirige a app con deep link
- Registro con Google (Web) → Redirige a `/oauth/success` → guarda token → redirige a `/home`
- Registro con Google (Mobile) → Redirige a app con deep link

---

## 🐛 Troubleshooting

### Problema: Deep link no abre la app

**Síntoma:** El navegador no cierra después del callback

**Soluciones:**
- Verificar `app.json`: `"scheme": "subwayapp"`
- En Expo Go funciona automáticamente
- En standalone, rebuild con `eas build`

---

### Problema: Error "Invalid redirect_uri"

**Síntoma:** Google rechaza el callback

**Solución:** Verificar que el URI esté configurado en Google Cloud Console:
```
https://admin.subwaycardgt.com/api/v1/auth/oauth/google/callback
```

---

### Problema: device_id validation error

**Síntoma:** Error 422 "The device id field is required when platform is mobile"

**Solución:**
- Asegurar que `device_id` se está enviando en la URL
- Verificar que el device_id es un string válido (UUID recomendado)
- Para web, device_id es opcional

---

### Problema: Callback con error

**Síntoma:** App recibe `subwayapp://oauth/callback?error=user_not_found`

**Posibles causas:**
- `action=login` pero el usuario no existe → usar `action=register`
- `action=register` pero el usuario ya existe → usar `action=login`
- Email de Google no tiene cuenta en el sistema

**Solución:** Manejar los errores en la app y mostrar mensaje apropiado

---

## 📞 Soporte

### Contactar Backend si:
- El callback no llega a la app
- Reciben errores específicos del backend
- Necesitan cambiar el deep link scheme
- Tienen problemas con device_id

### NO es problema de Backend si:
- `expo-web-browser` no abre
- Deep link no funciona en la app
- AsyncStorage no guarda el token
- Problemas de navegación en la app

---

## ✅ Checklist de Implementación

### Backend: ✅ COMPLETADO

- [x] Endpoint unificado `/google/redirect` con parámetro platform
- [x] OAuth 2.0 state parameter (sin sesión)
- [x] Validaciones corregidas (device_id/device_identifier requeridos)
- [x] Métodos `googleRedirect()` y `googleCallback()` actualizados
- [x] Deep link callback a `subwayapp://oauth/callback`
- [x] Vinculación automática de dispositivos
- [x] Rate limiting configurado
- [x] Swagger documentation actualizada
- [x] Testing page HTML implementada

### Frontend Web: 📋 POR IMPLEMENTAR

- [ ] Redirigir a `/api/v1/auth/oauth/google/redirect?action=login&platform=web`
- [ ] Manejar respuesta JSON del backend
- [ ] Guardar token en almacenamiento persistente
- [ ] Implementar interceptor para agregar token a peticiones API
- [ ] Manejar errores de autenticación

### Frontend Mobile: 📋 POR IMPLEMENTAR

- [ ] Configurar deep link scheme `subwayapp://`
- [ ] Implementar generación de device_id único
- [ ] Abrir navegador del sistema para OAuth
- [ ] Configurar deep link listener
- [ ] Parsear query params del callback
- [ ] Guardar token en almacenamiento seguro
- [ ] Llamar a `/api/v1/profile` para obtener datos completos
- [ ] Manejar errores del callback

---

**Última actualización:** 2025-01-18
**Versión:** 4.0 - Guía Agnóstica Web + Mobile
**Autor:** Backend Team
