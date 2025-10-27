# Sistema de Combos - Grupos de Elección
## Documentación de Implementación Conceptual

---

## ⚠️ ALCANCE DE ESTA IMPLEMENTACIÓN

**IMPORTANTE**: Esta documentación cubre **EXCLUSIVAMENTE** la implementación del lado **ADMIN** (creación y gestión de combos con grupos de elección).

### ✅ Lo que se implementará AHORA:
- Interfaz admin para crear combos con grupos de elección
- Backend para almacenar y gestionar grupos de opciones
- Validaciones de creación y edición
- Estructura de base de datos completa

### ⏳ Lo que se implementará DESPUÉS:
- **Interfaz del cliente** para hacer pedidos de combos con elección
- Flujo de selección en la app/web del cliente
- Integración con carrito de compras del cliente

### 🎯 Objetivo Actual:
Crear la funcionalidad completa en el **panel de administración** para que, cuando se desarrolle el lado del cliente, toda la estructura y lógica ya esté lista y funcional.

---

## Índice
1. [Visión General](#visión-general)
2. [Problema Actual](#problema-actual)
3. [Solución Propuesta](#solución-propuesta)
4. [Conceptos Fundamentales](#conceptos-fundamentales)
5. [Tipos de Items en Combos](#tipos-de-items-en-combos)
6. [Casos de Uso](#casos-de-uso)
7. [Reglas de Negocio](#reglas-de-negocio)
8. [Interfaz de Usuario](#interfaz-de-usuario)
9. [Flujo del Cliente](#flujo-del-cliente)
10. [Cálculo de Precios](#cálculo-de-precios)
11. [Validaciones](#validaciones)
12. [Compatibilidad](#compatibilidad)

---

## Visión General

Esta extensión al sistema de combos permite crear **items con múltiples opciones**, donde el cliente puede **elegir** entre varios productos en lugar de tener un producto fijo.

### Ejemplo Real

**Antes (Sistema Actual):**
```
Combo Personal: Q48
├─ Item 1: Italian B.M.T. 15cm (FIJO)
├─ Item 2: Bebida 1 (FIJO)
└─ Item 3: Papas Lays (FIJO)
```

**Después (Sistema Extendido):**
```
Combo Personal: Q48
├─ Item 1: "Sub de 15cm a elección" (GRUPO CON 8 OPCIONES)
│   ├─ Opción: Italian B.M.T. 15cm
│   ├─ Opción: Pollo Teriyaki 15cm
│   ├─ Opción: Atún 15cm
│   ├─ Opción: Pavo 15cm
│   ├─ Opción: Jamón 15cm
│   ├─ Opción: Vegetariano 15cm
│   ├─ Opción: Roast Beef 15cm
│   └─ Opción: Club 15cm
│
├─ Item 2: "Bebida" (GRUPO CON 4 OPCIONES)
│   ├─ Opción: Bebida 1
│   ├─ Opción: Bebida 2
│   ├─ Opción: Bebida 3
│   └─ Opción: Bebida 4
│
└─ Item 3: "Complemento" (GRUPO CON 3 OPCIONES)
    ├─ Opción: Papas Lays
    ├─ Opción: Galleta Chocolate Chip
    └─ Opción: Galleta Avena
```

El cliente **elige 1 opción de cada grupo** al armar su combo.

---

## Problema Actual

### Limitación del Sistema Actual

El sistema actual solo permite **items fijos**:

```
✅ Puedo agregar: "Italian B.M.T. 15cm"
❌ NO puedo: "Elige cualquier sub de 15cm"
```

### Impacto en Experiencia del Usuario

**Descripción vs Realidad:**
- Descripción del combo dice: "Sub de 15cm a elección"
- Realidad en sistema: Solo Italian B.M.T. disponible
- **Cliente frustrado**: No puede elegir otros subs

### Problemas para el Negocio

1. **Falta de Flexibilidad**: Cada combinación requiere un combo diferente
   - Combo con Italian B.M.T.
   - Combo con Pollo Teriyaki
   - Combo con Atún
   - ... (10+ combos para una misma estructura)

2. **Mantenimiento Complejo**: Cambiar precio = actualizar 10+ combos

3. **Experiencia Pobre**: Cliente no percibe el valor de "elección"

---

## Solución Propuesta

### Concepto: Grupos de Elección

Permitir que un item del combo sea un **"Grupo de Elección"** en lugar de un producto fijo.

```
Item del Combo = Grupo de Elección
├─ Etiqueta: "Sub de 15cm a elección"
├─ Cantidad: 1
├─ Mínimo a elegir: 1
├─ Máximo a elegir: 1
└─ Opciones disponibles:
    ├─ Producto A
    ├─ Producto B
    ├─ Producto C
    └─ Producto D
```

### Filosofía

**"Uno de muchos, no uno fijo"**

El administrador define:
- ✅ QUÉ productos están disponibles en el grupo
- ✅ CUÁNTOS debe elegir el cliente (1, 2, o más)
- ✅ CÓMO se etiqueta el grupo

El cliente decide:
- ✅ CUÁL producto específico quiere

---

## Conceptos Fundamentales

### 1. Item Fijo vs Item de Elección

#### Item Fijo (Sistema Actual)
```
Tipo: Fijo
Producto: Italian B.M.T. 15cm
Variante: 15cm
Cantidad: 1

→ Cliente recibe exactamente esto
```

#### Item de Elección (Sistema Nuevo)
```
Tipo: Grupo de Elección
Etiqueta: "Bebida a elección"
Cantidad: 1
Elige: 1 de 4 opciones

Opciones:
├─ Bebida 1
├─ Bebida 2
├─ Bebida 3
└─ Bebida 4

→ Cliente elige UNO de estos
```

### 2. Grupo de Elección

**Definición**: Un contenedor que agrupa múltiples productos/variantes relacionados, de los cuales el cliente debe elegir uno o varios.

**Componentes:**
- **Etiqueta**: Nombre descriptivo para el cliente (ej: "Elige tu sub de 15cm")
- **Opciones**: Lista de productos/variantes disponibles
- **Reglas de Selección**: Cuántos puede/debe elegir

### 3. Opción dentro del Grupo

**Definición**: Un producto específico (con variante opcional) que el cliente puede seleccionar.

**Ejemplo:**
```
Opción:
├─ Producto: Italian B.M.T.
├─ Variante: 15cm
└─ Personalización: Hereda del producto (salsas, vegetales, etc.)
```

---

## Tipos de Items en Combos

### Tipo 1: Item Fijo (Actual)

**Uso**: Cuando el producto NO puede cambiar.

**Ejemplo:**
```
Item: Papas Lays Originales
├─ Producto: Papas Lays
├─ Sin variantes
├─ Cantidad: 1
└─ Cliente NO elige, recibe este producto fijo
```

**Cuándo usar:**
- Productos únicos sin alternativas
- Items promocionales específicos
- Complementos sin variación

---

### Tipo 2: Item de Elección Simple (Nuevo)

**Uso**: Cliente elige 1 opción de varias.

**Ejemplo:**
```
Item: "Bebida"
├─ Etiqueta: "Elige tu bebida"
├─ Cantidad: 1
├─ Elige: 1 de 4
└─ Opciones:
    ├─ Bebida 1
    ├─ Bebida 2
    ├─ Bebida 3
    └─ Bebida 4
```

**Cuándo usar:**
- Productos intercambiables del mismo valor
- Diferentes sabores/variedades
- Alternativas equivalentes

---

### Tipo 3: Item de Elección Múltiple (Futuro)

**Uso**: Cliente elige VARIOS de las opciones.

**Ejemplo:**
```
Item: "Elige 2 complementos"
├─ Etiqueta: "Elige 2 complementos"
├─ Cantidad: 2
├─ Elige: 2 de 4
└─ Opciones:
    ├─ Papas Lays
    ├─ Galleta Chocolate Chip
    ├─ Galleta Avena
    └─ Nachos
```

**Cuándo usar:**
- "Elige 2 salsas"
- "Elige 3 toppings"
- Combos personalizables

**Nota**: Este tipo se puede implementar en el futuro usando `min_selections` y `max_selections`.

---

## Casos de Uso

### Caso 1: Combo Personal Flexible

**Descripción**: El cliente puede elegir su sub, bebida y complemento.

**Configuración del Combo:**
```
Nombre: "Combo Personal"
Precio: Q48 (Capital Pickup)
Items:

1. Grupo: "Sub de 15cm a elección"
   Elige: 1 de 8 opciones
   Opciones:
   ├─ Italian B.M.T. 15cm
   ├─ Pollo Teriyaki 15cm
   ├─ Atún 15cm
   ├─ Pavo 15cm
   ├─ Jamón 15cm
   ├─ Vegetariano 15cm
   ├─ Roast Beef 15cm
   └─ Club 15cm

2. Grupo: "Bebida"
   Elige: 1 de 4 opciones
   Opciones:
   ├─ Bebida 1
   ├─ Bebida 2
   ├─ Bebida 3
   └─ Bebida 4

3. Grupo: "Complemento"
   Elige: 1 de 3 opciones
   Opciones:
   ├─ Papas Lays
   ├─ Galleta Chocolate Chip
   └─ Galleta Avena
```

**Experiencia del Cliente (Conceptual - Futura):**
1. Selecciona "Combo Personal"
2. Elige: Pollo Teriyaki 15cm
3. Elige: Bebida 2
4. Elige: Papas Lays
5. Personaliza el sub (salsas, vegetales)
6. Precio final: Q48 + extras de personalización

---

### Caso 2: Combo Mixto (Fijos + Elección)

**Descripción**: Algunos items son fijos, otros son de elección.

**Configuración del Combo:**
```
Nombre: "Combo Especial del Mes"
Precio: Q65
Items:

1. FIJO: Sub Italian B.M.T. 30cm
   (Producto específico, no cambia)

2. FIJO: Bebida 1
   (Producto específico, no cambia)

3. Grupo: "Elige tu complemento"
   Elige: 1 de 4 opciones
   Opciones:
   ├─ Papas Lays
   ├─ Galleta Chocolate Chip
   ├─ Galleta Avena
   └─ Brownie
```

**Experiencia del Cliente (Conceptual - Futura):**
1. Recibe Italian B.M.T. 30cm (fijo)
2. Recibe Bebida 1 (fijo)
3. **Elige** su complemento
4. Personaliza el sub

---

### Caso 3: Combo con Variantes por Tamaño

**Descripción**: Diferentes tamaños del mismo producto.

**Configuración del Combo:**
```
Nombre: "Combo Sub Clásico"
Precio: Q38
Items:

1. Grupo: "Elige tu sub clásico de 15cm"
   Elige: 1 de 4 opciones
   Opciones:
   ├─ Italian B.M.T. 15cm
   ├─ Jamón 15cm
   ├─ Pavo 15cm
   └─ Vegetariano 15cm

2. FIJO: Bebida 1
   (Producto específico)
```

---

### Caso 4: Combo Todo Flexible

**Descripción**: TODO es elección del cliente.

**Configuración del Combo:**
```
Nombre: "Combo Arma Tu Comida"
Precio: Q55
Items:

1. Grupo: "Proteína Principal"
   Elige: 1 de 6 opciones
   Opciones:
   ├─ Sub de Pollo 15cm
   ├─ Sub de Res 15cm
   ├─ Ensalada de Pollo
   ├─ Ensalada de Atún
   ├─ Wrap de Pollo
   └─ Wrap Vegetariano

2. Grupo: "Bebida"
   Elige: 1 de 4 opciones
   Opciones:
   ├─ Bebida 1
   ├─ Bebida 2
   ├─ Bebida 3
   └─ Bebida 4

3. Grupo: "Extra"
   Elige: 1 de 5 opciones
   Opciones:
   ├─ Papas Lays
   ├─ Papas Doritos
   ├─ Galleta
   ├─ Brownie
   └─ Fruta
```

---

### Caso 5: Combo Familiar con Repeticiones

**Descripción**: Múltiples unidades del mismo grupo.

**Configuración del Combo:**
```
Nombre: "Combo Familiar 4 Subs"
Precio: Q180
Items:

1. Grupo: "Sub de 15cm a elección"
   **CANTIDAD: 4** ← El cliente elige 4 veces
   Elige: 1 de 10 opciones (por cada cantidad)
   Opciones:
   ├─ Italian B.M.T. 15cm
   ├─ Pollo Teriyaki 15cm
   ├─ Atún 15cm
   ├─ Pavo 15cm
   ├─ Jamón 15cm
   ├─ Vegetariano 15cm
   ├─ Roast Beef 15cm
   ├─ Club 15cm
   ├─ Albóndiga 15cm
   └─ BBQ Rib 15cm

2. Grupo: "Bebida"
   **CANTIDAD: 4**
   Elige: 1 de 4 opciones (por cada cantidad)
   Opciones:
   ├─ Bebida 1
   ├─ Bebida 2
   ├─ Bebida 3
   └─ Bebida 4
```

**Experiencia del Cliente (Conceptual - Futura):**
1. Selecciona "Combo Familiar 4 Subs"
2. **Para cada uno de los 4 subs:**
   - Elige el tipo de sub
   - Personaliza (salsas, vegetales)
3. **Para cada una de las 4 bebidas:**
   - Elige la bebida
4. Precio final: Q180 + personalizaciones

---

## Reglas de Negocio

### 1. Creación de Grupos de Elección

#### Regla: Mínimo de Opciones
- Un grupo de elección debe tener **mínimo 2 opciones**
- No tiene sentido un "grupo" con 1 sola opción (usar item fijo)

```
✅ VÁLIDO:
Grupo con 2 opciones: Coca-Cola o Pepsi

❌ INVÁLIDO:
Grupo con 1 opción: Solo Coca-Cola
→ Usar item fijo en su lugar
```

#### Regla: Todas las Opciones Activas
- Al crear/activar el combo, todas las opciones del grupo deben estar activas
- Si un producto se desactiva después, el grupo se marca como "incompleto"

```
Grupo: "Bebida Mediana"
├─ Coca-Cola Mediano ✅ Activo
├─ Pepsi Mediano ❌ Desactivado
├─ Sprite Mediano ✅ Activo
└─ Estado: ⚠️ Advertencia (1 opción desactivada)
```

#### Regla: Coherencia de Variantes
- Si un producto tiene variantes obligatorias, DEBE especificarse en la opción
- No puedes agregar "Sub de Pollo" sin especificar tamaño si el producto requiere variante

```
❌ INVÁLIDO:
Opción: Sub de Pollo (sin variante)
→ Producto tiene variantes: 15cm, 30cm
→ ERROR: Debes especificar variante

✅ VÁLIDO:
Opción: Sub de Pollo 15cm
```

---

### 2. Selecciones del Cliente

#### Regla: Selección Obligatoria
- El cliente DEBE elegir la cantidad especificada
- No puede dejar grupos sin seleccionar

```
Grupo: "Bebida Mediana" (Elige 1)
├─ Cantidad requerida: 1
└─ Cliente DEBE elegir 1 opción

❌ No puede: Saltarse este paso
❌ No puede: No elegir nada
✅ Debe: Elegir exactamente 1
```

#### Regla: Una Elección por Cantidad
- Si quantity = 1 → Cliente elige 1 vez
- Si quantity = 4 → Cliente elige 4 veces (pueden ser diferentes)

```
Ejemplo: quantity = 4

Cliente puede elegir:
├─ Elección 1: Pollo Teriyaki 15cm
├─ Elección 2: Italian B.M.T. 15cm
├─ Elección 3: Pollo Teriyaki 15cm (repetido, OK)
└─ Elección 4: Atún 15cm
```

#### Regla: Personalización Heredada
- Cada opción elegida hereda la personalización del producto
- El cliente personaliza CADA elección individualmente

```
Grupo: "Sub de 15cm" (quantity = 2)

Cliente elige:
├─ Sub 1: Pollo Teriyaki 15cm
│   └─ Personalización: Lechuga, Tomate, Mayo, BBQ (+Q8)
└─ Sub 2: Italian B.M.T. 15cm
    └─ Personalización: Cebolla, Mostaza, Sin queso (+Q3)

Total extras: Q8 + Q3 = Q11
```

---

### 3. Precio del Combo con Grupos

#### Regla: Precio Base Incluye Todas las Opciones
- El precio del combo cubre CUALQUIER opción del grupo
- No importa qué producto elija, el precio base es el mismo

```
Combo Personal: Q48

Grupo: "Sub de 15cm"
├─ Italian B.M.T. 15cm (precio individual: Q65)
├─ Pollo Teriyaki 15cm (precio individual: Q70)
└─ Atún 15cm (precio individual: Q60)

→ En combo TODOS cuestan Q48 (precio base)
→ El precio individual NO importa dentro del combo
```

**Implicación**: El administrador debe elegir productos de valor similar para el grupo.

#### Regla: Extras de Personalización se Suman
- Precio final = Precio base + Suma de extras de personalización

```
Combo: Q48
├─ Cliente elige: Pollo Teriyaki 15cm
│   └─ Extras: +Q8
├─ Cliente elige: Sprite Mediano
│   └─ Extras: Q0
└─ Cliente elige: Papas Lays
    └─ Extras: Q0

Precio final: Q48 + Q8 = Q56
```

#### Regla: Promociones a Nivel Combo
- Las promociones aplican al combo completo
- NO a las opciones individuales dentro del grupo

```
Promoción: "20% descuento en Combo Personal"

✅ Se aplica: Al precio del combo (Q48 → Q38.40)
❌ NO se aplica: A los productos elegidos individualmente
```

---

### 4. Disponibilidad del Combo

#### Regla: Validación de Disponibilidad
Un combo con grupos está disponible cuando:
1. `combo.is_active = true`
2. Todos los items fijos tienen productos activos
3. Todos los grupos tienen al menos 1 opción activa

```
Escenario 1: Combo Disponible ✅
├─ Combo activo: ✅
├─ Item fijo: Papas Lays ✅ Activo
└─ Grupo "Bebidas": 3/5 opciones activas ✅

Escenario 2: Combo NO Disponible ❌
├─ Combo activo: ✅
├─ Item fijo: Papas Lays ✅ Activo
└─ Grupo "Bebidas": 0/5 opciones activas ❌
    → TODAS las opciones desactivadas
    → Combo no disponible
```

#### Regla: Advertencias vs Bloqueos
- **Advertencia**: Si ALGUNAS opciones están desactivadas (pero quedan activas)
- **Bloqueo**: Si TODAS las opciones están desactivadas

```
Grupo con 5 opciones:
├─ 3 activas, 2 desactivadas → ⚠️ Advertencia
└─ 0 activas, 5 desactivadas → ❌ Combo bloqueado
```

---

### 5. Administración

#### Regla: Control Explícito
- El administrador elige EXACTAMENTE qué productos van en cada grupo
- No hay "auto-agregado" por categoría

**Ventajas:**
- ✅ Control total de qué se ofrece
- ✅ Control de coherencia de precios
- ✅ Sin sorpresas de productos nuevos

**Desventajas:**
- ❌ Mantenimiento manual
- ❌ Nuevos productos no se agregan automáticamente

#### Regla: Etiqueta Descriptiva
- Cada grupo DEBE tener una etiqueta clara para el cliente
- La etiqueta aparece en la interfaz de pedido

```
✅ BUENAS ETIQUETAS:
- "Elige tu sub de 15cm"
- "Bebida mediana a elección"
- "Tu complemento favorito"

❌ MALAS ETIQUETAS:
- "Item 1"
- "Grupo A"
- "Elige"
```

---

## Interfaz de Usuario

> **NOTA**: Esta sección describe tanto la interfaz **admin** (a implementar ahora) como la interfaz **cliente** (conceptual, a implementar después). La interfaz del cliente se incluye para entender el objetivo final del sistema.

### Admin: Crear/Editar Combo

**[IMPLEMENTACIÓN ACTUAL - ALTA PRIORIDAD]**

#### Sección: Items del Combo

```
┌─────────────────────────────────────────────────────────┐
│ Productos del Combo                                     │
├─────────────────────────────────────────────────────────┤
│                                                         │
│ ┌─────────────────────────────────────────────────┐   │
│ │ ⋮⋮ Item 1                               [✕]     │   │
│ │                                                 │   │
│ │ Tipo de Item:                                   │   │
│ │ ⚪ Producto fijo   🔘 Grupo de elección        │   │
│ │                                                 │   │
│ │ Etiqueta (para cliente) *                       │   │
│ │ [Sub de 15cm a elección_____________]           │   │
│ │                                                 │   │
│ │ Cantidad *                                      │   │
│ │ [1__]                                           │   │
│ │                                                 │   │
│ │ ┌─────────────────────────────────────────┐   │   │
│ │ │ Opciones Disponibles (mín 2)            │   │   │
│ │ ├─────────────────────────────────────────┤   │   │
│ │ │                                         │   │   │
│ │ │ 1. Italian B.M.T. 15cm          [✕]    │   │   │
│ │ │ 2. Pollo Teriyaki 15cm          [✕]    │   │   │
│ │ │ 3. Atún 15cm                    [✕]    │   │   │
│ │ │ 4. Pavo 15cm                    [✕]    │   │   │
│ │ │ 5. Jamón 15cm                   [✕]    │   │   │
│ │ │ 6. Vegetariano 15cm             [✕]    │   │   │
│ │ │ 7. Roast Beef 15cm              [✕]    │   │   │
│ │ │ 8. Club 15cm                    [✕]    │   │   │
│ │ │                                         │   │   │
│ │ │ [+ Agregar Opción]                     │   │   │
│ │ └─────────────────────────────────────────┘   │   │
│ └─────────────────────────────────────────────────┘   │
│                                                         │
│ [+ Agregar Item]                                       │
└─────────────────────────────────────────────────────────┘
```

#### Flujo de Agregar Opción al Grupo

```
1. Click en [+ Agregar Opción]

2. Modal/Dropdown:
   ┌─────────────────────────────────────┐
   │ Agregar Opción al Grupo             │
   ├─────────────────────────────────────┤
   │                                     │
   │ Buscar producto...                  │
   │ [Italian_____________] 🔍          │
   │                                     │
   │ Resultados:                         │
   │ 🍔 Italian B.M.T. (Subs)           │
   │    ├─ 15cm                          │
   │    └─ 30cm                          │
   │                                     │
   │ [Cancelar]          [Agregar]      │
   └─────────────────────────────────────┘

3. Si producto tiene variantes:
   - Muestra lista de variantes
   - Selecciona la variante específica

4. Agrega a la lista de opciones
```

---

### Cliente: Hacer Pedido de Combo

**[CONCEPTUAL - IMPLEMENTACIÓN FUTURA]**

> **NOTA IMPORTANTE**: Esta sección es **CONCEPTUAL**. Describe cómo funcionará la experiencia del cliente cuando se implemente. El objetivo actual es crear la estructura admin para que esta experiencia sea posible en el futuro.

#### Paso 1: Seleccionar Combo

```
┌─────────────────────────────────────────┐
│ 🍔 Combo Personal              Q48.00   │
├─────────────────────────────────────────┤
│ Sub de 15cm + Bebida + Complemento      │
│                                         │
│ [Seleccionar]                           │
└─────────────────────────────────────────┘
```

#### Paso 2: Elegir Opciones de Cada Grupo

```
┌─────────────────────────────────────────────────────┐
│ Arma tu Combo Personal                              │
├─────────────────────────────────────────────────────┤
│                                                     │
│ 1️⃣ Elige tu sub de 15cm                            │
│ ┌─────────────────────────────────────────────┐   │
│ │ ⚪ Italian B.M.T. 15cm                      │   │
│ │ 🔘 Pollo Teriyaki 15cm      ✓ Seleccionado │   │
│ │ ⚪ Atún 15cm                                │   │
│ │ ⚪ Pavo 15cm                                │   │
│ │ ⚪ Jamón 15cm                               │   │
│ │ ⚪ Vegetariano 15cm                         │   │
│ │ ⚪ Roast Beef 15cm                          │   │
│ │ ⚪ Club 15cm                                │   │
│ └─────────────────────────────────────────────┘   │
│                                                     │
│ 2️⃣ Elige tu bebida mediana                         │
│ ┌─────────────────────────────────────────────┐   │
│ │ ⚪ Coca-Cola Mediano                        │   │
│ │ ⚪ Pepsi Mediano                            │   │
│ │ 🔘 Sprite Mediano           ✓ Seleccionado │   │
│ │ ⚪ Fanta Mediano                            │   │
│ │ ⚪ Agua 500ml                               │   │
│ └─────────────────────────────────────────────┘   │
│                                                     │
│ 3️⃣ Elige tu complemento                            │
│ ┌─────────────────────────────────────────────┐   │
│ │ 🔘 Papas Lays               ✓ Seleccionado │   │
│ │ ⚪ Galleta Chocolate Chip                   │   │
│ │ ⚪ Galleta Avena                            │   │
│ └─────────────────────────────────────────────┘   │
│                                                     │
│ [Atrás]                          [Continuar]       │
└─────────────────────────────────────────────────────┘
```

#### Paso 3: Personalizar Producto Elegido

```
┌─────────────────────────────────────────────────────┐
│ Personaliza: Pollo Teriyaki 15cm                    │
├─────────────────────────────────────────────────────┤
│                                                     │
│ 🥬 Vegetales (elige los que quieras)                │
│ ☑️ Lechuga                                          │
│ ☑️ Tomate                                           │
│ ☑️ Cebolla (+Q5) 💰                                 │
│ ☐ Pepinillo                                         │
│ ☐ Pimiento                                          │
│                                                     │
│ 🌶️ Salsas (elige hasta 3)                          │
│ ☑️ Mayonesa                                         │
│ ☑️ BBQ (+Q3) 💰                                     │
│ ☐ Mostaza                                           │
│ ☐ Ranch                                             │
│                                                     │
│ Extras: +Q8                                         │
│                                                     │
│ [Atrás]                        [Agregar] Q56.00    │
└─────────────────────────────────────────────────────┘
```

#### Paso 4: Si Cantidad > 1

```
Ejemplo: Combo Familiar (4 subs)

┌─────────────────────────────────────────────────────┐
│ Sub 1 de 4                                          │
├─────────────────────────────────────────────────────┤
│ Elige tu sub de 15cm                                │
│ [Lista de opciones...]                              │
│                                                     │
│ [Continuar] →                                       │
└─────────────────────────────────────────────────────┘

[Cliente completa Sub 1]

┌─────────────────────────────────────────────────────┐
│ Sub 2 de 4                                          │
├─────────────────────────────────────────────────────┤
│ Elige tu sub de 15cm                                │
│ [Lista de opciones...]                              │
│                                                     │
│ [Continuar] →                                       │
└─────────────────────────────────────────────────────┘

[Y así sucesivamente...]
```

---

## Flujo del Cliente

**[CONCEPTUAL - IMPLEMENTACIÓN FUTURA]**

> **NOTA**: Este flujo describe cómo funcionará la experiencia del cliente. Se incluye aquí para entender el objetivo final y diseñar la estructura admin correctamente. La implementación del lado cliente se realizará en una fase posterior.

### Flujo Completo: Combo con Grupos de Elección

```
INICIO
  │
  ├─► 1. Cliente selecciona "Combo Personal"
  │      └─ Ve descripción y precio base
  │
  ├─► 2. Sistema identifica items del combo
  │      ├─ Item 1: Grupo de elección (Sub de 15cm)
  │      ├─ Item 2: Grupo de elección (Bebida)
  │      └─ Item 3: Grupo de elección (Complemento)
  │
  ├─► 3. Para Item 1: "Sub de 15cm"
  │      ├─ Sistema muestra 8 opciones
  │      ├─ Cliente selecciona: "Pollo Teriyaki 15cm"
  │      ├─ Sistema carga personalización del sub
  │      ├─ Cliente personaliza (vegetales, salsas)
  │      └─ Sistema calcula extras: +Q8
  │
  ├─► 4. Para Item 2: "Bebida"
  │      ├─ Sistema muestra 5 opciones
  │      ├─ Cliente selecciona: "Sprite Mediano"
  │      └─ Sin personalización
  │
  ├─► 5. Para Item 3: "Complemento"
  │      ├─ Sistema muestra 3 opciones
  │      ├─ Cliente selecciona: "Papas Lays"
  │      └─ Sin personalización
  │
  ├─► 6. Sistema calcula precio total
  │      ├─ Precio base: Q48
  │      ├─ Extras Item 1: +Q8
  │      ├─ Extras Item 2: Q0
  │      ├─ Extras Item 3: Q0
  │      └─ TOTAL: Q56
  │
  ├─► 7. Cliente confirma y agrega al carrito
  │      └─ Se guarda:
  │          ├─ combo_id
  │          ├─ Elecciones realizadas (product_id, variant_id)
  │          ├─ Personalizaciones de cada elección
  │          └─ Precio calculado
  │
FIN
```

---

## Cálculo de Precios

### Fórmula General

```
Precio Final Combo = Precio Base + Suma(Extras de Personalización de Cada Elección)
```

### Ejemplo Detallado

**Combo Personal: Q48**

```
Items:
1. Grupo: "Sub de 15cm" (quantity: 1)
   └─ Cliente elige: Pollo Teriyaki 15cm
      └─ Personalización:
          ├─ Lechuga (incluido)
          ├─ Tomate (incluido)
          ├─ Cebolla (+Q5, is_extra=true)
          └─ BBQ (+Q3, is_extra=true)
      └─ Extras: Q5 + Q3 = Q8

2. Grupo: "Bebida" (quantity: 1)
   └─ Cliente elige: Sprite Mediano
      └─ Sin personalización
      └─ Extras: Q0

3. Grupo: "Complemento" (quantity: 1)
   └─ Cliente elige: Papas Lays
      └─ Sin personalización
      └─ Extras: Q0

CÁLCULO:
├─ Precio base combo: Q48
├─ Extras Item 1: +Q8
├─ Extras Item 2: +Q0
├─ Extras Item 3: +Q0
└─ TOTAL: Q48 + Q8 = Q56
```

### Ejemplo con Cantidad Múltiple

**Combo Familiar: Q180 (4 subs + 4 bebidas)**

```
Items:
1. Grupo: "Sub de 15cm" (quantity: 4)
   ├─ Elección 1: Italian B.M.T. 15cm
   │   └─ Extras: +Q5
   ├─ Elección 2: Pollo Teriyaki 15cm
   │   └─ Extras: +Q8
   ├─ Elección 3: Atún 15cm
   │   └─ Extras: +Q3
   └─ Elección 4: Italian B.M.T. 15cm
       └─ Extras: +Q5

   Subtotal extras: Q5 + Q8 + Q3 + Q5 = Q21

2. Grupo: "Bebida" (quantity: 4)
   └─ 4 bebidas sin extras
   Subtotal extras: Q0

CÁLCULO:
├─ Precio base combo: Q180
├─ Extras totales: +Q21
└─ TOTAL: Q180 + Q21 = Q201
```

---

## Validaciones

### Validaciones de Creación (Admin)

#### 1. Validación: Grupo Mínimo de Opciones
```
Regla: Un grupo debe tener al menos 2 opciones

❌ ERROR:
Grupo "Bebida" con 1 opción
→ "Un grupo de elección debe tener al menos 2 opciones"

✅ VÁLIDO:
Grupo "Bebida" con 2+ opciones
```

#### 2. Validación: Etiqueta Requerida
```
Regla: Todo grupo debe tener etiqueta

❌ ERROR:
Grupo sin etiqueta
→ "La etiqueta del grupo es obligatoria"

✅ VÁLIDO:
Etiqueta: "Elige tu bebida"
```

#### 3. Validación: Productos Activos
```
Regla: Al activar combo, validar que opciones estén activas

❌ ERROR:
Intentando activar combo con:
├─ Grupo "Bebidas"
│   ├─ Coca-Cola ✅ Activo
│   └─ Pepsi ❌ Inactivo
→ "No puedes activar el combo porque tiene productos inactivos en el grupo 'Bebidas': Pepsi"

✅ VÁLIDO:
Todas las opciones activas
```

#### 4. Validación: Variantes Requeridas
```
Regla: Productos con variantes deben especificarla

❌ ERROR:
Opción: Sub de Pollo (sin variante)
Producto: Sub de Pollo (requiere variante: 15cm/30cm)
→ "Debes seleccionar una variante para este producto"

✅ VÁLIDO:
Opción: Sub de Pollo 15cm
```

#### 5. Validación: Sin Opciones Duplicadas
```
Regla: No repetir el mismo producto+variante en un grupo

❌ ERROR:
Grupo "Bebidas":
├─ Coca-Cola Mediano
├─ Pepsi Mediano
└─ Coca-Cola Mediano (duplicado)
→ "Ya existe esta opción en el grupo"

✅ VÁLIDO:
Sin duplicados en opciones
```

---

### Validaciones de Pedido (Cliente)

#### 1. Validación: Selección Completa
```
Regla: Todas las selecciones obligatorias deben completarse

❌ ERROR:
Grupo "Sub de 15cm": Sin selección
→ "Debes elegir tu sub de 15cm"

✅ VÁLIDO:
Todas las selecciones realizadas
```

#### 2. Validación: Cantidad Correcta
```
Regla: Cantidad de elecciones = quantity del item

❌ ERROR:
Item con quantity=4
Cliente solo eligió 2 veces
→ "Debes elegir 4 subs"

✅ VÁLIDO:
4 elecciones realizadas
```

#### 3. Validación: Opción Válida
```
Regla: La opción elegida debe estar en la lista de opciones del grupo

❌ ERROR:
Cliente envía: product_id = 999 (no está en opciones)
→ "Opción inválida seleccionada"

✅ VÁLIDO:
product_id pertenece a las opciones del grupo
```

#### 4. Validación: Disponibilidad en Tiempo Real
```
Regla: Al momento de agregar al carrito, validar disponibilidad

❌ ERROR:
Cliente eligió: Italian B.M.T. 15cm
Pero: Italian B.M.T. fue desactivado hace 1 minuto
→ "El producto seleccionado ya no está disponible"

✅ VÁLIDO:
Producto sigue activo
```

---

## Compatibilidad

### Compatibilidad con Combos Existentes

**Regla de Oro**: El sistema debe ser **100% compatible hacia atrás**.

#### Combos Actuales (Item Fijo)

```
Combo existente:
├─ Item 1: product_id = 10, variant_id = 5 (FIJO)
├─ Item 2: product_id = 20, variant_id = NULL (FIJO)
└─ Item 3: product_id = 30, variant_id = NULL (FIJO)

Comportamiento:
├─ is_choice_group = FALSE (por defecto)
├─ NO tiene opciones en combo_item_options
└─ Funciona EXACTAMENTE como antes
```

#### Combos Nuevos (Con Grupos)

```
Combo nuevo:
├─ Item 1: Grupo de elección
│   ├─ is_choice_group = TRUE
│   ├─ product_id = NULL
│   ├─ variant_id = NULL
│   └─ Tiene opciones en combo_item_options
└─ Item 2: Item fijo
    ├─ is_choice_group = FALSE
    ├─ product_id = 40
    └─ NO tiene opciones
```

### Migración de Datos

**NO se requiere migración de datos existentes.**

Los combos actuales siguen funcionando:
- `is_choice_group` se agrega con default `FALSE`
- No afecta estructura actual
- Combos existentes = items fijos

### Interfaz Admin

**Opción 1: Mantener vista separada**
- "Crear Combo Tradicional" (items fijos)
- "Crear Combo Flexible" (con grupos)

**Opción 2: Interfaz unificada (RECOMENDADO)**
- Al agregar item: Elegir tipo (Fijo o Grupo)
- Muestra campos según tipo seleccionado
- Más flexible, menos confusión

---

## Resumen de Beneficios

### Para el Negocio

1. **Menos Combos, Más Flexibilidad**
   - Antes: 20 combos (1 por cada combinación)
   - Después: 1 combo con opciones

2. **Mejor Valor Percibido**
   - Cliente siente que está "armando" su combo
   - Mayor satisfacción

3. **Mantenimiento Simplificado**
   - Actualizar precio: 1 combo en lugar de 20
   - Agregar nueva bebida: Solo agregar opción al grupo

### Para el Cliente

1. **Libertad de Elección**
   - Elige lo que realmente quiere
   - No "conformarse" con opciones fijas

2. **Claridad**
   - Ve todas sus opciones claramente
   - Sabe qué está eligiendo

3. **Personalización Completa**
   - Elige el producto + personaliza
   - Experiencia consistente con productos individuales

### Para el Sistema

1. **Escalabilidad**
   - Fácil agregar nuevos productos a grupos
   - No explota la cantidad de combos

2. **Consistencia**
   - Lógica de personalización reutilizada
   - No duplicación de código

3. **Claridad de Datos**
   - Estructura clara de qué eligió el cliente
   - Mejor para reportes y analytics

---

## Próximos Pasos

### 🎯 IMPLEMENTACIÓN ACTUAL (Fases 1-3 + 5)

Las siguientes fases se implementarán **AHORA** como parte del desarrollo del panel admin:

#### Fase 1: Diseño de Base de Datos ✅ PRIORIDAD
- Diseñar nuevas tablas/campos
- Planificar migración
- Definir relaciones Eloquent

#### Fase 2: Backend ✅ PRIORIDAD
- Crear modelos y relaciones
- Actualizar controladores
- Implementar validaciones
- Actualizar FormRequests

#### Fase 3: Frontend Admin ✅ PRIORIDAD
- Diseñar interfaz de creación/edición
- Implementar selector de tipo de item
- Crear componente de gestión de opciones
- Drag & drop de opciones

#### Fase 5: Testing ✅ PRIORIDAD
- Unit tests
- Feature tests
- Testing de validaciones
- Testing de cálculo de precios (conceptual)
- Testing de compatibilidad

---

### ⏳ IMPLEMENTACIÓN FUTURA (Fase 4)

Esta fase se implementará **DESPUÉS**, cuando se desarrolle el sistema de pedidos del cliente:

#### Fase 4: Frontend Cliente ⏳ FUTURO
- Diseñar flujo de selección
- Implementar interfaz de elección
- Integrar con carrito
- Manejo de múltiples elecciones (quantity > 1)

---

**Documento creado**: 2025-01-24
**Última actualización**: 2025-01-24
**Versión**: 1.1
**Estado**: Propuesta para revisión
**Alcance**: Implementación Admin (Fases 1-3 + 5) - Cliente Futuro (Fase 