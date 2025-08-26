# 📊 Documentación: Página de Actividad

## 📋 Descripción General

Sistema completo de seguimiento y visualización de actividad del sistema que combina logs de auditoría y actividades de usuarios en una interfaz unificada.

### **Funcionalidades Principales:**
- Vista unificada de actividades de usuarios y logs del sistema
- Filtros avanzados por tipo, usuario, fecha y búsqueda
- Descripciones enriquecidas con colores para cambios
- Paginación manual con preservación de filtros
- Estadísticas dinámicas en tiempo real

---

## 📄 Página Principal

### **activity/index.tsx** - Vista Unificada
- **Fuentes de datos**: UserActivity + ActivityLog combinados
- **Búsqueda**: Por nombre, descripción, tipo de evento
- **Filtros**: Tipo de evento (checkboxes), usuarios (searchable), rango de fechas
- **Vista responsive**: Tabla en desktop, cards en móvil
- **Descripción mejorada**: Colores para mostrar cambios (rojo=anterior, verde=nuevo)

---

## 🔧 Backend (ActivityController.php)

### **Método Principal:**
```php
index(Request $request)  # Vista unificada con filtros y paginación
```

### **Combinación de Fuentes:**
```php
// 1. UserActivity (actividades generales)
$activitiesQuery = UserActivity::with('user')
    ->whereNotIn('activity_type', ['heartbeat', 'page_view']);

// 2. ActivityLog (logs de auditoría)  
$activityQuery = ActivityLog::with('user')
    ->whereNotIn('event_type', ['heartbeat', 'page_view']);

// 3. Combinar y ordenar
$allActivities = $userActivities->concat($activityLogs)
    ->sortByDesc('created_at');
```

### **Paginación Manual:**
Implementa paginación personalizada para datos combinados con LengthAwarePaginator.

---

## 🗄️ Base de Datos

### **Tabla user_activities:**
```sql
id              # Primary key
user_id         # FK a users
activity_type   # Tipo de actividad  
description     # Descripción del evento
url             # URL visitada
method          # Método HTTP
metadata        # JSON con datos adicionales
created_at, updated_at
```

### **Tabla activity_logs:**
```sql
id              # Primary key
user_id         # FK a users
event_type      # Tipo de evento
target_model    # Modelo afectado
target_id       # ID del modelo
description     # Descripción del cambio
old_values      # JSON con valores anteriores
new_values      # JSON con valores nuevos
user_agent      # Agente de usuario
created_at, updated_at
```

---

## 🔍 Sistema de Filtros Avanzado

### **Filtros Disponibles:**
```typescript
interface ActivityFilters {
    search: string;           # Búsqueda global
    event_type: string;       # Tipos separados por coma
    user_id: string;         # IDs de usuarios separados por coma
    start_date?: string;     # Fecha inicio (YYYY-MM-DD)
    end_date?: string;       # Fecha fin (YYYY-MM-DD)
    per_page: number;        # Registros por página
}
```

### **Búsqueda Global:**
- Descripción de la actividad
- Tipo de evento
- Nombre y email del usuario
- URL visitada (en UserActivity)

---

## 🎨 Funcionalidades UI

### **Descripciones Enriquecidas:**
```typescript
const getEnhancedDescription = (activity: ActivityData) => {
    // Resalta cambios con colores
    // Rojo: valores anteriores
    // Verde: valores nuevos
    // Inteligente por tipo de evento
}
```

### **Códigos de Color por Tipo:**
- **Verde**: login, creaciones (user_created, role_created)
- **Azul**: logout, actualizaciones (user_updated, role_updated)
- **Rojo**: eliminaciones (user_deleted, role_deleted)
- **Amarillo**: restauraciones (user_restored, role_restored)
- **Gris**: navegación (page_view, heartbeat)

### **Componentes Utilizados:**
- shadcn/ui: Card, Dialog, ScrollArea, Badge, Checkbox
- Lucide icons: Users, Calendar, Filter, Search
- react-day-picker: Selector de fechas

---

## 📊 Estadísticas Dinámicas

### **Estadísticas Mostradas:**
```php
'stats' => [
    'total_events' => $totalEvents,     # Total de eventos en período
    'unique_users' => $uniqueUsers,     # Usuarios únicos activos
    'today_events' => $todayEvents,     # Eventos de hoy
]
```

### **Cálculo Inteligente:**
- **Sin filtros**: Estadísticas del período completo
- **Con filtros**: Estadísticas de resultados filtrados
- **Exclusión automática**: heartbeat y page_view no cuentan

---

## 🔧 Funcionalidades Técnicas

### **Exclusiones Automáticas:**
```php
// Eventos excluidos del sistema
->whereNotIn('activity_type', ['heartbeat', 'page_view'])
->whereNotIn('event_type', ['heartbeat', 'page_view'])
```

### **Traducción de Eventos:**
```php
$eventTypeTranslations = [
    'login' => 'Inicio de sesión',
    'user_created' => 'Usuario creado', 
    'user_updated' => 'Usuario actualizado',
    'role_users_updated' => 'Usuarios de rol actualizados',
    // etc...
];
```

### **Formateo de Fechas:**
```typescript
// Zona horaria Guatemala con formato local
date.toLocaleDateString('es-GT', {
    year: 'numeric', month: 'short', day: 'numeric',
    hour: '2-digit', minute: '2-digit',
    timeZone: 'America/Guatemala'
});
```

---

## 📱 Responsive Design

### **Desktop (lg+):**
- Tabla completa con columnas: Usuario, Actividad, Descripción, Fecha
- Filtros organizados en grid 4 columnas
- Paginación completa con elipsis

### **Mobile/Tablet:**
- Cards compactas con información esencial
- Filtros en modal/dialog expandible
- Paginación simplificada

---

## 🚀 Performance y Optimización

### **Eager Loading:**
```php
->with('user')  // Evita N+1 queries
```

### **Paginación Manual:**
```php
// Para datos combinados de múltiples tablas
$activities = new LengthAwarePaginator(
    $paginatedActivities->values(),
    $allActivities->count(),
    $perPage,
    $currentPage
);
```

### **Preservación de Estado:**
```typescript
// Mantiene filtros en navegación
router.get('/activity', filterParams, {
    preserveState: true,
    preserveScroll: true,
});
```

---

## 📊 Tipos de Eventos Soportados

### **Autenticación:**
- `login`: Inicio de sesión
- `logout`: Cierre de sesión

### **Gestión de Usuarios:**
- `user_created`: Usuario creado
- `user_updated`: Usuario actualizado  
- `user_deleted`: Usuario eliminado
- `user_restored`: Usuario restaurado
- `user_force_deleted`: Usuario eliminado permanentemente

### **Gestión de Roles:**
- `role_created`: Rol creado
- `role_updated`: Rol actualizado
- `role_deleted`: Rol eliminado
- `role_users_updated`: Usuarios de rol actualizados

### **Sistema:**
- `theme_changed`: Cambio de tema
- `action`: Acciones generales del sistema