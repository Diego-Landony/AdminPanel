# Implementación: Sistema de Productos con Variantes Inline

## Resumen

Se ha implementado exitosamente el nuevo flujo de creación/edición de productos con las siguientes características:

- **Categoría**: Selector dropdown de categorías existentes
- **Información del producto**: Nombre, descripción, imagen
- **Variantes opcionales**: Checkbox para activar/desactivar variantes
- **Precios condicionales**:
  - Si NO usa variantes: 4 campos de precios directos en el producto
  - Si USA variantes: Constructor inline de variantes con nombre + 4 precios cada una
- **Secciones**: Checkboxes para seleccionar secciones de personalización

## Cambios Realizados

### 1. Base de Datos

#### Migración: `2025_10_03_083444_add_category_and_pricing_to_products_table.php`
Agrega a la tabla `products`:
- `category_id` (FK a categories)
- `has_variants` (boolean flag)
- `precio_pickup_capital` (decimal nullable)
- `precio_domicilio_capital` (decimal nullable)
- `precio_pickup_interior` (decimal nullable)
- `precio_domicilio_interior` (decimal nullable)

#### Migración: `2025_10_03_083449_remove_prices_from_category_product_table.php`
Elimina campos de precios de la tabla pivot `category_product`:
- `precio_pickup_capital`
- `precio_domicilio_capital`
- `precio_pickup_interior`
- `precio_domicilio_interior`

### 2. Modelos

#### `app/Models/Menu/Product.php`
**Cambios**:
- Agregado `category_id`, `has_variants`, y 4 campos de precios a `$fillable`
- Agregado casts para `has_variants` (boolean) y precios (decimal:2)
- Nueva relación `category()`: BelongsTo
- Actualizada relación `categories()`: Eliminados precios del pivot

### 3. Validación

#### `app/Http/Requests/Menu/StoreProductRequest.php`
**Reglas agregadas**:
- `category_id`: requerido, debe existir
- `has_variants`: boolean
- Precios del producto: requeridos si `has_variants = false`
- `variants`: array requerido si `has_variants = true`, mínimo 1
- `variants.*.name`: string requerido
- `variants.*.precio_*`: numeric requerido para cada precio

#### `app/Http/Requests/Menu/UpdateProductRequest.php`
**Reglas agregadas**:
- Mismas reglas que StoreProductRequest
- `variants.*.id`: nullable, debe existir (para actualizar variantes existentes)

### 4. Controlador

#### `app/Http/Controllers/Menu/ProductController.php`
**Método `index()`**:
- Agregado `with('category')` y `withCount('variants')`
- Nueva estadística: `with_variants`

**Método `create()`**:
- Carga categorías activas para el selector

**Método `store()`**:
- Lógica condicional según `has_variants`:
  - Si `false`: guarda precios en el producto
  - Si `true`: crea variantes con sus precios
- Genera SKU automático para cada variante: `{slug}-{index}`
- Mantiene sync de secciones

**Método `edit()`**:
- Carga `category`, `variants` y `sections`
- Provee categorías para el selector

**Método `update()`**:
- Lógica condicional según `has_variants`:
  - Si `false`: actualiza precios del producto, elimina variantes existentes
  - Si `true`: actualiza/crea/elimina variantes según cambios
- Mantiene sync de secciones
- Actualiza variantes existentes si tienen `id`
- Crea nuevas variantes si no tienen `id`
- Elimina variantes que ya no están en la lista

### 5. Frontend

#### `resources/js/pages/menu/products/create.tsx`
**Estructura completa nueva**:
1. **Sección: Información del Producto**
   - Select de categorías
   - Nombre (input text)
   - Descripción (textarea)
   - Imagen (ImageUpload component)

2. **Sección: Precios y Variantes**
   - Checkbox "Este producto usa variantes"
   - **Modo SIN variantes**: 4 campos de precios en grid 2x2
   - **Modo CON variantes**:
     - Lista de variantes en cards
     - Cada card tiene: nombre + 4 precios en grid 2x2
     - Botón "Agregar Variante"
     - Botón X para eliminar variante (si hay más de 1)

3. **Sección: Secciones del Producto**
   - Checkboxes para seleccionar secciones

4. **Sección: Configuración**
   - Checkbox "Es personalizable"
   - Checkbox "Producto activo"

**Características**:
- Estado local para variantes y secciones seleccionadas
- Validación inline de errores
- Auto-agregar primera variante al activar checkbox
- Conditional rendering según `has_variants`

#### `resources/js/pages/menu/products/edit.tsx`
**Estructura idéntica a create.tsx con**:
- Pre-carga de datos del producto
- Pre-carga de variantes existentes con sus IDs
- Manejo de actualización vs creación de variantes
- Misma UX que create

## Flujo de Usuario

### Crear Producto

1. Usuario hace clic en "Crear Producto"
2. Selecciona categoría del dropdown
3. Completa nombre, descripción, imagen
4. **Decisión de variantes**:
   - **Sin variantes**: Completa 4 precios directos
   - **Con variantes**:
     - Marca checkbox "usa variantes"
     - Se muestra card de primera variante
     - Completa nombre (ej: "15cm") y 4 precios
     - Puede agregar más variantes con botón "Agregar Variante"
5. Selecciona secciones aplicables (checkboxes)
6. Configura opciones (personalizable, activo)
7. Click "Crear Producto"

### Editar Producto

1. Usuario hace clic en "Editar" en el listado
2. Ve formulario pre-llenado con datos actuales
3. **Si producto tiene variantes**: Ve lista de variantes existentes
4. **Si producto NO tiene variantes**: Ve 4 campos de precios
5. Puede cambiar de un modo a otro:
   - De precios directos a variantes: marca checkbox, se eliminarán precios directos
   - De variantes a precios directos: desmarca checkbox, se eliminarán variantes
6. Puede agregar/editar/eliminar variantes
7. Click "Guardar Cambios"

## Lógica de Negocio

### Regla Principal: `has_variants` flag

```
SI has_variants = false:
  - Precios se guardan en products.precio_*
  - product_variants tabla está vacía para este producto

SI has_variants = true:
  - products.precio_* son NULL
  - Cada variante tiene sus propios 4 precios en product_variants
```

### SKU Automático

- **Crear**: `{product_slug}-{index}` (ej: `sub-bmt-1`, `sub-bmt-2`)
- **Actualizar**: Se mantiene SKU existente, nuevas variantes usan `{product_slug}-{uniqid()}`

### Transaccionalidad

Todas las operaciones de crear/actualizar producto se ejecutan en transacción DB:
```php
DB::transaction(function () {
    // Crear/actualizar producto
    // Crear/actualizar/eliminar variantes
    // Sync secciones
});
```

## Estructura de Datos

### Producto SIN variantes
```json
{
  "id": 1,
  "category_id": 5,
  "name": "Ensalada Caesar",
  "has_variants": false,
  "precio_pickup_capital": 8.50,
  "precio_domicilio_capital": 9.00,
  "precio_pickup_interior": 9.00,
  "precio_domicilio_interior": 9.50,
  "variants": []
}
```

### Producto CON variantes
```json
{
  "id": 2,
  "category_id": 1,
  "name": "Sub B.M.T.",
  "has_variants": true,
  "precio_pickup_capital": null,
  "precio_domicilio_capital": null,
  "precio_pickup_interior": null,
  "precio_domicilio_interior": null,
  "variants": [
    {
      "id": 1,
      "name": "15cm",
      "sku": "sub-bmt-1",
      "precio_pickup_capital": 5.50,
      "precio_domicilio_capital": 6.00,
      "precio_pickup_interior": 6.00,
      "precio_domicilio_interior": 6.50
    },
    {
      "id": 2,
      "name": "30cm",
      "sku": "sub-bmt-2",
      "precio_pickup_capital": 10.00,
      "precio_domicilio_capital": 11.00,
      "precio_pickup_interior": 11.00,
      "precio_domicilio_interior": 11.50
    }
  ]
}
```

## Ventajas de esta Implementación

1. **UX Simplificada**: Todo en una sola página, sin navegación a otras vistas
2. **Flexibilidad**: Productos con/sin variantes en el mismo sistema
3. **Escalable**: Fácil agregar más tipos de precios o campos de variante
4. **Validación Clara**: Errores inline para cada campo de cada variante
5. **Transaccional**: Operaciones atómicas evitan estados inconsistentes
6. **Mantenible**: Código organizado y comentado

## Archivos Modificados

```
database/migrations/
├── 2025_10_03_083444_add_category_and_pricing_to_products_table.php
└── 2025_10_03_083449_remove_prices_from_category_product_table.php

app/Models/Menu/
└── Product.php

app/Http/Controllers/Menu/
└── ProductController.php

app/Http/Requests/Menu/
├── StoreProductRequest.php
└── UpdateProductRequest.php

resources/js/pages/menu/products/
├── create.tsx
└── edit.tsx
```

## Estado: ✅ COMPLETADO

Fecha: 2025-10-03
Build: Exitoso
Pint: Pasado
