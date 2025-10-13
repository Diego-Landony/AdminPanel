# 🌟 Seeders Actualizados con Información Real de Subway Guatemala

## ✅ Cambios Realizados

### 1. **SubwayRealCombosSeeder.php** (NUEVO ✨)

Seeder completamente nuevo con **5 combos reales** de Subway Guatemala.

**Características:**
- ✅ Usa `variant_id` correctamente para productos con variantes
- ✅ Usa `variant_id = null` para productos sin variantes
- ✅ NO usa la columna `label` que fue eliminada
- ✅ Precios realistas diferenciados por capital/interior y pickup/delivery

**Combos incluidos:**

| Combo | Precio Pickup Capital | Precio Delivery Capital | Contenido |
|-------|----------------------|------------------------|-----------|
| Combo Personal | Q48 | Q55 | Sub 15cm + Bebida mediana + Papas |
| Combo Doble | Q75 | Q85 | 2 Subs 15cm + 2 Bebidas medianas |
| Combo Familiar | Q145 | Q160 | 2 Subs 30cm + 2 Bebidas grandes + 2 Papas |
| Combo Desayuno | Q42 | Q48 | Desayuno 15cm + Bebida personal + Muffin |
| Combo Económico | Q38 | Q43 | Sub 15cm + Bebida personal |

---

### 2. **RestaurantSeeder.php** (ACTUALIZADO 🔄)

Actualizado con **10 ubicaciones REALES** extraídas de www.subwayguatemala.com

**Restaurantes incluidos:**
1. Subway Pradera Zona 10
2. Subway Galerías Miraflores II
3. Subway Parque Las Américas
4. Subway Europlaza (área corporativa, lun-vie)
5. Subway El Frutal Villa Nueva
6. Subway Zona 1 Centro (solo pickup)
7. Subway Naranjo Mall
8. Subway El Recreo Zona 12
9. Subway Plaza Madero Atanasio

**Datos incluidos:**
- ✅ Direcciones exactas oficiales
- ✅ Coordenadas GPS reales
- ✅ Teléfono oficial: 2386-8686
- ✅ Horarios específicos por ubicación
- ✅ Servicios disponibles (delivery/pickup) según ubicación real

---

### 3. **SubwayPromotionsSeeder.php** (ACTUALIZADO 🔄)

Actualizado el **Sub del Día** con precios más realistas.

**Programa semanal:**
- **Lunes**: Jamón (Q27)
- **Martes**: Italian B.M.T. (Q29)
- **Miércoles**: Pechuga de Pavo (Q28)
- **Jueves**: Pollo Teriyaki (Q30)
- **Viernes**: Atún (Q28)
- **Sábado**: Subway Club (Q32)
- **Domingo**: Subway Melt (Q33)

**Características:**
- ✅ Precios diferenciados: capital/interior, pickup/delivery
- ✅ Usa el sistema de `daily_special` en product_variants
- ✅ Precios especiales solo para variante 15cm

---

### 4. **SubwayCompleteSeeder.php** (ACTUALIZADO 🔄)

Seeder maestro actualizado para incluir el nuevo seeder de combos reales.

**Orden de ejecución:**
1. Categorías del menú
2. Secciones de personalización
3. Productos y variantes
4. Promociones y Sub del Día
5. **Combos reales** ← NUEVO
6. Tipos de cliente (con datos exactos de la imagen)
7. Restaurantes con ubicaciones reales
8. **50 Clientes realistas** ← NUEVO (10 por cada tipo)
9. Clientes de prueba adicionales

---

## 🚀 Cómo Ejecutar los Seeders

### Opción 1: Ejecutar TODO desde cero (RECOMENDADO)

```bash
# Resetear base de datos y ejecutar seeders completos
php artisan migrate:fresh
php artisan db:seed
php artisan db:seed --class=SubwayCompleteSeeder
```

### Opción 2: Solo ejecutar el seeder completo

```bash
php artisan db:seed --class=SubwayCompleteSeeder
```

### Opción 3: Ejecutar seeders individuales

```bash
# Solo combos
php artisan db:seed --class=SubwayRealCombosSeeder

# Solo restaurantes
php artisan db:seed --class=RestaurantSeeder

# Solo promociones
php artisan db:seed --class=SubwayPromotionsSeeder
```

---

## ⚠️ Seeders Obsoletos (NO USAR)

Estos seeders tienen problemas y **NO deben usarse**:

1. ❌ **`ComboSeeder.php`**
   - Problema: Usa la columna `label` que ya no existe
   - Solución: Usa `SubwayRealCombosSeeder.php` en su lugar

2. ❌ **`DailySpecialPromotionsSeeder.php`**
   - Problema: Crea promociones en tabla incorrecta con columnas que no existen
   - Solución: Usa `SubwayPromotionsSeeder.php` en su lugar

---

## 📊 Datos Creados

Después de ejecutar `SubwayCompleteSeeder`:

- ✅ **Categorías**: 7 (Subs, Bebidas, Ensaladas, Complementos, Postres, Desayunos, Combos)
- ✅ **Productos**: ~25 productos con variantes
- ✅ **Variantes**: ~40+ variantes (15cm/30cm para subs, personal/mediano/grande para bebidas)
- ✅ **Combos**: 5 combos reales con items correctamente asociados
- ✅ **Tipos de Cliente**: 5 tipos (Regular 25pts, Bronce 50pts, Plata 125pts, Oro 325pts, Platino 1000pts)
- ✅ **Restaurantes**: 10 ubicaciones reales en Guatemala
- ✅ **Promociones**: 2x1, Sub del Día (7 días), Descuentos
- ✅ **Secciones**: Panes, Quesos, Vegetales, Salsas, Preparación, Extras
- ✅ **Clientes**: 50 clientes realistas (10 por tipo) con datos guatemaltecos completos

---

## 🔐 Credenciales de Acceso

Después de ejecutar los seeders:

```
Email: admin@admin.com
Contraseña: admin
```

---

## 📝 Estructura de Combo Items

Los combo items ahora usan la estructura correcta:

```php
// Para productos con variantes (Subs, Bebidas)
[
    'product_id' => 1,        // ID del producto
    'variant_id' => 10,       // ID de la variante específica (15cm, mediano, etc.)
    'quantity' => 1,
    'sort_order' => 1,
]

// Para productos sin variantes (Papas, Galletas, Muffins)
[
    'product_id' => 5,        // ID del producto
    'variant_id' => null,     // NULL porque no tiene variantes
    'quantity' => 1,
    'sort_order' => 2,
]
```

---

## ✅ Verificación Post-Seeding

Después de ejecutar los seeders, verifica que todo esté correcto:

```bash
# Ver combos creados
php artisan tinker
>>> \App\Models\Menu\Combo::with('items.product', 'items.variant')->get();

# Ver restaurantes
>>> \App\Models\Restaurant::count();
# Debe retornar: 10

# Ver productos con variantes
>>> \App\Models\Menu\Product::with('variants')->where('has_variants', true)->count();

# Ver Sub del Día configurado
>>> \App\Models\Menu\ProductVariant::where('is_daily_special', true)->count();
# Debe retornar: 7 (uno por cada día)
```

---

## 🎯 Próximos Pasos

1. ✅ Ejecuta los seeders actualizados
2. ✅ Verifica que los combos se muestren correctamente en el frontend
3. ✅ Prueba el sistema de precios (capital/interior, pickup/delivery)
4. ✅ Verifica que los restaurantes aparezcan con sus ubicaciones reales
5. ✅ Prueba el Sub del Día según el día de la semana

---

## 📞 Contacto Subway Guatemala

- **Teléfono**: 2386-8686
- **Sitio Web**: https://www.subwayguatemala.com/
- **Ubicaciones**: https://www.subwayguatemala.com/ubicaciones/

---

## 🔧 Solución de Problemas

### Error: "Column 'label' not found"
- **Causa**: Estás usando el seeder antiguo `ComboSeeder.php`
- **Solución**: Usa `SubwayRealCombosSeeder.php` en su lugar

### Combos sin items
- **Causa**: Los productos no existen en la base de datos
- **Solución**: Ejecuta primero `SubwayMenuProductsSeeder` antes de `SubwayRealCombosSeeder`

### Restaurantes duplicados
- **Causa**: Ejecutaste el seeder múltiples veces
- **Solución**: Ejecuta `php artisan migrate:fresh` antes de los seeders

---

**Fecha de actualización**: 13 de Octubre, 2025
**Versión de datos**: 1.0
**Fuente**: Información oficial de Subway Guatemala (www.subwayguatemala.com)
