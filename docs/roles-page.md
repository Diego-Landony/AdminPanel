# 🔐 Documentación: Página de Roles

## 📋 Descripción General

Sistema completo de gestión de roles y permisos que permite crear, editar y eliminar roles, asignar permisos granulares, y gestionar usuarios por rol.

### **Funcionalidades Principales:**
- CRUD completo de roles
- Asignación de permisos por página y acción
- Gestión de usuarios por rol
- Sincronización automática de permisos
- Modal para ver usuarios asignados
- Protecciones para roles del sistema

---

## 📄 Páginas

### **roles/index.tsx** - Lista Principal
- **Vista**: Tabla responsive con cards en móvil
- **Búsqueda**: Por nombre, descripción, permisos y usuarios
- **Estadísticas**: Total roles, del sistema, personalizados
- **Modal usuarios**: Ver usuarios asignados a cada rol
- **Protecciones**: Roles del sistema tienen restricciones

### **roles/create.tsx** - Crear Rol
- **Campos**: Nombre, descripción opcional
- **Permisos**: Tabla organizada por página con 4 acciones (Ver, Crear, Editar, Eliminar)
- **Validación**: Al menos un permiso requerido
- **Sincronización**: Auto-detecta nuevas páginas del sistema

### **roles/edit.tsx** - Editar Rol
- **Gestión permisos**: Interfaz de checkboxes por grupo
- **Gestión usuarios**: Sheet lateral con búsqueda
- **Protecciones especiales**: Para rol admin y roles del sistema
- **Guardado automático**: Cambios se aplican inmediatamente

---

## 🔧 Backend (RoleController.php)

### **Métodos Principales:**
```php
index(Request $request)           # Lista paginada con eager loading
create()                          # Vista crear + sync permisos
store(Request $request)           # Crear rol con permisos
edit(Role $role)                  # Vista editar + validaciones
update(Request $request)          # Actualizar rol y permisos
updateUsers(Request $request)     # Actualizar usuarios del rol (AJAX)
destroy(Role $role)               # Eliminar rol con protecciones
```

### **Auto-sincronización:**
```php
syncPermissionsIfNeeded()         # Detecta nuevas páginas automáticamente
// Ejecuta PermissionDiscoveryService para encontrar nuevos permisos
// Actualiza rol admin con todos los permisos automáticamente
```

---

## 🤖 Sistema de Permisos Automático

### **PermissionDiscoveryService.php**
- **Escaneo automático**: Revisa `resources/js/pages/` por nuevas páginas
- **Detección de acciones**: Basado en archivos existentes (index.tsx, create.tsx, edit.tsx)
- **Generación de permisos**: Patrón `{página}.{acción}` (ej: users.view, roles.create)
- **Exclusiones**: auth, settings, no-access

### **Configuración de Páginas:**
```php
$pageConfig = [
    'home' => ['actions' => ['view']],
    'dashboard' => ['actions' => ['view']],
    'users' => ['actions' => ['view', 'create', 'edit', 'delete']],
    'roles' => ['actions' => ['view', 'create', 'edit', 'delete']],
    'activity' => ['actions' => ['view']],
];
```

---

## 🗄️ Base de Datos

### **Tabla roles:**
```sql
id            # Primary key
name          # Nombre único del rol
description   # Descripción opcional
is_system     # Si es rol del sistema (protegido)
created_at, updated_at
```

### **Tabla permissions:**
```sql
id           # Primary key  
name         # Nombre único (ej: users.view)
display_name # Nombre legible (ej: Ver Usuarios)
description  # Descripción detallada
group        # Grupo de la página (ej: users)
created_at, updated_at
```

### **Tablas Pivot:**
```sql
role_user         # Relación users ↔ roles
permission_role   # Relación roles ↔ permissions
```

---

## 🛡️ Protecciones de Seguridad

### **Rol Admin Especial:**
- **Protegido**: No se puede eliminar
- **Auto-permisos**: Siempre tiene todos los permisos
- **Usuario fijo**: admin@admin.com siempre asignado

### **Roles del Sistema:**
- **Solo admin puede editar**: Verificación en controller
- **Restricciones frontend**: Botones deshabilitados apropiadamente
- **Validaciones backend**: Permisos verificados en cada acción

### **Middlewares de Permisos:**
```php
Route::get('roles', [RoleController::class, 'index'])
    ->middleware('permission:roles.view');
Route::post('roles', [RoleController::class, 'store'])
    ->middleware('permission:roles.create');
```

---

## 🎨 UI/UX

### **Componentes Utilizados:**
- shadcn/ui: Card, Button, Dialog, Sheet, ScrollArea, Badge
- Lucide icons: Shield, Plus, Users, Edit, Trash2
- toast (sonner): Notificaciones

### **Funcionalidades UX:**
- **ActionsMenu**: Menú contextual con editar/eliminar
- **Estados de carga**: Spinners durante operaciones
- **Confirmación de eliminación**: Dialog con advertencia
- **Búsqueda en tiempo real**: Filtros preservados en navegación

---

## 🔍 Búsqueda y Filtros

### **Búsqueda por:**
- Nombre del rol
- Descripción
- Permisos asociados (relación)
- Usuarios asignados (relación)

### **Estadísticas Mostradas:**
- Total de roles en sistema
- Roles del sistema vs personalizados
- Conteo dinámico basado en filtros actuales

---

## 📊 Manejo de Errores

### **Validaciones:**
```php
'name' => 'required|string|max:255|unique:roles,name'
'permissions' => 'required|array|min:1'  # Al menos un permiso
'permissions.*' => 'exists:permissions,name'
```

### **Protección contra Errores:**
- try/catch en operaciones CRUD
- Validación de permisos existentes
- Mensajes específicos para duplicados
- Logs detallados de errores

### **Casos Especiales:**
- Verificación de permisos antes de operaciones
- Protección contra eliminación de roles con usuarios
- Auto-asignación de permisos a admin
- Preservación del usuario admin@admin.com