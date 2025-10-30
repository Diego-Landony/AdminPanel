# Plan de Implementación: Bundle Promotions

**Fecha:** 30 de Octubre, 2025
**Autor:** Claude AI
**Tipo de implementación:** Nueva funcionalidad

---

## 1. OBJETIVO

Implementar un sistema de **Bundle Promotions** (ofertas especiales tipo combo con vigencia temporal) que permita crear combos promocionales con precios fijos y restricciones temporales (fechas y horarios).

### Qué es Bundle Promotions

Combos temporales con precio especial que combinan múltiples productos en una oferta limitada por tiempo. A diferencia de los combos permanentes del menú, estos son ofertas especiales con vigencia configurable.

**Ejemplo de uso:**
```
"Combo San Valentín" - Q45.00
├── Válido: 10-14 febrero, 5pm-11pm
├── Sub 15cm a elección
├── Bebida
└── Cookie
```

### Casos de uso

1. **Ofertas estacionales**: "Combo Navideño", "Combo Día de la Madre"
2. **Ofertas por horario**: "Combo Almuerzo" (11am-2pm), "Combo Cena" (6pm-9pm)
3. **Ofertas de fin de semana**: "Combo Dominical" (solo domingos)
4. **Ofertas flash**: "Combo del Mes" con vigencia de 30 días

---

## 2. DIFERENCIACIÓN CONCEPTUAL

### Tabla Comparativa

| Característica | Combos (Menú) | Promociones Actuales | Bundle Promotions (Nuevo) |
|----------------|---------------|---------------------|---------------------------|
| **Ubicación** | Menú | Promociones | Promociones |
| **Naturaleza** | Producto independiente | Modificador de precio | Producto temporal |
| **Precio** | Fijo permanente | Descuento/regla | Fijo temporal |
| **Vigencia** | Permanente | Temporal (días/horas) | Temporal (fechas + horarios) |
| **Estructura** | Items + opciones | Items con reglas | Items + opciones (igual que combos) |
| **Ejemplo** | "Combo Personal Q48" | "2x1 en Subs", "10% descuento" | "Combo Navideño Q55" (dic 1-25) |

### Decisión de Diseño

**Por qué en Promociones y no en Menú:**

1. **Vigencia temporal**: Principal diferenciador. Son ofertas limitadas, no productos permanentes.
2. **Naturaleza promocional**: Son estrategias de marketing, no items estables del menú.
3. **Gestión centralizada**: Facilita que el equipo de marketing gestione todas las promociones en un solo lugar.
4. **Reutilización de estructura**: Aprovecha el sistema existente de promociones (vigencia, servicios, validaciones).

---

## 3. REQUISITOS FUNCIONALES

### 3.1 Estructura de Bundle

**Items del bundle:**
- **Items fijos**: Productos específicos incluidos (ej: 1 Bebida Lata)
- **Grupos de elección**: Cliente elige entre opciones (ej: Elige tu Sub: BMT / Pavo / Atún)

**Configuración de items:**
- Producto base (obligatorio)
- Variante específica (opcional, solo si el producto tiene variantes)
- Cantidad (default: 1)
- Orden de presentación (sort_order)

### 3.2 Precios

Dos precios únicos por bundle (sin diferenciar pickup/delivery):
- **Precio Capital**: Para zona capital
- **Precio Interior**: Para zona interior

**Justificación**: Los bundles son ofertas especiales con precio único independiente del servicio, simplificando la compra.

### 3.3 Vigencia Temporal

**Rango de fechas:**
- `valid_from`: Fecha de inicio (nullable = sin límite inferior)
- `valid_until`: Fecha de fin (nullable = sin límite superior)

**Horarios específicos:**
- `time_from`: Hora de inicio (nullable = todo el día)
- `time_until`: Hora de fin (nullable = todo el día)

**Lógica de validación:**
```
Bundle es válido SI:
  (hoy >= valid_from O valid_from es null) Y
  (hoy <= valid_until O valid_until es null) Y
  (hora_actual >= time_from O time_from es null) Y
  (hora_actual <= time_until O time_until es null)
```

### 3.4 Estados

- **Activo/Inactivo**: Control manual por admin
- **Válido ahora**: Calculado automáticamente según vigencia temporal
- **Próximamente**: Bundle creado pero aún no vigente
- **Expirado**: Bundle cuya vigencia ya terminó

### 3.5 Validaciones Backend

**Al crear/editar:**
1. Bundle debe tener al menos 1 item
2. Items fijos deben tener product_id (y variant_id si el producto tiene variantes)
3. Grupos de elección deben tener al menos 2 opciones
4. Si tiene valid_from y valid_until, valid_from < valid_until
5. Si tiene time_from y time_until, time_from < time_until
6. Precios > 0
7. No permitir eliminar bundle si tiene pedidos asociados (soft delete)

**Validaciones de negocio:**
1. Productos/variantes seleccionados deben estar activos
2. Si un producto se desactiva, mostrar advertencia en bundles afectados
3. Validar disponibilidad de productos antes de mostrar bundle al cliente

---

## 4. DISEÑO UX/UI

Siguiendo patrones establecidos en `UX-UI.md`.

### 4.1 Estructura de Navegación

```
Menu (web.php)
├── Categories
├── Products
├── Promotions
│   ├── Sub del Día (daily-special)
│   ├── 2x1 (two-for-one)
│   ├── Porcentaje (percentage)
│   └── Bundles (bundle) ← NUEVO
├── Sections
└── Combos
```

**Rutas:**
```
GET  /menu/promotions/bundle           → Lista de bundles
GET  /menu/promotions/bundle/create    → Crear bundle
POST /menu/promotions                  → Store (reutilizado)
GET  /menu/promotions/{id}/edit        → Editar (detecta tipo bundle)
PUT  /menu/promotions/{id}             → Update (reutilizado)
```

### 4.2 Index Page (Lista de Bundles)

**Componente de tabla:** `DataTable` (paginada)

**Columnas:**
1. **Bundle** (width: lg)
   - Nombre del bundle
   - Descripción (secundario, muted)
   - Usar `EntityInfoCell`

2. **Precio** (width: sm, center)
   - Capital / Interior
   - Formato: Q45 / Q47

3. **Vigencia** (width: md)
   - Fechas: "10 dic - 25 dic"
   - Horarios: "5pm - 11pm"
   - "Permanente" si no tiene restricciones

4. **Estado** (width: sm, center)
   - Badge: Activo / Inactivo / Expirado / Próximamente
   - Usar `StatusBadge` con config personalizada

5. **Acciones** (width: xs, right)
   - Editar / Eliminar
   - Usar `TableActions`

**Stats:**
```typescript
stats = [
  { title: 'bundles', value: total, icon: Gift },
  { title: 'activos', value: active, icon: Star },
  { title: 'válidos hoy', value: validNow, icon: Clock }
]
```

**Mobile Card:** `StandardMobileCard`
- Icon: Gift
- Title: Nombre del bundle
- Subtitle: Precio capital/interior
- Badge: Estado (activo/inactivo/expirado)
- DataFields:
  - Vigencia (fechas)
  - Horario
  - Items (cantidad)

### 4.3 Create Page (Crear Bundle)

**Layout:** `CreatePageLayout`

**Secciones (FormSection):**

**1. Información Básica** (icon: Gift)
- Nombre del bundle (Input, required)
- Descripción (Textarea, opcional)
- Checkbox: "Bundle activo"

**2. Precios Especiales** (icon: Banknote)
- Precio Capital (Input number, required)
- Precio Interior (Input number, required)
- Info: "Precio único para pickup y delivery"

**3. Vigencia Temporal** (icon: Calendar)
- Date Range: "Fecha inicio" - "Fecha fin" (ambos nullable)
- Time Range: "Hora inicio" - "Hora fin" (ambos nullable)
- Tooltip: "Deja vacío para sin límite"

**4. Items del Bundle** (icon: Package)
- Builder similar a combos
- Botón: "Agregar Item Fijo"
- Botón: "Agregar Grupo de Elección"

**Componente de Items:**
- Reutilizar/adaptar lógica de `combos/create.tsx`
- Drag & drop para reordenar (DndKit)
- Card por item:
  - Item fijo: Selector de producto + variante + cantidad
  - Grupo de elección: Label + lista de opciones (mínimo 2)

### 4.4 Edit Page (Editar Bundle)

**Layout:** `EditPageLayout`

**Mismas secciones que Create**

**Adicionales:**
- Mostrar warning si bundle tiene pedidos asociados
- Mostrar warning si productos/variantes están inactivos
- Track changes con `isDirty`
- Botón "Descartar cambios" si hay cambios sin guardar

### 4.5 Componentes a Reutilizar

Según `UX-UI.md`:

- `CreatePageLayout` / `EditPageLayout`
- `FormSection`
- `FormField`
- `DataTable`
- `StandardMobileCard`
- `TableActions`
- `StatusBadge`
- `DeleteConfirmationDialog`
- `EntityInfoCell`
- Inputs: `Input`, `Textarea`, `Checkbox`, `Select`
- Date/Time pickers (Shadcn)

### 4.6 Constantes UI

Agregar a `ui-constants.ts`:

```typescript
// PLACEHOLDERS
bundleName: 'Combo Navideño'
bundleDescription: 'Descripción de la oferta especial'

// FORM_SECTIONS
bundleItems: {
  title: 'Items del Bundle',
  description: 'Define los productos incluidos en esta oferta'
}
temporalValidity: {
  title: 'Vigencia Temporal',
  description: 'Configura las fechas y horarios de la oferta'
}

// STATUS (nuevo config)
BUNDLE_STATUS_CONFIGS = {
  active: { label: 'Activo', color: 'green' },
  inactive: { label: 'Inactivo', color: 'gray' },
  expired: { label: 'Expirado', color: 'red' },
  upcoming: { label: 'Próximamente', color: 'blue' }
}
```

---

## 5. MODELO DE DATOS

### 5.1 Tablas Nuevas

**`bundle_promotion_items`**
- Almacena los items que componen un bundle
- Similar a `combo_items`
- Relación: promotion_id (FK promotions)

**Campos:**
- `id`: BIGINT PK
- `promotion_id`: BIGINT FK (promotions)
- `product_id`: BIGINT FK (products) - Nullable si es grupo de elección
- `variant_id`: BIGINT FK (product_variants) - Nullable
- `is_choice_group`: BOOLEAN - True si es grupo de elección
- `choice_label`: VARCHAR(255) - Label del grupo (ej: "Elige tu Sub")
- `quantity`: INT - Cantidad (default: 1)
- `sort_order`: INT - Orden de presentación
- Timestamps

**`bundle_promotion_item_options`**
- Opciones dentro de un grupo de elección
- Similar a `combo_item_options`

**Campos:**
- `id`: BIGINT PK
- `bundle_item_id`: BIGINT FK (bundle_promotion_items)
- `product_id`: BIGINT FK (products)
- `variant_id`: BIGINT FK (product_variants) - Nullable
- `sort_order`: INT
- Timestamps

### 5.2 Modificaciones a Tablas Existentes

**`promotions`**

Agregar campos:
- `type`: Actualizar ENUM para incluir 'bundle_special'
- `special_bundle_price_capital`: DECIMAL(10,2) - Nullable
- `special_bundle_price_interior`: DECIMAL(10,2) - Nullable
- `valid_from`: DATE - Nullable
- `valid_until`: DATE - Nullable
- `time_from`: TIME - Nullable
- `time_until`: TIME - Nullable

**Nota:** Estos campos solo se usan cuando type = 'bundle_special'

### 5.3 Relaciones

```
Promotion (type='bundle_special')
├── bundleItems() hasMany BundlePromotionItem
    └── options() hasMany BundlePromotionItemOption
```

### 5.4 Scopes y Métodos

**Model: Promotion**

Scopes:
- `scopeBundle($query)` - Where type = 'bundle_special'
- `scopeValidNow($query)` - Bundles válidos en este momento
- `scopeUpcoming($query)` - Bundles que aún no inician
- `scopeExpired($query)` - Bundles cuya vigencia terminó

Métodos:
- `isValidNow()` - Bool, verifica vigencia temporal
- `getPriceForZone(string $zone)` - Retorna precio según zona
- `hasActiveItems()` - Bool, verifica si todos los items están activos
- `getInactiveItemsCount()` - Int, cuenta items inactivos

**Model: BundlePromotionItem**

Métodos:
- `isChoiceGroup()` - Bool
- `isAvailable()` - Bool, verifica si producto está activo
- `getActiveOptionsCount()` - Int, para grupos de elección

---

## 6. FASES DE IMPLEMENTACIÓN

### FASE 1: Fundamentos Backend (Base de Datos y Modelos)

**Objetivo:** Crear la estructura de datos y modelos necesarios.

**Tareas:**
1. Crear migration `create_bundle_promotions_structure.php`
   - Tabla `bundle_promotion_items`
   - Tabla `bundle_promotion_item_options`
   - Modificar `promotions`: agregar type='bundle_special' + campos temporales + precios
   - Índices para performance

2. Crear modelo `BundlePromotionItem.php`
   - Fillable, casts
   - Relaciones: promotion(), product(), variant(), options()
   - Métodos: isChoiceGroup(), isAvailable()

3. Crear modelo `BundlePromotionItemOption.php`
   - Fillable, casts
   - Relaciones: bundleItem(), product(), variant()

4. Actualizar modelo `Promotion.php`
   - Agregar relación bundleItems()
   - Scopes: scopeBundle(), scopeValidNow(), scopeUpcoming(), scopeExpired()
   - Métodos: isValidNow(), getPriceForZone(), hasActiveItems()
   - Casts para fechas y horas

**Criterios de aceptación:**
- Migration ejecuta sin errores
- Modelos creados con relaciones funcionales
- Scopes retornan correctamente según vigencia

**Archivos a crear/modificar:**
- `database/migrations/2025_10_30_XXXXXX_create_bundle_promotions_structure.php`
- `app/Models/Menu/BundlePromotionItem.php`
- `app/Models/Menu/BundlePromotionItemOption.php`
- `app/Models/Menu/Promotion.php` (modificar)

---

### FASE 2: Backend Logic (Controlador, Validaciones, Rutas)

**Objetivo:** Implementar lógica de negocio, validaciones y endpoints.

**Tareas:**
1. Crear `StoreBundlePromotionRequest.php`
   - Validar estructura básica (nombre, precios)
   - Validar vigencia temporal (fechas, horarios)
   - Validar items (mínimo 1, estructura correcta)
   - Validar grupos de elección (mínimo 2 opciones)

2. Crear `UpdateBundlePromotionRequest.php`
   - Mismas validaciones que Store
   - Adicional: validar que no se elimine bundle con pedidos asociados

3. Actualizar `PromotionController.php`
   - `bundleIndex()` - Lista bundles con stats
   - `createBundle()` - Form creación
   - Modificar `store()` - Detectar type='bundle_special' y crear items
   - Modificar `update()` - Detectar type='bundle_special' y actualizar items
   - `toggle()` - Activar/desactivar bundle

4. Agregar rutas en `routes/web.php`
   ```
   Route::prefix('promotions')->group(function () {
       Route::get('/bundle', [PromotionController::class, 'bundleIndex'])->name('bundle.index');
       Route::get('/bundle/create', [PromotionController::class, 'createBundle'])->name('bundle.create');
       // Resto de rutas reutilizan las existentes
   });
   ```

**Criterios de aceptación:**
- Requests validan correctamente todos los casos edge
- Controlador crea bundles con items y opciones en transacción
- Rutas responden correctamente
- Soft delete funciona para bundles con pedidos

**Archivos a crear/modificar:**
- `app/Http/Requests/Menu/StoreBundlePromotionRequest.php`
- `app/Http/Requests/Menu/UpdateBundlePromotionRequest.php`
- `app/Http/Controllers/Menu/PromotionController.php` (modificar)
- `routes/web.php` (modificar)

---

### FASE 3: Frontend Index (Listado de Bundles)

**Objetivo:** Crear la vista de listado de bundles con filtros y stats.

**Tareas:**
1. Crear componente `resources/js/pages/menu/promotions/bundle/index.tsx`
   - Usar `DataTable` paginada
   - Implementar columnas según diseño UX (5 columnas)
   - Stats: total, activos, válidos hoy
   - Filtros: búsqueda, estado
   - Integrar `DeleteConfirmationDialog`

2. Implementar `renderMobileCard` con `StandardMobileCard`
   - Icon: Gift
   - Datos: nombre, precios, vigencia, estado
   - Acciones: editar, eliminar

3. Crear `BundlesSkeleton` en `resources/js/components/skeletons.tsx`
   - Loading state para tabla

4. Agregar config de status a `status-badge.tsx`
   ```typescript
   BUNDLE_STATUS_CONFIGS = {
     active: { ... },
     inactive: { ... },
     expired: { ... },
     upcoming: { ... }
   }
   ```

5. Actualizar `ui-constants.ts`
   - Placeholders: bundleName, bundleDescription
   - Form sections para bundles

**Criterios de aceptación:**
- Tabla muestra bundles con paginación
- Stats calculan correctamente
- Búsqueda funciona
- Mobile responsive con cards
- Estados visuales correctos (activo/expirado/próximamente)
- Delete confirmation funciona

**Archivos a crear/modificar:**
- `resources/js/pages/menu/promotions/bundle/index.tsx`
- `resources/js/components/skeletons.tsx` (agregar BundlesSkeleton)
- `resources/js/components/status-badge.tsx` (agregar BUNDLE_STATUS_CONFIGS)
- `resources/js/constants/ui-constants.ts` (agregar constantes)

---

### FASE 4: Frontend Create/Edit (Formularios)

**Objetivo:** Implementar formularios de creación y edición de bundles.

**Tareas:**
1. Crear `resources/js/pages/menu/promotions/bundle/create.tsx`
   - Layout: `CreatePageLayout`
   - 4 FormSections: Info básica, Precios, Vigencia, Items
   - Builder de items con drag & drop
   - Lógica: items fijos vs grupos de elección
   - Validación frontend básica

2. Crear `resources/js/pages/menu/promotions/bundle/edit.tsx`
   - Layout: `EditPageLayout`
   - Mismas secciones que Create
   - Pre-cargar datos existentes
   - Track changes con `isDirty`
   - Warnings: productos inactivos, pedidos asociados

3. Crear componente reutilizable `BundleItemsBuilder.tsx`
   - Wrapper para gestión de items
   - Botones: "Agregar Item Fijo", "Agregar Grupo"
   - Drag & drop con DndKit
   - Card por item con acciones (editar, eliminar, reordenar)

4. Crear subcomponente `BundleItemCard.tsx`
   - Renderiza un item fijo o grupo de elección
   - Selector de producto + variante + cantidad
   - Para grupos: lista de opciones (mínimo 2)

5. Adaptar `ProductCombobox.tsx` existente
   - Reutilizar para selector de productos
   - Agregar filtro por variantes si aplica

**Criterios de aceptación:**
- Create form crea bundles correctamente
- Edit form carga y actualiza datos
- Drag & drop reordena items
- Validaciones frontend previenen errores comunes
- Warnings se muestran apropiadamente
- Mobile responsive

**Archivos a crear/modificar:**
- `resources/js/pages/menu/promotions/bundle/create.tsx`
- `resources/js/pages/menu/promotions/bundle/edit.tsx`
- `resources/js/components/bundles/BundleItemsBuilder.tsx`
- `resources/js/components/bundles/BundleItemCard.tsx`
- `resources/js/components/ProductCombobox.tsx` (posible adaptación)

---

### FASE 5: Testing, Seeders y Refinamiento

**Objetivo:** Pruebas, datos de ejemplo y ajustes finales.

**Tareas:**
1. Crear `BundlePromotionsSeeder.php`
   - 5-8 bundles de ejemplo
   - Variedad: diferentes vigencias, horarios, estados
   - Ejemplos: "Combo Navideño", "Combo Almuerzo", "Combo Fin de Semana"
   - Usar productos reales de la base de datos

2. Crear tests unitarios
   - `BundlePromotionItemTest.php` - Test modelo y relaciones
   - `BundlePromotionItemOptionTest.php` - Test modelo y relaciones

3. Crear tests de feature
   - `BundlePromotionControllerTest.php`
     - Test CRUD completo
     - Test validaciones (fechas, horarios, items)
     - Test vigencia temporal (isValidNow)
     - Test soft delete
     - Test toggle activo/inactivo

4. Actualizar `PromotionControllerTest.php` existente
   - Agregar tests para bundle_special type
   - Test integración con otros tipos de promociones

5. Testing manual
   - Crear bundle desde UI
   - Editar bundle existente
   - Verificar validaciones frontend/backend
   - Verificar mobile responsive
   - Verificar dark mode
   - Verificar estados: activo, inactivo, expirado, próximamente

6. Refinamiento
   - Ajustar estilos según feedback visual
   - Optimizar queries (N+1)
   - Agregar índices si es necesario
   - Mejorar mensajes de error
   - Agregar tooltips donde sea útil

7. Documentación
   - Actualizar `RESUMEN-AVANCES.md` con sección de Bundle Promotions
   - Actualizar `UX-UI.md` si se crearon componentes nuevos reutilizables

**Criterios de aceptación:**
- Seeders crean datos realistas
- Tests pasan al 100%
- Coverage de pruebas > 80% en lógica de bundles
- No hay N+1 queries
- UI funciona en mobile y desktop
- Dark mode funciona correctamente
- Documentación actualizada

**Archivos a crear/modificar:**
- `database/seeders/BundlePromotionsSeeder.php`
- `tests/Unit/Models/BundlePromotionItemTest.php`
- `tests/Unit/Models/BundlePromotionItemOptionTest.php`
- `tests/Feature/Menu/BundlePromotionControllerTest.php`
- `tests/Feature/Menu/PromotionControllerTest.php` (modificar)
- `docs/RESUMEN-AVANCES.md` (actualizar)
- `docs/UX-UI.md` (actualizar si aplica)

---

## 7. CONSIDERACIONES TÉCNICAS

### 7.1 Performance

**Optimizaciones:**
- Eager loading: `with(['bundleItems.product', 'bundleItems.options.product'])`
- Índices en `bundle_promotion_items`: `promotion_id`, `product_id`, `is_choice_group`
- Índices en `bundle_promotion_item_options`: `bundle_item_id`, `product_id`
- Cache para bundles válidos en el día (opcional, fase posterior)

### 7.2 Transacciones

**Operaciones que requieren transacción:**
- Create bundle (promotion + items + options)
- Update bundle (delete items antiguos + crear nuevos)
- Delete bundle (verificar pedidos asociados)

### 7.3 Manejo de Errores

**Errores comunes a manejar:**
- Fechas inválidas (from > until)
- Items sin producto
- Grupos de elección con < 2 opciones
- Productos/variantes inactivos
- Bundle con pedidos asociados (no permitir hard delete)

### 7.4 Seguridad

**Validaciones de seguridad:**
- Verificar que productos/variantes pertenecen al negocio
- Validar permisos: `menu.promotions.create`, `menu.promotions.edit`, etc.
- Sanitizar inputs
- Prevenir mass assignment con fillable

### 7.5 Compatibilidad

**Sistemas afectados:**
- **App Cliente**: Deberá consultar bundles válidos y mostrarlos como ofertas especiales
- **Sistema de Pedidos**: Deberá soportar bundles en el carrito
- **Sistema de Reportes**: Incluir bundles en reportes de ventas

**Nota:** Implementación en App Cliente está fuera del scope de este plan (solo Panel Admin).

---

## 8. RIESGOS Y MITIGACIONES

| Riesgo | Probabilidad | Impacto | Mitigación |
|--------|--------------|---------|------------|
| Complejidad del builder de items | Media | Alto | Reutilizar lógica de combos existente, iterar en pequeños pasos |
| Validaciones de vigencia complejas | Media | Medio | Tests exhaustivos de todos los casos edge |
| Performance con muchos bundles | Baja | Medio | Eager loading + índices, considerar cache posterior |
| Confusión usuario: combos vs bundles | Media | Bajo | Nomenclatura clara, tooltips explicativos |

---

## 9. CRITERIOS DE ÉXITO

**La implementación se considera exitosa cuando:**

1. ✅ Admin puede crear bundles con items fijos y grupos de elección
2. ✅ Admin puede configurar vigencia temporal (fechas + horarios)
3. ✅ Bundles se muestran/ocultan automáticamente según vigencia
4. ✅ Sistema valida correctamente todas las reglas de negocio
5. ✅ UI es responsive y sigue patrones de UX-UI.md
6. ✅ Tests cubren > 80% de la lógica
7. ✅ Seeders crean datos de ejemplo realistas
8. ✅ No hay N+1 queries
9. ✅ Documentación actualizada

---

## 10. PRÓXIMOS PASOS POST-IMPLEMENTACIÓN

**Fuera del scope actual, pero considerar para el futuro:**

1. **App Cliente**: Mostrar bundles en el menú como ofertas especiales
2. **Sistema de Carrito**: Soportar selección de opciones en bundles
3. **Reportes**: Analytics de bundles (más vendido, conversión, etc.)
4. **Notificaciones**: Avisar a clientes cuando inicia un bundle (push notifications)
5. **Descuentos acumulables**: Permitir combinar bundles con puntos de fidelidad
6. **Variantes de precio**: Diferenciar pickup vs delivery en bundles (si se requiere)
7. **Restricciones adicionales**: Por restaurante, por tipo de cliente, etc.

---

## NOTAS FINALES

Este plan está diseñado para ser implementado por Claude AI siguiendo estrictamente:
- Convenciones del proyecto (CLAUDE.md, rules.md)
- Patrones de diseño establecidos (UX-UI.md)
- Arquitectura Laravel 12 existente
- Componentes React/Inertia reutilizables

**Tiempo estimado total:** 10-14 horas de desarrollo (no incluye testing manual ni refinamiento UI)

**Complejidad:** Media-Alta

**Impacto:** Alto (nueva funcionalidad completa)

---

**Aprobación requerida para proceder a implementación.**
