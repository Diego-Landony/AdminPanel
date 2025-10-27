# Plan de Implementación: Combos con Grupos de Elección

**Documento**: Plan de Implementación Técnica
**Fecha**: 2025-01-24
**Versión**: 1.0
**Alcance**: Panel de Administración (ADMIN)

---

## Resumen Ejecutivo

Implementación del sistema de grupos de elección para combos, permitiendo crear items con múltiples opciones donde el cliente puede elegir. Este plan cubre exclusivamente el lado ADMIN para creación y gestión. El lado cliente (pedidos) se implementará en fase futura.

### Compatibilidad
100% compatible hacia atrás. Combos existentes no requieren migración y continúan funcionando como items fijos.

---

## FASE 1: Estructura de Base de Datos

### Objetivos
- Extender tabla `combo_items` para soportar grupos de elección
- Crear tabla `combo_item_options` para almacenar opciones
- Mantener compatibilidad con combos existentes

### Migración 1: Extender `combo_items`

**Validaciones**:
- `is_choice_group` default false preserva combos existentes
- `product_id` y `variant_id` NULL solo cuando `is_choice_group = true`

### Migración 2: Crear tabla `combo_item_options`

Tabla para almacenar las opciones disponibles en cada grupo de elección. Incluye referencias a productos, variantes opcionales, y orden de presentación.

**Características**:
- Eliminación en cascada cuando se elimina el combo item
- Restricción al eliminar productos/variantes usados
- Constraint único para evitar opciones duplicadas (misma combinación producto-variante)

### Verificación de Fase 1
- [ ] Migraciones ejecutan sin errores
- [ ] Rollback funciona correctamente
- [ ] Combos existentes mantienen `is_choice_group = false`
- [ ] Constraints de unique funcionan

---

## FASE 2: Modelos y Relaciones Eloquent

### Objetivos
- Crear modelo `ComboItemOption`
- Extender modelos existentes con nuevas relaciones
- Mantener convenciones del proyecto

### Nuevo Modelo: `ComboItemOption`

Modelo para representar cada opción dentro de un grupo de elección. Establece relaciones con:
- ComboItem (pertenece a)
- Product (pertenece a)
- ProductVariant (pertenece a, opcional)

### Extender Modelo: `ComboItem`

**Nuevos campos**:
- `is_choice_group`: indica si es grupo de elección
- `choice_label`: etiqueta descriptiva del grupo

**Nueva relación**:
- `options()`: relación HasMany con ComboItemOption, ordenada por sort_order

**Nuevos métodos**:
- `isChoiceGroup()`: verifica si el item es un grupo de elección
- `getProductWithSections()`: modificado para retornar null en grupos de elección

### Extender Modelo: `Combo`

**Scope modificado `available()`**:
Ahora considera dos tipos de items:
- Items fijos: todos los productos deben estar activos
- Grupos de elección: al menos 1 opción debe tener producto activo

**Nuevo scope `availableWithWarnings()`**:
Carga eager loading de opciones y productos para mostrar advertencias en admin

**Nuevo método `getInactiveOptionsCount()`**:
Cuenta cuántas opciones inactivas tiene el combo en sus grupos de elección

### Verificación de Fase 2
- [ ] Modelo ComboItemOption creado con todas las relaciones
- [ ] ComboItem extendido correctamente
- [ ] Combo scope available() funciona con grupos
- [ ] Método getInactiveOptionsCount() retorna correctamente
- [ ] Relaciones funcionan en tinker

---

## FASE 3: Validaciones Backend

### Objetivos
- Extender FormRequests con validaciones de grupos
- Implementar validaciones personalizadas
- Agregar mensajes de error claros

### Extender `StoreComboRequest`

**Nuevas reglas**:
- Validación de campos de grupo de elección (is_choice_group, choice_label)
- Validación de array de opciones (mínimo 2 opciones)
- Validación de cada opción (product_id, variant_id, sort_order)

**Nuevos mensajes**:
Mensajes de error personalizados y claros para cada regla de validación

**Nuevas validaciones personalizadas**:

1. **validateChoiceGroups**: Coordina todas las validaciones de grupos
   - Diferencia entre items fijos y grupos de elección
   - Valida que items fijos no tengan opciones
   - Valida mínimo 2 opciones en grupos

2. **validateNoDuplicateOptions**: Evita opciones duplicadas
   - Verifica que no exista la misma combinación producto-variante
   - Marca error en la opción específica duplicada

3. **validateVariantConsistency**: Valida coherencia de variantes
   - Si hay variantes, deben ser del mismo tamaño
   - Evita mezclar pan de 6" con 12" en mismo grupo

4. **validateOptionProductsActive**: Valida productos activos
   - Solo si el combo está activo
   - Verifica que todas las opciones tengan productos activos
   - Permite guardar inactivo con productos inactivos

### Extender `UpdateComboRequest`

Hereda todas las validaciones de StoreComboRequest. Validaciones idénticas para mantener consistencia.

### Verificación de Fase 3
- [ ] Validaciones rechazan grupos con < 2 opciones
- [ ] Validaciones rechazan opciones duplicadas
- [ ] Validaciones aceptan variantes consistentes
- [ ] Validaciones rechazan variantes inconsistentes
- [ ] Mensajes de error son claros
- [ ] Items fijos no pueden tener opciones

---

## FASE 4: Controllers

### Objetivos
- Extender ComboController para manejar grupos de elección
- Prevenir eliminación de productos usados en opciones
- Mantener API existente para items fijos

### Extender `ComboController`

**Modificar `store()` y `update()`**:
- Procesar items de dos tipos: fijos y grupos de elección
- Para items fijos: crear/actualizar como antes
- Para grupos de elección: crear/actualizar opciones
- Manejar eliminación de opciones removidas en updates

**Lógica de sincronización de opciones**:
1. Eliminar opciones que ya no existen en el request
2. Actualizar opciones existentes (por product_id + variant_id)
3. Crear opciones nuevas
4. Mantener sort_order correcto

**Modificar `index()`**:
Agregar eager loading de opciones para mostrar correctamente en listado

**Modificar `edit()`**:
Cargar opciones con productos y variantes para edición

### Extender `ProductController`

**Modificar `destroy()`**:
- Prevenir eliminación si el producto está usado en opciones de grupos
- Mensaje de error específico indicando en qué combos se usa

### Verificación de Fase 4
- [ ] Puede crear combo con grupo de elección
- [ ] Puede editar combo agregando/quitando opciones
- [ ] No puede eliminar producto usado en opciones
- [ ] Items fijos siguen funcionando igual
- [ ] Opciones se eliminan correctamente en cascada

---

## FASE 5: Constantes y Configuración

### Objetivos
- Definir tipos de items en constantes compartidas
- Configurar opciones de UI
- Mantener consistencia entre frontend y backend

### Extender `ui-constants.ts`

**Nuevas constantes**:

**COMBO_ITEM_TYPES**:
Define los tipos de items en un combo:
- FIXED: Item fijo tradicional (producto específico)
- CHOICE_GROUP: Grupo con opciones para elegir

**COMBO_ITEM_TYPE_LABELS**:
Etiquetas en español para mostrar en UI

**CHOICE_GROUP_CONFIG**:
Configuración de grupos de elección:
- Mínimo de opciones requeridas
- Máximo de opciones permitidas
- Placeholder para etiqueta del grupo

### Verificación de Fase 5
- [ ] Constantes accesibles en componentes
- [ ] Labels en español correctos
- [ ] Configuración MIN/MAX funciona en validaciones

---

## FASE 6: Componentes Frontend Reutilizables

### Objetivos
- Crear componentes modulares y reutilizables
- Seguir patrones del proyecto
- UI/UX consistente con el resto del admin

### Componente 1: `ItemTypeSelector`

**Propósito**: Selector visual para elegir entre item fijo o grupo de elección

**Funcionalidad**:
- Dos botones tipo radio estilizados
- Iconos descriptivos (package para fijo, layers para grupo)
- Cambia el tipo y notifica al componente padre
- Resetea datos relevantes al cambiar tipo

### Componente 2: `ProductSelectorModal`

**Propósito**: Modal para buscar y seleccionar productos (con o sin variantes)

**Funcionalidad**:
- Búsqueda por nombre de producto
- Muestra productos con sus variantes si las tienen
- Selección de producto o producto+variante
- Filtros para productos activos/inactivos
- Manejo de paginación si hay muchos productos

### Componente 3: `SortableChoiceOption`

**Propósito**: Tarjeta individual de opción con drag & drop

**Funcionalidad**:
- Muestra nombre del producto
- Muestra variante si aplica
- Botón de eliminación
- Indicador visual de estado (activo/inactivo)
- Drag handle para reordenar
- Integración con dnd-kit

### Componente 4: `ChoiceGroupEditor`

**Propósito**: Editor completo de un grupo de elección

**Funcionalidad**:
- Input para etiqueta del grupo
- Botón "Agregar Opción" que abre ProductSelectorModal
- Lista sortable de opciones
- Validación visual (mínimo 2 opciones)
- Eliminación de opciones
- Reordenamiento con drag & drop
- Muestra errores de validación

### Componente 5: `ComboItemCard`

**Propósito**: Tarjeta que representa un item completo del combo

**Funcionalidad**:
- Modo lectura y modo edición
- ItemTypeSelector en modo edición
- Para item fijo: muestra producto seleccionado
- Para grupo: muestra ChoiceGroupEditor
- Botón eliminar item completo
- Drag handle para reordenar items
- Indicadores de cantidad y sort_order

### Verificación de Fase 6
- [ ] ItemTypeSelector cambia entre tipos correctamente
- [ ] ProductSelectorModal busca y filtra productos
- [ ] SortableChoiceOption se reordena con drag & drop
- [ ] ChoiceGroupEditor valida mínimo 2 opciones
- [ ] ComboItemCard alterna entre fijo y grupo
- [ ] Todos los componentes son responsive

---

## FASE 7: Páginas Admin

### Objetivos
- Extender páginas existentes de combos
- Integrar componentes de Fase 6
- Mantener flujo de trabajo consistente

### Extender `create.tsx`

**Modificaciones**:
- Importar componentes de Fase 6
- Estado del formulario incluye is_choice_group y options
- Botón "Agregar Item" permite seleccionar tipo
- Renderizado condicional según tipo de item
- Validaciones frontend antes de submit
- Transformación correcta de datos para el backend

### Extender `edit.tsx`

**Modificaciones**:
- Cargar datos de opciones desde props de Inertia
- Transformar datos de backend a formato del formulario
- Permitir agregar/editar/eliminar opciones
- Mantener opciones existentes al editar
- Actualización correcta de opciones modificadas

### Extender `index.tsx`

**Modificaciones**:
- Mostrar indicador visual de combos con grupos
- Columna adicional mostrando cantidad de grupos
- Tooltip o badge mostrando opciones en grupos
- Filtros para distinguir combos con/sin grupos

### Verificación de Fase 7
- [ ] Puede crear combo con grupos desde cero
- [ ] Puede editar combo existente y agregar grupos
- [ ] Puede convertir item fijo en grupo y viceversa
- [ ] Listado muestra correctamente combos con grupos
- [ ] Validaciones frontend funcionan antes de submit

---

## FASE 8: Helpers y Utilidades

### Objetivos
- Centralizar lógica de transformación de datos
- Validaciones compartidas frontend
- Facilitar mantenimiento

### Crear archivo de helpers

**Funciones principales**:

**transformComboForBackend**:
- Transforma estructura del formulario al formato esperado por API
- Maneja items fijos y grupos de elección
- Limpia campos innecesarios según tipo

**transformComboFromBackend**:
- Transforma respuesta de API al formato del formulario
- Reconstruye estructura de opciones
- Prepara datos para componentes controlados

**validateComboItem**:
- Validación frontend de un item individual
- Retorna errores específicos por campo
- Usado antes del submit para feedback inmediato

**validateChoiceGroup**:
- Validación específica de grupos
- Verifica mínimo opciones
- Detecta duplicados
- Valida etiqueta requerida

**getProductDisplayName**:
- Formatea nombre de producto con variante
- Usado para mostrar en cards y selects

**isProductUsedInCombo**:
- Verifica si un producto está usado en algún item o opción
- Útil antes de eliminar productos

### Verificación de Fase 8
- [ ] Transformaciones funcionan bidireccionally
- [ ] Validaciones detectan todos los casos edge
- [ ] Helpers usados consistentemente en todos los componentes

---

## FASE 9: Testing Backend

### Objetivos
- Cobertura completa de funcionalidad de grupos
- Tests de integración y unitarios
- Validar compatibilidad hacia atrás

### Feature Test: ComboChoiceGroupTest

**Tests de creación**:
- Puede crear combo con grupo de elección
- Rechaza grupo con menos de 2 opciones
- Rechaza grupo sin etiqueta
- Rechaza grupo con opciones duplicadas
- Valida consistencia de variantes en grupo
- Rechaza productos inactivos en opciones (si combo activo)

**Tests de actualización**:
- Puede actualizar combo agregando grupo de elección
- Puede eliminar opciones de un grupo
- Puede agregar opciones a grupo existente
- Puede convertir item fijo en grupo
- Puede convertir grupo en item fijo

**Tests de disponibilidad**:
- Combo disponible cuando tiene al menos una opción activa en grupo
- Combo no disponible si todas las opciones están inactivas
- Combo con items fijos sigue funcionando igual

**Tests de eliminación**:
- No puede eliminar producto usado en grupo de elección
- Mensaje de error específico indica dónde se usa
- Puede eliminar producto no usado

### Unit Test: ComboItemOptionTest

**Tests de modelo**:
- ComboItemOption pertenece a ComboItem
- ComboItemOption pertenece a Product
- ComboItemOption pertenece a Variant (nullable)
- Opciones se eliminan en cascada cuando se elimina ComboItem
- ComboItemOption tiene casts correctos
- ComboItemOption tiene sort_order

**Tests de relaciones**:
- Puede tener múltiples opciones en un ComboItem
- Opciones ordenadas por sort_order
- Eager loading funciona correctamente

### Verificación de Fase 9
- [ ] Todos los tests pasan
- [ ] Cobertura > 80% en código nuevo
- [ ] Tests de compatibilidad confirman que combos viejos funcionan
- [ ] Tests de edge cases incluidos

---

## FASE 10: Verificación de Compatibilidad

### Objetivos
- Confirmar que combos existentes siguen funcionando
- Validar que la migración es segura
- Probar escenarios mixtos (combos con fijos y grupos)

### Tests de Compatibilidad

**Test: Combo Existente Sin Modificar**:
- Cargar combo creado antes de la implementación
- Verificar que se muestra correctamente
- Verificar que scope available() funciona
- Verificar que puede editarse sin problemas

**Test: Combo Mixto**:
- Crear combo con items fijos Y grupos de elección
- Verificar que ambos tipos coexisten
- Verificar validaciones aplicadas correctamente
- Verificar que scope available() evalúa ambos tipos

**Test: Conversión de Fijo a Grupo**:
- Cargar combo con item fijo
- Convertir item fijo en grupo de elección
- Verificar que la conversión preserva datos válidos
- Verificar que se eliminan datos incompatibles

### Checklist de Verificación Manual
- [ ] Cargar combos existentes en admin y verificar visualización
- [ ] Editar combo existente sin tocarlo y guardar (no debe cambiar)
- [ ] Crear combo solo con items fijos (flujo tradicional)
- [ ] Crear combo solo con grupos de elección
- [ ] Crear combo mixto (fijos + grupos)
- [ ] Verificar que scope available() retorna correctamente

---

## FASE 11: Documentación Técnica

### Objetivos
- Documentar la funcionalidad para futuros desarrolladores
- Explicar conceptos y decisiones de diseño
- Proveer ejemplos claros

### Actualizar `docs/COMBOS.md`

## Grupos de Elección

### Concepto

Un grupo de elección permite que un item del combo tenga múltiples opciones de productos, y el cliente seleccione uno al momento del pedido. Esto es diferente a los items fijos donde el producto está predeterminado.

**Ejemplo práctico**:
En vez de tener un combo con "Sub 6 pulgadas de Jamón", puedes tener un grupo "Elige tu Sub de 6 pulgadas" con opciones: Jamón, Pavo, BMT, Veggie, etc.

### Estructura de Datos

#### Combo Item con Grupo de Elección

Cuando un ComboItem tiene `is_choice_group = true`:
- El campo `product_id` debe ser NULL
- El campo `variant_id` debe ser NULL
- Debe tener al menos 2 opciones en `combo_item_options`
- Debe tener un `choice_label` descriptivo

### Reglas de Negocio

- Un grupo de elección debe tener mínimo 2 opciones
- No pueden existir opciones duplicadas (misma combinación producto-variante)
- Si hay variantes, deben ser del mismo tamaño para mantener consistencia
- Combos activos requieren que todas las opciones tengan productos activos
- Combos inactivos pueden guardar opciones con productos inactivos

### Disponibilidad

Un combo con grupos de elección está disponible cuando:
1. El combo está activo (`is_active = true`)
2. TODOS los items fijos tienen productos activos
3. TODOS los grupos tienen AL MENOS UNA opción con producto activo

Si un grupo pierde todas sus opciones activas, el combo completo se marca como no disponible.

### Limitaciones

- Solo aplicable en ADMIN para crear/gestionar
- El flujo de pedidos del lado cliente está fuera del alcance de esta implementación
- Las opciones no tienen precio diferencial (se implementará en futuro)
- No hay límite de cantidad por opción (se implementará en futuro)

### Crear `docs/COMBOS-CHOICE-GROUPS-IMPLEMENTATION.md`

## Arquitectura

### Base de Datos

#### Tabla: combo_items

Campos agregados:
- `is_choice_group`: boolean, indica si es grupo de elección
- `choice_label`: string nullable, etiqueta del grupo

Modificaciones:
- `product_id` y `variant_id` ahora nullable (solo NULL cuando es_choice_group = true)

#### Tabla: combo_item_options (nueva)

Almacena las opciones disponibles en cada grupo de elección.

Campos:
- `combo_item_id`: referencia al item del combo
- `product_id`: producto de esta opción
- `variant_id`: variante opcional
- `sort_order`: orden de presentación

Constraints:
- UNIQUE en (combo_item_id, product_id, variant_id)
- ON DELETE CASCADE desde combo_items
- ON DELETE RESTRICT desde products y product_variants

### Modelos Eloquent

#### ComboItemOption

Modelo nuevo que representa cada opción dentro de un grupo.

Relaciones:
- BelongsTo ComboItem
- BelongsTo Product
- BelongsTo ProductVariant (opcional)

#### ComboItem

Extendido con:
- Campo `is_choice_group`
- Campo `choice_label`
- Relación `options()` HasMany
- Método `isChoiceGroup()`

#### Combo

Extendido con:
- Scope `available()` modificado para considerar grupos
- Scope `availableWithWarnings()`
- Método `getInactiveOptionsCount()`

### Validaciones

#### StoreComboRequest / UpdateComboRequest

Validaciones agregadas:
- Grupos requieren mínimo 2 opciones
- Grupos requieren etiqueta (choice_label)
- No se permiten opciones duplicadas en un grupo
- Variantes deben ser consistentes en tamaño
- Productos activos requeridos si combo está activo

### Controllers

#### ComboController

Modificaciones en store() y update():
- Detecta tipo de item (fijo vs grupo)
- Para grupos: crea/actualiza/elimina opciones
- Sincroniza opciones en updates
- Eager loading de opciones en index() y edit()

#### ProductController

Modificación en destroy():
- Verifica si producto está usado en opciones
- Previene eliminación si está en uso
- Mensaje descriptivo indicando dónde se usa

### Frontend

#### Componentes Principales

- **ItemTypeSelector**: elige tipo de item (fijo o grupo)
- **ProductSelectorModal**: busca y selecciona productos/variantes
- **SortableChoiceOption**: tarjeta de opción con drag & drop
- **ChoiceGroupEditor**: editor completo de grupo
- **ComboItemCard**: tarjeta de item (fijo o grupo)

#### Páginas

Modificaciones en:
- `create.tsx`: formulario soporta grupos
- `edit.tsx`: carga y edita opciones
- `index.tsx`: visualiza combos con grupos

### Helpers

Utilidades para:
- Transformar datos formulario ↔ API
- Validaciones frontend
- Formateo de nombres de productos
- Detección de uso de productos

## Casos Edge

### 1. Producto Desactivado en Grupo

**Escenario**: Un producto usado en opciones se desactiva

**Comportamiento**:
- El combo muestra advertencia en admin
- Si es la única opción activa del grupo, combo pasa a no disponible
- Si hay otras opciones activas, el combo sigue disponible
- Admin puede editar y remover opción inactiva

### 2. Eliminar Producto Usado

**Escenario**: Intento de eliminar producto usado en opciones

**Comportamiento**:
- Eliminación es bloqueada por constraint
- Mensaje de error indica en qué combos se usa
- Admin debe remover opción primero
- Alternativa: desactivar producto en vez de eliminar

### 3. Variantes Inconsistentes

**Escenario**: Intento de mezclar variantes de diferentes tamaños en un grupo

**Comportamiento**:
- Validación rechaza el request
- Error específico indica inconsistencia
- Ejemplo: "No puedes mezclar pan 6\" y 12\" en el mismo grupo"

### 4. Duplicar Opción

**Escenario**: Agregar opción con misma combinación producto-variante

**Comportamiento**:
- Validación detecta duplicado
- Error en la opción específica
- Previene constraint unique de base de datos

## Troubleshooting

### Combo No Disponible

**Síntoma**: Combo activo no aparece en listados disponibles

**Diagnóstico**:
1. Verificar que combo.is_active = true
2. Revisar si items fijos tienen productos activos
3. Revisar si grupos tienen AL MENOS una opción con producto activo
4. Usar scope availableWithWarnings() para ver detalles

### Opciones No Aparecen en Edit

**Síntoma**: Al editar combo con grupos, las opciones no se cargan

**Diagnóstico**:
1. Verificar eager loading en ComboController::edit()
2. Confirmar que opciones existen en DB
3. Revisar transformación de datos en helper
4. Verificar estructura de props en Inertia

### Validación Falla Sin Razón Clara

**Síntoma**: Formulario rechazado pero error no es claro

**Diagnóstico**:
1. Revisar estructura exacta del request
2. Verificar que choice_label está presente si is_choice_group = true
3. Confirmar que opciones es array con mínimo 2 elementos
4. Validar que no hay duplicados en opciones

## Testing

### Feature Tests

Tests de integración completos cubriendo:
- Creación de combos con grupos
- Actualización agregando/quitando opciones
- Validaciones de negocio
- Prevención de eliminación de productos
- Disponibilidad con grupos
- Compatibilidad con combos legacy

### Unit Tests

Tests específicos de modelos:
- ComboItemOption y sus relaciones
- Métodos de ComboItem extendidos
- Scopes de Combo modificados

### Coverage

Meta mínima: 80% de cobertura en:
- Validaciones personalizadas
- Lógica de sincronización de opciones
- Helpers de transformación

## Performance

### Optimizaciones Implementadas

**Eager Loading**:
- Opciones cargadas con productos en listados
- Evita N+1 queries al mostrar combos con grupos

**Índices de Base de Datos**:
- combo_item_id indexado en combo_item_options
- Índice compuesto en combo_items (combo_id, is_choice_group)

**Validaciones Frontend**:
- Validación antes de submit reduce requests fallidos
- Feedback inmediato mejora UX

### Consideraciones Futuras

Para optimizar más si el volumen crece:
- Cache de combos disponibles
- Índices adicionales en búsquedas frecuentes
- Lazy loading de opciones en listados largos

## Migración

### De Combos Legacy a Grupos

No es necesario migrar combos existentes. Funcionan como siempre con `is_choice_group = false`.

Si se desea convertir:
1. Editar combo en admin
2. Cambiar item fijo a grupo usando ItemTypeSelector
3. Agregar opciones al grupo (el producto original puede ser una opción)
4. Guardar

### Agregar Grupos a Combo Existente

Para extender combo existente con grupos:
1. Editar combo
2. Agregar nuevo item
3. Seleccionar tipo "Grupo de Elección"
4. Configurar opciones
5. Guardar

## Referencias

- Documento original: COMBOS.md
- Estructura de datos: Ver migraciones en database/migrations/
- Modelos: app/Models/Menu/ComboItem.php, ComboItemOption.php
- Validaciones: app/Http/Requests/Menu/StoreComboRequest.php

### Verificación de Fase 11
- [ ] Documentación completa y clara
- [ ] Ejemplos incluidos
- [ ] Referencias correctas
- [ ] Casos edge documentados

---

## FASE 12: Revisión y Refinamiento Final

### Objetivos
- Code review exhaustivo
- Testing en múltiples navegadores
- Refinamiento de UI/UX
- Verificación de performance

### Code Review Checklist

**Backend**:
- [ ] Validaciones son exhaustivas y claras
- [ ] No hay N+1 queries
- [ ] Manejo de errores es robusto
- [ ] Nombres de métodos descriptivos
- [ ] Comentarios en lógica compleja
- [ ] Seguir PSR-12 code style
- [ ] Type hints en todos los métodos

**Frontend**:
- [ ] Componentes son reutilizables
- [ ] Props con TypeScript types correctos
- [ ] Manejo de loading states
- [ ] Manejo de errores user-friendly
- [ ] Nombres consistentes con convenciones del proyecto
- [ ] No hay console.logs olvidados
- [ ] Código formateado con Prettier

### Testing en Navegadores

Probar en:
- [ ] Chrome (latest)
- [ ] Firefox (latest)
- [ ] Safari (latest)
- [ ] Edge (latest)

Verificar:
- [ ] Drag & drop funciona smooth
- [ ] Modales se centran correctamente
- [ ] Validaciones en tiempo real
- [ ] Responsive en mobile/tablet

### Testing Manual Completo

**Crear Combo con Grupos**:
- [ ] Crear combo solo con grupos
- [ ] Agregar mínimo 2 opciones por grupo
- [ ] Reordenar opciones con drag & drop
- [ ] Guardar y verificar en DB
- [ ] Verificar que aparece en listado

**Editar Combo Existente**:
- [ ] Cargar combo con grupos
- [ ] Agregar nueva opción a grupo
- [ ] Eliminar opción existente
- [ ] Cambiar etiqueta del grupo
- [ ] Guardar y verificar cambios

**Validaciones**:
- [ ] Intentar guardar grupo con 1 opción (debe fallar)
- [ ] Intentar agregar opción duplicada (debe fallar)
- [ ] Intentar mezclar variantes 6" y 12" (debe fallar)
- [ ] Activar combo con productos inactivos (debe fallar)

**Compatibilidad**:
- [ ] Cargar combo legacy sin grupos
- [ ] Editar sin tocar, guardar (debe quedar igual)
- [ ] Convertir item fijo en grupo
- [ ] Crear combo mixto (fijos + grupos)

### Performance Check

- [ ] Listado de combos carga en < 2 segundos
- [ ] Formulario de edición carga en < 1 segundo
- [ ] Drag & drop responde sin lag
- [ ] Búsqueda de productos es instantánea
- [ ] No hay warnings en consola del navegador

### Refinamiento de Mensajes

Revisar y mejorar:
- [ ] Mensajes de validación son claros y accionables
- [ ] Mensajes de éxito confirman acción realizada
- [ ] Mensajes de error sugieren solución
- [ ] Tooltips explican campos no obvios
- [ ] Placeholders son descriptivos

### Verificación Final

- [ ] Todas las fases anteriores completadas al 100%
- [ ] Tests pasando (backend y potencialmente frontend)
- [ ] Documentación actualizada
- [ ] Code review aprobado
- [ ] Performance aceptable
- [ ] No hay bugs conocidos
- [ ] Demo exitoso con stakeholder

---

## Conclusión

Esta implementación agrega la funcionalidad de grupos de elección a los combos del sistema Subway, manteniendo 100% de compatibilidad con combos existentes. El alcance se limita al panel de administración, permitiendo crear y gestionar combos con opciones flexibles.

La arquitectura modular y las validaciones robustas aseguran que el sistema sea:
- **Confiable**: Validaciones evitan estados inconsistentes
- **Escalable**: Estructura permite futuras extensiones
- **Mantenible**: Código documentado y testeado
- **Compatible**: Combos legacy siguen funcionando sin cambios

### Próximos Pasos (Fuera del Alcance)

La implementación en el lado cliente (app móvil/web) requerirá:
- UI para seleccionar opciones durante el pedido
- Validación de selección requerida antes de agregar al carrito
- Cálculo de precios si se implementan precios diferenciales
- Integración con sistema de inventario

### Documentos de Referencia

- `docs/COMBOS.md` - Documentación general de combos
- `docs/COMBOS-CHOICE-GROUPS-IMPLEMENTATION.md` - Detalles de implementación
- Tests en `tests/Feature/Menu/ComboChoiceGroupTest.php`
- Componentes en `resources/js/components/combos/`
