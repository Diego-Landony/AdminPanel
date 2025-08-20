# 🔔 Sistema Global de Notificaciones

## 📋 Índice
- [Descripción General](#descripción-general)
- [Arquitectura del Sistema](#arquitectura-del-sistema)
- [Hook useNotifications](#hook-usenotifications)
- [Middleware HandleValidationErrors](#middleware-handlevalidationerrors)
- [Implementación en Páginas](#implementación-en-páginas)
- [Mensajes Personalizados](#mensajes-personalizados)
- [Ejemplos de Uso](#ejemplos-de-uso)
- [Configuración](#configuración)

---

## 🎯 Descripción General

El **Sistema Global de Notificaciones** proporciona una experiencia de usuario unificada para mostrar mensajes de feedback, errores de validación, estados de éxito y cualquier tipo de notificación en toda la aplicación usando **Sonner** como librería de toasts.

### **✅ Características Principales:**
- **Automático**: Manejo automático de mensajes flash del servidor
- **Traducido**: Errores de validación traducidos al español
- **Consistente**: Estilos y posiciones uniformes en toda la app
- **Inteligente**: Detecta tipos de mensaje automáticamente
- **Extensible**: Fácil de personalizar y extender

---

## 🏗️ Arquitectura del Sistema

### **1. 🎣 Hook Principal: useNotifications**
```typescript
// resources/js/hooks/useNotifications.ts
export function useNotifications() {
    // Manejo automático de mensajes flash
    // Traducción de errores de validación
    // Funciones de utilidad para notificaciones
}
```

### **2. 🛡️ Middleware: HandleValidationErrors**
```php
// app/Http/Middleware/HandleValidationErrors.php
class HandleValidationErrors {
    // Intercepta errores de validación
    // Traduce mensajes automáticamente
    // Convierte a mensajes flash amigables
}
```

### **3. 🔧 Configuración Global**
```php
// bootstrap/app.php
$middleware->web(append: [
    HandleValidationErrors::class, // Manejo global de errores
    HandleInertiaRequests::class,
]);
```

---

## 🎣 Hook useNotifications

### **Importación y Uso Básico:**
```typescript
import { useNotifications, useFormNotifications } from '@/hooks/useNotifications';

// En tu componente
export default function MyComponent() {
    const { notify } = useNotifications();
    // ó
    const { showFormSuccess, showFormError } = useFormNotifications();
}
```

### **Tipos de Notificaciones Disponibles:**

#### **1. ✅ Notificación de Éxito**
```typescript
notify.success('Operación exitosa', 'Los datos se guardaron correctamente');
```

#### **2. ❌ Notificación de Error**
```typescript
notify.error('Error en la operación', 'No se pudo guardar los datos');
```

#### **3. ⚠️ Notificación de Advertencia**
```typescript
notify.warning('Advertencia', 'Algunos campos están incompletos');
```

#### **4. ℹ️ Notificación Informativa**
```typescript
notify.info('Información', 'El proceso tardará unos minutos');
```

#### **5. ⏳ Notificación de Carga**
```typescript
const loadingToast = notify.loading('Guardando...');
// Luego dismissar:
toast.dismiss(loadingToast);
```

### **Funciones Especializadas para Formularios:**

#### **1. 📝 Éxito de Formulario**
```typescript
showFormSuccess('crear usuario'); // → "Crear usuario exitoso"
```

#### **2. 🚫 Error de Formulario**
```typescript
showFormError('actualizar perfil'); // → "Error al actualizar perfil"
```

#### **3. 🔍 Error de Validación**
```typescript
showValidationError('email', 'El formato no es válido');
```

---

## 🛡️ Middleware HandleValidationErrors

### **Traducciones Automáticas de Errores:**

El middleware intercepta errores de validación y los traduce automáticamente:

#### **Errores Comunes Traducidos:**
```php
'validation.required' → "El campo {nombre} es obligatorio."
'validation.email' → "El {correo electrónico} debe ser una dirección válida."
'validation.unique' → "Este {correo electrónico} ya está en uso."
'validation.min.string' → "El {nombre} debe tener al menos :min caracteres."
'validation.confirmed' → "La confirmación de {contraseña} no coincide."
```

#### **Campos Traducidos:**
```php
'name' → 'nombre'
'email' → 'correo electrónico'
'password' → 'contraseña'
'password_confirmation' → 'confirmación de contraseña'
'roles' → 'roles'
'permissions' → 'permisos'
```

### **Mensajes Flash Automáticos:**

El middleware convierte errores de validación en mensajes flash:
- `flash.error` → Primera validación fallida
- `flash.info` → Resumen si hay múltiples errores

---

## 🎯 Implementación en Páginas

### **1. 🔐 Páginas de Autenticación**

#### **Login (resources/js/pages/auth/login.tsx):**
```typescript
export default function Login() {
    const { notify } = useNotifications();

    const submit = (e) => {
        post(route('login'), {
            onSuccess: () => notify.success('Inicio de sesión exitoso', 'Bienvenido de vuelta'),
            onError: () => notify.error('Error de inicio de sesión', 'Verifica tus credenciales')
        });
    };
}
```

#### **Register (resources/js/pages/auth/register.tsx):**
```typescript
export default function Register() {
    const { notify } = useNotifications();

    const submit = (e) => {
        post(route('register'), {
            onSuccess: () => notify.success('Registro exitoso', 'Tu cuenta ha sido creada'),
            onError: () => notify.error('Error en el registro', 'Verifica los datos')
        });
    };
}
```

### **2. 👥 Páginas de Usuarios**

#### **Crear Usuario (resources/js/pages/users/create.tsx):**
```typescript
export default function CreateUser() {
    const { showFormSuccess, showFormError } = useFormNotifications();

    const handleSubmit = (e) => {
        post(route('users.store'), {
            onSuccess: () => showFormSuccess('crear usuario'),
            onError: () => showFormError('crear usuario')
        });
    };
}
```

#### **Editar Usuario (resources/js/pages/users/edit.tsx):**
```typescript
export default function EditUser() {
    const { showFormSuccess, showFormError } = useFormNotifications();

    const handleSubmit = (e) => {
        patch(route('users.update', user.id), {
            onSuccess: () => showFormSuccess('actualizar usuario'),
            onError: () => showFormError('actualizar usuario')
        });
    };
}
```

### **3. 🛡️ Páginas de Roles**

#### **Crear Rol (resources/js/pages/roles/create.tsx):**
```typescript
export default function CreateRole() {
    const { showFormSuccess, showFormError } = useFormNotifications();

    const handleSubmit = (e) => {
        post('/roles', {
            onSuccess: () => showFormSuccess('crear rol'),
            onError: () => showFormError('crear rol')
        });
    };
}
```

---

## 🎨 Mensajes Personalizados

### **Configuración de Posición y Duración:**
```typescript
notify.success('Mensaje', {
    description: 'Descripción opcional',
    duration: 4000,
    position: 'top-right'
});
```

### **Notificaciones con Promesas:**
```typescript
const saveData = async () => {
    return notify.promise(
        fetch('/api/save'),
        'Guardando datos...',
        'Datos guardados exitosamente',
        'Error al guardar datos'
    );
};
```

### **Notificaciones de Carga Personalizada:**
```typescript
const loadingToast = notify.loading('Procesando...');

// Después de completar
toast.dismiss(loadingToast);
notify.success('Completado');
```

---

## 💡 Ejemplos de Uso

### **1. 📝 Formulario con Validación**
```typescript
export default function MyForm() {
    const { showFormSuccess, showFormError } = useFormNotifications();

    const handleSubmit = (e) => {
        e.preventDefault();
        
        post('/my-endpoint', {
            onSuccess: () => {
                showFormSuccess('guardar datos');
                reset(); // Limpiar formulario
            },
            onError: () => {
                showFormError('guardar datos');
            }
        });
    };

    return (
        <form onSubmit={handleSubmit}>
            {/* Campos del formulario */}
            {/* Las validaciones se muestran automáticamente */}
        </form>
    );
}
```

### **2. 🔍 Búsqueda con Feedback**
```typescript
export default function SearchPage() {
    const { notify } = useNotifications();

    const handleSearch = () => {
        router.get('/search', { query: searchTerm }, {
            onSuccess: (page) => {
                const results = page.props.results;
                if (results.length === 0) {
                    notify.info('Sin resultados', `No se encontró "${searchTerm}"`);
                }
            },
            onError: () => {
                notify.error('Error de búsqueda', 'Intenta de nuevo');
            }
        });
    };
}
```

### **3. 🗑️ Eliminación con Confirmación**
```typescript
export default function ListPage() {
    const { showFormSuccess, showFormError } = useFormNotifications();

    const handleDelete = (item) => {
        if (confirm(`¿Eliminar ${item.name}?`)) {
            router.delete(`/items/${item.id}`, {
                onSuccess: () => showFormSuccess(`eliminar ${item.name}`),
                onError: () => showFormError('eliminar elemento')
            });
        }
    };
}
```

---

## ⚙️ Configuración

### **Personalizar Traducciones de Validación:**

En `HandleValidationErrors.php`, puedes agregar nuevas traducciones:

```php
private function translateValidationError(string $field, string $error): ?string
{
    // Agregar nuevos campos
    $fieldNames = [
        'name' => 'nombre',
        'email' => 'correo electrónico',
        'phone' => 'teléfono',        // ← Nuevo
        'address' => 'dirección',     // ← Nuevo
    ];

    // Agregar nuevos patrones
    $patterns = [
        '/The .+ field is required\./' => "El campo {$friendlyField} es obligatorio.",
        '/The .+ must be a valid phone number\./' => "El {$friendlyField} debe ser válido.", // ← Nuevo
    ];
}
```

### **Personalizar Estilos de Notificaciones:**

En `useNotifications.ts`, puedes modificar la configuración:

```typescript
notify.success(message, {
    description,
    duration: 5000,           // ← Cambiar duración
    position: 'top-center',   // ← Cambiar posición
    className: 'my-toast',    // ← Agregar clase CSS
});
```

### **Agregar Nuevos Tipos de Mensaje Flash:**

En el hook, puedes agregar manejo para nuevos tipos:

```typescript
useEffect(() => {
    // Mensajes existentes...
    
    // Nuevo tipo personalizado
    if (flash?.custom) {
        toast(flash.custom, {
            icon: '🎉',
            duration: 4000
        });
    }
}, [flash]);
```

---

## 🧪 Testing

### **Probar Notificaciones de Validación:**
```bash
# Enviar datos inválidos para ver traducciones
curl -X POST /users \
  -d "email=invalid" \
  -d "password=123"

# Debería mostrar: "El correo electrónico debe ser una dirección válida"
```

### **Probar Mensajes Flash:**
```php
// En un controlador
return back()->with('success', 'Operación exitosa');
return back()->with('error', 'Algo salió mal');
```

### **Probar Errores de Formulario:**
```javascript
// En el navegador, debería aparecer automáticamente
// cuando hay errores de validación o mensajes flash
```

---

## 🚀 Beneficios del Sistema

### **✅ Para Desarrolladores:**
- **Menos código**: No más `toast.success` manual en cada página
- **Consistencia**: Todos los mensajes siguen el mismo patrón
- **Traducción automática**: Errores en español sin configuración
- **Mantenimiento fácil**: Cambios centralizados

### **✅ Para Usuarios:**
- **Experiencia uniforme**: Misma apariencia en toda la app
- **Mensajes claros**: Errores en español comprensible
- **Feedback inmediato**: Notificaciones en tiempo real
- **Mejor usabilidad**: Indicaciones claras de éxito/error

### **✅ Para el Proyecto:**
- **Escalabilidad**: Fácil agregar nuevas páginas con notificaciones
- **Mantenibilidad**: Código centralizado y reutilizable
- **Profesionalismo**: UX pulida y consistente
- **Reducción de bugs**: Manejo de errores estandarizado

---

## 🎉 Conclusión

El **Sistema Global de Notificaciones** transforma la experiencia de usuario al proporcionar feedback inmediato, claro y consistente en toda la aplicación. Con traducciones automáticas, manejo inteligente de errores y una API simple, garantiza que los usuarios siempre sepan qué está pasando en el sistema.

**🎯 Resultado**: Una aplicación más profesional, usable y fácil de mantener.

