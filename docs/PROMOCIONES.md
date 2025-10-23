# 📢 Sistema de Promociones - Documentación Conceptual

## Índice
1. [Visión General](#visión-general)
2. [Tipos de Promociones](#tipos-de-promociones)
3. [Reglas de Negocio](#reglas-de-negocio)
4. [Estructura de Datos](#estructura-de-datos)
5. [Flujo de Aplicación](#flujo-de-aplicación)
6. [Interfaz de Usuario](#interfaz-de-usuario)
7. [Casos de Uso](#casos-de-uso)
8. [Validaciones](#validaciones)

---

## Visión General

El sistema de promociones permite crear, gestionar y aplicar automáticamente tres tipos diferentes de descuentos a los productos del menú. Las promociones se aplican automáticamente en el carrito de compras según reglas específicas de vigencia temporal, alcance y tipo de servicio.

### Características Principales
- ✅ Tres tipos de promociones distintas (2x1, Porcentaje, Sub del Día)
- ✅ Vigencia temporal flexible (permanente, por fechas, por horarios, días de la semana)
- ✅ Restricción por tipo de servicio (Delivery, Pickup, o ambos)
- ✅ Aplicación automática en el carrito
- ✅ Sistema de estados (Activa/Inactiva, Vigente/Futura/Expirada)
- ✅ Historial de promociones pasadas
- ✅ Vista previa de precios con promoción aplicada

---

## Tipos de Promociones

### 1. 🎁 Promoción 2x1 (Two for One)

**Descripción**: Por cada 2 productos de la misma categoría, el cliente paga el más caro y el más barato es gratis.

#### Características:

**Alcance - Solo Categorías:**
- Cada **item** representa UNA categoría completa
- Se aplica a TODOS los productos de esa categoría
- Los productos NO se mezclan entre categorías
- Ejemplo:
  ```
  Promoción: "2x1 en Bebidas y Postres"
  Item 1: Categoría "Bebidas"
  Item 2: Categoría "Postres"
  ```

**Mecánica de Descuento:**
- 2 productos de la categoría → 1 gratis (el más barato)
- 3 productos → 1 gratis (el más barato)
- 4 productos → 2 gratis (los 2 más baratos)
- 5 productos → 2 gratis (los 2 más baratos)
- 6 productos → 3 gratis (los 3 más baratos)
- Y así sucesivamente...

**IMPORTANTE**: El 2x1 se calcula con los precios YA descontados (si hay descuento de porcentaje).

#### Vigencia Temporal (4 opciones):

1. **Permanente**: Activa siempre sin límite de tiempo
2. **Por rango de fechas**: Del Día X al Día Y (todo el día)
   - Ejemplo: Del 1 al 31 de Enero
3. **Por horario permanente**: Todos los días de HH:MM a HH:MM
   - Ejemplo: 17:00 a 20:00 (Happy Hour)
4. **Por fecha + horario**: Del Día X al Y, de HH:MM a HH:MM
   - Ejemplo: Fines de semana de Enero de 12:00 a 18:00

#### Restricción de Servicio:
- Ambos (Delivery + Pickup)
- Solo Delivery
- Solo Pickup

#### Reglas Especiales:
- ✅ Pueden existir múltiples 2x1 en diferentes categorías simultáneamente
- ✅ Una promoción puede tener múltiples items (categorías)
- ⚠️ Cada categoría es independiente (no se mezclan productos)
- ⚠️ Se calcula sobre precios YA descontados por porcentaje
- ⚠️ No puede haber dos 2x1 activos en la misma categoría con vigencias solapadas

**Ejemplo de Aplicación:**
```
Cliente ordena (categoría Bebidas con 2x1 activo):
- 1x Coca Cola $30 (con 10% descuento = $27)
- 1x Pepsi $30 (con 10% descuento = $27)

Cálculo:
1. Aplica descuento de porcentaje: $27 c/u
2. Aplica 2x1: Paga solo la más cara = $27
Total: $27 (ahorro de $33)
```

---

### 2. 💯 Promoción de Porcentaje

**Descripción**: Reduce el precio de productos específicos por un porcentaje definido.

#### Características:

**Alcance - Sistema de Items:**
- Cada **item** representa UN producto específico con su porcentaje de descuento
- Puedes crear **múltiples items** en una sola promoción
- Cada item puede tener un porcentaje diferente
- Ejemplo: Una promoción "Happy Hour" puede tener 3 items:
  - Item 1: Hamburguesa Premium → 25%
  - Item 2: Hot Dog Gourmet → 20%
  - Item 3: Pizza Margarita → 15%

**Porcentaje de Descuento (por item):**
- Cada item requiere su propio porcentaje
- Valor entre 1% y 100%
- Se guarda a nivel de item (no de promoción)
- Permite flexibilidad: diferentes productos con diferentes descuentos en la misma promoción

#### Vigencia Temporal (4 opciones):

1. **Permanente**: Activa siempre sin límite de tiempo
2. **Por rango de fechas**: Del Día X al Día Y (todo el día)
   - Ejemplo: Del 1 al 31 de Enero
3. **Por horario permanente**: Todos los días de HH:MM a HH:MM
   - Ejemplo: 14:00 a 17:00 (Happy Hour diario)
4. **Por fecha + horario**: Del Día X al Y, de HH:MM a HH:MM
   - Ejemplo: Del 1 al 31 de Enero de 14:00 a 17:00

#### Restricción de Servicio:
- Ambos (Delivery + Pickup)
- Solo Delivery
- Solo Pickup

#### Reglas Especiales:
- ✅ Pueden coexistir múltiples promociones de porcentaje simultáneamente
- ✅ Una promoción puede tener múltiples items (productos) con diferentes porcentajes
- ⚠️ Si un producto tiene múltiples descuentos de porcentaje vigentes → **se aplica el MAYOR**
- ⚠️ Se aplica DESPUÉS del Sub del Día (sobre el precio especial)
- ⚠️ Se aplica ANTES del 2x1

**Ejemplo de Aplicación:**
```
Promoción: "Happy Hour"
Item 1: Hamburguesa → 25% descuento
Item 2: Hot Dog → 20% descuento

Cliente ordena:
- 1x Hamburguesa $100 → $75 (25% descuento)
- 1x Hot Dog $50 → $40 (20% descuento)
Total: $115 (ahorro de $35)
```

**Ejemplo de Resolución de Conflictos:**
```
Producto: Pizza Margarita
Precio base: $100

Escenario 1 (Múltiples descuentos):
- Promoción A: 15% descuento en Pizza Margarita
- Promoción B: 20% descuento en Pizza Margarita
→ Se aplica 20% (el mayor) = $80

Escenario 2 (Sub del Día + Porcentaje):
- Sub del Día: $80 (reemplaza precio base)
- Descuento: 10%
→ Precio final: $80 - ($80 * 10%) = $72
```

---

### 3. 🌟 Sub del Día

**Descripción**: Un producto tiene un precio especial fijo en días específicos de la semana.

#### Características:

**Alcance - Sistema de Items:**
- Cada **item** representa UN producto específico con sus precios especiales
- Puedes crear **múltiples items** en una sola promoción
- Ejemplo: Una promoción "Especiales de Lunes" puede tener 3 items:
  - Item 1: Hamburguesa → $50/$45
  - Item 2: Hot Dog → $35/$30
  - Item 3: Sandwich → $40/$35

**Precios Especiales (montos fijos por item):**
- Cada item requiere DOS precios:
  - **Precio Capital**: Aplica a zona capital (pickup y delivery)
  - **Precio Interior**: Aplica a zona interior (pickup y delivery)
- Los precios aplican a **todas las variantes** del producto
- El precio especial **reemplaza** el precio base antes de aplicar otros descuentos

#### Vigencia Temporal - Sistema Flexible:

**REQUERIDO:**
- **Días de la semana** (mínimo 1 día seleccionado):
  - Formato: 1=Lunes, 2=Martes, ..., 7=Domingo
  - Puedes seleccionar: 1 día, varios días, o todos los días
  - Ejemplos: [1,3,5] = Lunes, Miércoles, Viernes

**OPCIONAL** (restricciones adicionales por item):

Cada item puede tener opcionalmente:

1. **Solo días** → Aplica todos esos días sin límite de tiempo
   - Ejemplo: Lunes a Viernes → Válido siempre

2. **Días + rango de fechas** → Aplica solo entre esas fechas
   - Ejemplo: Lunes a Viernes del 1 al 31 de Enero

3. **Días + horario** → Aplica solo en ese horario
   - Ejemplo: Lunes a Viernes de 14:00 a 17:00 (Happy Hour)

4. **Días + fechas + horario** → Combinación completa
   - Ejemplo: Lunes a Viernes del 1 al 31 de Enero de 14:00 a 17:00

El sistema calcula automáticamente el tipo de vigencia:
- Solo días → `weekdays`
- Días + fechas → `date_range`
- Días + horarios → `time_range`
- Días + fechas + horarios → `date_time_range`

#### Restricción de Servicio:
- Ambos (Delivery + Pickup)
- Solo Delivery
- Solo Pickup

#### Reglas Especiales:
- ✅ Pueden existir múltiples "subs del día" simultáneamente
- ✅ Una promoción puede tener múltiples items (productos)
- ⚠️ Un producto no puede tener dos "Sub del Día" activos en los mismos días
- ⚠️ Cada item tiene un único conjunto de precios (no varía por día dentro del item)
- ⚠️ El precio especial reemplaza el precio base antes de otros descuentos

**Ejemplo de Validación de Conflictos:**
```
✅ PERMITIDO:
Promoción A: Hamburguesa - Lunes, Martes
Promoción B: Hamburguesa - Miércoles, Jueves

❌ PROHIBIDO:
Promoción A: Hamburguesa - Lunes, Martes, Miércoles
Promoción B: Hamburguesa - Miércoles, Jueves  ← Conflicto en Miércoles
```

---

## Reglas de Negocio

### 1. Jerarquía de Aplicación de Promociones

```
ORDEN DE APLICACIÓN (de primero a último):

1. Sub del Día
   └─> Si aplica: REEMPLAZA el precio base del producto

2. Descuento de Porcentaje
   └─> Si aplica: Calcula descuento sobre precio actual
   └─> Si múltiples descuentos vigentes: Aplica el MAYOR

3. Promoción 2x1
   └─> Ordena productos por precio (ya con descuentos aplicados)
   └─> Descuenta los N más baratos
```

### 2. Resolución de Conflictos

#### Conflicto: Múltiples descuentos de porcentaje en el mismo producto
**Resolución**: Se aplica el descuento MAYOR, no se suman.

**Ejemplo**:
- Promoción A: 15% en el producto
- Promoción B: 20% en el mismo producto
- **Resultado**: Se aplica 20% (el mayor)

#### Conflicto: Sub del Día + Descuento de Porcentaje
**Resolución**: El Sub del Día reemplaza el precio base, luego se aplica el descuento de porcentaje sobre ese nuevo precio.

**Ejemplo**:
- Precio normal: $100
- Sub del día: $80
- Descuento de porcentaje: 10%
- **Cálculo**: $80 - (10% de $80) = $72

### 3. Validación de Vigencia

Una promoción se considera **VIGENTE** cuando:

1. **Estado**: `is_active = true`
2. **Fecha**: Está dentro del rango de fechas (si aplica)
3. **Hora**: Está dentro del rango de horarios (si aplica)
4. **Día de semana**: Es uno de los días seleccionados (solo Sub del Día)
5. **Servicio**: Coincide con el tipo de servicio del pedido (Delivery/Pickup)

### 4. Estados de Promoción

#### Estados del Sistema:
- **🟢 Activa y Vigente**: `is_active = true` + dentro de vigencia → **SE APLICA**
- **🟡 Activa pero Futura**: `is_active = true` + aún no inicia → No se aplica
- **🔵 Activa pero Fuera de Horario**: `is_active = true` + fuera de horario → No se aplica
- **🔴 Inactiva**: `is_active = false` → No se aplica (pausada manualmente)
- **⚫ Expirada**: `end_date` pasado → No se aplica (pasa a historial)

---

## Estructura de Datos

### Arquitectura: Sistema de Dos Niveles

El sistema utiliza una arquitectura **Promoción → Items** que permite máxima flexibilidad y escalabilidad.

#### 📦 Nivel 1: Promoción (Contenedor)

Representa la promoción general con:
- **Identificación**: Nombre y descripción
- **Tipo**: 2x1, Porcentaje o Sub del Día
- **Estado**: Activa/Inactiva
- **Configuración global**: Restricciones de servicio aplicables a todos los items

```
PROMOCIÓN
│
├─ Nombre: "Especiales de Lunes"
├─ Tipo: Sub del Día
├─ Estado: Activa
└─ Servicio: Delivery y Pickup
```

#### 🎯 Nivel 2: Items de Promoción (Elementos Específicos)

Cada item representa **UN** elemento afectado:
- UN producto específico, O
- UNA categoría completa, O
- UNA variante específica de producto

Cada item contiene:
- **Alcance**: Qué producto/categoría afecta
- **Vigencia temporal**: Días, fechas, horarios
- **Configuración específica**: Precios especiales (Sub del Día), porcentaje (Descuento), etc.

```
ITEM 1
├─ Producto: "Hamburguesa Clásica"
├─ Precio Capital: $50
├─ Precio Interior: $45
├─ Días: Lunes, Miércoles, Viernes
└─ Horario: Todo el día

ITEM 2
├─ Producto: "Hot Dog"
├─ Precio Capital: $35
├─ Precio Interior: $30
├─ Días: Lunes, Miércoles, Viernes
└─ Horario: 14:00 - 17:00
```

### 🎨 Ejemplo Completo de Arquitectura

```
┌─────────────────────────────────────────────────────┐
│ PROMOCIÓN: "Especiales de Lunes"                    │
│ Tipo: Sub del Día                                   │
│ Estado: Activa                                      │
│ Servicio: Delivery y Pickup                         │
├─────────────────────────────────────────────────────┤
│                                                     │
│ ┌─────────────────────────────────────────────┐   │
│ │ ITEM 1: Hamburguesa Clásica                 │   │
│ │ • Capital: $50 | Interior: $45              │   │
│ │ • Días: Lunes                                │   │
│ │ • Horario: Todo el día                       │   │
│ └─────────────────────────────────────────────┘   │
│                                                     │
│ ┌─────────────────────────────────────────────┐   │
│ │ ITEM 2: Hot Dog                              │   │
│ │ • Capital: $35 | Interior: $30              │   │
│ │ • Días: Lunes                                │   │
│ │ • Horario: 14:00 - 17:00 (Happy Hour)       │   │
│ └─────────────────────────────────────────────┘   │
│                                                     │
│ ┌─────────────────────────────────────────────┐   │
│ │ ITEM 3: Sandwich Veggie                     │   │
│ │ • Capital: $40 | Interior: $35              │   │
│ │ • Días: Lunes                                │   │
│ │ • Horario: Todo el día                       │   │
│ └─────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────┘
```

### ✅ Ventajas de esta Arquitectura

- ✅ **Flexibilidad**: Una promoción puede afectar múltiples productos/categorías
- ✅ **Granularidad**: Cada item puede tener vigencia temporal diferente
- ✅ **Escalabilidad**: Fácil agregar nuevos tipos de promociones
- ✅ **Mantenibilidad**: Lógica clara y separada por responsabilidad
- ✅ **Reutilización**: Mismo producto puede estar en múltiples promociones

### 📋 Alcances Permitidos por Tipo de Promoción

| Tipo de Promoción | Alcance del Item | Explicación |
|-------------------|------------------|-------------|
| **Sub del Día** | `producto` | Un item = un producto con precios especiales |
| **Porcentaje** | `producto` | Un item = un producto con porcentaje de descuento |
| **2x1** | `categoría` | Un item = una categoría (aplica a todos sus productos) |

### 🔗 Relaciones Conceptuales

```
UNA Promoción ──tiene──> MUCHOS Items
UN Item ──pertenece a──> UNA Promoción
UN Item ──afecta a──> UN Producto O UNA Categoría
```

### ✏️ Validaciones de Integridad

#### 1. Alcance del Item (Exclusividad):
- Un item debe afectar **SOLO UNO** de los siguientes:
  - Un producto, O
  - Una categoría, O
  - Una variante
- ❌ No puede afectar múltiples elementos simultáneamente

#### 2. Tipo de Promoción vs Alcance:
- **Sub del Día** → Items deben afectar productos individuales
- **Porcentaje** → Items deben afectar productos individuales
- **2x1** → Items deben afectar categorías completas

#### 3. Campos Requeridos por Tipo:
- **Sub del Día** requiere en cada item:
  - Precio especial para Capital
  - Precio especial para Interior
  - Al menos 1 día de la semana seleccionado
- **Porcentaje** requiere en cada item:
  - Producto (requerido)
  - Porcentaje de descuento (1-100%)
- **2x1** requiere en cada item:
  - Categoría (requerido)

#### 4. Vigencia Temporal:
- **Fechas**: Si especificas fecha fin, debes especificar fecha inicio
- **Horarios**: Si especificas hora fin, debes especificar hora inicio
- **Coherencia**: Fecha fin >= Fecha inicio, Hora fin > Hora inicio
- **Formato días**: Array de números 1-7 (1=Lunes, 7=Domingo)

---

## Flujo de Aplicación

### 1. Flujo en el Carrito de Compras

```
INICIO: Usuario agrega productos al carrito
│
├─> Para cada producto en el carrito:
│   │
│   ├─ PASO 1: Obtener precio base del producto
│   │          (según variante, zona, y tipo de servicio)
│   │
│   ├─ PASO 2: ¿Existe Sub del Día vigente para este producto?
│   │          │
│   │          ├─ SÍ: Reemplazar precio base con precio especial
│   │          └─ NO: Mantener precio base
│   │
│   ├─ PASO 3: ¿Existe descuento de porcentaje individual vigente?
│   │          │
│   │          ├─ SÍ: Calcular descuento_individual
│   │          └─ NO: descuento_individual = 0
│   │
│   ├─ PASO 4: ¿Existe descuento de porcentaje de categoría vigente?
│   │          │
│   │          ├─ SÍ: Calcular descuento_categoria
│   │          └─ NO: descuento_categoria = 0
│   │
│   ├─ PASO 5: Aplicar el MAYOR entre descuento_individual y descuento_categoria
│   │          precio_con_descuento = precio_actual - (precio_actual * max(descuentos) / 100)
│   │
│   └─ RESULTADO: Precio individual del producto con descuentos aplicados
│
├─> Agrupar productos por categoría
│
├─> Para cada categoría:
│   │
│   ├─ PASO 6: ¿Existe 2x1 vigente para esta categoría?
│   │          │
│   │          ├─ NO: Calcular subtotal normal
│   │          │
│   │          └─ SÍ:
│   │              ├─ Ordenar productos de la categoría por precio (ya descontados) de mayor a menor
│   │              ├─ Calcular cantidad_gratis = floor(cantidad_total / 2)
│   │              ├─ Seleccionar los productos más baratos (últimos en el orden)
│   │              ├─ Marcarlos como "gratis" en el detalle del carrito
│   │              └─ Calcular subtotal sin incluir los productos gratis
│   │
│   └─ RESULTADO: Subtotal de la categoría
│
├─> Sumar todos los subtotales de categorías
│
└─> RESULTADO FINAL: Total del carrito con todas las promociones aplicadas
```

### 2. Algoritmo de Validación de Vigencia

```
FUNCIÓN: esPromocionVigente(promocion, fecha_actual, hora_actual, dia_semana, tipo_servicio)
│
├─ VALIDACIÓN 1: Estado Activo
│  └─ Si promocion.is_active = false → RETORNAR false
│
├─ VALIDACIÓN 2: Tipo de Servicio
│  ├─ Si promocion.service_type = 'both' → Continuar
│  ├─ Si promocion.service_type = 'delivery_only' AND tipo_servicio ≠ 'delivery' → RETORNAR false
│  └─ Si promocion.service_type = 'pickup_only' AND tipo_servicio ≠ 'pickup' → RETORNAR false
│
├─ VALIDACIÓN 3: Vigencia Temporal (según validity_type)
│  │
│  ├─ Si validity_type = 'permanent':
│  │  └─ RETORNAR true
│  │
│  ├─ Si validity_type = 'date_range':
│  │  └─ RETORNAR (fecha_actual >= start_date AND fecha_actual <= end_date)
│  │
│  ├─ Si validity_type = 'time_range':
│  │  └─ RETORNAR (hora_actual >= start_time AND hora_actual <= end_time)
│  │
│  ├─ Si validity_type = 'date_time_range':
│  │  ├─ fecha_valida = (fecha_actual >= start_date AND fecha_actual <= end_date)
│  │  ├─ hora_valida = (hora_actual >= start_time AND hora_actual <= end_time)
│  │  └─ RETORNAR (fecha_valida AND hora_valida)
│  │
│  └─ Si validity_type = 'weekdays':
│     └─ RETORNAR (dia_semana está en weekdays)
│
└─ RETORNAR true (pasó todas las validaciones)
```

### 3. Cálculo de Precio Final de un Producto

```
FUNCIÓN: calcularPrecioFinal(producto, zona, tipo_servicio, fecha_hora_actual)
│
├─ PASO 1: Obtener precio base
│  └─ precio_base = obtenerPrecioBase(producto, zona, tipo_servicio)
│
├─ PASO 2: Aplicar Sub del Día (si existe y está vigente)
│  ├─ sub = buscarSubDelDiaVigente(producto, fecha_hora_actual, tipo_servicio)
│  └─ Si sub existe:
│     └─ precio_base = (zona = 'capital') ? sub.special_price_capital : sub.special_price_interior
│
├─ PASO 3: Buscar descuentos de porcentaje vigentes
│  ├─ descuento_individual = buscarDescuentoIndividual(producto, fecha_hora_actual, tipo_servicio)
│  └─ descuento_categoria = buscarDescuentoCategoria(producto.categoria, fecha_hora_actual, tipo_servicio)
│
├─ PASO 4: Aplicar el mayor descuento
│  ├─ descuento_mayor = max(descuento_individual, descuento_categoria)
│  └─ Si descuento_mayor > 0:
│     └─ precio_base = precio_base - (precio_base * descuento_mayor / 100)
│
└─ RETORNAR precio_base
```

---

## Interfaz de Usuario

### 1. Página Principal de Promociones

**Ruta**: `/menu/promotions`

**Elementos**:

#### Header:
- Título: "📢 Promociones"
- Botón: "+ Nueva Promoción"

#### Filtros:
- Tipo de promoción (Dropdown): Todas / 2x1 / Porcentaje / Sub del Día
- Estado (Dropdown): Todas / Activas / Inactivas / Futuras / Expiradas
- Vigencia (Dropdown): Todas / Vigentes Ahora / Futuras / Permanentes
- Servicio (Dropdown): Todos / Delivery / Pickup

#### Listado:
Cada promoción muestra:
- Indicador de estado (color)
- Nombre de la promoción
- Tipo de promoción (badge)
- Alcance (categoría o producto)
- Vigencia temporal (resumen)
- Tipo de servicio
- Acciones: [Editar] [Menú contextual ⋮]

#### Menú Contextual (⋮):
- Vista Previa
- Duplicar
- Activar/Desactivar
- Eliminar

---

### 2. Formulario Crear/Editar Promoción

**Ruta**: `/menu/promotions/create` o `/menu/promotions/{id}/edit`

**Secciones del Formulario**:

#### Sección 1: Información Básica
- **Nombre** (input, requerido)
  - Placeholder: "ej: Promo Verano 2025"
- **Descripción** (textarea, opcional)
  - Placeholder: "Describe los detalles de la promoción..."

#### Sección 2: Tipo de Promoción
- **Radio buttons** (requerido):
  - ○ 2x1
  - ○ Descuento por Porcentaje
  - ○ Sub del Día

**Comportamiento dinámico**: Al seleccionar un tipo, se muestran/ocultan secciones específicas.

#### Sección 3: Alcance
**Si tipo = 2x1 o Porcentaje**:
- **Radio buttons**:
  - ○ Categoría: [Dropdown de categorías]
  - ○ Producto: [Dropdown de productos] (solo si tipo = Porcentaje)

**Si tipo = Sub del Día**:
- **Producto**: [Dropdown de productos] (requerido)

#### Sección 4: Configuración de Descuento
**Si tipo = Porcentaje**:
- **Porcentaje de descuento**: [Input numérico] %
  - Min: 1, Max: 100
  - Validación en tiempo real

**Si tipo = Sub del Día**:
- **Precio especial Capital**: $ [Input numérico]
- **Precio especial Interior**: $ [Input numérico]
- Nota informativa: "Estos precios aplicarán a todas las variantes del producto"

#### Sección 5: Vigencia Temporal
**Si tipo = 2x1 o Porcentaje**:
- **Radio buttons**:
  - ○ Permanente
  - ○ Por fechas
    - Del [Date picker] al [Date picker]
  - ○ Por horario (todos los días)
    - De [Time picker] a [Time picker]
  - ○ Por fechas + horario
    - Del [Date picker] al [Date picker]
    - De [Time picker] a [Time picker]

**Si tipo = Sub del Día**:
- **Días activos** (checkboxes):
  - ☐ L  ☐ M  ☐ M  ☐ J  ☐ V  ☐ S  ☐ D
  - Opción: [Seleccionar todos]

#### Sección 6: Restricciones de Servicio
- **Radio buttons** (requerido):
  - ○ Delivery y Pickup
  - ○ Solo Delivery
  - ○ Solo Pickup

#### Sección 7: Estado
- **Toggle switch**: Activa
  - Ayuda contextual: "Las promociones inactivas no se aplicarán aunque estén en vigencia"

#### Sección 8: Vista Previa (solo al editar)
- **Botón**: "Ver Vista Previa"
- Modal que muestra:
  - Producto/categoría seleccionada
  - Precio original
  - Precio con promoción aplicada
  - Ahorro calculado
  - Vigencia actual

#### Footer del Formulario:
- Botón: [Cancelar]
- Botón: [Guardar]
- Botón: [Guardar y Activar] (si está inactiva)

---

### 3. Modal de Vista Previa

**Trigger**: Click en "Ver Vista Previa" o en menú contextual

**Contenido**:

```
┌─────────────────────────────────────────────────┐
│ Vista Previa de Promoción                    [✕]│
├─────────────────────────────────────────────────┤
│                                                 │
│ 📢 [Nombre de la Promoción]                    │
│ [Descripción]                                  │
│                                                 │
│ ─────────────────────────────────────────────  │
│                                                 │
│ Tipo: [Badge: 2x1 / Porcentaje / Sub]         │
│ Alcance: [Categoría: Bebidas]                 │
│ Vigencia: [Lun-Vie, 14:00-17:00]              │
│ Servicio: [Delivery y Pickup]                 │
│                                                 │
│ ─────────────────────────────────────────────  │
│                                                 │
│ 💰 Ejemplo de Aplicación:                     │
│                                                 │
│ Producto: Coca Cola 500ml                     │
│ Precio original:    $30.00                    │
│ Precio promoción:   $24.00                    │
│ Ahorro:             $6.00 (20%)               │
│                                                 │
│ ─────────────────────────────────────────────  │
│                                                 │
│ ⏰ Estado Actual:                              │
│ 🟢 Activa y Vigente                           │
│ (Se está aplicando ahora)                     │
│                                                 │
└─────────────────────────────────────────────────┘
```

---

### 4. Historial de Promociones

**Ruta**: `/menu/promotions/history`

**Elementos**:

#### Header:
- Título: "📊 Historial de Promociones"

#### Filtros:
- Tipo (Dropdown): Todas / 2x1 / Porcentaje / Sub del Día
- Año (Dropdown): Lista de años con promociones
- Mes (Dropdown): Enero - Diciembre

#### Listado Cronológico:
Agrupado por mes/año, cada promoción muestra:
- ⚫ Indicador de expirada
- Nombre de la promoción
- Alcance
- Vigencia que tuvo
- Tipo de servicio
- Acciones: [Ver Detalle] [Duplicar]

**Funcionalidad "Duplicar"**:
- Crea una nueva promoción basada en una antigua
- Pre-llena todos los campos
- Permite ajustar fechas/horarios
- Útil para promociones recurrentes

---

### 5. Indicadores Visuales de Estado

#### Colores por Estado:
- 🟢 Verde: Activa y vigente (aplicándose ahora)
- 🟡 Amarillo: Activa pero futura (programada)
- 🔵 Azul: Activa pero fuera de horario (esperando)
- 🔴 Rojo: Inactiva (pausada manualmente)
- ⚫ Negro: Expirada (en historial)

#### Badges por Tipo:
- 2x1: Badge azul
- Porcentaje: Badge verde
- Sub del Día: Badge naranja

---

## Casos de Uso

### Caso 1: 2x1 en Bebidas los Fines de Semana

**Objetivo**: Ofrecer 2x1 en todas las bebidas solo sábados y domingos.

**Configuración**:
- Nombre: "2x1 Bebidas Fin de Semana"
- Tipo: 2x1
- Alcance: Categoría "Bebidas"
- Vigencia: Por horario permanente (00:00 a 23:59, días S-D)
- Servicio: Ambos

**Comportamiento**:
- Cliente agrega 2 Coca-Colas ($30 c/u) un sábado
- Sistema detecta 2x1 vigente
- Cobra solo 1 Coca-Cola
- Ahorro: $30

---

### Caso 2: Happy Hour con Descuentos en Productos Selectos

**Objetivo**: Descuentos en productos específicos de 2pm a 5pm todos los días.

**Configuración**:
- Nombre: "Happy Hour"
- Tipo: Porcentaje
- Items:
  - Item 1: Pizza Margarita → 15%
  - Item 2: Hamburguesa Premium → 25%
  - Item 3: Hot Dog → 20%
- Vigencia: Por horario permanente (14:00 a 17:00)
- Servicio: Delivery y Pickup

**Comportamiento**:
- Cliente ordena Pizza Margarita ($100) a las 3pm
- Sistema aplica 15% de descuento (del item específico)
- Precio final: $85
- Ahorro: $15

---

### Caso 3: Sub del Día - Hamburguesa Lunes a Viernes

**Objetivo**: Precio especial en hamburguesa los días laborables.

**Configuración**:
- Nombre: "Sub del Día: Hamburguesa Clásica"
- Tipo: Sub del Día
- Producto: Hamburguesa Clásica
- Precios: $50 (Capital) / $45 (Interior)
- Días: L-M-M-J-V
- Servicio: Ambos

**Comportamiento**:
- Precio normal de Hamburguesa: $70
- Cliente ordena un martes
- Sistema reemplaza precio con $50 (Capital)
- Ahorro: $20

---

### Caso 4: Combinación de Promociones

**Escenario Complejo**:
- Categoría "Bebidas" tiene 2x1 activo
- Producto "Coca Cola" tiene 10% de descuento individual
- Cliente agrega 2 Coca-Colas ($30 c/u)

**Flujo de Cálculo**:
1. Precio base: $30 c/u
2. Aplicar 10% individual: $30 - $3 = $27 c/u
3. Aplicar 2x1: Paga solo la más cara = $27
4. **Total: $27** (ahorro: $33)

---

### Caso 5: Sub del Día + Descuento de Porcentaje

**Escenario**:
- Producto "Hamburguesa" es Sub del Día ($50)
- Producto "Hamburguesa" tiene 20% de descuento
- Cliente ordena un día que aplica ambas

**Flujo de Cálculo**:
1. Precio base: $70
2. Sub del día: reemplaza a $50
3. Aplicar 20% sobre $50: $50 - $10 = $40
4. **Total: $40** (ahorro: $30)

---

### Caso 6: Múltiples Descuentos de Porcentaje en el Mismo Producto

**Escenario**:
- Promoción A: 15% en Pizza Margarita (todo enero)
- Promoción B: 25% en Pizza Margarita (del 10 al 20 de enero)
- Cliente ordena el 15 de enero

**Flujo de Cálculo**:
1. Ambas promociones están vigentes
2. Sistema compara: 15% vs 25%
3. Aplica el mayor: 25%
4. Pizza de $100 → $75

---

## Validaciones

### Validaciones del Formulario

#### Campo: Nombre
- ✅ Requerido
- ✅ Máximo 255 caracteres
- ✅ Debe ser único (no puede haber dos promociones con el mismo nombre activas)

#### Campo: Tipo de Promoción
- ✅ Requerido
- ✅ Debe ser uno de: '2x1', 'percentage', 'daily_special'

#### Campo: Alcance
- ✅ Si tipo = '2x1' → debe seleccionar categoría
- ✅ Si tipo = 'percentage' → debe seleccionar categoría O producto
- ✅ Si tipo = 'daily_special' → debe seleccionar producto
- ⚠️ No puede seleccionar ambos (categoría Y producto)

#### Campo: Porcentaje (si tipo = Porcentaje)
- ✅ Requerido
- ✅ Debe ser número entre 1 y 100
- ✅ Máximo 2 decimales

#### Campos: Precios Especiales (si tipo = Sub del Día)
- ✅ Ambos requeridos
- ✅ Deben ser números positivos
- ✅ Máximo 2 decimales
- ⚠️ No pueden ser $0

#### Campos: Vigencia Temporal
- ✅ Si tipo = 'date_range':
  - start_date y end_date requeridos
  - end_date >= start_date

- ✅ Si tipo = 'time_range':
  - start_time y end_time requeridos
  - end_time > start_time

- ✅ Si tipo = 'date_time_range':
  - Todos los campos requeridos
  - Validaciones de fecha y hora

- ✅ Si tipo = 'weekdays':
  - Al menos 1 día seleccionado

#### Campo: Tipo de Servicio
- ✅ Requerido
- ✅ Debe ser uno de: 'both', 'delivery_only', 'pickup_only'

---

### Validaciones de Negocio

#### Validación 1: Conflicto de 2x1 en la misma categoría
**Regla**: No pueden existir dos promociones 2x1 activas y vigentes simultáneamente en la misma categoría.

**Mensaje de Error**: "Ya existe una promoción 2x1 activa en la categoría [nombre] que se solapa con las fechas/horarios seleccionados."

#### Validación 2: Sub del Día duplicado
**Regla**: Un producto no puede tener dos "Sub del Día" activos con días que se solapen.

**Ejemplo de Conflicto**:
- Sub A: Lunes, Martes, Miércoles
- Sub B: Miércoles, Jueves, Viernes
- ❌ Error: Ambos incluyen "Miércoles"

**Mensaje de Error**: "Este producto ya tiene un Sub del Día activo en [días conflictivos]."

#### Validación 3: Fechas coherentes
**Regla**: No se pueden crear promociones con fecha de fin en el pasado.

**Mensaje de Error**: "La fecha de fin no puede estar en el pasado."

#### Validación 4: Horarios coherentes
**Regla**: start_time debe ser menor a end_time.

**Mensaje de Error**: "La hora de fin debe ser posterior a la hora de inicio."

---

### Validaciones en Tiempo Real (Frontend)

#### Al seleccionar Tipo de Promoción:
- Mostrar/ocultar secciones relevantes
- Limpiar campos no aplicables

#### Al seleccionar Alcance:
- Si es categoría: cargar lista de categorías
- Si es producto: cargar lista de productos

#### Al ingresar Porcentaje:
- Validar rango 1-100
- Mostrar vista previa del descuento

#### Al seleccionar Fechas:
- Validar que end_date >= start_date
- Calcular duración de la promoción
- Mostrar advertencia si la promoción ya expiró

#### Al seleccionar Producto (Sub del Día):
- Cargar precio actual del producto
- Mostrar comparativa: precio normal vs precio especial
- Calcular ahorro

---

## Consideraciones Técnicas

### Performance
- **Caché de Promociones Vigentes**: Las promociones vigentes se deben cachear para evitar consultas repetitivas
- **Índices de Base de Datos**: Crear índices en campos de búsqueda frecuente (type, category_id, product_id, is_active, start_date, end_date)
- **Eager Loading**: Cargar relaciones (categoria, producto) al listar promociones

### Seguridad
- **Autorización**: Solo usuarios con permisos específicos pueden crear/editar/eliminar promociones
- **Validación de Permisos**: `menu.promotions.create`, `menu.promotions.edit`, `menu.promotions.delete`
- **Auditoría**: Registrar quién creó/editó cada promoción (created_by, updated_by)

### Escalabilidad
- **Soft Deletes**: Nunca eliminar físicamente las promociones (usar deleted_at)
- **Historial Automático**: Las promociones expiradas automáticamente pasan a historial
- **Archivado**: Opción de archivar promociones muy antiguas (más de 2 años) en tabla separada

### Mantenimiento
- **Limpieza Automática**: Job programado para mover promociones expiradas a historial
- **Notificaciones Internas**: Alert en el dashboard si hay promociones con conflictos
- **Logs**: Registrar aplicación de promociones en los pedidos

---

## Glosario

- **Vigente**: Promoción que está dentro de su periodo de validez temporal
- **Activa**: Promoción que tiene `is_active = true` (no pausada)
- **Expirada**: Promoción cuya fecha de fin ya pasó
- **Futura**: Promoción cuya fecha de inicio aún no ha llegado
- **Alcance**: Ámbito de aplicación (categoría o producto)
- **Sub del Día**: Promoción de precio especial en días específicos
- **2x1**: Promoción donde por cada 2 unidades, la más barata es gratis
- **Soft Delete**: Eliminación lógica (no física) de registros

---

**Documento creado**: 3 de Octubre, 2025
**Última actualización**: 3 de Octubre, 2025
**Versión**: 1.0
