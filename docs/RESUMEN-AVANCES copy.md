# Resumen de Avances - Sistema SubwayApp

**Fecha:** 30 de Octubre, 2025

---

##  panel de Administración

Panel web para gestionar todas las operaciones de Subway desde un solo lugar.

---

## Lo que está implementado

### 1. CLIENTES
- Sistema de puntos con 4 niveles: Bronce, Plata, Oro, Platino
- El cliente sube de nivel automáticamente al acumular puntos
- Cada nivel gana más puntos (multiplicadores: 1x, 1.2x, 1.5x, 2x)
- Múltiples direcciones de entrega por cliente
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
- Ejemplo: Sub de Pollo Q55 → Q40 solo Lunes y Miércoles
- NO se combina con otras promociones

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

---

## 🎯 Funcionalidades Generales

✅ Búsqueda en tiempo real mientras escribes
✅ Filtros avanzados (por categoría, estado, nivel, fecha, etc.)
✅ Estadísticas en vivo (Total clientes, productos activos, promociones vigentes)
✅ Arrastrar y soltar para cambiar orden de productos/combos/categorías
✅ Se adapta a computadora, tablet y celular automáticamente




**30 de Octubre, 2025**
