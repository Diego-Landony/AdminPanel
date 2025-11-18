# Guía de Implementación OAuth Google para Frontend Web (Livewire)

> **Para desarrolladores del frontend web**
> Última actualización: 2025-01-18

---

## ⚠️ IMPORTANTE: El Backend YA está Completo

**NO necesitas que el backend haga cambios**. El sistema OAuth para web ya funciona correctamente.

El flujo actual:
1. Frontend redirige → Backend OAuth
2. Backend procesa → Google OAuth
3. Google redirige → Backend callback
4. Backend guarda en sesión → Redirige a `/oauth/success`
5. **Vista HTML automática** → Guarda token en localStorage → Redirige a `/home`

---

## 📋 Implementación en 3 Pasos

### Paso 1: Botón "Continuar con Google"

En tu componente Livewire o Blade, agrega un botón que redirija al usuario:

**Opción A: HTML simple**
```html
<a href="/api/v1/auth/oauth/google/redirect?platform=web&action=login"
   class="btn btn-primary">
    Continuar con Google
</a>
```

**Opción B: JavaScript**
```html
<button onclick="loginWithGoogle()">
    Continuar con Google
</button>

<script>
function loginWithGoogle() {
    // Para login (usuario existente)
    window.location.href = '/api/v1/auth/oauth/google/redirect?platform=web&action=login';

    // Para registro (nuevo usuario)
    // window.location.href = '/api/v1/auth/oauth/google/redirect?platform=web&action=register';
}
</script>
```

**Opción C: Livewire**
```blade
<button wire:click="loginWithGoogle">
    Continuar con Google
</button>
```

```php
// En tu componente Livewire
public function loginWithGoogle()
{
    return redirect('/api/v1/auth/oauth/google/redirect?platform=web&action=login');
}
```

### Paso 2: Todo lo Demás es Automático

Después de hacer clic en "Continuar con Google":

1. ✅ Usuario autoriza en Google (automático)
2. ✅ Backend procesa la autenticación (automático)
3. ✅ Vista HTML se carga con spinner (automático)
4. ✅ Token se guarda en `localStorage` (automático)
5. ✅ Usuario es redirigido a `/home` (automático)

**NO necesitas escribir código para manejar el callback.**

### Paso 3 (Opcional): Escuchar el Evento OAuth

Si quieres hacer algo especial cuando el OAuth se complete exitosamente, puedes escuchar el evento:

```javascript
// En tu layout principal o app.js
window.addEventListener('oauth-success', (event) => {
    const { token, customerId, isNewCustomer, message } = event.detail;

    console.log('Usuario autenticado:', customerId);
    console.log('Token:', token);
    console.log('¿Es nuevo?:', isNewCustomer);

    // Si usas Livewire, puedes actualizar el estado:
    @this.call('handleOAuthSuccess', { customerId, isNewCustomer });

    // O simplemente recargar:
    // window.location.reload();
});
```

---

## 🔍 Personalización

### Cambiar la Ruta de Redirección

Si quieres que redirija a una página diferente después del OAuth (en lugar de `/home`):

1. Edita: `resources/views/auth/oauth-success.blade.php`
2. Busca la línea 95:
```javascript
window.location.href = '/home';
```
3. Cámbiala por tu ruta:
```javascript
window.location.href = '/dashboard'; // o la ruta que necesites
```

### Cambiar el Diseño de la Página de Éxito

Puedes personalizar completamente `resources/views/auth/oauth-success.blade.php`:
- Cambiar el spinner
- Agregar tu logo
- Cambiar colores
- Agregar mensajes personalizados

---

## 🔐 Usar el Token en Peticiones API

El token ya está guardado en `localStorage`. Para usarlo en peticiones API:

**Opción A: Axios (Recomendado)**
```javascript
// Configurar axios para incluir el token automáticamente
axios.defaults.headers.common['Authorization'] =
    'Bearer ' + localStorage.getItem('auth_token');

// Luego hacer peticiones normalmente
axios.get('/api/v1/profile')
    .then(response => {
        console.log('Perfil:', response.data);
    });
```

**Opción B: Fetch**
```javascript
fetch('/api/v1/profile', {
    headers: {
        'Authorization': 'Bearer ' + localStorage.getItem('auth_token'),
        'Accept': 'application/json'
    }
})
.then(res => res.json())
.then(data => console.log('Perfil:', data));
```

**Opción C: Livewire con Alpine.js**
```blade
<div x-data="{ token: localStorage.getItem('auth_token') }">
    <span x-text="token ? 'Autenticado' : 'No autenticado'"></span>
</div>
```

---

## ❌ Lo que NO Debes Hacer

**NO implementes:**
- ❌ Popup + postMessage
- ❌ Google Sign-In SDK
- ❌ Peticiones AJAX al endpoint OAuth
- ❌ Parseo de deep links
- ❌ Cambios en el backend

**El sistema ya funciona. Solo necesitas redirigir al usuario.**

---

## 🐛 Resolución de Problemas

### Problema: "No pasa nada después de autorizar en Google"

**Causa:** Probablemente estás siendo redirigido a `/oauth/success` pero hay un error en la vista.

**Solución:** Verifica los logs de Laravel:
```bash
tail -f storage/logs/laravel.log
```

### Problema: "El token no se guarda en localStorage"

**Causa:** JavaScript puede estar bloqueado o hay un error en la vista.

**Solución:**
1. Abre DevTools → Console
2. Busca errores de JavaScript
3. Verifica que `resources/views/auth/oauth-success.blade.php` existe y está correcto

### Problema: "Error 422 al iniciar OAuth"

**Causa:** Parámetros incorrectos en la URL.

**Solución:** Asegúrate de incluir `platform=web` y `action=login` o `action=register`

Correcto:
```
/api/v1/auth/oauth/google/redirect?platform=web&action=login
```

Incorrecto:
```
/api/v1/auth/oauth/google/redirect  ❌ (faltan parámetros)
```

### Problema: "No me redirige a /home después del OAuth"

**Causa:** El JavaScript de la vista tiene un error o el timeout no se está ejecutando.

**Solución:** Edita `resources/views/auth/oauth-success.blade.php` y cambia:
```javascript
setTimeout(() => {
    window.location.href = '/home';
}, 1000);
```

Por una redirección inmediata:
```javascript
window.location.href = '/home';
```

---

## 📊 Flujo Completo (Para Referencia)

```
Usuario hace clic en "Continuar con Google"
    ↓
Frontend: window.location.href = '/api/v1/auth/oauth/google/redirect?platform=web&action=login'
    ↓
Backend: Redirige a Google OAuth
    ↓
Google: Usuario autoriza
    ↓
Google: Redirige a /api/v1/auth/oauth/google/callback con code y state
    ↓
Backend:
    - Obtiene datos de Google
    - Autentica/crea usuario
    - Genera token Sanctum
    - Guarda en sesión: token, customer_id, is_new, message
    - Redirige a /oauth/success
    ↓
Laravel: Renderiza resources/views/auth/oauth-success.blade.php
    ↓
Vista HTML (automático):
    - Muestra spinner de carga
    - JavaScript lee datos de variables Blade
    - Guarda token en localStorage
    - Guarda customer_id en localStorage
    - Emite evento 'oauth-success'
    - Espera 1 segundo
    - Redirige a /home
    ↓
Usuario en /home (autenticado)
```

---

## 📞 Contacto

Si tienes problemas:
1. Revisa los logs de Laravel: `storage/logs/laravel.log`
2. Revisa la consola del navegador (DevTools)
3. Verifica que la vista `resources/views/auth/oauth-success.blade.php` existe
4. Verifica que `/oauth/success` está en `routes/web.php` (línea 38)

**NO necesitas cambios en el backend - todo ya está implementado.**

---

## ✅ Checklist

- [ ] Agregué botón "Continuar con Google" que redirige a `/api/v1/auth/oauth/google/redirect?platform=web&action=login`
- [ ] Verifiqué que la vista `resources/views/auth/oauth-success.blade.php` existe
- [ ] (Opcional) Agregué listener para evento `oauth-success`
- [ ] (Opcional) Personalicé la ruta de redirección en la vista
- [ ] Probé el flujo completo y verifiqué que el token se guarda en localStorage

---

**Versión:** 1.0
**Última actualización:** 2025-01-18
