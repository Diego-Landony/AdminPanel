# Resumen de Avances - Sistema SubwayApp

**Fecha ultima actualización:** 30 de Octubre, 2025

---

## Tecnologías

**Backend:**
- Laravel 12
- PHP 8.4
- MariaDB

**Frontend Web (Panel Admin):**
- React 19
- Inertia.js v2
- TypeScript
- Tailwind CSS v4

**App Móvil tecnologías que usará:**
- React Native (mismo código para Android e iOS)
- TypeScript

---

## Acceso al Sistema

**URL:** admin.subwaycardgt.com

**Credenciales:**
- Correo: admin@admin.com
- Contraseña: admin

---

## Panel de Administración

Panel web para gestionar todas las operaciones de Subway desde un solo lugar.

---

## Lo que está implementado

### 1. CLIENTES
- Registro de clientes con tarjeta Subway
- Tipos de cliente: Bronce, Plata, Oro, Platino (estructura base)
- Gestión de múltiples direcciones de entrega
- Búsqueda por nombre, email, teléfono, tarjeta Subway

### 2. Restaurantes
- Ubicación en mapa con GPS
- **GEOCERCAS**: zona de cobertura, solo entregas dentro del área
- Horarios por día de la semana
- Control individual de delivery y pickup
- Muestra si está abierto o cerrado en tiempo real

### 3. MENÚ
- **Categorías**: Sándwiches, Bebidas, Postres, etc.
- **Productos** con 4 precios diferentes:
  - Pickup capital / Delivery capital
  - Pickup Interior / Delivery Interior
- **Variantes de producto**: Si se crea "Sub de Pollo" en categoría subs (15 cm y 30 cm), automáticamente se crean ambos tamaños para en los productos ponerle sus respectivos precios.
- **Personalización de los productos por producto**:
  - Pan (Blanco, Integral, Plano)
  - Vegetales (Lechuga, Tomate, etc.)
  - Salsas (Mostaza, Chipotle, etc.)
  - Extras con costo adicional (Aguacate +Q10, Queso extra +Q5, champiñones +Q7, etc.)

### 4. COMBOS
Dos tipos de items en un combo:
- **Fijos**: Vienen incluidos (galleta + bebida)
- **Elección**: Cliente elige (Elige tu sándwich: bmt / Pavo / etc) + tipo de bebida, pepsi, sptrite

Precio del combo unico pero construido a partir de los productos seleccionados.
  
### 5. PROMOCIONES 

**Sub del Día**
- Producto específico con precio especial en días seleccionados
- Ejemplo: Sub de Pollo Q40 → Q22 toda la semana
- NO se combina con otras promociones (combos, 2x1, toman el precio normal Q40)

**2x1**
- Categoría completa (Bebidas, Sándwiches, etc.)
- Compras 2, pagas el más caro
- Ejemplo: Coca-Cola Q15 + Sprite Q10 = Pagas Q15

**Descuento por Porcentaje**
- 5%, 10%, 20%, etc. sobre productos seleccionados
- Ejemplo: 20% en todas las Ensaladas

**Todas las promociones se pueden configurar:**
- Días específicos (Lunes a Viernes)
- Horarios (2pm - 5pm)
- Solo pickup / Solo delivery / Ambos
- Rango de fechas (Del 1 al 15 de Diciembre)

### 6. CONTROL DE ACCESO
- **Usuarios** con login y contraseña
- **Roles**: Administrador, Gerente, Supervisor, Marketing, etc.
- **Permisos por módulo**: Quién puede ver/crear/editar/eliminar
- **Historial**: Registro de quién hizo qué cambio y cuándo


## Pendiente de Implementar

### Panel Administrativo
- **Sistema de Motoristas**: Gestión de repartidores (asignación, disponibilidad, historial de entregas)
- **Autoimpresión de Comandas**: Impresión automática de tickets en cocina de restaurante
- **Dashboard de Pedidos**: Panel en tiempo real para recibir y gestionar pedidos desde la app
- **Toma de Pedidos por Call Center**: Dashboard para que operadores tomen pedidos telefónicos
- **Sistema de Puntos**: Lógica completa de acumulación, canje y gestión de puntos (el admin maneja toda la lógica)

### Aplicación Móvil para Clientes
- **Diseño y Desarrollo Completo de la App**:
  - Registro e inicio de sesión
  - Visualización de menú con categorías
  - Personalización de productos (pan, vegetales, extras)
  - Carrito de compras
  - Gestión de direcciones de entrega
  - Realizar pedidos
  - Métodos de pago (efectivo/tarjeta)
  - Integración con Infile para pagos con tarjeta
  - Tracking de pedido en tiempo real
  - Historial de pedidos
  - Visualización de puntos (saldo, historial, canje) mediante API

---

## Roadmap de Implementación

### FASE 1: App Móvil DEMO + Sistema de Pedidos (Noviembre - Diciembre 2025)
**Objetivo:** Demo funcional de la app para presentar + panel de pedidos básico

**App Móvil (Demo para Diciembre):**
- Registro e inicio de sesión
- Recuperación de contraseña
- Gestión de direcciones de entrega
- Visualización de menú con categorías
- Personalización de productos (pan, vegetales, extras)
- Carrito de compras
- Selección de restaurante y tipo de servicio (pickup/delivery)
- Realizar y confirmar pedido (solo efectivo por ahora)
- Historial de pedidos

**Panel Administrativo:**
- Dashboard de Pedidos básico
  - Recibe pedidos de la app en tiempo real
  - Estados: Pendiente, Preparando, Listo, Entregado, Cancelado
  - Filtros por restaurante y estado
- Sistema de Motoristas básico
  - Registro y asignación manual

**Duración:** 6-8 semanas

---

### FASE 2: App Completa con Pagos y Puntos (Enero - Marzo 2026)
**Objetivo:** App 100% funcional con pagos y programa de lealtad

**App Móvil:**
- Integración con Infile (pagos con tarjeta)
- Guardado de tarjetas (tokenización)
- Tracking de pedido en tiempo real
- Notificaciones push
- Perfil de usuario completo
- Visualización de puntos (saldo, historial, canje) mediante API

**Panel Administrativo:**
- Sistema de puntos completo (lógica de acumulación, canje, vencimiento)
- API para consumo desde app móvil
- Toma de Pedidos por Call Center (dashboard para operadores tomen pedidos telefónicos)

**Duración:** 10-12 semanas

---

### FASE 3: Estabilización y Correcciones (Abril 2026)
**Objetivo:** Corregir errores y optimizar el sistema

**Funcionalidades:**
- Monitoreo de errores y corrección de bugs
- Optimizaciones de rendimiento

**Duración:** 4 semanas

---

## Estado del Proyecto

**Completado (Octubre 2025):**
- Panel administrativo con gestión completa de catálogos

**En Desarrollo (Noviembre - Diciembre 2025):**
- FASE 1: App móvil DEMO + Sistema de pedidos

**Meta Diciembre 2025:**
- Demo funcional de app para presentación
- Panel de pedidos operativo

**Próximamente:**
- FASE 2: App completa con pagos y puntos (Ene-Mar 2026)
- FASE 3: Estabilización y correcciones (Abril 2026)

**Lanzamiento Oficial Estimado:** Abril 2026

---


