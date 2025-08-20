# 📢 Estrategia de Mensajes y Notificaciones

## 🎯 Objetivo

Eliminar la duplicación de mensajes entre la página y las notificaciones Sonner, proporcionando una experiencia de usuario clara y consistente.

## 🔄 Antes vs Ahora

### ❌ **ANTES (Con duplicación)**
- **Errores de validación**: Se mostraban tanto en la página como en Sonner
- **Mensajes del servidor**: Se mostraban tanto en la página como en Sonner
- **Confusión**: El usuario veía el mismo mensaje dos veces

### ✅ **AHORA (Sin duplicación)**
- **Errores de validación**: Solo se muestran en la página (texto rojo debajo de campos)
- **Mensajes del servidor**: Solo se muestran en Sonner (notificaciones toast)
- **Claridad**: Cada tipo de mensaje tiene su lugar específico

## 📍 Ubicación de los Mensajes

### 1. **Errores de Validación** → Solo en la Página
```tsx
// ❌ NO mostrar en Sonner
// ✅ Solo mostrar en la página con FormField
<FormField
    label="Contraseña"
    error={errors.password}
    description="Mínimo 6 caracteres"
>
    <Input
        type="password"
        value={data.password}
        onChange={(e) => setData('password', e.target.value)}
    />
</FormField>
```

**Resultado**: El error "La contraseña debe tener al menos 6 caracteres" aparece solo debajo del campo, no como notificación.

### 2. **Mensajes del Servidor** → Solo en Sonner
```tsx
// ✅ Solo mostrar en Sonner
// ❌ NO mostrar en la página
useEffect(() => {
    if (flash?.success) {
        toast.success(flash.success); // Solo notificación
    }
    if (flash?.error) {
        toast.error(flash.error); // Solo notificación
    }
}, [flash]);
```

**Resultado**: "Usuario creado exitosamente" aparece solo como notificación toast, no duplicado en la página.

## 🛠️ Componentes Utilizados

### FormField
```tsx
import { FormField } from '@/components/ui/form-field';

<FormField
    label="Nombre"
    error={errors.name}
    required
    description="Nombre completo del usuario"
>
    <Input
        value={data.name}
        onChange={(e) => setData('name', e.target.value)}
    />
</FormField>
```

**Características**:
- ✅ Maneja automáticamente la etiqueta
- ✅ Muestra errores de validación
- ✅ Indica campos requeridos
- ✅ Permite descripciones
- ✅ Estilo consistente

### FormError
```tsx
import { FormError } from '@/components/ui/form-error';

<FormError message={errors.permissions} />
```

**Características**:
- ✅ Icono de alerta
- ✅ Estilo de error consistente
- ✅ Solo para errores generales del formulario

## 🔧 Implementación Técnica

### Hook de Notificaciones
```tsx
// Solo maneja mensajes flash del servidor
export function useNotifications() {
    const { props } = usePage();
    const { flash } = props as any; // ❌ NO incluir 'errors'

    useEffect(() => {
        // Solo mensajes del servidor
        if (flash?.success) {
            toast.success(flash.success);
        }
        // ... otros mensajes flash
    }, [flash]);
}
```

### Layout Principal
```tsx
// Solo maneja mensajes flash del servidor
useEffect(() => {
    if (props.flash?.success) {
        toast.success(props.flash.success);
    }
    // ... otros mensajes flash
}, [props.flash]);
```

### Middleware de Validación
```php
// NO agrega mensajes flash duplicados
public function handle(Request $request, Closure $next): Response
{
    try {
        return $next($request);
    } catch (ValidationException $e) {
        // Para Inertia, mantener comportamiento normal
        if ($request->expectsJson() || $request->header('X-Inertia')) {
            throw $e; // Los errores se mostrarán en la vista
        }
        
        // Solo para peticiones normales (no Inertia)
        return back()->withErrors($e->errors())->withInput();
    }
}
```

## 📱 Ejemplos de Uso

### Página de Crear Usuario
```tsx
export default function CreateUser() {
    const { showFormSuccess, showFormError } = useFormNotifications();

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        
        post(route('users.store'), {
            onSuccess: () => {
                showFormSuccess('crear usuario'); // ✅ Solo Sonner
                reset();
            },
            onError: () => {
                showFormError('crear usuario'); // ✅ Solo Sonner
            }
        });
    };

    return (
        <form onSubmit={handleSubmit}>
            <FormField
                label="Contraseña"
                error={errors.password} // ✅ Solo en la página
                required
            >
                <Input
                    type="password"
                    value={data.password}
                    onChange={(e) => setData('password', e.target.value)}
                />
            </FormField>
            
            {/* ... otros campos */}
        </form>
    );
}
```

## 🎨 Estilos y Consistencia

### Colores de Error
- **Borde del campo**: `border-red-500` cuando hay error
- **Texto de error**: `text-red-600 dark:text-red-400`
- **Icono de error**: `AlertCircle` de Lucide React

### Posicionamiento de Notificaciones
- **Sonner**: `top-center` para notificaciones del servidor
- **Errores de página**: Debajo de cada campo con `FormField`

## 🚫 Qué NO Hacer

### ❌ NO duplicar mensajes
```tsx
// ❌ MAL: Mostrar error tanto en página como en Sonner
{errors.password && (
    <p className="text-red-600">{errors.password}</p>
)}
useEffect(() => {
    if (errors.password) {
        toast.error(errors.password); // ❌ DUPLICADO
    }
}, [errors]);
```

### ❌ NO usar el hook de notificaciones para errores de validación
```tsx
// ❌ MAL: Hook que maneja errores de validación
export function useNotifications() {
    const { flash, errors } = props as any; // ❌ NO incluir errors
    
    useEffect(() => {
        if (errors && Object.keys(errors).length > 0) {
            toast.error('Error de validación'); // ❌ NO hacer esto
        }
    }, [errors]);
}
```

## ✅ Qué SÍ Hacer

### ✅ Usar FormField para campos con errores
```tsx
<FormField
    label="Email"
    error={errors.email}
    required
>
    <Input
        type="email"
        value={data.email}
        onChange={(e) => setData('email', e.target.value)}
    />
</FormField>
```

### ✅ Usar Sonner solo para mensajes del servidor
```tsx
useEffect(() => {
    if (flash?.success) {
        toast.success(flash.success);
    }
}, [flash]);
```

### ✅ Usar el hook de notificaciones para acciones del formulario
```tsx
const { showFormSuccess, showFormError } = useFormNotifications();

post(route('users.store'), {
    onSuccess: () => {
        showFormSuccess('crear usuario');
    },
    onError: () => {
        showFormError('crear usuario');
    }
});
```

## 🔍 Casos de Uso Comunes

### 1. **Crear Usuario**
- **Errores de validación**: Solo en la página (FormField)
- **Éxito**: Solo en Sonner (toast.success)
- **Error del servidor**: Solo en Sonner (toast.error)

### 2. **Editar Usuario**
- **Errores de validación**: Solo en la página (FormField)
- **Éxito**: Solo en Sonner (toast.success)
- **Error del servidor**: Solo en Sonner (toast.error)

### 3. **Cambiar Contraseña**
- **Errores de validación**: Solo en la página (FormField)
- **Éxito**: Solo en Sonner (toast.success)
- **Error del servidor**: Solo en Sonner (toast.error)

## 📊 Beneficios de la Nueva Estrategia

1. **🎯 Claridad**: Cada mensaje tiene su lugar específico
2. **🚫 Sin duplicación**: El usuario no ve el mismo mensaje dos veces
3. **🎨 Consistencia**: Estilo uniforme en toda la aplicación
4. **📱 Mejor UX**: Experiencia más limpia y profesional
5. **🔧 Mantenibilidad**: Código más organizado y fácil de mantener

## 🚀 Próximos Pasos

1. **Migrar todas las páginas** para usar `FormField` y `FormError`
2. **Eliminar duplicaciones** en hooks de notificaciones
3. **Actualizar documentación** de componentes
4. **Crear tests** para verificar la estrategia
5. **Revisar páginas existentes** para aplicar la nueva estrategia

---

**Nota**: Esta estrategia asegura que los errores de validación se muestren claramente en la página donde ocurren, mientras que las notificaciones del servidor se muestren como toasts para feedback inmediato, sin duplicación.
