# 👥 Documentación: Página de Usuarios

## 📋 Descripción General

Sistema de gestión de usuarios con funcionalidades CRUD completas, búsqueda en tiempo real, y seguimiento de actividad.

### **Funcionalidades Principales:**
- Lista paginada con búsqueda y filtros
- Creación de usuarios con validaciones
- Edición de datos básicos y contraseñas
- Eliminación con protecciones de seguridad
- Estados en tiempo real (en línea, reciente, desconectado)
- Auto-actualización cada minuto

---

## 📄 Páginas

### **users/index.tsx** - Lista Principal
- **Vista**: Tabla responsive con vista de cards en móvil
- **Búsqueda**: Campo de búsqueda con botón aplicar
- **Paginación**: 10, 25, 50, 100 resultados por página
- **Estados de usuario**: En línea (<5 min), Reciente (<15 min), Desconectado, Nunca
- **Estadísticas**: Total usuarios, en línea, desconectados
- **Auto-refresh**: Cada 60 segundos sin recargar página

### **users/create.tsx** - Crear Usuario
- **Campos**: Nombre, email, contraseña, confirmación
- **Validación**: CustomPassword rule, email único
- **Auto-verificación**: Usuarios creados por admin son verificados automáticamente

### **users/edit.tsx** - Editar Usuario  
- **Campos**: Nombre, email
- **Contraseña opcional**: Con checkbox "Cambiar contraseña"
- **Sidebar**: Información del sistema (ID, email verificado, fechas)
- **Validación condicional**: Solo valida contraseña si se proporciona

---

## 🔧 Backend (UserController.php)

### **Métodos Principales:**
```php
index(Request $request)     # Lista paginada con búsqueda
keepAlive(Request $request) # Actualiza last_activity_at cada 30s
create()                    # Vista formulario crear
store(Request $request)     # Crear usuario con validaciones
edit(User $user)           # Vista formulario editar  
update(Request $request)   # Actualizar datos y contraseña opcional
destroy(User $user)        # Eliminar con protecciones
```

### **Protecciones de Seguridad:**
- No se puede eliminar usuario admin principal (`admin@admin.com`)
- No se puede auto-eliminar
- Validación única de email excluyendo propio usuario
- Marcado como no verificado si email cambia

---

## 🗄️ Base de Datos

### **Tabla users:**
```sql
id                  # Primary key
name               # Nombre del usuario  
email              # Email único
email_verified_at  # Fecha verificación
password           # Hash de contraseña
last_login_at      # Último login
last_activity_at   # Última actividad
timezone           # Zona horaria (default: America/Guatemala)
remember_token     # Token remember me
created_at, updated_at, deleted_at
```

### **Estados de Usuario:**
- **online**: last_activity_at < 5 minutos
- **recent**: last_activity_at < 15 minutos  
- **offline**: last_activity_at > 15 minutos
- **never**: Sin last_activity_at

---

## 🔍 Búsqueda y Filtros

### **Búsqueda por:**
- Nombre de usuario (LIKE)
- Email (LIKE)
- Roles asignados (relación)

### **Paginación:**
- Preserva filtros en navegación
- Opciones: 10, 25, 50, 100 por página
- Información de resultados (mostrando X de Y)

---

## 🎨 UI/UX

### **Componentes Utilizados:**
- shadcn/ui: Card, Button, Input, Select, Dialog, Badge, Table
- Lucide icons: Users, Shield, Plus, Search, Trash2, Edit
- toast (sonner): Notificaciones de éxito/error

### **Responsive:**
- Desktop: Tabla completa
- Mobile/Tablet: Cards con información compacta
- Skeleton loading durante búsquedas

---

## 📊 Manejo de Errores

### **Validaciones Frontend:**
- Campos requeridos con indicadores visuales
- Confirmación de contraseña
- Email válido

### **Validaciones Backend:**
```php
'name' => 'required|string|max:255'
'email' => 'email|max:255|unique:users'  
'password' => ['required', 'confirmed', new CustomPassword]
```

### **Protección contra Errores:**
- try/catch en todas las operaciones CRUD
- Logs detallados de errores de BD
- Mensajes de error específicos para usuarios