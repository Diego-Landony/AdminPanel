# Sistema de Categorías

Documentación conceptual del módulo de categorías del AdminPanel.

---

## Resumen Ejecutivo

Las **Categorías** son el eje central de organización del menú. Actúan como contenedores lógicos que agrupan productos y definen cómo se comportan sus precios (directos o por variantes).

---

## Arquitectura Conceptual

```
┌─────────────────────────────────────────────────────────────────┐
│                          CATEGORÍA                              │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │ • Nombre                                                 │   │
│  │ • Estado (activa/inactiva)                               │   │
│  │ • Tipo (normal o combo)                                  │   │
│  │ • Usa variantes (sí/no)                                  │   │
│  │ • Definiciones de variantes (ej: ["15cm", "30cm"])       │   │
│  │ • Orden de visualización                                 │   │
│  └──────────────────────────────────────────────────────────┘   │
│                              │                                  │
│         ┌────────────────────┼────────────────────┐             │
│         ▼                    ▼                    ▼             │
│   ┌──────────┐        ┌──────────┐        ┌──────────┐          │
│   │ Producto │        │ Producto │        │  Combo   │          │
│   └──────────┘        └──────────┘        └──────────┘          │
└─────────────────────────────────────────────────────────────────┘
```

---

## Tipos de Categoría

### 1. Categoría Normal (sin variantes)

Los productos tienen precios fijos definidos en la tabla pivot `category_product`.

**Comportamiento:**
- Al asociar un producto, se requieren los 4 precios
- Los precios se almacenan en la relación, no en el producto
- Un mismo producto puede tener precios diferentes en categorías distintas

**Ejemplo:** Categoría "Bebidas"
- Coca-Cola: Q15 pickup capital, Q18 domicilio capital...

---

### 2. Categoría con Variantes

Los productos generan variantes automáticamente basadas en `variant_definitions`.

**Comportamiento:**
- La categoría define las variantes disponibles (ej: `["15cm", "30cm"]`)
- Al asociar un producto, se crean automáticamente las variantes
- Cada variante tiene sus propios 4 precios
- Las variantes inician inactivas y sin precios (deben configurarse manualmente)

**Ejemplo:** Categoría "Subs"
- variant_definitions: ["15cm", "30cm"]
- Producto "Pollo Teriyaki" genera:
  - Variante "15cm" con sus 4 precios
  - Variante "30cm" con sus 4 precios

---

### 3. Categoría de Combos

Contenedor especial para agrupar combos.

**Comportamiento:**
- `is_combo_category = true`
- Los combos tienen relación directa con la categoría
- Los combos contienen items que referencian productos/variantes

---

## Modelo de Precios

El sistema maneja **4 precios** por unidad vendible:

| Precio | Zona | Tipo Servicio |
|--------|------|---------------|
| `precio_pickup_capital` | Capital | Pickup |
| `precio_domicilio_capital` | Capital | Domicilio |
| `precio_pickup_interior` | Interior | Pickup |
| `precio_domicilio_interior` | Interior | Domicilio |

**Ubicación de precios según tipo de categoría:**

| Tipo Categoría | Ubicación de Precios |
|----------------|----------------------|
| Sin variantes | Tabla pivot `category_product` |
| Con variantes | Tabla `product_variants` |
| Combos | Directamente en tabla `combos` |

---

## Sincronización de Variantes

Cuando se modifican las `variant_definitions` de una categoría, el sistema sincroniza automáticamente con todos los productos asociados.

### Operaciones de Sincronización

| Acción | Comportamiento |
|--------|----------------|
| **Agregar variante** | Crea variante en todos los productos (inactiva, sin precios) |
| **Renombrar variante** | Actualiza nombre en todos los productos (conserva precios) |
| **Eliminar variante** | Solo permitido si ningún producto tiene esa variante activa |

### Detección de Renombramiento

El sistema detecta renombramientos comparando posiciones en el array:
- Si el elemento en posición N cambió de valor
- Y el valor antiguo no aparece en otro lugar
- Y el valor nuevo no existía antes
- → Se considera renombramiento (no eliminación + creación)

---

## Relaciones en Base de Datos

```
┌─────────────────┐
│   categories    │
│─────────────────│
│ id              │
│ name            │
│ is_active       │
│ is_combo_cat    │──────────────────┐
│ uses_variants   │                  │
│ variant_defs[]  │                  │
│ sort_order      │                  │
└────────┬────────┘                  │
         │                           │
         │ N:N                       │ 1:N
         │                           │
┌────────┴────────┐          ┌───────┴───────┐
│category_product │          │    combos     │
│─────────────────│          │───────────────│
│ category_id     │          │ category_id   │
│ product_id      │          │ name          │
│ sort_order      │          │ precios...    │
│ precios...      │          └───────────────┘
└────────┬────────┘
         │
         │ N:1
         │
┌────────┴────────┐
│    products     │
│─────────────────│
│ id              │
│ name            │──────────────────┐
│ category_id     │                  │
│ has_variants    │                  │ 1:N
│ ...             │                  │
└─────────────────┘                  │
                             ┌───────┴────────┐
                             │product_variants│
                             │────────────────│
                             │ product_id     │
                             │ sku            │
                             │ name           │
                             │ size           │
                             │ precios...     │
                             │ is_active      │
                             └────────────────┘
```

---

## Flujo de Operaciones

### Crear Categoría

```
1. Usuario ingresa datos básicos
2. Si uses_variants = true:
   - Debe definir al menos una variante
   - variant_definitions se almacena como JSON
3. Se genera sort_order automáticamente
```

### Asociar Producto a Categoría

```
Sin Variantes:
1. Seleccionar producto
2. Ingresar los 4 precios
3. Se crea registro en category_product con precios

Con Variantes:
1. Seleccionar producto
2. Se crea registro en category_product (sin precios)
3. VariantGeneratorService genera variantes automáticamente
4. Usuario debe activar variantes y asignar precios manualmente
```

### Modificar Variantes de Categoría

```
1. Usuario modifica variant_definitions
2. VariantSyncService compara con definiciones anteriores
3. Detecta: agregados, renombrados, eliminados
4. Aplica cambios en transacción:
   - Nuevas variantes → crear en productos (inactivas)
   - Renombradas → actualizar nombre (conservar precios)
   - Eliminadas → validar que no estén en uso
```

---

## Frontend

### Páginas

| Ruta | Componente | Función |
|------|------------|---------|
| `/menu/categories` | `index.tsx` | Listado con drag & drop para reordenar |
| `/menu/categories/create` | `create.tsx` | Formulario de creación |
| `/menu/categories/{id}/edit` | `edit.tsx` | Formulario de edición |

### Componentes Reutilizables

| Componente | Uso |
|------------|-----|
| `CategoryCombobox` | Selector de categoría con búsqueda |
| `VariantsFromCategory` | Renderiza y edita variantes heredadas de categoría |
| `VariantDefinitionsInput` | Input para definir nombres de variantes |

### Características UI

- **Drag & Drop**: Las categorías se pueden reordenar arrastrando
- **Responsive**: Vista tabla en desktop, cards en móvil
- **Alerta de sincronización**: Al editar variantes, muestra advertencia de impacto

---

## Rutas del API

| Método | Ruta | Acción |
|--------|------|--------|
| GET | `/menu/categories` | Listar categorías |
| GET | `/menu/categories/create` | Formulario crear |
| POST | `/menu/categories` | Guardar nueva |
| GET | `/menu/categories/{id}` | Ver detalle |
| GET | `/menu/categories/{id}/edit` | Formulario editar |
| PUT/PATCH | `/menu/categories/{id}` | Actualizar |
| DELETE | `/menu/categories/{id}` | Eliminar |
| POST | `/menu/categories/reorder` | Reordenar |
| POST | `/menu/categories/{id}/products/attach` | Asociar producto |
| DELETE | `/menu/categories/{id}/products/{pid}` | Desasociar producto |
| PATCH | `/menu/categories/{id}/products/{pid}/prices` | Actualizar precios |

---

## Consideraciones Técnicas

### Validaciones

- Nombre único entre categorías
- Si `uses_variants = true`, debe tener al menos una variante definida
- Variantes no pueden tener nombres duplicados dentro de la misma categoría
- Máximo 50 caracteres por nombre de variante

### Índices de BD

- `idx_active_order`: Optimiza consultas de categorías activas ordenadas
- Índice único en `category_product(category_id, product_id)`

### Traits del Modelo

- `LogsActivity`: Registra cambios para auditoría
- `HasFactory`: Soporte para factories en tests

---

## Sub del Día

Las variantes soportan precios especiales para días específicos:

- `is_daily_special`: Indica si la variante participa en promociones diarias
- `daily_special_days`: Array de días (0=Domingo, 6=Sábado)
- `daily_special_precio_*`: Precios especiales para esos días

Esto permite que un "Sub de 15cm" tenga precio regular Q45 pero Q35 los martes.
