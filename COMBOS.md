# Sistema de Combos - Documentación Conceptual

## Índice
1. [Visión General](#visión-general)
2. [Concepto de Combo](#concepto-de-combo)
3. [Reglas de Negocio](#reglas-de-negocio)
4. [Estructura de Datos](#estructura-de-datos)
5. [Flujo de Aplicación](#flujo-de-aplicación)
6. [Interfaz de Usuario](#interfaz-de-usuario)
7. [Casos de Uso](#casos-de-uso)
8. [Validaciones](#validaciones)

---

## Visión General

El sistema de combos permite crear y gestionar productos compuestos permanentes que agrupan múltiples productos individuales bajo un precio especial. Los combos son productos del menú que ofrecen un descuento al comprar varios productos juntos.

### Características Principales
- ✅ Productos compuestos permanentes (NO son promociones temporales)
- ✅ Precio único para el combo completo (Capital/Interior, Pickup/Delivery)
- ✅ Herencia automática de personalización de productos individuales
- ✅ Agrupación flexible de productos de diferentes categorías
- ✅ Cálculo automático de extras por personalización
- ✅ Gestión independiente de productos del menú
- ✅ Sistema de activación/desactivación

### Diferencia con Promociones

| Característica | Combos | Promociones |
|----------------|--------|-------------|
| **Permanencia** | Permanentes en el menú | Temporales con vigencia |
| **Propósito** | Producto compuesto con precio especial | Descuento sobre productos existentes |
| **Ubicación** | `/menu/combos` | `/menu/promotions` |
| **Personalización** | Hereda de productos individuales | N/A |
| **Precio** | Precio fijo del combo + extras | Descuento sobre precio base |

---

## Concepto de Combo

### 📦 ¿Qué es un Combo?

Un combo es un **producto compuesto permanente** que agrupa varios productos del menú bajo un precio especial. Funciona como un producto más del menú, pero en lugar de ser un ítem individual, es una **agrupación de productos**.

### 🎯 Filosofía del Sistema

**Principio Fundamental**: Un combo **NO copia** la información de los productos, **REFERENCIA** a ellos.

```
COMBO = Agrupación de referencias a productos + Precio especial del combo

┌─────────────────────────────────────────┐
│  Combo "2 Subs + 2 Bebidas"             │
│  Precio: $150                           │
├─────────────────────────────────────────┤
│  Items:                                 │
│  ├─ Producto: Sub de Pollo (referencia)│
│  ├─ Producto: Sub de Res (referencia)  │
│  ├─ Producto: Coca Cola (referencia)   │
│  └─ Producto: Pepsi (referencia)       │
└─────────────────────────────────────────┘

Cada producto CONSERVA sus secciones de personalización
```

### ✅ Ventajas de la Referencia vs Copia

1. **Actualización automática**: Si cambias las secciones del "Sub de Pollo" → se refleja automáticamente en todos los combos que lo incluyen
2. **Sin duplicación de datos**: Las secciones existen en UN solo lugar (el producto)
3. **Mantenimiento simple**: Cambias una vez, se actualiza en todos lados
4. **Consistencia**: El producto se comporta igual dentro o fuera del combo

### 🎨 Ejemplo Conceptual

```
Producto Individual: "Sub de Pollo"
├─ Precio normal: $70
├─ Secciones de personalización:
│   ├─ Vegetales (requerido, múltiple)
│   │   ├─ Lechuga (gratis)
│   │   ├─ Tomate (gratis)
│   │   └─ Cebolla ($5 extra)
│   └─ Salsas (opcional, múltiple)
│       ├─ Mayo (gratis)
│       ├─ Mostaza (gratis)
│       └─ BBQ ($3 extra)

Combo: "2 Subs Clásicos"
├─ Precio del combo: $120
├─ Items:
│   ├─ Item 1: Sub de Pollo (HEREDA todas sus secciones)
│   └─ Item 2: Sub de Res (HEREDA todas sus secciones)
```

**Si el cliente personaliza**:
- Sub de Pollo: + Cebolla ($5) + BBQ ($3) = $8 extras
- Sub de Res: + Cebolla ($5) = $5 extras
- **Precio final del combo**: $120 + $8 + $5 = $133

---

## Reglas de Negocio

### 1. Herencia de Personalización

#### Regla Fundamental:
**Los combos heredan TODA la personalización de los productos que contienen, sin modificaciones.**

```
SI producto tiene secciones de personalización
ENTONCES combo permite personalizarlo igual que el producto individual
```

#### Implicaciones:

✅ **Permitido**:
- Cliente puede personalizar cada producto del combo
- Cada personalización agrega su costo individual al total
- Las secciones requeridas siguen siendo requeridas
- Las opciones con `price_modifier` siguen agregando al precio

❌ **NO Permitido**:
- Desactivar personalización a nivel combo
- Redefinir secciones específicas para el combo
- Cambiar reglas de personalización (is_required, allow_multiple)

#### Ejemplo de Validación:

```
Combo "2 Subs + Bebida"
├─ Item 1: Sub de Pollo
│   └─ Sección "Vegetales" (is_required=true) → Cliente DEBE seleccionar
├─ Item 2: Sub de Res
│   └─ Sección "Vegetales" (is_required=true) → Cliente DEBE seleccionar
└─ Item 3: Coca Cola
    └─ Sin secciones → No requiere personalización
```

### 2. Estructura de Precios

#### Precio Base del Combo:
Los combos tienen **4 precios base** (como los productos individuales):

- **Precio Capital - Pickup**: Para pedidos pickup en zona capital
- **Precio Capital - Delivery**: Para pedidos delivery en zona capital
- **Precio Interior - Pickup**: Para pedidos pickup en zona interior
- **Precio Interior - Delivery**: Para pedidos delivery en zona interior

#### Precio Final = Precio Base + Extras:

```
Precio Final del Combo = precio_base_combo + sum(todos los extras de personalizaciones)

Donde:
- precio_base_combo = según zona (capital/interior) y servicio (pickup/delivery)
- extras = sum de price_modifier de todas las opciones seleccionadas donde is_extra=true
```

#### Ejemplo de Cálculo Completo:

```
Combo: "2 Subs Clásicos"
Precio base (Capital-Delivery): $150

Items del combo:
├─ Sub de Pollo
│   Personalizaciones seleccionadas:
│   ├─ Lechuga (gratis)
│   ├─ Tomate (gratis)
│   ├─ Cebolla (is_extra=true, price_modifier=$5)
│   └─ BBQ (is_extra=true, price_modifier=$3)
│   Subtotal extras: $8
│
└─ Sub de Res
    Personalizaciones seleccionadas:
    ├─ Lechuga (gratis)
    ├─ Tomate (gratis)
    └─ Queso Extra (is_extra=true, price_modifier=$10)
    Subtotal extras: $10

CÁLCULO FINAL:
Precio base: $150
Extras Sub 1: +$8
Extras Sub 2: +$10
─────────────────
TOTAL: $168
```

### 3. Items del Combo

#### Características de Items:

Cada item en un combo representa:
- **UNA referencia a un producto existente**
- **Una cantidad** (por defecto 1, puede ser más)
- **Un label descriptivo** (para distinguir productos repetidos)
- **Un orden de visualización** (sort_order)

#### Productos Repetidos:

✅ **Permitido**: Mismo producto múltiples veces con diferentes labels

```
Combo "4 Empanadas Mixtas"
├─ Item 1: Empanada de Carne (label: "Empanada 1")
├─ Item 2: Empanada de Carne (label: "Empanada 2")
├─ Item 3: Empanada de Pollo (label: "Empanada 3")
└─ Item 4: Empanada de Pollo (label: "Empanada 4")

Cada empanada se personaliza individualmente
```

#### Validación de Items:

- ✅ Mínimo 2 productos en un combo
- ✅ es posible tener productos repetidos en un combo.
- ✅ Todos los productos deben estar activos
- ✅ No puede haber items sin producto asignado

### 4. Interacción con Promociones

#### Regla de Aplicación:
**Los combos son inmunes a promociones individuales de productos.**
```
SI cliente ordena un combo
ENTONCES:
  - NO se aplican descuentos de porcentaje de productos individuales
  - NO se aplican Sub del Día de productos individuales
  - NO se aplican 2x1 (los combos no cuentan para 2x1 de categorías)
  - El precio del combo es FIJO + extras de personalización
```

#### Excepción: Descuentos sobre Combos

En el futuro, se podría crear promociones que apliquen directamente sobre combos:
- Ejemplo: "20% descuento en Combo Familiar los domingos"
- Esto requeriría extensión del sistema de promociones (no está en alcance actual)

### 5. Estados del Combo

#### Estado Activo/Inactivo:

- **Activo** (`is_active = true`): Se muestra en el menú, se puede ordenar
- **Inactivo** (`is_active = false`): Oculto del menú, no se puede ordenar

#### Validación de Disponibilidad:

```
Un combo está DISPONIBLE cuando:
1. is_active = true
2. TODOS los productos del combo están activos (product.is_active = true)
3. TODOS los productos del combo existen (no fueron eliminados)
```

**Comportamiento automático**:
- Si un producto del combo se desactiva → el combo se marca automáticamente como no disponible
- Si un producto del combo se elimina (soft delete) → el combo se marca automáticamente como no disponible
- Se muestra advertencia en el admin si un combo tiene productos inactivos

---

## Estructura de Datos

### Arquitectura: Sistema de Dos Niveles

El sistema utiliza una arquitectura **Combo → Items → Productos (por referencia)**.

#### 📦 Nivel 1: Combo (Contenedor)

Representa el combo completo con:
- **Identificación**: Nombre, slug, descripción, imagen
- **Precios**: 4 precios (Capital/Interior × Pickup/Delivery)
- **Estado**: Activo/Inactivo
- **Configuración**: Orden de visualización

```
COMBO
│
├─ Nombre: "Combo Familiar"
├─ Slug: "combo-familiar"
├─ Descripción: "2 Subs grandes + 2 bebidas + papas"
├─ Imagen: "/storage/combos/combo-familiar.jpg"
├─ Precios:
│   ├─ Capital Pickup: $200
│   ├─ Capital Delivery: $220
│   ├─ Interior Pickup: $180
│   └─ Interior Delivery: $200
├─ Estado: Activo
└─ Orden: 1
```

#### 🎯 Nivel 2: Items del Combo (Referencias a Productos)

Cada item representa **UNA referencia a un producto**:
- Producto al que hace referencia (product_id)
- Cantidad (quantity)
- Label descriptivo para UI
- Orden de visualización

```
ITEM 1
├─ Producto: "Sub de Pollo" (REFERENCIA, NO COPIA)
├─ Cantidad: 1
├─ Label: "Sub Principal"
└─ Orden: 1

ITEM 2
├─ Producto: "Sub de Res" (REFERENCIA, NO COPIA)
├─ Cantidad: 1
├─ Label: "Sub Secundario"
└─ Orden: 2

ITEM 3
├─ Producto: "Coca Cola 500ml" (REFERENCIA, NO COPIA)
├─ Cantidad: 2
├─ Label: "Bebidas"
└─ Orden: 3
```

### 🎨 Ejemplo Completo de Arquitectura

```
┌───────────────────────────────────────────────────────────┐
│ COMBO: "Combo Familiar"                                   │
│ Slug: combo-familiar                                      │
│ Estado: Activo                                            │
│ Precios:                                                  │
│ • Capital Pickup: $200 | Capital Delivery: $220          │
│ • Interior Pickup: $180 | Interior Delivery: $200        │
├───────────────────────────────────────────────────────────┤
│                                                           │
│ ┌─────────────────────────────────────────────────────┐ │
│ │ ITEM 1: Sub de Pollo (referencia)                   │ │
│ │ • Cantidad: 1                                       │ │
│ │ • Label: "Sub Principal"                            │ │
│ │ • Hereda: Todas las secciones del producto         │ │
│ │   - Vegetales (requerido)                           │ │
│ │   - Salsas (opcional)                               │ │
│ │   - Quesos (opcional, con extras)                   │ │
│ └─────────────────────────────────────────────────────┘ │
│                                                           │
│ ┌─────────────────────────────────────────────────────┐ │
│ │ ITEM 2: Sub de Res (referencia)                     │ │
│ │ • Cantidad: 1                                       │ │
│ │ • Label: "Sub Secundario"                           │ │
│ │ • Hereda: Todas las secciones del producto         │ │
│ │   - Vegetales (requerido)                           │ │
│ │   - Salsas (opcional)                               │ │
│ │   - Quesos (opcional, con extras)                   │ │
│ └─────────────────────────────────────────────────────┘ │
│                                                           │
│ ┌─────────────────────────────────────────────────────┐ │
│ │ ITEM 3: Coca Cola 500ml (referencia)                │ │
│ │ • Cantidad: 2                                       │ │
│ │ • Label: "Bebidas"                                  │ │
│ │ • Hereda: Sin secciones (bebida simple)            │ │
│ └─────────────────────────────────────────────────────┘ │
│                                                           │
│ ┌─────────────────────────────────────────────────────┐ │
│ │ ITEM 4: Papas Fritas (referencia)                   │ │
│ │ • Cantidad: 1                                       │ │
│ │ • Label: "Acompañamiento"                           │ │
│ │ • Hereda: Todas las secciones del producto         │ │
│ │   - Tamaño (requerido)                              │ │
│ │   - Salsas (opcional, con extras)                   │ │
│ └─────────────────────────────────────────────────────┘ │
└───────────────────────────────────────────────────────────┘
```

### ✅ Ventajas de esta Arquitectura

- ✅ **DRY Principle**: Información de productos existe en un solo lugar
- ✅ **Actualización automática**: Cambios en productos se reflejan en combos
- ✅ **Simplicidad**: No duplica lógica de secciones
- ✅ **Mantenibilidad**: Modificas el producto una vez, se actualiza en todos los combos
- ✅ **Consistencia**: Producto se comporta igual dentro y fuera del combo
- ✅ **Escalabilidad**: Fácil agregar nuevos combos sin tocar estructura de productos

### 🔗 Relaciones Conceptuales

```
UN Combo ──tiene──> MUCHOS Items
UN Item ──pertenece a──> UN Combo
UN Item ──referencia a──> UN Producto (NO copia)
UN Producto ──tiene──> MUCHAS Secciones (N:N)
UNA Sección ──tiene──> MUCHAS Opciones (1:N)
```

### ✏️ Validaciones de Integridad

#### 1. Items del Combo:
- Un combo debe tener mínimo 2 items
- Un combo puede tener máximo 10 items
- Cada item debe referenciar un producto válido y activo
- Un mismo producto puede aparecer múltiples veces (con labels diferentes)

#### 2. Precios del Combo:
- Todos los 4 precios son requeridos
- Todos los precios deben ser mayores a 0
- Los precios de delivery deben ser >= precios de pickup (misma zona)

#### 3. Disponibilidad:
- Un combo solo está disponible si TODOS sus productos están activos
- Si un producto se desactiva/elimina, el combo se marca como no disponible

---

## Flujo de Aplicación

### 1. Flujo en el Carrito de Compras

```
INICIO: Usuario selecciona un combo en el menú
│
├─> PASO 1: Cargar combo con sus items
│   └─ Eager load: combo.items.product.sections.options
│
├─> PASO 2: Obtener precio base del combo
│   ├─ Detectar zona del pedido (capital/interior)
│   ├─ Detectar tipo de servicio (pickup/delivery)
│   └─ Seleccionar precio correspondiente
│       Ejemplo: Capital + Delivery → precio_domicilio_capital
│
├─> PASO 3: Para cada item del combo:
│   │
│   ├─ Cargar producto con sus secciones
│   │
│   ├─ Mostrar UI de personalización (si tiene secciones)
│   │   ├─ Mostrar secciones requeridas (is_required=true)
│   │   ├─ Mostrar secciones opcionales (is_required=false)
│   │   └─ Marcar opciones con precio extra (is_extra=true)
│   │
│   └─ Esperar selección del cliente
│
├─> PASO 4: Validar selecciones
│   │
│   └─ Para cada producto del combo:
│       ├─ Verificar que secciones requeridas tengan selección
│       ├─ Verificar min_selections y max_selections
│       └─ Si falla → mostrar error, no permitir agregar al carrito
│
├─> PASO 5: Calcular precio total del combo
│   │
│   ├─ precio_total = precio_base_combo
│   │
│   └─ Para cada item del combo:
│       └─ Para cada sección del producto:
│           └─ Para cada opción seleccionada:
│               └─ Si opcion.is_extra = true:
│                   └─ precio_total += opcion.price_modifier
│
├─> PASO 6: Agregar combo al carrito
│   └─ Guardar:
│       ├─ combo_id
│       ├─ precio_base
│       ├─ precio_total (con extras)
│       └─ personalizaciones (JSON con todas las selecciones)
│
└─> RESULTADO FINAL: Combo agregado al carrito con personalización completa
```

### 2. Algoritmo de Cálculo de Precio

```
FUNCIÓN: calcularPrecioCombo(combo, zona, tipo_servicio, personalizaciones)
│
├─ PASO 1: Obtener precio base según zona y servicio
│   │
│   ├─ Si zona = 'capital' AND tipo_servicio = 'pickup':
│   │   └─ precio_base = combo.precio_pickup_capital
│   │
│   ├─ Si zona = 'capital' AND tipo_servicio = 'delivery':
│   │   └─ precio_base = combo.precio_domicilio_capital
│   │
│   ├─ Si zona = 'interior' AND tipo_servicio = 'pickup':
│   │   └─ precio_base = combo.precio_pickup_interior
│   │
│   └─ Si zona = 'interior' AND tipo_servicio = 'delivery':
│       └─ precio_base = combo.precio_domicilio_interior
│
├─ PASO 2: Inicializar acumulador de extras
│   └─ total_extras = 0
│
├─ PASO 3: Por cada item del combo
│   │
│   └─ Por cada personalización del item
│       │
│       └─ Si opcion.is_extra = true:
│           └─ total_extras += opcion.price_modifier
│
├─ PASO 4: Calcular precio final
│   └─ precio_final = precio_base + total_extras
│
└─ RETORNAR precio_final
```

### 3. Algoritmo de Validación de Disponibilidad

```
FUNCIÓN: esComboDisponible(combo)
│
├─ VALIDACIÓN 1: Estado Activo del Combo
│  └─ Si combo.is_active = false → RETORNAR false
│
├─ VALIDACIÓN 2: Productos Activos
│  │
│  └─ Para cada item del combo:
│      ├─ Si item.product = null → RETORNAR false (producto eliminado)
│      └─ Si item.product.is_active = false → RETORNAR false
│
└─ RETORNAR true (pasó todas las validaciones)
```

### 4. Carga de Datos Eficiente (Eager Loading)

```
Al listar combos en el menú:

Combos::with([
    'items.product.sections.options'
])->where('is_active', true)->get()

Esto precarga:
- Los items del combo
- Los productos referenciados por cada item
- Las secciones de cada producto
- Las opciones de cada sección

Evita el problema N+1 de consultas
```

---

## Interfaz de Usuario

### 1. Página Principal de Combos

**Ruta**: `/menu/combos`

**Elementos**:

#### Header:
- Título: "🍔 Combos"
- Botón: "+ Nuevo Combo"

#### Estadísticas (Cards superiores):
```
┌──────────────────┬──────────────────┬──────────────────┐
│ Total Combos     │ Combos Activos   │ Combos Inactivos │
│      15          │        12        │         3        │
└──────────────────┴──────────────────┴──────────────────┘
```

#### Filtros:
- Estado (Dropdown): Todos / Activos / Inactivos
- Búsqueda: Por nombre

#### Listado (DataTable):
Cada combo muestra:

| Imagen | Nombre | Items | Precio Capital | Precio Interior | Estado | Acciones |
|--------|--------|-------|----------------|-----------------|--------|----------|
| [IMG]  | Combo Familiar | 4 productos | $200 - $220 | $180 - $200 | 🟢 Activo | [⋮] |
| [IMG]  | 2 Subs Clásicos | 2 productos | $120 - $130 | $110 - $120 | 🟢 Activo | [⋮] |

**Columnas**:
- Imagen: Thumbnail del combo
- Nombre: Nombre descriptivo
- Items: Cantidad de productos en el combo
- Precio Capital: Rango pickup-delivery
- Precio Interior: Rango pickup-delivery
- Estado: Badge verde (activo) o rojo (inactivo)
- Acciones: Menú contextual

#### Menú Contextual (⋮):
- Editar
- Ver Detalle
- Duplicar
- Activar/Desactivar
- Eliminar

---

### 2. Formulario Crear Combo

**Ruta**: `/menu/combos/create`

**Secciones del Formulario**:

#### Sección 1: Información Básica

```
┌─────────────────────────────────────────────────────┐
│ Información Básica                                  │
├─────────────────────────────────────────────────────┤
│                                                     │
│ Nombre del Combo *                                  │
│ [________________________________]                  │
│ ej: Combo Familiar, 2 Subs Clásicos                │
│                                                     │
│ Descripción (opcional)                              │
│ [________________________________]                  │
│ [________________________________]                  │
│ [________________________________]                  │
│                                                     │
│ Imagen del Combo                                    │
│ [Seleccionar imagen] [Vista previa]                │
│                                                     │
└─────────────────────────────────────────────────────┘
```

#### Sección 2: Items del Combo

```
┌─────────────────────────────────────────────────────┐
│ Items del Combo (mínimo 2, máximo 10) *            │
├─────────────────────────────────────────────────────┤
│                                                     │
│ ┌─────────────────────────────────────────────┐   │
│ │ Item 1                              [✕]     │   │
│ │                                             │   │
│ │ Producto *                                  │   │
│ │ [Buscar producto... ▼]                     │   │
│ │                                             │   │
│ │ Label *                                     │   │
│ │ [_____________________________]            │   │
│ │ ej: Sub Principal, Bebida 1                │   │
│ │                                             │   │
│ │ Cantidad *                                  │   │
│ │ [1 ▼]                                      │   │
│ └─────────────────────────────────────────────┘   │
│                                                     │
│ ┌─────────────────────────────────────────────┐   │
│ │ Item 2                              [✕]     │   │
│ │ ...                                         │   │
│ └─────────────────────────────────────────────┘   │
│                                                     │
│ [+ Agregar Item]                                   │
│                                                     │
│ Nota: Las secciones de personalización se          │
│ heredan automáticamente de cada producto            │
│                                                     │
└─────────────────────────────────────────────────────┘
```

**Comportamiento del selector de productos**:
- Combobox con búsqueda
- Muestra productos activos
- Permite seleccionar el mismo producto múltiples veces
- Al seleccionar, muestra badge si el producto tiene personalización

#### Sección 3: Precios del Combo

```
┌─────────────────────────────────────────────────────┐
│ Precios del Combo *                                 │
├─────────────────────────────────────────────────────┤
│                                                     │
│ Zona Capital                                        │
│ ├─ Pickup:    $ [________]                         │
│ └─ Delivery:  $ [________]                         │
│                                                     │
│ Zona Interior                                       │
│ ├─ Pickup:    $ [________]                         │
│ └─ Delivery:  $ [________]                         │
│                                                     │
│ ℹ️ Estos precios NO incluyen extras de             │
│    personalización. Los extras se calculan          │
│    automáticamente según las opciones del cliente.  │
│                                                     │
└─────────────────────────────────────────────────────┘
```

**Validación en tiempo real**:
- Delivery >= Pickup (misma zona)
- Todos los precios > 0

#### Sección 4: Calculadora de Referencia (Opcional)

```
┌─────────────────────────────────────────────────────┐
│ 💡 Calculadora de Precio Sugerido                  │
├─────────────────────────────────────────────────────┤
│                                                     │
│ Precio individual de productos:                    │
│ ├─ Sub de Pollo:     $70                          │
│ ├─ Sub de Res:       $70                          │
│ ├─ Coca Cola (×2):   $60                          │
│ └─ Papas Fritas:     $40                          │
│                                                     │
│ Total individual: $240                             │
│ Descuento sugerido (20%): -$48                     │
│ Precio sugerido: $192                              │
│                                                     │
│ [Aplicar precio sugerido]                          │
│                                                     │
└─────────────────────────────────────────────────────┘
```

**Comportamiento**:
- Se calcula automáticamente al agregar productos
- Sugiere 20% de descuento por defecto
- Permite aplicar o ignorar la sugerencia

#### Sección 5: Estado y Orden

```
┌─────────────────────────────────────────────────────┐
│ Configuración                                       │
├─────────────────────────────────────────────────────┤
│                                                     │
│ Estado                                              │
│ ○ Activo   ○ Inactivo                              │
│                                                     │
│ Orden de visualización                              │
│ [____] (menor número = aparece primero)            │
│                                                     │
└─────────────────────────────────────────────────────┘
```

#### Footer del Formulario:
```
[Cancelar]                           [Guardar Combo]
```

---

### 3. Formulario Editar Combo

**Ruta**: `/menu/combos/{id}/edit`

**Elementos**:

Igual que crear, con adiciones:

#### Header:
- Título: "✏️ Editar Combo: [Nombre]"
- Botón adicional: [Ver Vista Previa]

#### Validaciones especiales al editar:

```
⚠️ ADVERTENCIA: Productos Inactivos
┌─────────────────────────────────────────────────────┐
│ ⚠️ Atención                                         │
├─────────────────────────────────────────────────────┤
│ Los siguientes productos están inactivos:          │
│                                                     │
│ • Sub de Pollo (Item 1)                            │
│                                                     │
│ El combo se marcará como no disponible hasta       │
│ que reactives los productos o los reemplaces.       │
│                                                     │
│ [Reemplazar productos] [Mantener y continuar]      │
└─────────────────────────────────────────────────────┘
```

---

### 4. Modal de Vista Previa

**Trigger**: Click en "Ver Vista Previa" o en menú contextual

**Contenido**:

```
┌─────────────────────────────────────────────────────┐
│ Vista Previa del Combo                         [✕] │
├─────────────────────────────────────────────────────┤
│                                                     │
│ [Imagen del combo]                                 │
│                                                     │
│ 🍔 Combo Familiar                                  │
│ 2 Subs grandes + 2 bebidas + papas                 │
│                                                     │
│ ────────────────────────────────────────────────   │
│                                                     │
│ Incluye:                                           │
│ ✓ Sub de Pollo (Personalizable)                   │
│ ✓ Sub de Res (Personalizable)                     │
│ ✓ Coca Cola 500ml (×2)                            │
│ ✓ Papas Fritas (Personalizable)                   │
│                                                     │
│ ────────────────────────────────────────────────   │
│                                                     │
│ 💰 Precios:                                        │
│                                                     │
│ Capital                                            │
│ • Pickup:     $200                                 │
│ • Delivery:   $220                                 │
│                                                     │
│ Interior                                           │
│ • Pickup:     $180                                 │
│ • Delivery:   $200                                 │
│                                                     │
│ * Los extras de personalización se cobran aparte   │
│                                                     │
│ ────────────────────────────────────────────────   │
│                                                     │
│ 📊 Comparación de Precios                          │
│                                                     │
│ Si compras individual (Capital-Delivery):          │
│ • Sub de Pollo: $70                                │
│ • Sub de Res: $70                                  │
│ • Coca Cola (×2): $60                              │
│ • Papas: $40                                       │
│ Total: $240                                        │
│                                                     │
│ Con este combo: $220                               │
│ Ahorro: $20 (8%)                                   │
│                                                     │
│ ────────────────────────────────────────────────   │
│                                                     │
│ Estado: 🟢 Activo y Disponible                     │
│                                                     │
└─────────────────────────────────────────────────────┘
```

---

### 5. Vista de Detalle del Combo (Read-only)

**Ruta**: `/menu/combos/{id}`

Similar a la vista previa pero con más información técnica:

```
┌─────────────────────────────────────────────────────┐
│ Detalle del Combo                                   │
├─────────────────────────────────────────────────────┤
│                                                     │
│ [Imagen]                                           │
│                                                     │
│ Combo Familiar                                      │
│ Slug: combo-familiar                               │
│ Creado: 15 de Enero, 2025                          │
│ Última edición: 20 de Enero, 2025                  │
│                                                     │
│ ────────────────────────────────────────────────   │
│                                                     │
│ Items del Combo:                                   │
│                                                     │
│ 1. Sub de Pollo                                    │
│    • Label: "Sub Principal"                        │
│    • Cantidad: 1                                   │
│    • Personalización: Sí (3 secciones)            │
│    • Estado: 🟢 Activo                             │
│                                                     │
│ 2. Sub de Res                                      │
│    • Label: "Sub Secundario"                       │
│    • Cantidad: 1                                   │
│    • Personalización: Sí (3 secciones)            │
│    • Estado: 🟢 Activo                             │
│                                                     │
│ 3. Coca Cola 500ml                                 │
│    • Label: "Bebidas"                              │
│    • Cantidad: 2                                   │
│    • Personalización: No                           │
│    • Estado: 🟢 Activo                             │
│                                                     │
│ ────────────────────────────────────────────────   │
│                                                     │
│ [Editar Combo] [Duplicar] [Desactivar] [Eliminar] │
│                                                     │
└─────────────────────────────────────────────────────┘
```

---

### 6. Indicadores Visuales

#### Estados:
- 🟢 Verde: Activo y disponible (todos los productos activos)
- 🟡 Amarillo: Activo pero no disponible (productos inactivos)
- 🔴 Rojo: Inactivo

#### Badges:
- **Personalizable**: Si al menos un producto tiene secciones
- **Simple**: Si ningún producto tiene secciones
- **X items**: Cantidad de productos en el combo

---

## Casos de Uso

### Caso 1: Combo Simple sin Personalización

**Escenario**: "Combo 3 Bebidas"

**Configuración**:
- Nombre: "Combo 3 Bebidas"
- Items:
  - Item 1: Coca Cola 500ml (cantidad: 1, label: "Bebida 1")
  - Item 2: Pepsi 500ml (cantidad: 1, label: "Bebida 2")
  - Item 3: Fanta 500ml (cantidad: 1, label: "Bebida 3")
- Precio Capital-Delivery: $70
- Estado: Activo

**Precios individuales**:
- Coca Cola: $30
- Pepsi: $30
- Fanta: $30
- **Total individual**: $90

**Ahorro con combo**: $20 (22%)

**Comportamiento en el carrito**:
- Cliente selecciona "Combo 3 Bebidas"
- No hay personalización (bebidas simples)
- Precio final: $70 (sin extras)
- Se agrega directo al carrito

---

### Caso 2: Combo con Personalización Simple

**Escenario**: "2 Subs Clásicos"

**Configuración**:
- Nombre: "2 Subs Clásicos"
- Items:
  - Item 1: Sub de Pollo (label: "Sub 1")
  - Item 2: Sub de Res (label: "Sub 2")
- Precio Capital-Delivery: $120
- Estado: Activo

**Personalización de cada Sub**:
- Sección "Vegetales" (requerida, múltiple):
  - Lechuga (gratis)
  - Tomate (gratis)
  - Cebolla (is_extra=true, +$5)
- Sección "Salsas" (opcional, múltiple):
  - Mayo (gratis)
  - Mostaza (gratis)
  - BBQ (is_extra=true, +$3)

**Flujo de compra**:
1. Cliente selecciona combo
2. Sistema muestra personalización para "Sub 1":
   - Selecciona: Lechuga, Tomate, Cebolla (+$5)
   - Selecciona: Mayo, BBQ (+$3)
   - **Extras Sub 1**: $8
3. Sistema muestra personalización para "Sub 2":
   - Selecciona: Lechuga, Tomate
   - Selecciona: Mostaza
   - **Extras Sub 2**: $0
4. **Precio final**: $120 + $8 + $0 = $128

---

### Caso 3: Combo Familiar Completo

**Escenario**: "Combo Familiar Completo"

**Configuración**:
- Nombre: "Combo Familiar"
- Items:
  - Item 1: Sub Grande de Pollo (label: "Sub Principal")
  - Item 2: Sub Grande de Res (label: "Sub Secundario")
  - Item 3: Coca Cola 1L (cantidad: 2, label: "Bebidas")
  - Item 4: Papas Fritas Grande (label: "Papas")
- Precio Capital-Delivery: $250
- Estado: Activo

**Personalización**:

**Sub de Pollo**:
- Vegetales: Lechuga, Tomate, Cebolla (+$5)
- Salsas: Mayo, BBQ (+$3)
- Quesos: Queso Extra (+$10)
- **Subtotal extras**: $18

**Sub de Res**:
- Vegetales: Lechuga, Tomate
- Salsas: Mostaza
- **Subtotal extras**: $0

**Papas Fritas**:
- Tamaño: Grande (ya incluido)
- Salsas: Ketchup (gratis), Mayo BBQ (+$5)
- **Subtotal extras**: $5

**Bebidas**: Sin personalización

**Cálculo final**:
```
Precio base combo:     $250
Extras Sub 1:          +$18
Extras Sub 2:          +$0
Extras Papas:          +$5
Extras Bebidas:        +$0
──────────────────────────
TOTAL:                 $273
```

**Comparación con compra individual**:
- Sub de Pollo: $90
- Sub de Res: $90
- Coca Cola 1L (×2): $80
- Papas Fritas: $50
- **Total individual**: $310

**Con extras del ejemplo**: $310 + $23 = $333
**Con combo + extras**: $273

**Ahorro total**: $60 (18%)

---

### Caso 4: Producto Repetido con Diferentes Personalizaciones

**Escenario**: "4 Empanadas Mixtas"

**Configuración**:
- Nombre: "4 Empanadas Mixtas"
- Items:
  - Item 1: Empanada de Carne (label: "Empanada 1")
  - Item 2: Empanada de Carne (label: "Empanada 2")
  - Item 3: Empanada de Pollo (label: "Empanada 3")
  - Item 4: Empanada de Pollo (label: "Empanada 4")
- Precio Capital-Delivery: $60
- Estado: Activo

**Personalización de Empanadas**:
- Sección "Cocción" (requerida, única):
  - Al horno (gratis)
  - Frita (gratis)
- Sección "Extras" (opcional):
  - Chimichurri (+$2)
  - Queso extra (+$5)

**Flujo de compra**:
El cliente personaliza CADA empanada individualmente:

1. **Empanada 1** (Carne):
   - Cocción: Al horno
   - Extras: Chimichurri (+$2)

2. **Empanada 2** (Carne):
   - Cocción: Frita
   - Extras: Queso extra (+$5)

3. **Empanada 3** (Pollo):
   - Cocción: Al horno
   - Extras: Ninguno

4. **Empanada 4** (Pollo):
   - Cocción: Frita
   - Extras: Chimichurri (+$2)

**Cálculo**:
```
Precio base combo:    $60
Extras Empanada 1:    +$2
Extras Empanada 2:    +$5
Extras Empanada 3:    +$0
Extras Empanada 4:    +$2
─────────────────────────
TOTAL:                $69
```

---

### Caso 5: Combo con Producto Inactivo (Error)

**Escenario**: Administrador intenta activar un combo pero uno de sus productos está inactivo.

**Configuración del combo**:
- Nombre: "Combo 2 Subs"
- Items:
  - Item 1: Sub de Pollo (🟢 Activo)
  - Item 2: Sub de Jamón (🔴 Inactivo)
- Estado actual del combo: Inactivo

**Flujo**:

1. Admin intenta activar el combo
2. Sistema valida disponibilidad de productos
3. Detecta que "Sub de Jamón" está inactivo
4. **Muestra error**:

```
❌ No se puede activar el combo

El combo "Combo 2 Subs" contiene productos inactivos:
• Sub de Jamón (Item 2)

Opciones:
1. Reemplazar "Sub de Jamón" por otro producto
2. Reactivar el producto "Sub de Jamón"
3. Mantener el combo inactivo

[Reemplazar productos] [Cancelar]
```

5. Admin debe resolver el problema antes de activar el combo

---

## Validaciones

### Validaciones del Formulario

#### Campo: Nombre del Combo
- ✅ Requerido
- ✅ Máximo 255 caracteres
- ✅ Debe ser único (no puede haber dos combos con el mismo nombre)
- ⚠️ Se genera slug automático (ej: "Combo Familiar" → "combo-familiar")

#### Campo: Descripción
- ✅ Opcional
- ✅ Máximo 500 caracteres

#### Campo: Imagen
- ✅ Opcional
- ✅ Formatos permitidos: JPG, PNG, WEBP
- ✅ Tamaño máximo: 2MB
- ✅ Dimensiones recomendadas: 800×600px

#### Sección: Items del Combo

**Cantidad de items**:
- ✅ Mínimo 2 items requeridos
- ✅ Máximo 10 items permitidos
- ❌ Error si < 2: "Un combo debe tener al menos 2 productos"
- ❌ Error si > 10: "Un combo no puede tener más de 10 productos"

**Por cada item**:
- ✅ Producto requerido
- ✅ Producto debe estar activo
- ✅ Label requerido (máximo 100 caracteres)
- ✅ Cantidad mínima: 1
- ✅ Cantidad máxima: 10

**Validación de duplicados**:
- ✅ Permitido: Mismo producto múltiples veces
- ⚠️ Recomendación: Labels diferentes para productos repetidos

#### Sección: Precios

**Todos los precios son requeridos**:
- ✅ precio_pickup_capital (requerido)
- ✅ precio_domicilio_capital (requerido)
- ✅ precio_pickup_interior (requerido)
- ✅ precio_domicilio_interior (requerido)

**Validaciones de valores**:
- ✅ Deben ser números positivos
- ✅ Deben ser mayores a 0
- ✅ Máximo 2 decimales
- ⚠️ precio_domicilio >= precio_pickup (misma zona)

**Mensajes de error**:
```
❌ "El precio debe ser mayor a 0"
❌ "El precio de delivery debe ser mayor o igual al precio de pickup"
❌ "El precio debe tener máximo 2 decimales"
```

#### Campo: Estado
- ✅ Requerido
- ✅ Valores permitidos: activo, inactivo
- ⚠️ Al activar, se valida que todos los productos estén activos

#### Campo: Sort Order
- ✅ Opcional (default: 0)
- ✅ Debe ser número entero

---

### Validaciones de Negocio

#### Validación 1: Productos Activos al Activar Combo

**Regla**: No se puede activar un combo si contiene productos inactivos.

**Validación**:
```
Al intentar activar un combo:
1. Verificar que combo.is_active = true
2. Verificar que TODOS los productos de los items estén activos
3. Si algún producto está inactivo → Mostrar error

Error: "No se puede activar el combo porque contiene productos inactivos"
Detalle: Lista de productos inactivos con sus items
```

**Comportamiento automático**:
Si un combo está activo y uno de sus productos se desactiva:
- El combo NO se desactiva automáticamente
- PERO se marca como "no disponible" en el menú
- Se muestra advertencia en el listado admin
- No se puede agregar al carrito

#### Validación 2: Productos Existentes

**Regla**: Todos los items deben referenciar productos que existen.

**Validación**:
```
Al guardar un combo:
1. Para cada item:
   └─ Verificar que product_id exista en la tabla products
2. Si algún producto no existe → Error 404

Error: "El producto seleccionado no existe o fue eliminado"
```

#### Validación 3: Nombre Único

**Regla**: No pueden existir dos combos con el mismo nombre.

**Validación**:
```
Al crear/editar combo:
1. Verificar que no exista otro combo con el mismo nombre
2. Al editar, excluir el combo actual de la búsqueda
3. Si existe → Error

Error: "Ya existe un combo con el nombre '[nombre]'"
```

#### Validación 4: Slug Único

**Regla**: El slug debe ser único.

**Validación**:
```
Al crear combo:
1. Generar slug desde el nombre
2. Si ya existe, agregar sufijo numérico
   Ejemplo: "combo-familiar-2"
```

#### Validación 5: Precios Coherentes

**Regla**: Delivery >= Pickup (misma zona).

**Validación**:
```
Al guardar precios:
1. Verificar: precio_domicilio_capital >= precio_pickup_capital
2. Verificar: precio_domicilio_interior >= precio_pickup_interior
3. Si no cumple → Error

Error: "El precio de delivery debe ser mayor o igual al de pickup"
```

---

### Validaciones en Tiempo Real (Frontend)

#### Al agregar items:

**Validación de cantidad mínima**:
```tsx
if (items.length < 2) {
  showWarning("Debes agregar al menos 2 productos al combo");
  disableSubmit();
}
```

**Validación de cantidad máxima**:
```tsx
if (items.length >= 10) {
  showWarning("Has alcanzado el máximo de 10 productos");
  disableAddItemButton();
}
```

#### Al seleccionar producto:

**Mostrar badge de personalización**:
```tsx
if (product.is_customizable) {
  showBadge("Este producto tiene personalización");
}
```

#### Al ingresar precios:

**Validar coherencia de precios**:
```tsx
if (precio_domicilio < precio_pickup) {
  showError("Delivery debe ser >= Pickup");
  markFieldInvalid();
}
```

**Calculadora automática**:
```tsx
// Al agregar items, calcular precio sugerido
const totalIndividual = items.reduce((sum, item) =>
  sum + (item.product.price * item.quantity), 0
);
const descuentoSugerido = totalIndividual * 0.20; // 20%
const precioSugerido = totalIndividual - descuentoSugerido;

showSuggestion(`Precio sugerido: $${precioSugerido}`);
```

#### Al activar combo:

**Validar productos activos**:
```tsx
const productosInactivos = items.filter(item =>
  !item.product.is_active
);

if (productosInactivos.length > 0 && combo.is_active) {
  showError(
    `No puedes activar el combo porque tiene productos inactivos:
    ${productosInactivos.map(i => i.product.name).join(', ')}`
  );
  preventActivation();
}
```

---

## Consideraciones Técnicas

### Performance

#### Eager Loading:
```
Al listar combos:
Combo::with(['items.product.sections.options'])
  ->where('is_active', true)
  ->orderBy('sort_order')
  ->get()

Esto precarga en 1 query:
- Combos
- Items de cada combo
- Productos de cada item
- Secciones de cada producto
- Opciones de cada sección

Evita N+1 queries
```

#### Caché:
- Cachear lista de combos activos (invalidar al crear/editar/eliminar)
- Cachear productos con secciones (invalidar al modificar producto)
- TTL recomendado: 1 hora

#### Índices de Base de Datos:
```sql
-- Combos
INDEX(is_active)
INDEX(sort_order)
INDEX(slug) UNIQUE

-- Combo Items
INDEX(combo_id)
INDEX(product_id)
INDEX(sort_order)

-- Relaciones
FOREIGN KEY(combo_id) REFERENCES combos(id) ON DELETE CASCADE
FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE RESTRICT
```

### Seguridad

#### Autorización:
- Solo usuarios con permisos específicos pueden gestionar combos
- Permisos requeridos:
  - `menu.combos.view`: Ver listado
  - `menu.combos.create`: Crear nuevos
  - `menu.combos.edit`: Editar existentes
  - `menu.combos.delete`: Eliminar

#### Validación:
- Todos los datos se validan en FormRequest
- Sanitización de inputs (nombre, descripción)
- Validación de imágenes (tipo, tamaño)

#### Auditoría:
- Registrar quién creó cada combo (created_by)
- Registrar quién editó cada combo (updated_by)
- Timestamps automáticos (created_at, updated_at)

### Escalabilidad

#### Soft Deletes:
- Nunca eliminar físicamente los combos
- Usar `deleted_at` para soft delete
- Mantener historial de combos eliminados

#### Versionado (futuro):
- Considerar versionar combos para análisis histórico
- Útil para reportes de ventas

#### Localización (futuro):
- Preparar estructura para múltiples idiomas
- Campos traducibles: name, description

### Mantenimiento

#### Limpieza Automática:
- Job programado para detectar combos con productos inactivos
- Notificar al admin si hay combos afectados

#### Notificaciones:
- Alert en dashboard si hay combos con productos inactivos
- Email al admin cuando un combo se marca como no disponible

#### Logs:
- Registrar cambios en combos (create, update, delete)
- Registrar cuando un combo se vuelve no disponible por productos inactivos

---

## Glosario

- **Combo**: Producto compuesto permanente con precio especial
- **Item del Combo**: Referencia a un producto individual dentro del combo
- **Herencia de Personalización**: El combo usa las secciones del producto sin copiarlas
- **Precio Base**: Precio del combo SIN extras de personalización
- **Extras**: Opciones de personalización que agregan costo (is_extra=true)
- **Label**: Etiqueta descriptiva para distinguir productos en el combo
- **Disponible**: Combo activo con todos sus productos activos
- **No Disponible**: Combo activo pero con productos inactivos
- **Soft Delete**: Eliminación lógica (no física) de registros

---

**Documento creado**: [Fecha de hoy]
**Última actualización**: [Fecha de hoy]
**Versión**: 1.0
