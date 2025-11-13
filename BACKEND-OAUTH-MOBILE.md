# OAuth de Google - Backend Laravel + Expo React Native
## Guía de Implementación Completa

> **✅ ESTADO: IMPLEMENTACIÓN COMPLETADA Y VALIDADA**
>
> Última actualización: 2025-11-13
> Versión: 3.1 - Browser-only OAuth + Callback con customer_id

---

## 📋 Resumen Ejecutivo

**Solución OAuth unificada que funciona para web y mobile:**

- ✅ Backend maneja TODO el flujo OAuth (más seguro)
- ✅ Solo OAuth vía navegador (NO requiere Google SDK nativo)
- ✅ OAuth 2.0 state parameter (estándar, sin sesión)
- ✅ Funciona en Expo Go (sin builds nativos)
- ✅ Cliente tipo "Aplicación web" en Google Cloud Console
- ✅ Callback con customer_id (seguro, no expone datos en URLs)

---

## 🎯 Arquitectura

### Backend (Laravel)
- Socialite + Laravel Sanctum
- OAuth 2.0 Authorization Code Grant
- State parameter para mantener contexto (no usa sesión)
- Genera tokens Sanctum para autenticación API
- Gestiona vinculación de dispositivos

### Frontend (Expo React Native)
- `expo-web-browser` para abrir OAuth en navegador
- Deep link (`subwayapp://`) para recibir callback
- AsyncStorage para guardar tokens
- NO requiere Google SDK

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

## 🔄 Flujo Completo OAuth (Mobile)

```
┌─────────────────────────────────────────────────────────────────┐
│ 1. Usuario toca "Continuar con Google" en la app               │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│ 2. App abre navegador → Backend                                │
│    GET /api/v1/auth/oauth/google/redirect                      │
│    ?action=login&platform=mobile&device_id=uuid                │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│ 3. Backend codifica parámetros en OAuth state                  │
│    state = base64({platform, action, device_id, nonce})        │
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
│    - Vincula dispositivo con token                             │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│ 8. Backend redirige a app con deep link                        │
│    subwayapp://oauth/callback?token=xxx&customer_id=xxx        │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│ 9. App recibe callback, guarda token, navega a home            │
└─────────────────────────────────────────────────────────────────┘
```

---

## 📱 Guía para Desarrolladores de Expo App

### 🎯 Qué Hacer en la App

#### 1. Configurar Deep Link en `app.json`

```json
{
  "expo": {
    "scheme": "subwayapp",
    "name": "Subway Guatemala",
    "slug": "subway-guatemala"
  }
}
```

**⚠️ El scheme debe ser exactamente `subwayapp`**

---

#### 2. Generar Device ID Único

```typescript
// Generar UUID único cuando la app se instala
// Guardarlo en AsyncStorage
// Usarlo en TODAS las llamadas OAuth

const deviceId = '550e8400-e29b-41d4-a716-446655440000'; // UUID v4
```

**Este device_id es OBLIGATORIO para mobile**

---

#### 3. Implementar Login con Google

**Usar `expo-web-browser` (NO expo-auth-session):**

```typescript
import * as WebBrowser from 'expo-web-browser';

// 1. Obtener device_id del AsyncStorage
const deviceId = await getDeviceId();

// 2. Construir URL del backend
const authUrl = `https://admin.subwaycardgt.com/api/v1/auth/oauth/google/redirect?action=login&platform=mobile&device_id=${deviceId}`;

// 3. Abrir navegador
const result = await WebBrowser.openAuthSessionAsync(
  authUrl,
  'subwayapp://oauth/callback'
);

// 4. Manejar callback
if (result.type === 'success' && result.url) {
  const params = new URLSearchParams(result.url.split('?')[1]);
  const token = params.get('token');
  const customerId = params.get('customer_id');
  const isNewCustomer = params.get('is_new_customer');

  // 5. Guardar token
  await AsyncStorage.setItem('auth_token', token);
  await AsyncStorage.setItem('customer_id', customerId);

  // 6. Obtener perfil completo del usuario
  const profileResponse = await fetch('https://admin.subwaycardgt.com/api/v1/profile', {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json',
    },
  });
  const customer = await profileResponse.json();

  // 7. Guardar perfil y navegar
  await AsyncStorage.setItem('customer', JSON.stringify(customer));
  navigation.navigate('Home');
}
```

---

#### 4. Implementar Register con Google

**Exactamente igual al login, pero cambiar:**
```typescript
// Cambiar action=login → action=register
const authUrl = `https://admin.subwaycardgt.com/api/v1/auth/oauth/google/redirect?action=register&platform=mobile&device_id=${deviceId}`;
```

---

### ✅ Callback del Backend

**Éxito:**
```
subwayapp://oauth/callback
  ?token=12|SUisABC123xyz...
  &customer_id=81
  &is_new_customer=0
```

**⚠️ Nota de Seguridad:**
El callback solo envía `customer_id` (no el objeto completo) por:
- **Seguridad**: URLs se guardan en logs y historial del navegador
- **Tamaño**: Deep links largos pueden fallar en algunos dispositivos
- **Privacidad**: Evita exponer datos sensibles en URLs

**Usa el token para obtener el perfil completo** vía `GET /api/v1/profile`

**Error:**
```
subwayapp://oauth/callback
  ?error=user_not_found
  &message=No existe una cuenta con este correo
```

---

### ⚠️ Cosas Importantes

**✅ Hacer:**
- Usar `expo-web-browser` (NO `expo-auth-session`)
- Generar device_id único y guardarlo
- Pasar device_id en TODAS las llamadas OAuth
- Obtener perfil completo con `GET /api/v1/profile` después del callback
- Manejar tanto éxito como errores

**❌ NO Hacer:**
- NO instalar Google Sign-In SDK
- NO configurar nada en Google Cloud Console
- NO usar `expo-auth-session`
- NO intentar validar el token de Google en la app

---

## 📊 Endpoint de la API

### GET /api/v1/auth/oauth/google/redirect

**Descripción:** Inicia el flujo OAuth (web/mobile) via navegador

**Parámetros Query:**

| Parámetro | Tipo | Requerido | Valores | Descripción |
|-----------|------|-----------|---------|-------------|
| `action` | string | ✅ Sí | `login`, `register` | Tipo de acción |
| `platform` | string | ✅ Sí | `web`, `mobile` | Plataforma del cliente |
| `device_id` | string | ⚠️ Si platform=mobile | UUID | Identificador único del dispositivo |

**Respuestas:**
- **302:** Redirige a Google OAuth
- **422:** Error de validación

**Callback (automático):**

Después de la autorización en Google, el backend redirige:

- **Web:** Retorna JSON con token
- **Mobile:** Redirige a `subwayapp://oauth/callback?token=xxx&customer_id=xxx`

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

### 1. Test desde Navegador

**Login web (device_id opcional):**
```
https://admin.subwaycardgt.com/api/v1/auth/oauth/google/redirect?action=login&platform=web
```

**Login mobile (device_id REQUERIDO):**
```
https://admin.subwaycardgt.com/api/v1/auth/oauth/google/redirect?action=login&platform=mobile&device_id=550e8400-e29b-41d4-a716-446655440000
```

### 2. Test desde React Native

```typescript
const deviceId = await getOrCreateDeviceId();

const authUrl = `https://admin.subwaycardgt.com/api/v1/auth/oauth/google/redirect?action=login&platform=mobile&device_id=${deviceId}`;

const result = await WebBrowser.openAuthSessionAsync(
  authUrl,
  'subwayapp://oauth/callback'
);
```

### 3. Verificar Logs

```bash
tail -f storage/logs/laravel.log | grep "OAuth"
```

**Logs esperados:**
```
[info] OAuth Redirect Initiated {"platform":"mobile","action":"login","device_id":"550e8400..."}
[info] OAuth Callback Processing {"email":"user@example.com","platform":"mobile"}
[info] Device synced with token {"customer_id":81,"device_id":"550e8400..."}
```

### 4. Test Page HTML

Existe una página de testing en:
```
https://admin.subwaycardgt.com/test-auth-redirect.html
```

Permite probar:
- Login con Google (Web)
- Login con Google (Mobile)
- Registro con Google (Web)
- Registro con Google (Mobile)

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

### Frontend Mobile: 📋 PENDIENTE

- [ ] Configurar `scheme: "subwayapp"` en app.json
- [ ] Implementar generación de device_id único
- [ ] Implementar `WebBrowser.openAuthSessionAsync()`
- [ ] Configurar deep link listener
- [ ] Parsear query params del callback
- [ ] Guardar token en AsyncStorage
- [ ] Manejar errores del callback
- [ ] Testing en Expo Go
- [ ] Testing en standalone build

---

**Última actualización:** 2025-11-13
**Versión:** 3.0 - OAuth 2.0 State Parameter + Validaciones Corregidas
**Autor:** Backend Team
**Implementado por:** Claude Code
**Revisado por:** Claude Code con Context7
