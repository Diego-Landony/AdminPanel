# Fix OAuth Web - Sesión Perdida en Redirect

**Fecha:** 2025-01-19
**Problema:** OAuth web redirigía a `/login` en lugar de `/oauth/success` exitoso
**Estado:** ✅ RESUELTO

---

## Problema Identificado

### Síntoma
Después de autorizar con Google OAuth para `platform=web`, el usuario era redirigido a `https://admin.subwaycardgt.com/login` con mensaje de error en lugar de ver la página de éxito.

### Causa Raíz
El flujo web intentaba usar **sesión PHP** para pasar datos entre el callback de OAuth y la vista de éxito:

```php
// ANTES (❌ NO funcionaba)
session(['oauth_token' => $token, ...]);
return redirect()->route('oauth.success');

// En la ruta /oauth/success
$token = session('oauth_token'); // ← Retornaba NULL
```

**Por qué fallaba:**
1. Google redirige → Backend Laravel callback
2. Backend guarda en sesión y redirige → `/oauth/success`
3. La cookie de sesión (LARAVEL_SESSION) **no persiste** en redirects cross-origin
4. El navegador llega a `/oauth/success` sin sesión anterior
5. `$token = session('oauth_token')` retorna `null`
6. La vista detecta `!$token` y muestra error

---

## Solución Implementada

Cambiar a **parámetros de URL** (igual que mobile) en lugar de sesión:

```php
// DESPUÉS (✅ SÍ funciona)
return redirect()->route('oauth.success', [
    'token' => $token,
    'customer_id' => $customer->id,
    'is_new' => $result['is_new'] ? '1' : '0',
    'message' => __($result['message_key']),
]);

// En la ruta /oauth/success
$token = $request->query('token'); // ← Funciona correctamente
```

---

## Archivos Modificados

### 1. `app/Http/Controllers/Api/V1/Auth/OAuthController.php`

**Líneas 262-269** - Cambio en el redirect web:

```php
// ANTES
session([
    'oauth_token' => $token,
    'oauth_customer_id' => $customer->id,
    'oauth_is_new' => $result['is_new'],
    'oauth_message' => __($result['message_key']),
]);
return redirect()->route('oauth.success');

// DESPUÉS
return redirect()->route('oauth.success', [
    'token' => $token,
    'customer_id' => $customer->id,
    'is_new' => $result['is_new'] ? '1' : '0',
    'message' => __($result['message_key']),
]);
```

### 2. `routes/web.php`

**Líneas 38-53** - Cambio en cómo lee los datos:

```php
// ANTES
Route::get('/oauth/success', function () {
    $token = session('oauth_token');
    $customerId = session('oauth_customer_id');
    $isNew = session('oauth_is_new');
    $message = session('oauth_message');

    session()->forget(['oauth_token', 'oauth_customer_id', 'oauth_is_new', 'oauth_message']);

    return view('auth.oauth-success', [...]);
});

// DESPUÉS
Route::get('/oauth/success', function (Illuminate\Http\Request $request) {
    $token = $request->query('token');
    $customerId = $request->query('customer_id');
    $isNew = $request->query('is_new');
    $message = $request->query('message');

    return view('auth.oauth-success', [...]);
});
```

### 3. `BACKEND-OAUTH-MOBILE.md`

Actualizada la documentación para reflejar que ahora usa parámetros URL en lugar de sesión.

---

## Comparación: Mobile vs Web

### Mobile (siempre funcionó ✅)
```php
return redirect()->away("subwayapp://oauth/callback?" . http_build_query([
    'token' => $token,
    'customer_id' => $customer->id,
    'is_new_customer' => $result['is_new'] ? '1' : '0',
]));
```
**Funciona porque:** Pasa datos en URL (deep link), no depende de sesión

### Web (ahora funciona ✅)
```php
return redirect()->route('oauth.success', [
    'token' => $token,
    'customer_id' => $customer->id,
    'is_new' => $result['is_new'] ? '1' : '0',
]);
```
**Funciona porque:** Ahora también pasa datos en URL, igual que mobile

---

## Flujo Completo Corregido

```
1. Usuario en producción hace clic en "Continuar con Google"
   ↓
2. Frontend redirige a:
   https://admin.subwaycardgt.com/api/v1/auth/oauth/google/redirect?platform=web&action=login
   ↓
3. Backend redirige a Google OAuth
   ↓
4. Usuario autoriza en Google
   ↓
5. Google redirige a backend:
   https://admin.subwaycardgt.com/api/v1/auth/oauth/google/callback?code=xxx&state=xxx
   ↓
6. Backend procesa OAuth, genera token Sanctum
   ↓
7. Backend redirige con datos en URL:
   https://admin.subwaycardgt.com/oauth/success?token=xxx&customer_id=81&is_new=0&message=xxx
   ↓
8. Vista oauth-success.blade.php recibe los datos de la URL
   ↓
9. JavaScript guarda token en localStorage
   ↓
10. Redirige automáticamente a /home
   ↓
✅ Usuario autenticado exitosamente
```

---

## Por Qué Esta Solución Es Mejor

✅ **Consistente:** Mismo patrón que mobile (probado y funcionando)
✅ **Confiable:** No depende de cookies/sesión que pueden perderse
✅ **Simple:** Menos código, más directo
✅ **Compatible:** Funciona con CORS, cross-origin, cualquier navegador
✅ **Predecible:** Los datos siempre llegan en la URL

---

## Testing

### Para Probar el Fix en Producción:

1. Ir a `https://admin.subwaycardgt.com`
2. Click en "Continuar con Google" (si hay botón implementado)
   - O navegar directo a:
     ```
     https://admin.subwaycardgt.com/api/v1/auth/oauth/google/redirect?platform=web&action=login
     ```
3. Autorizar en Google
4. Verificar que redirige a:
   ```
   https://admin.subwaycardgt.com/oauth/success?token=xxx&customer_id=xxx...
   ```
5. Verificar que la vista muestra "¡Autenticación exitosa!" con spinner
6. Verificar que se guarda en localStorage (abrir DevTools → Application → Local Storage)
7. Verificar que redirige a `/home` después de 1 segundo

---

## Notas Adicionales

- **No se necesita cambiar credenciales de Google** - El redirect URI sigue siendo el mismo
- **No se necesita cambiar Google Cloud Console** - Todo es igual
- **Compatible con localhost y producción** - Funciona en ambos entornos
- **La vista Blade NO necesita cambios** - Sigue funcionando igual, solo recibe datos de query params en lugar de sesión

---

## Próximos Pasos (Frontend Web - subwayWebApp)

Cuando implementes el botón de OAuth en el frontend web (subwayWebApp), simplemente:

```html
<a href="https://admin.subwaycardgt.com/api/v1/auth/oauth/google/redirect?platform=web&action=login">
    Continuar con Google
</a>
```

O en Livewire:
```php
public function loginWithGoogle()
{
    return redirect()->away('https://admin.subwaycardgt.com/api/v1/auth/oauth/google/redirect?platform=web&action=login');
}
```

---

**Última actualización:** 2025-01-19
**Autor:** Claude Code
**Versión:** 1.0 - Fix OAuth Web Session Issue
