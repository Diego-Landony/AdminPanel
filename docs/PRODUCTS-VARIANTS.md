# Sistema de Productos y Variantes - Documentación Conceptual

## Arquitectura General

### Componentes Principales

**1. Categorías (categories)**
- Define el tipo de producto (Subs, Bebidas, Postres, etc.)
- Contiene configuración de variantes:
  - `uses_variants` (boolean): indica si la categoría maneja variantes
  - `variant_definitions` (JSON array): lista de variantes disponibles
  - Ejemplo: `["15cm", "30cm", "45cm"]`

**2. Productos (products)**
- Información base del producto (nombre, descripción, imagen)
- Pertenece a una categoría mediante `category_id`
- No almacena precios si usa variantes
- Campo `has_variants` deriva automáticamente de su categoría

**3. Variantes de Producto (product_variants)**
- Almacena cada combinación producto + tamaño con sus precios
- Campos clave:
  - `product_id`: relación con producto
  - `name`: nombre de la variante (ej: "15cm")
  - `size`: tamaño de la variante
  - 4 precios: pickup/domicilio × capital/interior
  - `is_active`: estado activo/inactivo
  - `sort_order`: orden de presentación

---

## Modelo de Precios

### Sistema de 4 Precios por Variante

Cada variante maneja 4 tipos de precio según:
- **Ubicación**: Capital vs Interior
- **Tipo de servicio**: Pickup (recoger) vs Domicilio (delivery)

**Combinaciones:**
1. Pickup Capital
2. Domicilio Capital
3. Pickup Interior
4. Domicilio Interior

---

## Flujos de Uso

### Flujo 1: Crear Categoría con Variantes

**Escenario:** Crear categoría "Subs" que usará variantes de tamaño.

**Pasos:**
1. Usuario accede a crear categoría
2. Ingresa nombre: "Subs"
3. Activa toggle `uses_variants = true`
4. Define variantes disponibles:
   - Agrega "15cm"
   - Agrega "30cm"
   - Agrega "45cm"
5. Guarda categoría

**Resultado:**
```
Categoría: Subs
  uses_variants: true
  variant_definitions: ["15cm", "30cm", "45cm"]
```

---

### Flujo 2: Crear Producto con Variantes

**Escenario:** Crear producto "Subway Pollo" en categoría Subs.

**Pasos:**
1. Usuario accede a crear producto
2. Selecciona categoría: "Subs"
3. Sistema detecta que Subs usa variantes
4. Frontend carga automáticamente las 3 variantes de la categoría
5. Usuario ve formulario dinámico:

```
✓ 15cm (activa)
  Pickup Capital:    Q 45.00
  Domicilio Capital: Q 50.00
  Pickup Interior:   Q 48.00
  Domicilio Interior: Q 53.00

✓ 30cm (activa)
  Pickup Capital:    Q 60.00
  Domicilio Capital: Q 65.00
  Pickup Interior:   Q 63.00
  Domicilio Interior: Q 68.00

☐ 45cm (desactivada - no se creará)
```

6. Usuario activa solo 15cm y 30cm
7. Completa los 4 precios de cada variante activa
8. Guarda producto

**Resultado Backend:**
```
Product: Subway Pollo
  category_id: 1 (Subs)
  has_variants: true (heredado de categoría)

ProductVariants:
  - name: "15cm", is_active: true, precio_pickup_capital: 45.00, ...
  - name: "30cm", is_active: true, precio_pickup_capital: 60.00, ...
```

---

### Flujo 3: Crear Producto sin Variantes

**Escenario:** Crear producto "Coca Cola" en categoría Bebidas.

**Pasos:**
1. Usuario crea categoría "Bebidas" con `uses_variants = false`
2. Al crear producto "Coca Cola"
3. Selecciona categoría: "Bebidas"
4. Sistema detecta que NO usa variantes
5. Muestra campos de precio directos en el producto:

```
Precios del Producto:
  Pickup Capital:    Q 12.00
  Domicilio Capital: Q 15.00
  Pickup Interior:   Q 12.00
  Domicilio Interior: Q 15.00
```

**Resultado Backend:**
```
Product: Coca Cola
  category_id: 2 (Bebidas)
  has_variants: false
  precio_pickup_capital: 12.00
  precio_domicilio_capital: 15.00
  precio_pickup_interior: 12.00
  precio_domicilio_interior: 15.00
```

---

### Flujo 4: Editar Producto - Activar Variante Existente

**Escenario:** Subway Pollo inicialmente solo tenía 15cm y 30cm. Ahora quieren agregar 45cm.

**Pasos:**
1. Usuario edita "Subway Pollo"
2. Ve variantes existentes:
   - ✓ 15cm (activa) - precios actuales
   - ✓ 30cm (activa) - precios actuales
   - ☐ 45cm (disponible pero no creada)
3. Activa checkbox de 45cm
4. Completa 4 precios de la nueva variante
5. Guarda

**Resultado Backend:**
```
ProductVariants (Subway Pollo):
  - name: "15cm", is_active: true (sin cambios)
  - name: "30cm", is_active: true (sin cambios)
  - name: "45cm", is_active: true (NUEVO REGISTRO)
```

---

### Flujo 5: Editar Producto - Desactivar Variante

**Escenario:** Descontinúan el tamaño 45cm para Subway Pollo.

**Pasos:**
1. Usuario edita "Subway Pollo"
2. Ve variantes:
   - ✓ 15cm (activa)
   - ✓ 30cm (activa)
   - ✓ 45cm (activa)
3. Desactiva checkbox de 45cm
4. Guarda

**Resultado Backend:**
```
ProductVariants (Subway Pollo):
  - name: "15cm", is_active: true
  - name: "30cm", is_active: true
  - name: "45cm", is_active: false (REGISTRO CONSERVADO, solo marcado inactivo)
```

**Importante:** El registro NO se elimina para:
- Conservar historial de precios
- Permitir reactivación futura
- Mantener referencias en promociones/combos pasados

---

### Flujo 6: Actualizar Precios de Variantes

**Escenario:** Actualización de precios por inflación.

**Pasos:**
1. Usuario edita "Subway Pollo"
2. Ve variantes actuales con sus precios
3. Modifica precios de variantes activas:
   - 15cm: Pickup Capital cambia de Q45 → Q48
   - 30cm: Domicilio Capital cambia de Q65 → Q70
4. Guarda

**Resultado Backend:**
```
ProductVariants:
  - name: "15cm", precio_pickup_capital: 48.00 (actualizado)
  - name: "30cm", precio_domicilio_capital: 70.00 (actualizado)
```

---

### Flujo 7: Categoría Agrega Nueva Variante Global

**Escenario:** Subway lanza tamaño "60cm" para todas las categorías Subs.

**Pasos:**
1. Usuario edita categoría "Subs"
2. Actualiza `variant_definitions`:
   - De: `["15cm", "30cm", "45cm"]`
   - A: `["15cm", "30cm", "45cm", "60cm"]`
3. Guarda categoría
4. Sistema automáticamente:
   - Crea variante "60cm" en TODOS los productos Subs existentes
   - La crea con `is_active = false` (desactivada)
   - Precios = NULL (sin asignar)

**Efecto Inmediato:**
- TODOS los productos Subs ahora tienen variante "60cm" disponible
- La variante está desactivada por defecto
- Admin debe editar cada producto para activarla y asignar precios

**Productos después del cambio:**
```
ProductVariants (Subway Pollo):
  ✓ 15cm (activa) - precios existentes
  ✓ 30cm (activa) - precios existentes
  ☐ 45cm (desactivada)
  ☐ 60cm (desactivada, CREADA AUTOMÁTICAMENTE, sin precios)
```

---

### Flujo 8: Categoría Renombra Variante Globalmente

**Escenario:** Cambio de estándar - "15cm" pasa a llamarse "6 pulgadas" (branding).

**Pasos:**
1. Usuario edita categoría "Subs"
2. Actualiza `variant_definitions`:
   - De: `["15cm", "30cm", "45cm"]`
   - A: `["6 pulgadas", "30cm", "45cm"]`
3. Guarda categoría
4. Sistema automáticamente:
   - `UPDATE product_variants SET name = "6 pulgadas" WHERE name = "15cm"`
   - Afecta TODOS los productos de categoría Subs
   - Mantiene precios existentes
   - Mantiene estado is_active

**Efecto Inmediato:**
```
ANTES:
ProductVariants (Subway Pollo):
  - name: "15cm", precio_pickup_capital: 45.00, is_active: true
  - name: "30cm", precio_pickup_capital: 60.00, is_active: true

DESPUÉS:
ProductVariants (Subway Pollo):
  - name: "6 pulgadas", precio_pickup_capital: 45.00, is_active: true ← RENOMBRADO
  - name: "30cm", precio_pickup_capital: 60.00, is_active: true
```



---

## Reglas de Negocio

### 1. Herencia de Variantes
- Producto hereda `uses_variants` de su categoría
- Si categoría `uses_variants = true` → producto debe usar variantes
- Si categoría `uses_variants = false` → producto tiene precios directos

### 2. Validación de Precios
- Variantes activas (`is_active = true`) **requieren** los 4 precios completos
- Variantes inactivas pueden tener precios NULL
- No se permite guardar variante activa sin precios

### 3. Ciclo de Vida de Variantes
- **Crear:** Agregar nueva variante con checkbox activo + precios
- **Desactivar:** Cambiar `is_active = false` (preserva registro)
- **Reactivar:** Cambiar `is_active = true` (requiere validar precios)
- **Nunca eliminar:** Registro permanente para historial

### 4. Consistencia de Nombres
- Variantes usan nombres de `variant_definitions` de la categoría
- No se permite escribir variantes personalizadas
- Frontend carga opciones desde la categoría (no hardcodeadas)

### 5. Modificación de Categorías (Sincronización Automática)

**Agregar variante:**
- Sistema crea automáticamente la variante en TODOS los productos de la categoría
- Se crea con `is_active = false` (desactivada)
- Precios NULL (sin asignar)
- Admin debe editar productos individualmente para activar y asignar precios

**Renombrar variante:**
- Sistema renombra automáticamente en TODOS los productos de la categoría
- Mantiene precios existentes
- Mantiene estado activo/inactivo
- Ejemplo: "15cm" → "15 centímetros" actualiza todos los productos

**Eliminar variante:**
- NO se permite si existen productos con esa variante (activa o inactiva)
- Sistema valida antes de eliminar
- Debe desasociar/eliminar manualmente variantes de productos primero

---

## Casos de Uso Específicos

### Caso 1: Sub Vegetariano Solo 30cm

**Contexto:** El sub vegetariano solo se vende en tamaño 30cm por política de negocio.

**Implementación:**
1. Crear producto "Sub Vegetariano" en categoría Subs
2. De las variantes disponibles (15cm, 30cm, 45cm):
   - ✓ Activar solo 30cm
   - ☐ Desactivar 15cm y 45cm
3. Asignar precios solo a 30cm

**Resultado:** Cliente ve solo tamaño 30cm para Sub Vegetariano.

---

### Caso 2: Promoción Temporal de Tamaño

**Contexto:** Promoción del mes - todos los subs tienen descuento en 15cm.

**Implementación:**
1. No requiere cambios en productos ni variantes
2. Sistema de promociones aplica descuento a variantes `name = "15cm"`
3. Al terminar promoción, precios regresan a normales

**Ventaja:** Variantes consistentes facilitan promociones masivas.

---



---

### Caso 4: Cambio de Nombre por Branding

**Contexto:** Marketing decide cambiar "15cm" y "30cm" a "6 pulgadas" y "12 pulgadas" para alinearse con Subway USA.

**Implementación:**
1. Editar categoría "Subs"
2. Actualizar variant_definitions:
   - De: `["15cm", "30cm", "45cm"]`
   - A: `["6 pulgadas", "12 pulgadas", "45cm"]`
3. Sistema renombra automáticamente en TODOS los productos


**Ventaja:** Un solo cambio actualiza 100+ productos instantáneamente.



---

### Caso 5: Producto con Precios Diferenciados

**Contexto:** Sub Premium tiene precios más altos que Sub Regular, mismo tamaño.

**Implementación:**
- Ambos usan variantes de categoría Subs (15cm, 30cm)
- Cada producto define sus propios precios:

```
Sub Regular 15cm: Q45
Sub Regular 30cm: Q60

Sub Premium 15cm: Q55 (Q10 más caro)
Sub Premium 30cm: Q75 (Q15 más caro)
```

**Ventaja:** Flexibilidad total de precios por producto.

---

### Caso 6: Migrar Producto a Otra Categoría

**Contexto:** "Ensalada de Pollo" pasa de categoría Ensaladas (sin variantes) a Subs (con variantes).

**Implementación:**
1. Editar producto
2. Cambiar categoría de Ensaladas → Subs
3. Sistema detecta nueva categoría usa variantes
4. Frontend muestra variantes disponibles
5. Usuario activa variantes deseadas y asigna precios
6. Precios directos del producto se limpian (pasan a NULL)

---

## Ventajas del Sistema

### 1. Consistencia
- Variantes definidas centralmente en categoría
- Imposible tener typos (15cm vs 15 cm vs quince cm)
- Frontend siempre carga opciones correctas

### 2. Mantenimiento
- Agregar variante global = editar solo categoría
- No requiere actualizar código frontend
- Nuevas variantes disponibles inmediatamente

### 3. Flexibilidad
- Productos pueden activar/desactivar variantes individualmente
- Precios independientes por producto
- Soporte para productos con/sin variantes en mismo sistema

### 4. Escalabilidad
- Fácil agregar nuevas categorías con sus variantes
- Sistema soporta categorías mixtas (con y sin variantes)
- Preparado para futuras funcionalidades (promociones, combos)

### 5. UX Mejorada
- Formularios dinámicos según categoría
- Sin campos hardcodeados
- Validaciones automáticas de precios

---

## Sincronización Automática - Reglas Críticas

### 1. Cambios en Nombre de Categoría
- Actualizar `categories.name` NO afecta productos
- Solo es un cambio de etiqueta/visualización
- Productos mantienen `category_id` (FK)

### 2. Agregar Variante a Categoría
```
Categoría Subs: ["15cm", "30cm"] → ["15cm", "30cm", "45cm"]

Sistema automáticamente:
  1. Crea registro en product_variants para TODOS los productos Subs
  2. is_active = false (desactivada por defecto)
  3. Precios = NULL
  4. Notifica admin para revisar y activar
```

### 3. Renombrar Variante en Categoría
```
Categoría Subs: ["15cm", "30cm"] → ["quince cm", "30cm"]

Sistema automáticamente:
  1. UPDATE product_variants SET name = "quince cm" WHERE name = "15cm"
  2. Mantiene precios existentes
  3. Mantiene is_active actual
  4. Afecta TODOS los productos de la categoría
```

### 4. Eliminar Variante de Categoría
```
Intento: Eliminar "15cm" de variant_definitions

Sistema valida:
  1. ¿Existen registros en product_variants con name = "15cm"?
  2. Si SÍ → BLOQUEAR operación con error
  3. Si NO → Permitir eliminación

Mensaje: "No se puede eliminar '15cm'. 12 productos la están usando."
```

### 5. Implicaciones de Sincronización Automática

**Ventajas:**
- Consistencia global garantizada
- Cambios se propagan instantáneamente
- No hay nombres huérfanos o inconsistentes

**Precauciones:**
- Renombrar variante afecta historial/reportes
- Agregar variante crea muchos registros NULL
- Eliminar requiere limpieza previa de productos

---



### Mejoras Implementadas
✅ Sincronización automática de variantes
✅ Validación de eliminación de variantes
✅ Renombrado automático con preservación de precios



---

**Documento creado:** 2025-10-24
**Última actualización:** 2025-10-24
