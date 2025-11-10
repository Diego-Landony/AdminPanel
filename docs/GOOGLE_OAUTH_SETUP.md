# Configuración de Google OAuth para SubwayApp

Este documento explica cómo configurar Google Cloud Console para soportar autenticación OAuth en:
- **Mobile App (React Native)** - usando id_token directo
- **Web App** - usando flujo OAuth redirect tradicional

## Arquitectura

```
Mobile App (React Native) ──id_token──┐
                                       ├──> Laravel Backend API
Web App (Frontend separado) ─redirect─┘
```

## Requisitos

1. Cuenta de Google Cloud Platform
2. Proyecto creado en Google Cloud Console
3. API de Google+ habilitada

## ¿Necesito Firebase?

**NO** - Firebase **NO es necesario** para esta implementación.

Esta arquitectura utiliza **Laravel** para todo el backend de autenticación:

- **Laravel Sanctum** maneja los tokens de autenticación
- **Laravel Socialite** valida los tokens OAuth de Google
- **Base de datos Laravel** almacena los usuarios

Firebase sería **completamente redundante** porque duplicaría toda esta funcionalidad.

**Nota**: Si ya usas Firebase para otras funcionalidades (notificaciones push, analytics, etc.), puedes seguir usándolo, pero no es necesario para la autenticación OAuth.

## Configuración en Google Cloud Console

### 1. Client ID Web (Para Backend Laravel)

Este Client ID se usa para:
- Validar id_tokens enviados desde mobile apps
- Flujo OAuth redirect para web apps

**Pasos:**
1. Ve a [Google Cloud Console](https://console.cloud.google.com/)
2. Selecciona tu proyecto
3. Ve a **APIs & Services > Credentials**
4. Clic en **+ CREATE CREDENTIALS > OAuth 2.0 Client ID**
5. Selecciona **Tipo de aplicación: Web application**
6. Configuración:
   ```
   Nombre: SubwayApp - Backend Server

   Orígenes JavaScript autorizados:
   - https://admin.subwaycardgt.com (producción)
   - http://localhost:8000 (desarrollo)

   URIs de redireccionamiento autorizados:
   - https://admin.subwaycardgt.com/api/v1/auth/oauth/google/callback
   - http://localhost:8000/api/v1/auth/oauth/google/callback
   ```
7. Guarda y copia el **Client ID** y **Client Secret**

### 2. Client ID Android (Para App React Native en Android)

**Pasos:**
1. En la misma página de Credentials
2. Clic en **+ CREATE CREDENTIALS > OAuth 2.0 Client ID**
3. Selecciona **Tipo de aplicación: Android**
4. Configuración:
   ```
   Nombre: SubwayApp - Android

   Nombre del paquete: com.subwaycardgt.app
   (o el package name que uses en tu app.json)

   Huella digital del certificado SHA-1:
   ```

**Obtener SHA-1 para desarrollo:**
```bash
cd android
./gradlew signingReport
```
Busca la línea que dice `SHA1:` bajo `Variant: debug`

**Obtener SHA-1 para producción:**
```bash
keytool -list -v -keystore /path/to/your-release-key.keystore -alias your-key-alias
```

### 3. Client ID iOS (Para App React Native en iOS)

**Pasos:**
1. En la misma página de Credentials
2. Clic en **+ CREATE CREDENTIALS > OAuth 2.0 Client ID**
3. Selecciona **Tipo de aplicación: iOS**
4. Configuración:
   ```
   Nombre: SubwayApp - iOS

   ID del paquete: com.subwaycardgt.app
   (o el bundle ID que uses en tu app.json/Info.plist)
   ```

## Configuración en Laravel (.env)

Agrega estas variables a tu archivo `.env`:

```bash
# Google OAuth Configuration
GOOGLE_CLIENT_ID=tu-web-client-id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=tu-client-secret
GOOGLE_REDIRECT_URI=https://admin.subwaycardgt.com/api/v1/auth/oauth/google/callback
```

**Notas importantes:**
- `GOOGLE_CLIENT_ID` debe ser el **Web Client ID** (no Android o iOS)
- Los mobile apps enviarán el id_token directamente, no necesitan el client secret
- `GOOGLE_REDIRECT_URI` solo se usa para el flujo web OAuth

## Endpoints Disponibles

### Mobile App (React Native)

**1. Registro con Google**
```http
POST /api/v1/auth/oauth/google/register
Content-Type: application/json

{
  "id_token": "eyJhbGciOiJSUzI1NiIsImtpZCI6IjhlY...",
  "os": "android"
}
```
- **Crea cuenta nueva** si el email no existe
- Retorna error si el email ya existe

**2. Login con Google**
```http
POST /api/v1/auth/oauth/google
Content-Type: application/json

{
  "id_token": "eyJhbGciOiJSUzI1NiIsImtpZCI6IjhlY...",
  "os": "ios"
}
```
- **NO crea cuentas**, solo autentica
- Retorna error si el email no existe

### Web App

**1. Iniciar flujo OAuth**
```http
GET /api/v1/auth/oauth/google/redirect
```
Redirige al usuario a Google para autorización.

**2. Callback de Google**
```http
GET /api/v1/auth/oauth/google/callback?code=...&state=...
```
Google redirige aquí después de autorización. Retorna el token Sanctum.

## Flujo de Autenticación

### Mobile (React Native)

```javascript
// En tu componente React Native
import { GoogleSignin } from '@react-native-google-signin/google-signin';

// 1. Configurar Google Sign-In (solo una vez al iniciar la app)
GoogleSignin.configure({
  webClientId: 'tu-web-client-id.apps.googleusercontent.com', // Web Client ID (REQUERIDO)
  offlineAccess: false, // false porque usamos id_token directo, no server auth code
});

// IMPORTANTE: Firebase NO es necesario para esta implementación
// Laravel maneja toda la autenticación backend

// SOLO si ya usas Firebase para otras cosas (no para auth), puedes usar:
// GoogleSignin.configure({
//   webClientId: 'autoDetect', // Detecta automáticamente desde GoogleService-Info.plist/google-services.json
// });

// 2. Para REGISTRO
async function registerWithGoogle() {
  try {
    await GoogleSignin.hasPlayServices();
    const userInfo = await GoogleSignin.signIn();

    const response = await fetch('https://admin.subwaycardgt.com/api/v1/auth/oauth/google/register', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        id_token: userInfo.idToken,
        os: Platform.OS // 'ios' o 'android'
      })
    });

    const data = await response.json();

    if (response.ok) {
      // Guardar token y navegar
      await AsyncStorage.setItem('token', data.data.access_token);
      navigation.navigate('Home');
    } else {
      // Mostrar error (ej: email ya existe)
      Alert.alert('Error', data.message);
    }
  } catch (error) {
    console.error(error);
  }
}

// 3. Para LOGIN
async function loginWithGoogle() {
  try {
    await GoogleSignin.hasPlayServices();
    const userInfo = await GoogleSignin.signIn();

    const response = await fetch('https://admin.subwaycardgt.com/api/v1/auth/oauth/google', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        id_token: userInfo.idToken,
        os: Platform.OS
      })
    });

    const data = await response.json();

    if (response.ok) {
      await AsyncStorage.setItem('token', data.data.access_token);
      navigation.navigate('Home');
    } else {
      // Mostrar error (ej: cuenta no existe, usar registro)
      Alert.alert('Cuenta no encontrada', 'Por favor regístrate primero');
    }
  } catch (error) {
    console.error(error);
  }
}
```

### Web App

```javascript
// Redirigir a Google OAuth
window.location.href = 'https://admin.subwaycardgt.com/api/v1/auth/oauth/google/redirect';

// Manejar callback (en la página de callback)
const urlParams = new URLSearchParams(window.location.search);
const code = urlParams.get('code');

// El backend ya procesó el código y retornó el token
// Necesitarás ajustar el callback para que redirija a tu frontend con el token
```

## Diferencias Importantes

| Característica | Login | Registro |
|---------------|-------|----------|
| Endpoint | `/api/v1/auth/oauth/google` | `/api/v1/auth/oauth/google/register` |
| Crea cuenta nueva | ❌ NO | ✅ SI |
| Email no existe | Error 422 | Crea cuenta |
| Email ya existe | Login exitoso | Error 422 |
| Código HTTP éxito | 200 | 201 |

## Seguridad

### ¿Por qué usar el Web Client ID en mobile?

El id_token enviado desde el mobile app contiene el audience (`aud` claim) que debe coincidir con el Web Client ID. Esto es lo que Laravel Socialite verifica al llamar `userFromToken()`.

### ¿Los Client IDs de Android/iOS son necesarios?

**SÍ**, son necesarios para que:
1. Google Sign-In SDK funcione en las apps nativas
2. El usuario pueda ver la pantalla de consentimiento de Google
3. Se genere el id_token correctamente

Pero el backend **solo necesita** el Web Client ID para validar los tokens.

## Troubleshooting

### Error: "Token de Google inválido o expirado"

**Causas posibles:**
1. El `webClientId` en la configuración de React Native no coincide con el Web Client ID de Google Console
2. El id_token expiró (válido por 1 hora)
3. El Client ID no está correctamente configurado en Google Console

**Solución:**
```javascript
// Verificar que usas el Web Client ID correcto
GoogleSignin.configure({
  webClientId: 'XXXXXXX.apps.googleusercontent.com', // Este debe ser el WEB Client ID
});
```

### Error: "No existe una cuenta con este correo electrónico"

Esto significa que el usuario intentó hacer LOGIN pero no tiene cuenta registrada.

**Solución para el usuario:**
Usar el endpoint de REGISTRO primero: `/api/v1/auth/oauth/google/register`

### Error: "Ya existe una cuenta con este correo electrónico"

El usuario intentó REGISTRARSE pero ya tiene una cuenta.

**Solución para el usuario:**
Usar el endpoint de LOGIN: `/api/v1/auth/oauth/google`

## Validación de Documentación

Esta documentación ha sido **validada contra las fuentes oficiales** usando Context7:

### ✅ Confirmaciones

- **@react-native-google-signin/google-signin (v12.x)**: Configuración y uso de `webClientId` confirmados
- **Laravel Socialite (v5.x)**: Métodos `stateless()` y `userFromToken()` validados
- **Google API PHP Client**: Flujos OAuth 2.0 verificados

### Características Adicionales (Opcional)

- **Firebase autoDetect**: Si YA usas Firebase para otras funcionalidades (no para auth), puedes configurar `webClientId: 'autoDetect'` en lugar del Client ID explícito. **Recordatorio: Firebase NO es necesario para esta arquitectura.**
- **hasPlayServices()**: Siempre llama a `GoogleSignin.hasPlayServices()` antes de sign-in para verificar disponibilidad
- **Expo Config Plugin**: Si usas Expo, el plugin `@react-native-google-signin/google-signin` automatiza la configuración nativa

### Notas de Versión

- React Native Google Sign-In: Compatible con New Architecture
- Laravel Socialite: v5.x incluye soporte para Laravel 11 y PHP 8.2
- Google Client Library: v2.x con OAuth 2.0 actualizado

## Referencias

- [Google Sign-In for React Native](https://github.com/react-native-google-signin/google-signin)
- [Laravel Socialite Documentation](https://laravel.com/docs/11.x/socialite)
- [Google OAuth 2.0 Documentation](https://developers.google.com/identity/protocols/oauth2)
- [Google API PHP Client](https://github.com/googleapis/google-api-php-client)
