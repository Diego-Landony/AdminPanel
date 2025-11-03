# Plan de Implementación: Combinados (Bundle Promotions)

**Documento**: Plan de Implementación Técnica
**Fecha**: 31 de Octubre, 2025
**Versión**: 1.0
**Alcance**: Panel de Administración (ADMIN)

---

## Resumen Ejecutivo

Implementación de sistema de **Combinados** (Bundle Promotions): combos temporales con precio fijo y vigencia configurable (fechas, horarios, fechas y horarios). A diferencia de los combos permanentes del menú, estos son ofertas especiales limitadas por tiempo ubicadas en la sección de Promociones.

### Diferenciación con Combos del Menú

| Aspecto | Combos (Menú) | Combinados (Promociones) |
|---------|---------------|--------------------------|
| **Ubicación** | Menú → Combos | Menú → Promociones → Combinados |
| **Vigencia** | Permanente | Temporal (fechas + horarios + días) |
| **Precio** | 4 precios (capital/interior × pickup/delivery) | 2 precios (capital/interior) |
| **Naturaleza** | Producto del menú | Oferta promocional |
| **Estructura Items** | Items fijos + grupos de elección | Items fijos + grupos de elección (misma lógica) |

### Casos de Uso

1. **Ofertas estacionales**: "Combinado xx" (1-25 diciembre)
2. **Ofertas por horario**: "Combinado Almuerzo" (11am-2pm, lunes a viernes)
3. **Ofertas de fin de semana**: "Combinado Familiar" (sábados y domingos)
4. **Ofertas flash**: "Combinado del Mes" (30 días específicos)

**Ejemplo completo**:
```
"Combinado San Valentín" - Q45.00 (capital) / Q48.00 (interior)
├── Vigencia: 10-14 febrero
├── Horario: 5pm-11pm
├── Días: Todos
├── Sub 15cm a elección (grupo)
├── Bebida (fijo)
└── Cookie (fijo)
```

---

## FASE 1: Estructura de Base de Datos

### Objetivos
- Extender tabla `promotions` con campos de vigencia temporal y precios
- Crear tabla `bundle_promotion_items` (análoga a `combo_items`)
- Crear tabla `bundle_promotion_item_options` (análoga a `combo_item_options`)
- Reutilizar arquitectura probada de combos

### Migración 1: Extender tabla `promotions`

**Campos a agregar**:

**Type enum**:
- Agregar valor `'bundle_special'` al enum `type` existente
- Mantener valores actuales: `daily_special`, `two_for_one`, `percentage`

**Precios especiales** (solo 2, no 4):
- `special_bundle_price_capital`: precio para zona capital
- `special_bundle_price_interior`: precio para zona interior
- Ambos DECIMAL(10,2), nullable (solo para type='bundle_special')

**Vigencia temporal completa**:
- `valid_from`: fecha de inicio (DATE, nullable = sin límite inferior)
- `valid_until`: fecha de fin (DATE, nullable = sin límite superior)
- `time_from`: hora de inicio (TIME, nullable = todo el día)
- `time_until`: hora de fin (TIME, nullable = todo el día)
- `weekdays`: días de la semana permitidos (JSON, nullable = todos los días)
  - Formato: array de números [0-6] donde 0=Domingo, 6=Sábado
  - Ejemplo: [1,2,3,4,5] = lunes a viernes

**Índices para performance**:
- Índice en `type` para filtrar combinados rápidamente
- Índice compuesto en (`type`, `is_active`) para listados
- Índice compuesto en (`valid_from`, `valid_until`) para queries de vigencia

### Migración 2: Crear tabla `bundle_promotion_items`

Estructura idéntica a `combo_items` pero referenciando `promotions`:

**Campos principales**:
- `promotion_id`: FK a promotions (ON DELETE CASCADE)
- `product_id`: FK a products, nullable cuando es grupo de elección
- `variant_id`: FK a product_variants, nullable
- `is_choice_group`: boolean, indica si es grupo de elección
- `choice_label`: string, etiqueta del grupo (ej: "Elige tu Sub")
- `quantity`: integer, cantidad del producto
- `sort_order`: integer, orden de presentación

**Índices**:
- `promotion_id` indexado
- `product_id` indexado
- `variant_id` indexado
- Índice compuesto en (`promotion_id`, `is_choice_group`)

**Validaciones a nivel DB**:
- `product_id` NULL solo cuando `is_choice_group = true`
- ON DELETE CASCADE desde promotions (eliminar combinado elimina items)
- ON DELETE RESTRICT desde products (no eliminar producto usado)

### Migración 3: Crear tabla `bundle_promotion_item_options`

Estructura idéntica a `combo_item_options` pero referenciando `bundle_promotion_items`:

**Campos**:
- `bundle_item_id`: FK a bundle_promotion_items (ON DELETE CASCADE)
- `product_id`: FK a products (requerido)
- `variant_id`: FK a product_variants (nullable)
- `sort_order`: integer, orden de presentación

**Constraint único**:
- Unique en (`bundle_item_id`, `product_id`, `variant_id`)
- Previene opciones duplicadas en el mismo grupo

**Índices**:
- `bundle_item_id` indexado
- `product_id` indexado
- `variant_id` indexado

### Verificación de Fase 1
- [ ] Migraciones ejecutan sin errores
- [ ] Rollback funciona correctamente
- [ ] Campos nullable correctos según reglas de negocio
- [ ] Constraints e índices funcionan
- [ ] Type enum incluye 'bundle_special'
- [ ] JSON weekdays acepta arrays válidos

---

## FASE 2: Modelos y Relaciones Eloquent

### Objetivos
- Crear modelos `BundlePromotionItem` y `BundlePromotionItemOption`
- Extender modelo `Promotion` con relaciones y scopes
- Mantener convenciones del proyecto (usar estructura de combos como referencia)

### Nuevo Modelo: `BundlePromotionItem`

Análogo a `ComboItem`, representa cada item en el combinado.

**Campos fillable**:
- Todos los campos de la migración excepto timestamps

**Casts importantes**:
- `is_choice_group` → boolean
- Campos numéricos (IDs, quantity, sort_order) → integer

**Relaciones**:
- `promotion()`: BelongsTo Promotion
- `product()`: BelongsTo Product (nullable)
- `variant()`: BelongsTo ProductVariant (nullable)
- `options()`: HasMany BundlePromotionItemOption, ordenado por sort_order

**Métodos helper**:
- `isChoiceGroup()`: retorna true si es grupo de elección
- `getProductWithSections()`: retorna null si es grupo, product con sections si es fijo

### Nuevo Modelo: `BundlePromotionItemOption`

Análogo a `ComboItemOption`, representa cada opción dentro de un grupo.

**Campos fillable**:
- `bundle_item_id`, `product_id`, `variant_id`, `sort_order`

**Casts**:
- Todos los IDs y sort_order → integer

**Relaciones**:
- `bundleItem()`: BelongsTo BundlePromotionItem
- `product()`: BelongsTo Product
- `variant()`: BelongsTo ProductVariant (nullable)

### Extender Modelo: `Promotion`

**Nueva relación**:
- `bundleItems()`: HasMany BundlePromotionItem, ordenado por sort_order

**Nuevos Scopes**:

**`scopeCombinados()`**:
- Filtra solo promociones donde type = 'bundle_special'

**`scopeValidNow()`**:
- Filtra combinados válidos en este momento
- Verifica `is_active = true`
- Valida fecha: hoy entre `valid_from` y `valid_until` (o null)
- Valida hora: hora actual entre `time_from` y `time_until` (o null)
- Valida día de semana: día actual en array `weekdays` (o null para todos)
- Lógica con OR para campos nullable (null = sin restricción)

**`scopeUpcoming()`**:
- Filtra combinados próximos (valid_from > hoy)
- Solo combinados activos

**`scopeExpired()`**:
- Filtra combinados expirados (valid_until < hoy)

**`scopeAvailable()`**:
- Extiende `validNow()` con validación de items activos
- Para items fijos: todos deben tener product activo
- Para grupos: al menos 1 opción debe tener product activo
- Similar a `Combo::scopeAvailable()` pero adaptado

**Nuevos Métodos**:

**`isValidNow()`**:
- Método público que ejecuta todas las validaciones de vigencia
- Retorna boolean indicando si el combinado es válido ahora
- Incluye validación de fecha, hora, día de semana Y estado de items

**`getPriceForZone(string $zone)`**:
- Retorna precio según zona ('capital' o 'interior')
- Retorna float del campo correspondiente

**`hasActiveItems()`**:
- Verifica si todos los items/opciones tienen productos activos
- Usado para mostrar advertencias en admin

**Nuevos Casts**:
- `special_bundle_price_capital` → decimal:2
- `special_bundle_price_interior` → decimal:2
- `valid_from` → date
- `valid_until` → date
- `time_from` → time (usando cast de Carbon)
- `time_until` → time
- `weekdays` → array (cast automático de JSON a array)

### Verificación de Fase 2
- [ ] Modelos creados con todas las relaciones
- [ ] Scopes funcionan correctamente (probar en tinker)
- [ ] `validNow()` filtra según fecha, hora y weekdays correctamente
- [ ] `isValidNow()` valida todas las condiciones
- [ ] Relaciones cargan eager loading sin N+1
- [ ] Casts de fechas/horas/JSON funcionan
- [ ] Método `getPriceForZone()` retorna precios correctos

---

## FASE 3: Validaciones Backend

### Objetivos
- Crear FormRequests para crear/actualizar combinados
- Reutilizar validaciones de combos (items fijos y grupos)
- Agregar validaciones específicas de vigencia temporal
- Mensajes de error claros y accionables

### Crear `StoreBundlePromotionRequest`

Basado en `StoreComboRequest` pero adaptado a combinados.

**Validaciones básicas**:
- `name`: requerido, string, max 255
- `description`: opcional, string, max 500
- `is_active`: boolean

**Validaciones de precios** (solo 2, no 4):
- `special_bundle_price_capital`: requerido, numeric, min 0, max 9999.99
- `special_bundle_price_interior`: requerido, numeric, min 0, max 9999.99

**Validaciones de vigencia temporal**:
- `valid_from`: opcional, date, debe ser hoy o posterior
- `valid_until`: opcional, date, debe ser >= valid_from
- `time_from`: opcional, formato H:i (24 horas)
- `time_until`: opcional, formato H:i, debe ser > time_from
- `weekdays`: opcional, array
- `weekdays.*`: integer entre 0 y 6

**Validaciones de items** (idénticas a combos):
- `items`: requerido, array, mínimo 2 items
- `items.*.is_choice_group`: boolean
- `items.*.choice_label`: requerido si es grupo
- `items.*.product_id`: requerido si NO es grupo
- `items.*.variant_id`: condicional según producto
- `items.*.quantity`: requerido, integer, min 1, max 10
- `items.*.options`: array, requerido si es grupo
- `items.*.options.*.product_id`: requerido
- `items.*.options.*.variant_id`: condicional según producto

**Validaciones personalizadas** (método `withValidator`):

1. **validateActiveProducts**:
   - Verifica que productos en items y opciones estén activos
   - Idéntica a validación de combos

2. **validateVariantRequirements**:
   - Si producto tiene variantes, variant_id es requerido
   - Si producto NO tiene variantes, variant_id debe ser null
   - Idéntica a validación de combos

3. **validateChoiceGroups**:
   - Items fijos requieren product_id
   - Grupos requieren choice_label
   - Grupos requieren mínimo 2 opciones
   - No opciones duplicadas en grupo
   - Variantes consistentes en tamaño dentro de grupo
   - Idéntica a validación de combos

4. **validateTemporalLogic** (nueva, específica de combinados):
   - Si hay `time_until`, debe haber `time_from`
   - Si hay `valid_until`, debe ser >= `valid_from`
   - Weekdays solo acepta valores 0-6
   - Validar que al menos UNA condición temporal está definida (opcional)

**Preparación de datos** (método `prepareForValidation`):
- Forzar `type = 'bundle_special'`
- Convertir `weekdays` vacío a null
- Asignar `is_active = true` por defecto

**Mensajes personalizados**:
- Mensajes en español, claros y accionables
- Ejemplos: "La hora de fin debe ser posterior a la hora de inicio"
- "Los días de la semana deben ser valores entre 0 (Domingo) y 6 (Sábado)"

### Crear `UpdateBundlePromotionRequest`

**Diferencias con Store**:
- Hereda todas las validaciones de `StoreBundlePromotionRequest`
- Validación de `name`: debe ser único excepto el actual
  - Regla: `unique:promotions,name,{id}`
- `valid_from` NO requiere ser >= today (puede estar en pasado si ya existe)
- Opcional: validar que no tenga pedidos asociados (futuro)

### Verificación de Fase 3
- [ ] Request valida correctamente fechas (from <= until)
- [ ] Request valida correctamente horarios (from < until)
- [ ] Request valida weekdays como array de 0-6
- [ ] Request rechaza grupos con < 2 opciones
- [ ] Request rechaza opciones duplicadas
- [ ] Request valida variantes consistentes
- [ ] Request valida productos activos
- [ ] Mensajes de error son claros
- [ ] `prepareForValidation` asigna type correcto

---

## FASE 4: Controllers

### Objetivos
- Extender `PromotionController` con métodos para combinados
- Mantener separación con otras promociones
- Reutilizar lógica de transacciones de combos

### Métodos Nuevos en `PromotionController`

**`combinadosIndex(Request $request)`**:
- Lista todos los combinados (type = 'bundle_special')
- Filtro de búsqueda por nombre/descripción
- Eager loading: `bundleItems.product`
- `withCount('bundleItems')` para mostrar cantidad de items
- Orden: por `valid_from` desc, luego `created_at` desc
- Calcular stats: total, activos, válidos ahora
- Retornar vista Inertia: `menu/promotions/bundle-specials/index`

**`createCombinado()`**:
- Cargar productos con variantes activas (igual que combos)
- No cargar categorías (combinados no tienen categoría)
- Retornar vista Inertia: `menu/promotions/bundle-specials/create`

**`editCombinado(Promotion $promotion)`**:
- Verificar que `type === 'bundle_special'` (abort 404 si no)
- Eager loading completo: items, options, productos, variantes
- Cargar productos disponibles (igual que create)
- Retornar vista Inertia: `menu/promotions/bundle-specials/edit`

**`toggleCombinado(Promotion $promotion)`**:
- Verificar que sea combinado
- Toggle `is_active`
- Retornar back con mensaje de éxito

### Modificar Métodos Existentes

**`store(Request $request)`**:
- Detectar si `type === 'bundle_special'`
- Si es combinado: validar con `StoreBundlePromotionRequest`
- Usar transacción DB
- Crear promotion
- Iterar items:
  - Si es item fijo: crear con product_id/variant_id
  - Si es grupo: crear con is_choice_group=true, product_id=null
  - Si es grupo: iterar opciones y crear cada una
- Redirect a `bundle-specials.index`

**`update(UpdateBundlePromotionRequest $request, Promotion $promotion)`**:
- Verificar que sea combinado
- Usar transacción DB
- Actualizar campos de promotion
- Eliminar todos los items existentes (`$promotion->bundleItems()->delete()`)
- Recrear items desde cero (igual que store)
- Nota: eliminación en cascada elimina opciones automáticamente
- Redirect a `bundle-specials.index`

**`destroy(Promotion $promotion)`**:
- Verificar que sea combinado
- Soft delete (campo `deleted_at`)
- Items y opciones se mantienen (no hard delete)
- Redirect con mensaje de éxito

### Rutas en `routes/web.php`

Dentro de prefix `menu/promotions`:
- `GET /bundle-specials` → `bundleSpecialsIndex`
- `GET /bundle-specials/create` → `createBundleSpecial`
- `GET /bundle-specials/{promotion}/edit` → `editBundleSpecial`
- `POST /bundle-specials` → `storeBundleSpecial`
- `PUT /bundle-specials/{promotion}` → `updateBundleSpecial`
- `DELETE /bundle-specials/{promotion}` → `destroy`
- `POST /bundle-specials/{promotion}/toggle` → `toggleBundleSpecial`
- **`POST /bundle-specials/reorder` → `reorderBundleSpecials`** (nueva)

**Nomenclatura de rutas**:
- Rutas: `bundle-specials` (inglés, kebab-case)
- Nombres: `menu.promotions.bundle-specials.*`
- Métodos controller: `bundleSpecialsIndex()`, `createBundleSpecial()`, etc.

### Verificación de Fase 4
- [ ] Puede crear combinado con items y opciones
- [ ] Transacción rollback si falla alguna parte
- [ ] Puede editar combinado y modificar items
- [ ] Eliminación de items elimina opciones en cascada
- [ ] Toggle cambia estado activo/inactivo
- [ ] Soft delete funciona
- [ ] Rutas responden correctamente
- [ ] Stats calculan valores correctos

---

## FASE 5: Frontend - Constantes y Configuración

### Objetivos
- leer documentación existente ux-ui.md rules.md
- Definir constantes UI para combinados
- Configurar estados visuales (badges)
- Preparar placeholders y labels
- Mantener consistencia con proyecto

### Extender `ui-constants.ts`

**Categoría: PLACEHOLDERS**:
- `combinadoName`: "Combinado Navideño"
- `combinadoDescription`: "Descripción de la oferta especial"
- Reutilizar `search` existente para búsqueda

**Categoría: FORM_SECTIONS**:
- `combinadoItems`: { title, description } para sección de items
- `temporalValidity`: { title, description } para sección de vigencia
- `specialPrices`: { title, description } para sección de precios

**Categoría: STATUS_CONFIGS** (nueva):
No agregar a constantes, crear directamente en `status-badge.tsx`

### Extender `status-badge.tsx`

**Nueva configuración: `COMBINADO_STATUS_CONFIGS`**:

Define 4 estados posibles:
1. **active**: Combinado activo y vigente ahora
   - Color: verde
   - Icono: CheckCircle2

2. **inactive**: Combinado desactivado manualmente
   - Color: gris
   - Icono: XCircle

3. **expired**: Vigencia terminada (valid_until < hoy)
   - Color: rojo
   - Icono: AlertCircle

4. **upcoming**: Aún no inicia (valid_from > hoy)
   - Color: azul
   - Icono: Clock

**Lógica de determinación de estado**:
- Si `!is_active` → inactive
- Si `valid_from > hoy` → upcoming
- Si `valid_until < hoy` → expired
- Sino → active

### Verificación de Fase 5
- [ ] Constantes accesibles en componentes
- [ ] `COMBINADO_STATUS_CONFIGS` define 4 estados
- [ ] Labels en español
- [ ] Colores e iconos consistentes con diseño

---

## FASE 6: Frontend - Página Index (Listado)

### Objetivos
- Vista de listado de combinados
- Stats de resumen
- Mobile responsive con StandardMobileCard
- **Ordenables mediante drag & drop (como combos)**

### Página: `bundle-specials/index.tsx`

Basada en `combos/index.tsx` con **SortableTable** (ordenable mediante drag & drop).

**Estructura de SortableTable**:

**5 Columnas**:
1. **Combinado** (width: lg):
   - `EntityInfoCell` con icono Gift
   - Nombre como texto principal
   - Descripción como texto secundario

2. **Precio** (width: sm, center):
   - Dos líneas: Capital e Interior
   - Formato: "C: Q45.00" / "I: Q48.00"

3. **Vigencia** (width: md):
   - Helper `formatVigencia()` que genera string legible
   - Formato: "1/12/2024 - 25/12/2024 | 17:00 - 23:00 | Lun, Mar, Mié"
   - Si todo es null: "Siempre válido"

4. **Estado** (width: sm, center):
   - `StatusBadge` con estado calculado dinámicamente
   - Usa `COMBINADO_STATUS_CONFIGS`

5. **Acciones** (width: xs, right):
   - `TableActions` con editar/eliminar
   - Tracking de estado deleting

**Stats** (3):
- Total combinados (icono Gift)
- Activos (icono Star, color verde)
- Válidos ahora (icono Clock, color azul)

**Funcionalidad de búsqueda**:
- Placeholder genérico
- Búsqueda por nombre y descripción
- Sin debounce (búsqueda inmediata)

**DeleteConfirmationDialog**:
- Confirmar antes de eliminar
- Mostrar nombre del combinado
- Entity type: "combinado"

### Mobile Card con `StandardMobileCard`

**Estructura**:
- Icon: Gift
- Title: nombre del combinado
- Subtitle: descripción
- Badge: estado (usando StatusBadge)
- Data Fields:
  - Precio Capital
  - Precio Interior
  - Vigencia (formateada)
  - Cantidad de items
- Actions: editar/eliminar

### Helpers de formateo

**`getCombinadoStatus(combinado)`**:
- Lógica en frontend para determinar estado
- Considera fecha actual del navegador
- Retorna: 'active' | 'inactive' | 'expired' | 'upcoming'

**`formatVigencia(combinado)`**:
- Formatea fechas con `toLocaleDateString('es-GT')`
- Formatea horarios desde string TIME
- Mapea weekdays a nombres cortos: ['Dom', 'Lun', 'Mar', ...]
- Combina con separador " | "

### Skeleton de carga

Crear `CombinadosSkeleton` en `skeletons.tsx`:
- 5 filas de skeleton
- Simular estructura de SortableTable
- Pulsing animation

### Funcionalidad de Reordenamiento

**Handler `handleReorder`**:
- Recibe array de combinados reordenados
- Mapea a formato `{id, sort_order}` con índice + 1
- POST a `route('menu.promotions.bundle-specials.reorder')`
- Muestra indicador `isSaving` durante el proceso
- preserveState: true para mantener estado
- Notificación de error si falla

**Backend**:
- Ruta: `POST /menu/promotions/bundle-specials/reorder`
- Controller: `PromotionController@reorderBundleSpecials`
- Actualiza campo `sort_order` en tabla `promotions`
- Solo actualiza combinados (verifica type='bundle_special')

**Campo en DB**:
- Migración: `add_sort_order_to_promotions_table`
- Campo: `sort_order` (integer, default 0, indexed)
- Ordenamiento por defecto: `orderBy('sort_order')` en `bundleSpecialsIndex`

### Verificación de Fase 6
- [x] SortableTable muestra todos los combinados
- [x] Columnas muestran datos correctos
- [x] Estado se calcula correctamente (4 estados posibles)
- [x] Vigencia se formatea legible en español
- [x] Stats muestran valores correctos
- [x] Búsqueda filtra por nombre/descripción
- [x] Mobile card muestra todos los datos
- [x] DeleteConfirmationDialog funciona
- [x] Dark mode funciona
- [x] Skeleton loading mientras carga
- [x] **Drag & drop para reordenar funciona**
- [x] **Botón "Guardar Orden" aparece cuando hay cambios**
- [x] **Indicador de guardado muestra progreso**

---

## FASE 7: Frontend - Páginas Create/Edit

### Objetivos
- Formulario de creación de combinados
- Formulario de edición
- Reutilizar componentes de combos para items
- Agregar sección de vigencia temporal

### Página: `bundle-specials/create.tsx`

Basada en `combos/create.tsx`.

**Layout**: `CreatePageLayout`

**Form State**:
- Campos básicos: name, description, is_active
- Precios: special_bundle_price_capital, special_bundle_price_interior
- Vigencia: valid_from, valid_until, time_from, time_until, weekdays
- Items: array de items (estructura idéntica a combos)

**4 FormSections**:

**1. Información Básica** (icon: Gift):
- Input: Nombre
- Textarea: Descripción
- Switch: Combinado activo

**2. Precios Especiales** (icon: Banknote):
- Input numérico: Precio Capital
- Input numérico: Precio Interior
- Nota: Solo 2 precios, no 4 como combos

**3. Vigencia Temporal** (icon: Calendar):
Sección nueva, específica de combinados.

Sub-sección **Fechas**:
- DatePicker: Fecha de inicio (nullable)
- DatePicker: Fecha de fin (nullable)
- Permitir null para "sin límite"

Sub-sección **Horarios**:
- TimePicker: Hora de inicio (nullable)
- TimePicker: Hora de fin (nullable)
- Formato 24 horas
- Permitir null para "todo el día"

Sub-sección **Días de la Semana**:
- `WeekdaySelector` (componente reutilizable existente)
- Permite seleccionar múltiples días
- Visual: botones toggle para cada día
- State: array de números [0-6]
- Permitir vacío para "todos los días"

**4. Items del Combinado** (icon: Package):
- **Reutilizar completamente** componentes de combos:
  - `ComboItemCard`
  - `ItemTypeSelector`
  - `ChoiceGroupEditor`
  - `ProductSelectorModal`
  - `SortableChoiceOption`
- Drag & drop para reordenar items
- Botón "Agregar Item"
- Mínimo 2 items requeridos

**Validaciones frontend**:
- Nombre requerido
- Precios > 0
- Si hay time_until, debe haber time_from
- Si hay valid_until, debe ser >= valid_from
- Mínimo 2 items
- Validaciones de items (delegadas a componentes de combos)

**Submit**:
- Preparar datos con helper `prepareComboDataForSubmit` (reutilizar)
- POST a `route('menu.promotions.bundle-specials.store')`
- Mostrar notificación de éxito/error
- Redirect a index en éxito

### Página: `bundle-specials/edit.tsx`

Basada en `combos/edit.tsx`.

**Layout**: `EditPageLayout`

**Diferencias con Create**:
- Pre-cargar datos desde props de Inertia
- Transformar datos backend → formulario (fechas, JSON weekdays)
- Permitir cambios con tracking `isDirty`
- Botón "Descartar cambios" si hay modificaciones
- PUT a `route('menu.promotions.bundle-specials.update', id)`

**Warnings/Alertas**:
- Mostrar si hay productos/variantes inactivos en items/opciones
- Mostrar si combinado tiene pedidos asociados (futuro)
- Alert visual cuando valid_until < hoy (expirado)

### WeekdaySelector Component

Componente reutilizable para seleccionar días de la semana.

**Props**:
- `selectedDays`: array de números [0-6]
- `onChange`: callback con nuevo array

**Funcionalidad**:
- 7 botones (uno por día)
- Labels: Dom, Lun, Mar, Mié, Jue, Vie, Sáb
- Visual: botón activo si está en array
- Click toggle: agregar/quitar del array
- Permitir seleccionar todos o ninguno

**Estado visual**:
- Seleccionado: bg-primary, text-white
- No seleccionado: bg-gray-100, text-gray-600
- Responsive: wrap en mobile

### Verificación de Fase 7
- [ ] Create form muestra 4 secciones correctas
- [ ] Puede agregar/editar/eliminar items (igual que combos)
- [ ] WeekdaySelector permite seleccionar días
- [ ] DatePickers permiten null
- [ ] TimePickers validan formato H:i
- [ ] Edit form carga datos correctamente
- [ ] Edit form transforma weekdays JSON → array
- [ ] Validaciones frontend funcionan
- [ ] Submit crea combinado correctamente
- [ ] Warnings muestran productos inactivos
- [ ] Mobile responsive
- [ ] Dark mode funciona

---

## FASE 8: Testing Backend

### Objetivos
- Cobertura completa de funcionalidad de combinados
- Tests de validaciones de vigencia temporal
- Tests de creación/actualización de items
- Compatibilidad con otras promociones

### Feature Test: `BundlePromotionControllerTest`

**Tests de creación**:
- Puede crear combinado con items fijos
- Puede crear combinado con grupos de elección
- Puede crear combinado mixto (fijos + grupos)
- Valida precios requeridos
- Valida mínimo 2 items
- Acepta vigencia con solo fechas
- Acepta vigencia con solo horarios
- Acepta vigencia con solo weekdays
- Acepta vigencia completa (fechas + horarios + weekdays)
- Acepta vigencia null (siempre válido)

**Tests de validaciones de vigencia**:
- Rechaza valid_until < valid_from
- Rechaza time_until <= time_from
- Rechaza weekdays con valores fuera de 0-6
- Acepta weekdays vacío (todos los días)
- Acepta valid_from null (sin límite inferior)

**Tests de actualización**:
- Puede actualizar combinado y cambiar items
- Puede agregar items nuevos
- Puede eliminar items
- Puede cambiar vigencia temporal
- Elimina items anteriores correctamente
- Mantiene soft delete al eliminar

**Tests de disponibilidad (scope validNow)**:
- Combinado dentro de fechas es válido
- Combinado fuera de fechas NO es válido
- Combinado dentro de horario es válido
- Combinado fuera de horario NO es válido
- Combinado en día de semana permitido es válido
- Combinado en día NO permitido NO es válido
- Combinado con producto inactivo en item NO es válido
- Combinado con al menos 1 opción activa en grupo es válido

**Tests de scopes**:
- `combinados()` filtra solo type='bundle_special'
- `validNow()` respeta fecha/hora/weekdays
- `upcoming()` filtra solo futuros
- `expired()` filtra solo expirados
- `available()` considera items activos

### Unit Test: `BundlePromotionItemTest`

**Tests de modelo**:
- BundlePromotionItem pertenece a Promotion
- BundlePromotionItem pertenece a Product (nullable)
- BundlePromotionItem tiene opciones (HasMany)
- `isChoiceGroup()` retorna correctamente
- `getProductWithSections()` retorna null si es grupo

### Unit Test: `BundlePromotionItemOptionTest`

**Tests de modelo**:
- BundlePromotionItemOption pertenece a BundlePromotionItem
- BundlePromotionItemOption pertenece a Product
- BundlePromotionItemOption pertenece a Variant (nullable)
- Opciones se eliminan en cascada cuando se elimina item

### Unit Test: `PromotionTest` (extender existente)

**Tests de métodos nuevos**:
- `isValidNow()` valida fecha correctamente
- `isValidNow()` valida hora correctamente
- `isValidNow()` valida weekdays correctamente
- `getPriceForZone('capital')` retorna precio correcto
- `getPriceForZone('interior')` retorna precio correcto
- `hasActiveItems()` detecta productos inactivos

### Verificación de Fase 8
- [ ] Todos los tests pasan
- [ ] Cobertura en código nuevo
- [ ] Tests de validaciones cubren edge cases
- [ ] Tests de scopes verifican lógica temporal
- [ ] Tests de relaciones confirman cascadas
- [ ] Tests de compatibilidad con otras promociones

---

## FASE 9: Seeder con Datos de Ejemplo

### Objetivos
- Crear datos realistas para desarrollo y demos
- Cubrir diferentes casos de uso
- Facilitar testing manual

### Crear `CombinadosSeeder`

**5-8 Combinados de ejemplo**:

**Ejemplo 1: "Combinado Navideño"**:
- Vigencia: 1-25 diciembre del año actual
- Horario: null (todo el día)
- Weekdays: null (todos los días)
- Precio: Q55 capital, Q58 interior
- Items: Sub 30cm a elección, 2 bebidas, 2 cookies

**Ejemplo 2: "Combinado Almuerzo Express"**:
- Vigencia: null (siempre)
- Horario: 11:00 - 14:00
- Weekdays: [1,2,3,4,5] (lunes a viernes)
- Precio: Q35 capital, Q38 interior
- Items: Sub 15cm a elección, bebida, chips

**Ejemplo 3: "Combinado Fin de Semana"**:
- Vigencia: null
- Horario: null
- Weekdays: [0,6] (domingo y sábado)
- Precio: Q65 capital, Q68 interior
- Items: Sub 30cm a elección, 3 cookies, 2 bebidas, chips

**Ejemplo 4: "Combinado San Valentín"**:
- Vigencia: 10-14 febrero del año actual
- Horario: 17:00 - 23:00
- Weekdays: null
- Precio: Q75 capital, Q78 interior
- Items: 2 subs 15cm a elección, 2 bebidas, 2 cookies

**Ejemplo 5: "Combinado Expirado"** (para testing):
- Vigencia: fecha pasada (ej: 1-31 enero del año pasado)
- Estado: activo pero expirado
- Útil para probar filtros y estados

**Ejemplo 6: "Combinado Próximo"** (para testing):
- Vigencia: fecha futura (ej: dentro de 30 días)
- Estado: activo pero aún no vigente
- Útil para probar filtros y estados

### Consideraciones

**Productos usados**:
- Usar productos existentes del ProductSeeder
- Usar variantes activas
- Incluir productos populares (BMT, Italiano, Jamón)
- Incluir bebidas y cookies comunes

**Variedad de casos**:
- Al menos 1 con solo items fijos
- Al menos 2 con grupos de elección
- Al menos 1 con vigencia completa (fecha + hora + weekdays)
- Al menos 1 con vigencia parcial (solo horarios)
- Al menos 1 "siempre válido" (todo null)

**Estado activo/inactivo**:
- Mayoría activos
- 1-2 inactivos para probar filtros

### Verificación de Fase 9
- [ ] Seeder crea 5-8 combinados
- [ ] Combinados tienen datos realistas
- [ ] Variedad de vigencias (fechas, horarios, weekdays)
- [ ] Al menos 1 expirado y 1 próximo
- [ ] Items y opciones se crean correctamente
- [ ] Seeder es idempotente (puede ejecutarse múltiples veces)

---

## FASE 10: Documentación y Refinamiento

### Objetivos
- Documentar funcionalidad para futuros desarrolladores
- Refinar UX/UI basado en testing manual
- Validar performance
- Preparar para producción

### Actualizar `docs/PROMOTIONS.md` (o crear)

**Sección: Tipos de Promociones**:
Documentar los 4 tipos:
1. Sub del Día (daily_special)
2. 2x1 (two_for_one)
3. Porcentaje (percentage)
4. **Combinados (bundle_special)** ← nuevo

**Sección: Combinados**:

**Concepto**:
Combos temporales con precio fijo y vigencia configurable. Diferencia con combos del menú.

**Casos de uso**:
- Ofertas estacionales
- Ofertas por horario
- Ofertas de fin de semana
- Ofertas flash

**Estructura**:
- Precios: solo 2 (capital/interior)
- Vigencia: fechas + horarios + weekdays
- Items: igual que combos (fijos + grupos)

**Reglas de negocio**:
- Mínimo 2 items
- Vigencia opcional (null = siempre válido)
- Validación temporal: fecha AND hora AND weekday
- Disponibilidad depende de items activos

**Disponibilidad**:
Un combinado está disponible cuando:
1. is_active = true
2. Fecha actual entre valid_from y valid_until (o null)
3. Hora actual entre time_from y time_until (o null)
4. Día actual en weekdays (o null)
5. Items/opciones tienen productos activos

**Limitaciones**:
- Solo en ADMIN (creación/gestión)
- Pedidos del lado cliente fuera de alcance
- No precios diferenciales por opción (futuro)

### Actualizar `docs/UX-UI.md` (si aplica)

Agregar sección sobre:
- Configuración de `COMBINADO_STATUS_CONFIGS`
- Uso de WeekdaySelector
- Formato de vigencia temporal

### Crear `docs/BUNDLE-PROMOTIONS-IMPLEMENTATION.md`

Similar a COMBOS-CHOICE-GROUPS-IMPLEMENTATION.md:

**Arquitectura**:
- Estructura de tablas
- Relaciones de modelos
- Scopes y métodos

**Validaciones**:
- Validaciones de vigencia temporal
- Validaciones de items (reutilizadas)

**Frontend**:
- Componentes reutilizados de combos
- WeekdaySelector nuevo
- Helpers de formateo

**Casos Edge**:
- Combinado expira mientras está en carrito
- Producto se desactiva mientras combinado activo
- Cambio de horario afecta disponibilidad

**Troubleshooting**:
- Combinado no aparece en app (validar vigencia)
- Validaciones fallan (revisar formato de datos)
- Performance lenta (revisar eager loading)

### Testing Manual Completo

**Crear Combinado**:
- [ ] Crear con vigencia completa (fecha + hora + días)
- [ ] Crear con solo fechas
- [ ] Crear con solo horarios
- [ ] Crear con solo weekdays
- [ ] Crear siempre válido (todo null)
- [ ] Crear con items fijos
- [ ] Crear con grupos de elección
- [ ] Crear mixto (fijos + grupos)

**Editar Combinado**:
- [ ] Cambiar vigencia temporal
- [ ] Agregar/quitar items
- [ ] Cambiar precios
- [ ] Desactivar/activar
- [ ] Warnings aparecen si productos inactivos

**Validaciones**:
- [ ] Rechaza valid_until < valid_from
- [ ] Rechaza time_until <= time_from
- [ ] Rechaza < 2 items
- [ ] Rechaza grupos con < 2 opciones
- [ ] Acepta weekdays vacío

**Estados y Filtros**:
- [ ] Estado "activo" muestra correctamente
- [ ] Estado "inactivo" muestra correctamente
- [ ] Estado "expirado" muestra correctamente
- [ ] Estado "próximamente" muestra correctamente
- [ ] Búsqueda filtra por nombre/descripción

**Responsive**:
- [ ] Desktop funciona correctamente
- [ ] Tablet funciona correctamente
- [ ] Mobile muestra cards
- [ ] WeekdaySelector responsive
- [ ] Dark mode funciona en todas las vistas

### Performance Check

**Métricas objetivo**:
- [ ] Listado carga en < 2 segundos
- [ ] Create form carga en < 1 segundo
- [ ] Edit form carga en < 1 segundo
- [ ] Submit procesa en < 3 segundos
- [ ] No N+1 queries (verificar con Debugbar)

**Optimizaciones**:
- Eager loading de relaciones en index
- Índices en campos de búsqueda
- Cache de productos (opcional, futuro)

### Verificación Final de Fase 10
- [ ] Documentación completa y clara
- [ ] Ejemplos de uso incluidos
- [ ] Casos edge documentados
- [ ] Testing manual 100% completado
- [ ] Performance aceptable
- [ ] No bugs conocidos
- [ ] Dark mode funciona
- [ ] Mobile responsive

---

## FASE 11: Integración con Navegación

### Objetivos
- Agregar link en menú de navegación
- Integrar en flujo de promociones
- Breadcrumbs correctos

### Actualizar Navegación del Admin

**Ubicación**: Menú lateral, sección "Menú"

**Jerarquía**:
```
Menú
├── Categorías
├── Productos
├── Secciones
├── Combos
└── Promociones
    ├── Sub del Día
    ├── 2x1
    ├── Porcentaje
    └── Combinados ← nuevo
```

**Link**:
- Ruta: `/menu/promotions/bundle-specials`
- Icono: Gift (de lucide-react)
- Label: "Combinados"
- Active cuando pathname incluye `bundle-specials`

### Breadcrumbs

**Index**: Menú > Promociones > Combinados
**Create**: Menú > Promociones > Combinados > Crear
**Edit**: Menú > Promociones > Combinados > Editar

### Verificación de Fase 11
- [ ] Link aparece en navegación
- [ ] Link activo cuando en página de combinados
- [ ] Breadcrumbs correctos en todas las páginas
- [ ] Icono consistente con diseño

---

## FASE 12: Revisión Final y Deploy

### Objetivos
- Code review exhaustivo
- Validación de todos los criterios de éxito
- Preparación para producción
- Plan de rollback si es necesario

### Code Review Checklist

**Backend**:
- [ ] Validaciones exhaustivas y claras
- [ ] No N+1 queries
- [ ] Type hints en todos los métodos
- [ ] Comentarios en lógica compleja
- [ ] Seguir PSR-12 code style
- [ ] Nombres descriptivos
- [ ] Manejo de errores robusto
- [ ] Transacciones DB donde corresponde

**Frontend**:
- [ ] Componentes reutilizables
- [ ] TypeScript types correctos
- [ ] Manejo de loading states
- [ ] Manejo de errores user-friendly
- [ ] Nombres consistentes
- [ ] No console.logs
- [ ] Formateado con Prettier
- [ ] Accesibilidad básica (labels, aria)

**Database**:
- [ ] Migraciones con rollback
- [ ] Índices en campos de búsqueda
- [ ] Constraints correctos
- [ ] Cascadas configuradas

### Checklist de Funcionalidad

**CRUD Completo**:
- [ ] Create funciona correctamente
- [ ] Read/List funciona correctamente
- [ ] Update funciona correctamente
- [ ] Delete funciona correctamente (soft)
- [ ] Toggle estado funciona

**Validaciones**:
- [ ] Backend valida correctamente
- [ ] Frontend valida antes de submit
- [ ] Mensajes de error claros
- [ ] Edge cases cubiertos

**Vigencia Temporal**:
- [ ] Fechas validan correctamente
- [ ] Horarios validan correctamente
- [ ] Weekdays funciona correctamente
- [ ] Scopes filtran correctamente

**Items y Opciones**:
- [ ] Items fijos funcionan
- [ ] Grupos de elección funcionan
- [ ] Reordenar items funciona
- [ ] Eliminar opciones funciona

**UI/UX**:
- [ ] Desktop responsive
- [ ] Mobile responsive
- [ ] Dark mode funciona
- [ ] Loading states
- [ ] Empty states
- [ ] Error states

**Performance**:
- [ ] Listado carga rápido (< 2s)
- [ ] Forms cargan rápido (< 1s)
- [ ] No queries lentos
- [ ] Eager loading correcto

**Testing**:
- [ ] Feature tests pasan
- [ ] Unit tests pasan
- [ ] Coverage > 80%

**Documentación**:
- [ ] README actualizado si aplica
- [ ] Docs técnicos completos
- [ ] Casos de uso documentados

### Plan de Rollback

Si algo falla en producción:

**Paso 1**: Revertir migraciones
- Ejecutar rollback de las 3 migraciones
- Verificar que DB vuelve a estado anterior

**Paso 2**: Revertir código
- Git revert de commits relacionados
- Redeployar versión anterior

**Paso 3**: Limpiar datos
- Si se crearon combinados de prueba, eliminarlos
- Verificar que no quedan referencias huérfanas

### Deploy Checklist

**Pre-Deploy**:
- [ ] Todos los tests pasan en staging
- [ ] Migraciones probadas en staging
- [ ] Seeder ejecutado en staging
- [ ] Testing manual completo en staging
- [ ] Backup de DB producción creado

**Deploy**:
- [ ] Ejecutar migraciones en producción
- [ ] Opcional: ejecutar seeder (si se desean ejemplos)
- [ ] Verificar que app funciona
- [ ] Probar crear 1 combinado de prueba
- [ ] Verificar listado
- [ ] Eliminar combinado de prueba

**Post-Deploy**:
- [ ] Monitorear errores en logs
- [ ] Verificar performance (queries lentos)
- [ ] Confirmar que usuarios pueden acceder
- [ ] Documentar issues si aparecen

---

## Criterios de Éxito

**Funcionalidad Core**:
1. ✅ Admin puede crear combinados con items fijos y grupos
2. ✅ Admin puede configurar vigencia (fechas + horarios + días)
3. ✅ Combinados se muestran/ocultan según vigencia
4. ✅ Sistema valida reglas de negocio
5. ✅ Items y opciones se gestionan correctamente

**Calidad**:
6. ✅ Tests > 80% coverage
7. ✅ No hay N+1 queries
8. ✅ UI responsive según UX-UI.md
9. ✅ Dark mode funciona

**Documentación**:
10. ✅ Docs técnicos completos
11. ✅ Seeders con datos realistas
12. ✅ Plan de implementación seguido

---

## Próximos Pasos Post-Implementación

Funcionalidad fuera del alcance actual:

1. **App Cliente**: Mostrar combinados como ofertas especiales
2. **Sistema de Carrito**: Soportar selección de opciones en grupos
3. **Sistema de Pedidos**: Procesar combinados correctamente
4. **Reportes**: Analytics de combinados más vendidos
5. **Notificaciones Push**: Alertar cuando inicia combinado nuevo
6. **Precios diferenciales**: Opciones con sobreprecio
7. **Restricciones adicionales**: Por restaurante, tipo de cliente
8. **Cache**: Optimización con cache de combinados válidos

---

## Conclusión

Esta implementación agrega la funcionalidad de Combinados al sistema, reutilizando la arquitectura probada de Combos pero adaptada a la naturaleza temporal de las promociones. El sistema permite crear ofertas especiales flexibles con vigencia completa (fechas + horarios + días) y precios fijos por zona.

### Arquitectura Reutilizada
- Estructura de items y opciones igual que combos
- Validaciones de productos y variantes
- Componentes frontend de combos
- Patrones de UI/UX establecidos

### Funcionalidad Nueva
- Vigencia temporal completa (fecha + hora + weekday)
- Solo 2 precios (vs 4 de combos)
- Scopes de disponibilidad temporal
- Estados: activo, inactivo, expirado, próximamente
- WeekdaySelector component

### Mantenibilidad
- Código documentado
- Tests completos
- Seeders para desarrollo
- Separación clara con otras promociones

**Tiempo estimado total**: 12-16 horas
**Complejidad**: Media-Alta
**Impacto**: Alto (nueva funcionalidad completa)
