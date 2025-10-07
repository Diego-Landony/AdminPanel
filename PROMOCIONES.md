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

### 1. 🎁 Promoción 2x1

**Descripción**: Por cada 2 productos de la misma categoría, el cliente paga el más caro y el más barato es gratis.

#### Características:
- **Alcance**: Toda una categoría
- **Mecánica de Descuento**:
  - 2 productos → 1 gratis (el más barato)
  - 4 productos → 2 gratis (los 2 más baratos)
  - 6 productos → 3 gratis (los 3 más baratos)
  - **Fórmula**: `cantidad_gratis = floor(cantidad_total / 2)`

#### Vigencia Temporal (4 opciones):
1. **Permanente**: Activa siempre
2. **Por rango de fechas**: Del Día X al Día Y (todo el día)
3. **Por horario permanente**: Todos los días de HH:MM a HH:MM
4. **Por fecha + horario**: Del Día X al Y, de HH:MM a HH:MM

#### Restricción de Servicio:
- Ambos (Delivery + Pickup)
- Solo Delivery
- Solo Pickup

#### Reglas Especiales:
- ✅ Pueden existir múltiples 2x1 en diferentes categorías simultáneamente
- ⚠️ Cada categoría es independiente (no se mezclan productos de diferentes categorías)
- ⚠️ Se aplica DESPUÉS de los descuentos por porcentaje

---

### 2. 💯 Promoción de Porcentaje

**Descripción**: Reduce el precio de productos por un porcentaje definido.

#### Características:
- **Alcance** (2 niveles):
  - **Categoría completa**: Todos los productos de una categoría
  - **Producto individual**: Solo un producto específico

- **Porcentaje**: Valor entre 1% y 100%

#### Vigencia Temporal (4 opciones):
1. **Permanente**: Activa siempre
2. **Por rango de fechas**: Del Día X al Día Y (todo el día)
3. **Por horario permanente**: Todos los días de HH:MM a HH:MM
4. **Por fecha + horario**: Del Día X al Y, de HH:MM a HH:MM

#### Restricción de Servicio:
- Ambos (Delivery + Pickup)
- Solo Delivery
- Solo Pickup

#### Reglas Especiales:
- ✅ Pueden coexistir múltiples promociones de porcentaje en la misma categoría con diferentes vigencias
- ⚠️ Si un producto tiene descuento individual Y su categoría tiene descuento → **se aplica el mayor**
- ⚠️ Se aplica ANTES del 2x1

---

### 3. 🌟 Sub del Día

**Descripción**: Un producto tiene un precio especial fijo en días específicos de la semana.

#### Características:
- **Alcance**: Un producto específico
- **Precios Especiales** (montos fijos):
  - Precio especial para Capital (aplica a pickup y delivery)
  - Precio especial para Interior (aplica a pickup y delivery)
  - Los precios aplican a **todas las variantes** del producto

#### Vigencia Temporal:
- **Días de la semana**: Selección mediante checkboxes
  - Lunes, Martes, Miércoles, Jueves, Viernes, Sábado, Domingo
  - Puede ser 1 solo día, varios días, o todos los días

#### Restricción de Servicio:
- Ambos (Delivery + Pickup)
- Solo Delivery
- Solo Pickup

#### Reglas Especiales:
- ✅ Pueden existir múltiples "subs del día" en diferentes categorías simultáneamente
- ⚠️ Un producto solo puede tener UN conjunto de precios especiales (mismo precio para todos los días seleccionados)
- ⚠️ No puede tener diferentes precios para diferentes días
- ⚠️ El precio especial **reemplaza** el precio base antes de aplicar otros descuentos

---

## Reglas de Negocio

### 1. Jerarquía de Aplicación de Promociones

```
ORDEN DE APLICACIÓN (de primero a último):

1. Sub del Día
   └─> Si aplica: REEMPLAZA el precio base del producto

2. Descuento de Porcentaje Individual
   └─> Si aplica: Calcula descuento sobre precio actual

3. Descuento de Porcentaje de Categoría
   └─> Compara con descuento individual
   └─> Aplica el MAYOR de los dos

4. Promoción 2x1
   └─> Ordena productos por precio (ya con descuentos aplicados)
   └─> Descuenta los N más baratos
```

### 2. Resolución de Conflictos

#### Conflicto: Producto con descuento individual + Categoría con descuento
**Resolución**: Se aplica el descuento MAYOR de los dos, no se suman.

**Ejemplo**:
- Producto: 20% de descuento individual
- Categoría: 15% de descuento
- **Resultado**: Se aplica 20%

#### Conflicto: Sub del Día + Descuento de Porcentaje
**Resolución**: El Sub del Día reemplaza el precio base, luego se aplica el descuento de porcentaje sobre ese nuevo precio.

**Ejemplo**:
- Precio normal: $100
- Sub del día: $80
- Descuento de categoría: 10%
- **Cálculo**: $80 - (10% de $80) = $72

#### Conflicto: Múltiples promociones de porcentaje vigentes en la misma categoría
**Resolución**: Se aplica el porcentaje MAYOR.

**Ejemplo**:
- Promoción A: 15% vigente todo enero
- Promoción B: 20% vigente del 10 al 20 de enero
- **Resultado el 15 de enero**: Se aplica 20% (la mayor)

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

### Entidad: Promotion

```
PROMOTION
│
├─ IDENTIFICACIÓN
│  ├─ id (PK)
│  ├─ name (string, requerido)
│  └─ description (text, opcional)
│
├─ TIPO Y ALCANCE
│  ├─ type (enum: '2x1', 'percentage', 'daily_special')
│  ├─ scope_type (enum: 'category', 'product')
│  ├─ category_id (FK, nullable)
│  └─ product_id (FK, nullable)
│
├─ CONFIGURACIÓN POR TIPO
│  ├─ discount_percentage (decimal, nullable)
│  │  └─ Solo si type = 'percentage'
│  │
│  ├─ special_price_capital (decimal, nullable)
│  └─ special_price_interior (decimal, nullable)
│     └─ Solo si type = 'daily_special'
│
├─ RESTRICCIONES
│  └─ service_type (enum: 'both', 'delivery_only', 'pickup_only')
│
├─ VIGENCIA TEMPORAL
│  ├─ validity_type (enum: 'permanent', 'date_range', 'time_range', 'date_time_range', 'weekdays')
│  ├─ start_date (date, nullable)
│  ├─ end_date (date, nullable)
│  ├─ start_time (time, nullable)
│  ├─ end_time (time, nullable)
│  └─ weekdays (json, nullable)
│     └─ Ejemplo: [1,2,3,4,5] para Lunes a Viernes
│
├─ ESTADO
│  └─ is_active (boolean, default: true)
│
└─ AUDITORÍA
   ├─ created_at (timestamp)
   ├─ updated_at (timestamp)
   └─ deleted_at (timestamp, nullable)
      └─ Soft deletes para mantener historial
```

### Relaciones:

```
Promotion ─┬─> Category (belongsTo, nullable)
           └─> Product (belongsTo, nullable)

Category ──> Promotion (hasMany)
Product ───> Promotion (hasMany)
```

### Validaciones de Integridad:

1. **Alcance**:
   - Si `type = '2x1'` → `scope_type` debe ser `'category'`
   - Si `type = 'percentage'` → `scope_type` puede ser `'category'` o `'product'`
   - Si `type = 'daily_special'` → `scope_type` debe ser `'product'`

2. **Campos Requeridos por Tipo**:
   - Si `type = 'percentage'` → `discount_percentage` es requerido
   - Si `type = 'daily_special'` → `special_price_capital` y `special_price_interior` son requeridos

3. **Vigencia**:
   - Si `validity_type = 'date_range'` → `start_date` y `end_date` son requeridos
   - Si `validity_type = 'time_range'` → `start_time` y `end_time` son requeridos
   - Si `validity_type = 'date_time_range'` → todos los campos de fecha y hora son requeridos
   - Si `validity_type = 'weekdays'` → `weekdays` es requerido

4. **Fechas**:
   - `end_date` debe ser mayor o igual a `start_date`
   - `end_time` debe ser mayor a `start_time`

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

### Caso 2: Happy Hour con 15% en Pizzas

**Objetivo**: 15% de descuento en pizzas de 2pm a 5pm todos los días.

**Configuración**:
- Nombre: "Happy Hour Pizzas"
- Tipo: Porcentaje (15%)
- Alcance: Categoría "Pizzas"
- Vigencia: Por horario permanente (14:00 a 17:00)
- Servicio: Delivery y Pickup

**Comportamiento**:
- Cliente ordena Pizza Margarita ($100) a las 3pm
- Sistema aplica 15% de descuento
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

### Caso 5: Sub del Día + Descuento de Categoría

**Escenario**:
- Producto "Hamburguesa" es Sub del Día ($50)
- Categoría "Hamburguesas" tiene 20% de descuento
- Cliente ordena un día que aplica ambas

**Flujo de Cálculo**:
1. Precio base: $70
2. Sub del día: reemplaza a $50
3. Aplicar 20% sobre $50: $50 - $10 = $40
4. **Total: $40** (ahorro: $30)

---

### Caso 6: Múltiples Descuentos de Porcentaje

**Escenario**:
- Promoción A: 15% en categoría "Pizzas" (todo enero)
- Promoción B: 25% en categoría "Pizzas" (del 10 al 20 de enero)
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
