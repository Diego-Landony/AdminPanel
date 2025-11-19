# OAuth desde Localhost → Backend Producción

## Problema

Cuando desarrollas en **localhost** pero usas el **backend de producción**, el OAuth no puede redirigir de vuelta a localhost porque Google solo conoce el callback de producción.

## Solución Implementada

El backend ahora acepta un parámetro `redirect_url` que permite especificar dónde redirigir después del OAuth exitoso.

---

## Cómo Usar

### 1. Crear Página de Callback en Localhost

En tu frontend Livewire/Inertia (localhost), necesitas crear una ruta que reciba el callback:

**Opción A: Ruta Web Simple**

```php
// routes/web.php (en tu proyecto local)
Route::get('/oauth/local-callback', function () {
    $token = request('token');
    $customerId = request('customer_id');
    $isNew = request('is_new_customer');
    $message = request('message');

    return view('auth.local-oauth-callback', [
        'token' => $token,
        'customerId' => $customerId,
        'isNewCustomer' => $isNew,
        'message' => $message,
    ]);
});
```

**Vista: `resources/views/auth/local-oauth-callback.blade.php`**

```html
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Autenticación exitosa</title>
</head>
<body>
    <h1>¡Autenticación exitosa!</h1>
    <p>Guardando token y redirigiendo...</p>

    <script>
        const authData = {
            token: @json($token),
            customerId: @json($customerId),
            isNewCustomer: @json($isNewCustomer),
            message: @json($message)
        };

        // Guardar en localStorage
        localStorage.setItem('auth_token', authData.token);
        localStorage.setItem('customer_id', authData.customerId);

        // Emitir evento para Livewire
        window.dispatchEvent(new CustomEvent('oauth-success', {
            detail: authData
        }));

        // Redirigir a home
        setTimeout(() => {
            window.location.href = '/home';
        }, 1000);
    </script>
</body>
</html>
```

### 2. Iniciar OAuth con redirect_url

Cuando redirijas al OAuth de producción, incluye tu URL local:

```javascript
// En tu frontend local (localhost)
const localCallbackUrl = 'http://localhost:8000/oauth/local-callback';

window.location.href =
  `https://admin.subwaycardgt.com/api/v1/auth/oauth/google/redirect?` +
  `platform=web&` +
  `action=login&` +
  `redirect_url=${encodeURIComponent(localCallbackUrl)}`;
```

O en HTML:

```html
<a href="https://admin.subwaycardgt.com/api/v1/auth/oauth/google/redirect?platform=web&action=login&redirect_url=http://localhost:8000/oauth/local-callback">
    Continuar con Google
</a>
```

---

## Flujo Completo

```
1. Usuario en localhost hace clic en "Continuar con Google"
   ↓
2. Frontend redirige a:
   https://admin.subwaycardgt.com/api/v1/auth/oauth/google/redirect?
     platform=web&
     action=login&
     redirect_url=http://localhost:8000/oauth/local-callback
   ↓
3. Backend de producción guarda redirect_url en el state de OAuth
   ↓
4. Backend redirige a Google OAuth
   ↓
5. Usuario autoriza en Google
   ↓
6. Google redirige a backend de producción:
   https://admin.subwaycardgt.com/api/v1/auth/oauth/google/callback
   ↓
7. Backend procesa autenticación, genera token
   ↓
8. Backend detecta que hay redirect_url en el state
   ↓
9. Backend redirige a:
   http://localhost:8000/oauth/local-callback?
     token=xxx&
     customer_id=xxx&
     is_new_customer=0&
     message=xxx
   ↓
10. Tu página local recibe los datos, guarda el token, redirige a /home
```

---

## Seguridad

**IMPORTANTE:** Solo usa esto en desarrollo. En producción, NO uses `redirect_url` externas.

El backend valida que `redirect_url` sea una URL válida con el validador `url` de Laravel, pero para mayor seguridad podrías:

```php
// En producción, validar que redirect_url sea del mismo dominio
$validated = $request->validate([
    'redirect_url' => ['nullable', 'url', 'starts_with:http://localhost,https://localhost'],
]);
```

---

## Alternativas

Si no quieres hacer cambios en el backend, las únicas opciones son:

1. **Desarrollar todo localmente** - Correr backend y frontend en localhost
2. **Probar OAuth solo en producción** - No intentar OAuth desde localhost

---

## Ejemplo Completo (Livewire)

```blade
{{-- resources/views/livewire/login.blade.php --}}
<div>
    <button wire:click="loginWithGoogle">
        Continuar con Google
    </button>
</div>
```

```php
<?php
// app/Http/Livewire/Login.php

namespace App\Http\Livewire;

use Livewire\Component;

class Login extends Component
{
    public function loginWithGoogle()
    {
        $localCallback = url('/oauth/local-callback');
        $productionBackend = 'https://admin.subwaycardgt.com/api/v1/auth/oauth/google/redirect';

        $url = $productionBackend . '?' . http_build_query([
            'platform' => 'web',
            'action' => 'login',
            'redirect_url' => $localCallback,
        ]);

        return redirect()->away($url);
    }

    public function render()
    {
        return view('livewire.login');
    }
}
```

---

**Última actualización:** 2025-01-18
