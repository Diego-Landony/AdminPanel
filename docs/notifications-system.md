# 🔔 Sistema de Notificaciones

## 📋 Descripción General

Sistema básico de notificaciones usando toast (sonner) para mostrar mensajes de retroalimentación al usuario.

### **Funcionalidades:**
- Notificaciones toast con sonner
- Manejo de mensajes flash desde Laravel
- Notificaciones de éxito, error e información
- Posicionamiento y duración configurables

---

## 🎨 Implementación Frontend

### **Librería Utilizada:**
- **sonner**: Librería de toast para React
- **Importación**: `import { toast } from "sonner"`

### **Uso Básico:**
```typescript
// Notificaciones simples
toast.success('Operación exitosa');
toast.error('Error en la operación');
toast.info('Información importante');

// Con descripción
toast.success('Título', { 
  description: 'Descripción detallada' 
});

// Con duración personalizada
toast.success('Mensaje', { 
  duration: 5000 
});
```

---

## 🔧 Implementación Backend

### **Mensajes Flash de Laravel:**
```php
// En controladores
return back()->with('success', 'Usuario creado exitosamente');
return back()->with('error', 'Error al crear usuario');
return redirect()->route('users.index')->with('success', 'Operación completada');
```

### **Manejo Automático:**
Los mensajes flash se procesan automáticamente en el layout principal de la aplicación.

---

## 📄 Páginas que Usan Notificaciones

### **users/index.tsx:**
```typescript
// Búsqueda sin resultados
toast.info(`No se encontraron usuarios para: "${searchValue}"`);

// Error al cargar datos
toast.error("Error al cargar los datos de actividad");
```

### **activity/index.tsx:**
```typescript
// Sin resultados en búsqueda
toast.info("No se encontraron resultados", {
    description: "Intenta ajustar los criterios de búsqueda"
});

// Error al cargar
toast.error("Error al cargar los datos de actividad");
```

### **roles/index.tsx:**
```typescript
// Error al cargar usuarios de rol
toast.error('Error al cargar usuarios del rol');
```

---

## ⚙️ Configuración

### **Componente Toaster:**
El componente `<Toaster />` está configurado en el layout principal para mostrar todas las notificaciones.

### **Posición por Defecto:**
Las notificaciones aparecen en la posición estándar definida por sonner.

---

## 💡 Uso Recomendado

### **Casos de Uso:**
- **Éxito**: Operaciones CRUD completadas
- **Error**: Errores de servidor o validación
- **Info**: Mensajes informativos (sin resultados, etc.)

### **Mensajes Claros:**
- Usar mensajes descriptivos
- Incluir contexto cuando sea necesario
- Mantener consistencia en el tono

---

## 📊 Limitaciones Actuales

### **No Implementado:**
- Hooks personalizados para notificaciones
- Middleware de validación con traducción automática
- Sistema complejo de manejo de errores
- Notificaciones persistentes o con acciones

### **Sistema Actual:**
- Implementación básica con sonner
- Mensajes flash de Laravel
- Uso directo de toast() en componentes