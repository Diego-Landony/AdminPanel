# Cambio: is_customizable es ahora un Accessor Calculado

## ¿Qué cambió?

Antes, `is_customizable` era una columna en la base de datos que se tenía que setear manualmente.

Ahora, `is_customizable` es un **accessor calculado automáticamente** basado en si el producto tiene secciones.

## ¿Por qué?

Si un producto NO tiene secciones → NO puede ser personalizable
Si un producto TIENE secciones → ES personalizable

No tiene sentido guardar este dato en la BD cuando se puede calcular dinámicamente.

## Cambios Técnicos

### 1. Base de Datos

**Migración**: `2025_10_03_084927_remove_is_customizable_from_products_table.php`

```php
Schema::table('products', function (Blueprint $table) {
    $table->dropColumn('is_customizable');
});
```

### 2. Modelo Product

**Agregado accessor**:
```php
protected $appends = ['is_customizable'];

public function getIsCustomizableAttribute(): bool
{
    return $this->sections()->count() > 0;
}
```

**Eliminado**:
- `is_customizable` de `$fillable`
- `is_customizable` de `$casts`

### 3. Controlador

**Eliminado**:
```php
// Ya NO se hace esto:
$validated['is_customizable'] = ! empty($sectionIds);
```

El valor se calcula automáticamente cuando accedes a `$product->is_customizable`.

### 4. Frontend

**Eliminado checkbox** de:
- `resources/js/pages/menu/products/create.tsx`
- `resources/js/pages/menu/products/edit.tsx`

**Eliminado** `is_customizable` del form data.

## ¿Cómo funciona ahora?

```php
// Crear producto
$product = Product::create([
    'name' => 'Sub B.M.T.',
    // ... otros campos
]);

// Asociar secciones
$product->sections()->attach([1, 2, 3]);

// Automáticamente:
$product->is_customizable; // true (porque tiene 3 secciones)
```

```php
// Producto sin secciones
$product = Product::create([
    'name' => 'Bebida Coca Cola',
]);

// NO asociar secciones

// Automáticamente:
$product->is_customizable; // false (porque tiene 0 secciones)
```

## Ventajas

✅ **Siempre consistente**: No puede haber inconsistencias entre el flag y las secciones
✅ **Menos código**: No hay que setear manualmente en create/update
✅ **Más simple**: Una columna menos en la BD
✅ **Performance**: El accessor hace un `count()` eficiente
✅ **Auto-serializable**: Se incluye automáticamente en JSON/arrays gracias a `$appends`

## Testing

El comportamiento es transparente para el frontend y la API:

```json
// GET /api/products/1
{
  "id": 1,
  "name": "Sub B.M.T.",
  "is_customizable": true,  // ← Calculado automáticamente
  "sections": [
    {"id": 1, "title": "Vegetales"},
    {"id": 2, "title": "Salsas"}
  ]
}
```

## Estado: ✅ COMPLETADO

- Migración ejecutada
- Modelo actualizado con accessor
- Controlador limpio
- Frontend simplificado
- Pint pasado

Fecha: 2025-10-03
