# Sistema de Combos - Documentación Técnica

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

El sistema de combos permite crear y gestionar **productos compuestos permanentes** que agrupan múltiples productos individuales bajo un precio especial. Los combos son entidades independientes en el sistema, NO son un tipo de producto.

### Características Principales
- ✅ Entidad separada con tabla propia (`combos`)
- ✅ Precio único para el combo completo (Capital/Interior, Pickup/Delivery)
- ✅ Herencia automática de personalización de productos hijos
- ✅ Agrupación flexible de productos de diferentes categorías
- ✅ Cálculo automático de extras por personalización
- ✅ **Las promociones SÍ aplican a combos** (a nivel combo, no a productos hijos)
- ✅ Sistema de activación/desactivación
- ✅ Gestión en `/menu/combos` (interfaz separada)

### Diferencia con Promociones

| Característica | Combos | Promociones |
|----------------|--------|-------------|
| **Naturaleza** | Producto compuesto permanente | Descuento temporal sobre productos |
| **Entidad** | Tabla `combos` separada | Tabla `promotions` |
| **Ubicación** | `/menu/combos` | `/menu/promotions` |
| **Personalización** | Hereda de productos hijos | N/A |
| **Precio** | Precio fijo del combo + extras | Descuento sobre precio base |
| **Vigencia** | Permanente (mientras esté activo) | Temporal (fechas, días, horas) |
| **Promociones** | ✅ Puede recibir promociones | ✅ Aplica a productos/combos |

---

## Concepto de Combo

### 📦 ¿Qué es un Combo?

Un combo es una **entidad independiente** que agrupa varios productos del menú bajo un precio especial. NO es un tipo de producto, es una entidad separada con su propia tabla y lógica.

### 🎯 Filosofía del Sistema

**Principio Fundamental**: Un combo **referencia** productos existentes, **NO los copia**.

```
COMBO (Entidad separada)
│
├─ Tabla: combos
├─ Precio del combo: Q150
├─ Categorías: [Promociones, Combos Especiales]
│
└─ Items (vía combo_items):
    ├─ Producto: Sub de Pollo (REFERENCIA)
    ├─ Producto: Sub de Res (REFERENCIA)
    ├─ Producto: Coca Cola (REFERENCIA)
    └─ Producto: Pepsi (REFERENCIA)

Cada producto CONSERVA:
- Sus secciones de personalización
- Sus categorías originales
- Su información completa
```

### ✅ Ventajas de Entidad Separada

1. **Single Responsibility**: Combos y productos tienen responsabilidades distintas
2. **Código limpio**: Sin condicionales `if (type === 'combo')` por todos lados
3. **Escalabilidad**: Fácil agregar campos específicos de combos sin afectar productos
4. **Performance**: Queries directas sin filtros constantes
5. **Mantenibilidad**: Cambios en combos NO afectan tabla products
6. **Testing**: Tests específicos por entidad

### 🎨 Arquitectura Visual

```
┌─────────────────────────────────────────────────────┐
│ COMBOS TABLE                                        │
│ ┌─────────────────────────────────────────────────┐ │
│ │ ID: 1                                           │ │
│ │ Name: "Combo Familiar"                          │ │
│ │ Precio Capital Pickup: Q200                     │ │
│ │ Precio Capital Delivery: Q220                   │ │
│ └─────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────┘
           │
           │ combo_items (pivot table)
           ├──────────────────────┐
           │                      │
           ▼                      ▼
┌──────────────────────┐  ┌──────────────────────┐
│ PRODUCTS TABLE       │  │ PRODUCTS TABLE       │
│ ID: 10               │  │ ID: 11               │
│ Name: "Sub Pollo"    │  │ Name: "Sub Res"      │
│ Precio: Q70          │  │ Precio: Q70          │
│ ├─ Secciones →       │  │ ├─ Secciones →       │
│ └─ Categorías →      │  │ └─ Categorías →      │
└──────────────────────┘  └──────────────────────┘
```

### 💰 Precio del Combo

**El precio del combo PREDOMINA sobre los productos hijos:**

```
Precio Final = Precio Base del Combo + Extras de Personalización

Donde:
- Precio Base = combo.precio_pickup_capital (o según zona/servicio)
- Extras = Suma de price_modifier de opciones con is_extra=true
- Los productos hijos NO aportan su precio base
```

**Ejemplo:**
```
Combo "2 Subs Clásicos": Q120

Items:
├─ Sub de Pollo (precio individual Q70, NO SE SUMA)
│  └─ Extras: +Cebolla (Q5) + BBQ (Q3) = Q8
└─ Sub de Res (precio individual Q70, NO SE SUMA)
   └─ Extras: +Queso (Q10) = Q10

Precio final = Q120 (combo) + Q8 + Q10 = Q138
```

---

## Reglas de Negocio

### 1. Herencia de Personalización

**Regla Fundamental**: Los combos heredan TODA la personalización de los productos hijos.

```
SI producto tiene secciones de personalización
ENTONCES combo permite personalizarlo igual que el producto individual
```

**Implicaciones:**
- ✅ Cliente puede personalizar cada producto del combo
- ✅ Cada personalización agrega su costo al total
- ✅ Las secciones requeridas siguen siendo requeridas
- ✅ Las opciones con `price_modifier` siguen agregando al precio

### 2. Estructura de Precios

Los combos tienen **4 precios base** (igual que productos):

- `precio_pickup_capital`: Pickup en zona capital
- `precio_domicilio_capital`: Delivery en zona capital
- `precio_pickup_interior`: Pickup en zona interior
- `precio_domicilio_interior`: Delivery en zona interior

**Validación de coherencia:**
```
precio_domicilio >= precio_pickup (misma zona)
```

### 3. Items del Combo

Cada item representa:
- **Referencia** a un producto existente (product_id)
- **Cantidad** (quantity, default 1)
- **Label descriptivo** (para UI, ej: "Sub Principal")
- **Orden de visualización** (sort_order)

**Productos Repetidos:**
✅ **Permitido**: Mismo producto múltiples veces

```
Combo "4 Empanadas Mixtas"
├─ Item 1: Empanada de Carne (label: "Empanada 1")
├─ Item 2: Empanada de Carne (label: "Empanada 2")
├─ Item 3: Empanada de Pollo (label: "Empanada 3")
└─ Item 4: Empanada de Pollo (label: "Empanada 4")
```

**Validaciones:**
- ✅ Mínimo 2 productos en un combo
- ✅ Productos repetidos permitidos
- ✅ Todos los productos deben estar activos
- ✅ No puede haber items sin producto asignado

### 4. Interacción con Promociones

**⚡ REGLA IMPORTANTE: Las promociones SÍ aplican a combos**

```
Las promociones se aplican A NIVEL COMBO, NO a productos individuales hijos.
```

**Escenarios:**

#### ✅ Promociones QUE APLICAN a Combos:

1. **Sub del Día en Combo Completo:**
   ```
   Promoción: "Sub del Día - Combo Familiar"
   - Se aplica al combo entero
   - Precio especial: Q180 (en lugar de Q220)
   ```

2. **Descuento Porcentual en Combo:**
   ```
   Promoción: "20% descuento en Combo 2 Subs"
   - Se aplica al precio del combo
   - Q120 - 20% = Q96
   ```

3. **2x1 en Combos:**
   ```
   Promoción: "2x1 en Combos los Martes"
   - Compras 2 combos, pagas 1
   ```

#### ❌ Promociones QUE NO APLICAN:

**Los productos HIJOS del combo NO reciben promociones individuales:**

```
Combo "2 Subs Clásicos" (Q120)
├─ Sub de Pollo
│  └─ ❌ NO recibe "Sub del Día - Sub de Pollo Q30"
│  └─ ❌ NO recibe "20% descuento en Subs"
└─ Sub de Res
   └─ ❌ NO recibe promociones individuales

✅ El combo COMPLETO puede recibir promociones
```

**Lógica de Cálculo:**
```php
// Pseudocódigo
if (item_is_combo) {
    $precio_base = $combo->precio_pickup_capital;

    // Buscar promociones para COMBOS
    $promocion = Promotion::forCombo($combo)->activeNow()->first();

    if ($promocion) {
        $precio_base = aplicar_promocion($precio_base, $promocion);
    }

    // NO buscar promociones de productos hijos
    $precio_final = $precio_base + $extras_personalizacion;
}
```

### 5. Categorías del Combo

Los combos **pertenecen a UNA categoría** de tipo combo:

- Relación 1:N (BelongsTo) vía campo `category_id`
- La categoría debe tener `is_combo_category = true`
- Un combo puede tener productos de diferentes categorías
- La categoría del combo es **REQUERIDA**

**Arquitectura:**
```
Combo "2 Subs + Bebida"
├─ Categoría del combo: "Combos Especiales" (is_combo_category = true)
└─ Items (productos pueden ser de diferentes categorías):
    ├─ Sub de Pollo → Categoría original: "Subs"
    ├─ Sub de Res → Categoría original: "Subs"
    └─ Coca Cola → Categoría original: "Bebidas"
```

**Importante:** Los productos dentro del combo mantienen sus categorías originales independientes.

### 6. Estados del Combo

**Estado Activo/Inactivo:**
- `is_active = true`: Se muestra en el menú
- `is_active = false`: Oculto del menú

**Validación de Disponibilidad:**
```
Un combo está DISPONIBLE cuando:
1. is_active = true
2. TODOS los productos hijos están activos (product.is_active = true)
3. TODOS los productos hijos existen (no soft deleted)
```

**Comportamiento automático:**
- Si un producto hijo se desactiva → combo se marca como no disponible
- Se muestra advertencia en admin
- No se puede agregar al carrito

---

## Estructura de Datos

### Arquitectura: Sistema de Tres Niveles

```
NIVEL 1: Combos (Tabla combos)
    │
    ├─ Relación 1:N con Categoría (campo category_id → categories)
    │
    └─ NIVEL 2: Items del Combo (Tabla combo_items)
            │
            └─ NIVEL 3: Productos (Tabla products)
                    │
                    ├─ Relación N:N con Secciones (tabla product_sections)
                    └─ Relación N:N con Categorías (tabla category_product)
```

### DDL: Definición de Tablas

#### Tabla: `combos`

```sql
CREATE TABLE combos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- Relación con categoría
    category_id BIGINT UNSIGNED,

    -- Información básica
    name VARCHAR(255) NOT NULL UNIQUE,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    image VARCHAR(255),

    -- Precios del combo (4 precios)
    precio_pickup_capital DECIMAL(10, 2) NOT NULL,
    precio_domicilio_capital DECIMAL(10, 2) NOT NULL,
    precio_pickup_interior DECIMAL(10, 2) NOT NULL,
    precio_domicilio_interior DECIMAL(10, 2) NOT NULL,

    -- Configuración
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,

    -- Timestamps
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL, -- Soft deletes

    -- Foreign keys
    FOREIGN KEY (category_id)
        REFERENCES categories(id)
        ON DELETE SET NULL
        ON UPDATE RESTRICT,

    -- Índices
    INDEX idx_active (is_active),
    INDEX idx_sort_order (sort_order),
    INDEX idx_slug (slug),
    INDEX idx_category (category_id)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### Tabla: `combo_items`

```sql
CREATE TABLE combo_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- Relaciones
    combo_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,

    -- Configuración del item
    quantity INT UNSIGNED DEFAULT 1,
    label VARCHAR(100) NOT NULL,
    sort_order INT DEFAULT 0,

    -- Timestamps
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    -- Foreign keys
    FOREIGN KEY (combo_id)
        REFERENCES combos(id)
        ON DELETE CASCADE
        ON UPDATE RESTRICT,

    FOREIGN KEY (product_id)
        REFERENCES products(id)
        ON DELETE RESTRICT
        ON UPDATE RESTRICT,

    -- Índices
    INDEX idx_combo (combo_id),
    INDEX idx_product (product_id),
    INDEX idx_sort_order (sort_order)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Constraints importantes:**
- `ON DELETE CASCADE` en combo_id: Si elimino combo, se eliminan sus items
- `ON DELETE RESTRICT` en product_id: NO puedo eliminar un producto si está en un combo activo

### Relaciones Eloquent

#### Modelo: `Combo`

```php
<?php

namespace App\Models\Menu;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Combo extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'image',
        'precio_pickup_capital',
        'precio_domicilio_capital',
        'precio_pickup_interior',
        'precio_domicilio_interior',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'precio_pickup_capital' => 'decimal:2',
        'precio_domicilio_capital' => 'decimal:2',
        'precio_pickup_interior' => 'decimal:2',
        'precio_domicilio_interior' => 'decimal:2',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Relación: Un combo tiene muchos items
     */
    public function items(): HasMany
    {
        return $this->hasMany(ComboItem::class)->orderBy('sort_order');
    }

    /**
     * Relación: Un combo tiene muchos productos (via items)
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'combo_items')
            ->withPivot('quantity', 'label', 'sort_order')
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    /**
     * Relación: Un combo pertenece a una categoría
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Scope: Combos activos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Combos disponibles (activos + todos productos activos)
     */
    public function scopeAvailable($query)
    {
        return $query->active()
            ->whereDoesntHave('products', function ($q) {
                $q->where('is_active', false);
            });
    }

    /**
     * Scope: Ordenar por configuración
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('is_active', 'desc')
            ->orderBy('sort_order', 'asc')
            ->orderBy('created_at', 'desc');
    }

    /**
     * Verifica si el combo está disponible
     */
    public function isAvailable(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        // Verificar que TODOS los productos estén activos
        return $this->products()->where('is_active', false)->doesntExist();
    }

    /**
     * Obtiene el precio para una zona y tipo de servicio
     */
    public function getPriceForZone(string $zone, string $serviceType): float
    {
        $field = match([$zone, $serviceType]) {
            ['capital', 'pickup'] => 'precio_pickup_capital',
            ['capital', 'delivery'] => 'precio_domicilio_capital',
            ['interior', 'pickup'] => 'precio_pickup_interior',
            ['interior', 'delivery'] => 'precio_domicilio_interior',
            default => 'precio_pickup_capital',
        };

        return (float) $this->$field;
    }
}
```

#### Modelo: `ComboItem`

```php
<?php

namespace App\Models\Menu;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComboItem extends Model
{
    protected $fillable = [
        'combo_id',
        'product_id',
        'quantity',
        'label',
        'sort_order',
    ];

    protected $casts = [
        'combo_id' => 'integer',
        'product_id' => 'integer',
        'quantity' => 'integer',
        'sort_order' => 'integer',
    ];

    /**
     * Relación: Un item pertenece a un combo
     */
    public function combo(): BelongsTo
    {
        return $this->belongsTo(Combo::class);
    }

    /**
     * Relación: Un item referencia a un producto
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Obtiene el producto con todas sus secciones cargadas
     */
    public function getProductWithSections()
    {
        return $this->product()->with('sections.options')->first();
    }
}
```

#### Extensión al Modelo: `Product`

```php
/**
 * Relación inversa: Un producto puede estar en muchos combos
 */
public function combos(): BelongsToMany
{
    return $this->belongsToMany(Combo::class, 'combo_items')
        ->withPivot('quantity', 'label', 'sort_order')
        ->withTimestamps();
}

/**
 * Verifica si el producto está en algún combo activo
 */
public function isInActiveCombos(): bool
{
    return $this->combos()->where('is_active', true)->exists();
}
```

### Diagrama de Relaciones

```
┌──────────────────────┐
│  categories          │
│(is_combo_category=1) │
└──────────────────────┘
           │ 1
           │ HasMany
           ▼ N
┌──────────────────────┐
│      combos          │◄────────────────┐
│   (category_id)      │                 │
└──────────────────────┘                 │
           │ 1                           │
           │ HasMany                     │
           ▼ N                           │
┌──────────────────────┐                 │
│   combo_items        │                 │
└──────────────────────┘                 │
           │ N                           │
           │ BelongsTo                   │
           ▼ 1                           │
┌──────────────────────┐  N             │ N
│     products         │◄───────┐       │
└──────────────────────┘        │       │
           │ N                  │       │
           │ BelongsToMany      │       │
           ▼ N                  │       │
┌──────────────────────┐   ┌────────────┴───────┐
│ category_product     │───│   categories       │
│      (pivot)         │   │(is_combo_category  │
└──────────────────────┘   │    = 0 o 1)        │
                           └────────────────────┘
```

---

## Flujo de Aplicación

### 1. Flujo en el Carrito de Compras

```
INICIO: Usuario selecciona un combo en el menú

├─► PASO 1: Cargar combo con eager loading
│   └─ Combo::with(['items.product.sections.options', 'categories'])->find($id)

├─► PASO 2: Verificar disponibilidad
│   ├─ Verificar combo.is_active = true
│   └─ Verificar que TODOS los productos hijos estén activos

├─► PASO 3: Buscar promociones aplicables AL COMBO
│   ├─ Promotion::forCombo($combo)->activeNow()->first()
│   └─ Si existe, calcular precio con descuento

├─► PASO 4: Obtener precio base del combo
│   ├─ Detectar zona (capital/interior)
│   ├─ Detectar tipo de servicio (pickup/delivery)
│   └─ $precio_base = $combo->getPriceForZone($zona, $servicio)

├─► PASO 5: Para cada item del combo (producto hijo):
│   │
│   ├─ Cargar producto con sus secciones
│   │
│   ├─ Mostrar UI de personalización
│   │   ├─ Secciones requeridas (is_required=true)
│   │   ├─ Secciones opcionales (is_required=false)
│   │   └─ Marcar opciones con extra (is_extra=true)
│   │
│   └─ Esperar selecciones del cliente

├─► PASO 6: Validar selecciones
│   │
│   └─ Para cada producto del combo:
│       ├─ Verificar secciones requeridas completas
│       ├─ Verificar min_selections y max_selections
│       └─ Si falla → error

├─► PASO 7: Calcular precio total
│   │
│   ├─ precio_total = precio_base_combo (ya con promoción si aplica)
│   │
│   └─ Para cada item del combo:
│       └─ Para cada opción seleccionada:
│           └─ Si opcion.is_extra = true:
│               └─ precio_total += opcion.price_modifier

├─► PASO 8: Agregar combo al carrito
│   └─ Guardar:
│       ├─ combo_id
│       ├─ precio_base
│       ├─ precio_total
│       ├─ promocion_id (si aplica)
│       └─ personalizaciones (JSON)

└─► RESULTADO: Combo en carrito con personalización completa
```

### 2. Algoritmo de Cálculo de Precio

```php
function calcularPrecioCombo(
    Combo $combo,
    string $zona,
    string $tipoServicio,
    array $personalizaciones
): float {
    // PASO 1: Precio base del combo
    $precioBase = $combo->getPriceForZone($zona, $tipoServicio);

    // PASO 2: Aplicar promoción SI EXISTE (a nivel combo)
    $promocion = Promotion::forCombo($combo)->activeNow()->first();

    if ($promocion) {
        $precioBase = aplicarPromocion($precioBase, $promocion);
    }

    // PASO 3: Sumar extras de personalización
    $totalExtras = 0;

    foreach ($combo->items as $item) {
        $personalizacionItem = $personalizaciones[$item->id] ?? [];

        foreach ($personalizacionItem['opciones'] as $opcionId) {
            $opcion = Option::find($opcionId);

            if ($opcion && $opcion->is_extra) {
                $totalExtras += $opcion->price_modifier;
            }
        }
    }

    // PASO 4: Precio final
    return $precioBase + $totalExtras;
}
```

### 3. Validación de Disponibilidad

```php
function esComboDisponible(Combo $combo): bool
{
    // Validación 1: Combo activo
    if (!$combo->is_active) {
        return false;
    }

    // Validación 2: TODOS los productos activos
    foreach ($combo->items as $item) {
        if (!$item->product || !$item->product->is_active) {
            return false;
        }
    }

    return true;
}
```

### 4. Carga Eficiente (Eager Loading)

```php
// Al listar combos en el menú
$combos = Combo::with([
    'items.product.sections.options',
    'categories'
])
->available()
->ordered()
->get();

// Precarga:
// - Items del combo
// - Productos de cada item
// - Secciones de cada producto
// - Opciones de cada sección
// - Categorías del combo

// Evita N+1 queries
```

---

## Interfaz de Usuario

### 1. Página Principal: `/menu/combos`

**Elementos:**

#### Header:
- Título: "Combos"
- Botón: "+ Nuevo Combo"
- Breadcrumbs: Menú / Combos

#### Estadísticas:
```
┌──────────────────┬──────────────────┬──────────────────┐
│ Total Combos     │ Combos Activos   │ No Disponibles   │
│      15          │        12        │         3        │
└──────────────────┴──────────────────┴──────────────────┘
```

#### Filtros:
- Búsqueda por nombre
- Estado: Todos / Activos / Inactivos
- Categoría (si aplica)

#### DataTable:

| Imagen | Nombre | Items | Categorías | Precio Capital | Estado | Acciones |
|--------|--------|-------|------------|----------------|--------|----------|
| [IMG] | Combo Familiar | 4 items | Promociones | Q200 - Q220 | 🟢 Activo | [⋮] |
| [IMG] | 2 Subs Clásicos | 2 items | Combos | Q120 - Q130 | 🟢 Activo | [⋮] |

**Menú Contextual (⋮):**
- Ver
- Editar
- Duplicar
- Activar/Desactivar
- Eliminar

### 2. Formulario Crear: `/menu/combos/create`

**Secciones:**

#### Sección 1: Información Básica
```
┌─────────────────────────────────────────────────────┐
│ Información Básica                                  │
├─────────────────────────────────────────────────────┤
│                                                     │
│ Nombre del Combo *                                  │
│ [________________________________]                  │
│                                                     │
│ Descripción (opcional)                              │
│ [________________________________]                  │
│ [________________________________]                  │
│                                                     │
│ Imagen                                              │
│ [Seleccionar imagen] [Vista previa]                │
│                                                     │
│ Categorías                                          │
│ [Multi-select de categorías]                       │
│                                                     │
└─────────────────────────────────────────────────────┘
```

#### Sección 2: Items del Combo
```
┌─────────────────────────────────────────────────────┐
│ Items del Combo (mínimo 2) *                       │
├─────────────────────────────────────────────────────┤
│                                                     │
│ ┌─────────────────────────────────────────────┐   │
│ │ Item 1                              [✕]     │   │
│ │                                             │   │
│ │ Producto *                                  │   │
│ │ [Buscar producto... ▼]                     │   │
│ │                                             │   │
│ │ Label *                                     │   │
│ │ [Sub Principal____________]                │   │
│ │                                             │   │
│ │ Cantidad *                                  │   │
│ │ [1 ▼]                                      │   │
│ │                                             │   │
│ │ ℹ️ Este producto tiene 3 secciones de      │   │
│ │    personalización                          │   │
│ └─────────────────────────────────────────────┘   │
│                                                     │
│ [+ Agregar Item]                                   │
│                                                     │
└─────────────────────────────────────────────────────┘
```

#### Sección 3: Precios
```
┌─────────────────────────────────────────────────────┐
│ Precios del Combo *                                 │
├─────────────────────────────────────────────────────┤
│                                                     │
│ 🏙️ Zona Capital                                    │
│ ├─ Pickup:    Q [________]                         │
│ └─ Delivery:  Q [________]                         │
│                                                     │
│ 🏘️ Zona Interior                                   │
│ ├─ Pickup:    Q [________]                         │
│ └─ Delivery:  Q [________]                         │
│                                                     │
│ 💡 Calculadora de Precio Sugerido                  │
│ ┌─────────────────────────────────────────────┐   │
│ │ Suma de productos: Q240                     │   │
│ │ Descuento sugerido (20%): -Q48              │   │
│ │ Precio sugerido: Q192                       │   │
│ │ [Aplicar sugerencia]                        │   │
│ └─────────────────────────────────────────────┘   │
│                                                     │
└─────────────────────────────────────────────────────┘
```

#### Sección 4: Estado
```
┌─────────────────────────────────────────────────────┐
│ Configuración                                       │
├─────────────────────────────────────────────────────┤
│                                                     │
│ Estado                                              │
│ [🔘 Activo] ○ Inactivo                             │
│                                                     │
│ Orden de visualización                              │
│ [0___] (menor = aparece primero)                   │
│                                                     │
└─────────────────────────────────────────────────────┘
```

#### Footer:
```
[Cancelar]                           [Guardar Combo]
```

### 3. Formulario Editar: `/menu/combos/{id}/edit`

Igual que crear, con adiciones:

**Advertencia de productos inactivos:**
```
⚠️ ADVERTENCIA: Productos Inactivos
┌─────────────────────────────────────────────────────┐
│ ⚠️ Atención                                         │
├─────────────────────────────────────────────────────┤
│ Los siguientes productos están inactivos:          │
│                                                     │
│ • Sub de Pollo (Item 1)                            │
│                                                     │
│ El combo está marcado como no disponible.          │
│ Reactiva los productos o reemplázalos.              │
│                                                     │
│ [Reemplazar productos] [Mantener]                  │
└─────────────────────────────────────────────────────┘
```

---

## Casos de Uso

### Caso 1: Combo Simple sin Personalización

**Configuración:**
```
Nombre: "Combo 3 Bebidas"
Items:
  - Coca Cola 500ml (cantidad: 1, label: "Bebida 1")
  - Pepsi 500ml (cantidad: 1, label: "Bebida 2")
  - Fanta 500ml (cantidad: 1, label: "Bebida 3")
Precio Capital-Delivery: Q70
Categorías: ["Bebidas", "Combos"]
```

**Comparación:**
- Individual: Q30 + Q30 + Q30 = Q90
- Combo: Q70
- **Ahorro: Q20 (22%)**

**En el carrito:**
- Cliente selecciona combo
- NO hay personalización (bebidas simples)
- Precio final: Q70
- Se agrega directo al carrito

### Caso 2: Combo con Personalización

**Configuración:**
```
Nombre: "2 Subs Clásicos"
Items:
  - Sub de Pollo (label: "Sub 1")
  - Sub de Res (label: "Sub 2")
Precio Capital-Delivery: Q120
Categorías: ["Combos Especiales"]
```

**Personalización:**

Sub 1 (Pollo):
- Vegetales: Lechuga, Tomate, Cebolla (+Q5)
- Salsas: Mayo, BBQ (+Q3)
- **Extras: Q8**

Sub 2 (Res):
- Vegetales: Lechuga, Tomate
- Salsas: Mostaza
- **Extras: Q0**

**Precio final:**
```
Precio base: Q120
Extras Sub 1: +Q8
Extras Sub 2: +Q0
──────────────
TOTAL: Q128
```

### Caso 3: Combo con Promoción

**Configuración:**
```
Nombre: "Combo Familiar"
Precio Capital-Delivery: Q220
Items: 2 Subs + 2 Bebidas + Papas
```

**Promoción aplicable:**
```
Tipo: Descuento Porcentual
Nombre: "20% descuento en Combo Familiar - Domingos"
Aplica a: Combo Familiar (entidad completa)
Descuento: 20%
Vigencia: Domingos
```

**Cálculo:**
```
Precio base combo: Q220
Promoción (20%): -Q44
──────────────────────
Precio con promo: Q176

Personalizaciones:
- Sub 1 extras: +Q10
- Sub 2 extras: +Q5
- Papas extras: +Q3
──────────────────────
TOTAL FINAL: Q194
```

**Importante:**
❌ Los productos hijos NO reciben promociones individuales:
- Si "Sub de Pollo" tiene promoción "Sub del Día Q30"
- NO se aplica cuando está dentro del combo
- Solo se aplica la promoción del combo completo

### Caso 4: Producto Repetido con Personalizaciones Diferentes

**Configuración:**
```
Nombre: "4 Empanadas Mixtas"
Items:
  - Empanada de Carne (label: "Empanada 1")
  - Empanada de Carne (label: "Empanada 2")
  - Empanada de Pollo (label: "Empanada 3")
  - Empanada de Pollo (label: "Empanada 4")
Precio: Q60
```

**Personalización individual:**

Empanada 1: Al horno + Chimichurri (+Q2)
Empanada 2: Frita + Queso (+Q5)
Empanada 3: Al horno
Empanada 4: Frita + Chimichurri (+Q2)

**Precio final:**
```
Precio base: Q60
Extras: Q2 + Q5 + Q0 + Q2 = Q9
──────────────────────────────
TOTAL: Q69
```

---

## Validaciones

### Validaciones de Formulario

#### Campo: Nombre
- ✅ Requerido
- ✅ Máximo 255 caracteres
- ✅ Único (no puede haber dos combos con el mismo nombre)
- ⚠️ Slug se genera automático

#### Campo: Descripción
- ✅ Opcional
- ✅ Máximo 500 caracteres

#### Campo: Imagen
- ✅ Opcional
- ✅ Formatos: JPG, PNG, WEBP
- ✅ Tamaño máximo: 2MB

#### Sección: Items
- ✅ Mínimo 2 items requeridos
- ✅ Productos repetidos permitidos
- ✅ Cada item requiere: product_id, label
- ✅ Quantity mínimo: 1

**Mensajes de error:**
```
❌ "Un combo debe tener al menos 2 productos"
❌ "El producto seleccionado no existe o está inactivo"
❌ "El label es requerido"
```

#### Sección: Precios
- ✅ Los 4 precios son requeridos
- ✅ Deben ser números positivos > 0
- ✅ Máximo 2 decimales
- ✅ precio_domicilio >= precio_pickup (misma zona)

**Mensajes de error:**
```
❌ "El precio debe ser mayor a 0"
❌ "El precio de delivery debe ser mayor o igual al de pickup"
```

### Validaciones de Negocio

#### Validación 1: Productos Activos al Activar

**Regla:** No puedo activar un combo si tiene productos inactivos.

```php
if ($combo->is_active) {
    foreach ($combo->items as $item) {
        if (!$item->product->is_active) {
            throw ValidationException::withMessages([
                'is_active' => 'No puedes activar el combo porque tiene productos inactivos'
            ]);
        }
    }
}
```

#### Validación 2: Nombre Único

```php
Rule::unique('combos', 'name')->ignore($combo->id)
```

#### Validación 3: Slug Único

```php
// Generar slug desde el nombre
$slug = Str::slug($nombre);

// Si existe, agregar sufijo numérico
if (Combo::where('slug', $slug)->exists()) {
    $slug = $slug . '-2';
}
```

#### Validación 4: Coherencia de Precios

```php
// En el FormRequest
'precio_domicilio_capital' => [
    'required',
    'numeric',
    'min:0',
    function ($attribute, $value, $fail) {
        if ($value < $this->precio_pickup_capital) {
            $fail('El precio de delivery debe ser mayor o igual al de pickup');
        }
    }
]
```

---

## Consideraciones Técnicas

### Performance

**Eager Loading:**
```php
// BUENO ✅
$combos = Combo::with(['items.product.sections.options', 'categories'])
    ->available()
    ->get();

// MALO ❌
$combos = Combo::all();
foreach ($combos as $combo) {
    foreach ($combo->items as $item) {
        $product = $item->product; // N+1 query
    }
}
```

**Caché:**
```php
Cache::remember('combos.available', 3600, function () {
    return Combo::with(['items.product', 'categories'])
        ->available()
        ->ordered()
        ->get();
});
```

**Índices:**
- `combos.is_active`: Para filtrar activos
- `combos.slug`: Para búsqueda por URL
- `combo_items.combo_id`: Para joins eficientes
- `combo_items.product_id`: Para relaciones

### Seguridad

**Autorización:**
```php
Gate::define('menu.combos.view', fn($user) => $user->hasPermission('menu.combos.view'));
Gate::define('menu.combos.create', fn($user) => $user->hasPermission('menu.combos.create'));
Gate::define('menu.combos.edit', fn($user) => $user->hasPermission('menu.combos.edit'));
Gate::define('menu.combos.delete', fn($user) => $user->hasPermission('menu.combos.delete'));
```

**Validación:**
- Todos los datos se validan en FormRequest
- Sanitización de inputs (nombre, descripción)
- Validación de imágenes (tipo, tamaño)

**Auditoría:**
- Soft deletes para mantener historial
- Timestamps automáticos (created_at, updated_at)

### Escalabilidad

**Soft Deletes:**
- Nunca eliminar físicamente combos
- Usar `deleted_at` para soft delete
- Útil para reportes históricos

**Jobs Programados:**
```php
// Detectar combos con productos inactivos
Schedule::command('combos:check-availability')->daily();
```

---

## Glosario

- **Combo**: Entidad independiente que agrupa productos bajo un precio especial
- **Item del Combo**: Referencia a un producto dentro del combo (via combo_items)
- **Producto Hijo**: Producto referenciado por un combo
- **Herencia de Personalización**: El combo usa las secciones del producto sin copiarlas
- **Precio Base**: Precio del combo SIN extras de personalización
- **Extras**: Opciones de personalización que agregan costo (is_extra=true)
- **Disponible**: Combo activo con todos sus productos activos
- **No Disponible**: Combo activo pero con productos inactivos
- **Soft Delete**: Eliminación lógica (no física) de registros

---

**Documento creado**: 2025-01-09
**Última actualización**: 2025-01-09
**Versión**: 2.0 (Arquitectura con Tabla Separada)
