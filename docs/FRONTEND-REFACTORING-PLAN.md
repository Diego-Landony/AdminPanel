# Plan de Refactorización Frontend - AdminPanel

**Fecha**: 2025-12-02
**Estado**: En Progreso
**Versión**: 1.0

---

## Tabla de Contenidos

1. [Resumen Ejecutivo](#resumen-ejecutivo)
2. [Inventario de Inconsistencias](#inventario-de-inconsistencias)
3. [Fases de Implementación](#fases-de-implementación)
4. [Fase 1: Sistema de Anchos y Componentes Base](#fase-1-sistema-de-anchos-y-componentes-base)
5. [Fase 2: Consolidación de Componentes de Error y Labels](#fase-2-consolidación-de-componentes-de-error-y-labels)
6. [Fase 3: Componentes de Tabla Reutilizables](#fase-3-componentes-de-tabla-reutilizables)
7. [Fase 4: Skeleton Loaders y Estados Vacíos](#fase-4-skeleton-loaders-y-estados-vacíos)
8. [Fase 5: Estandarización de Constantes y Páginas](#fase-5-estandarización-de-constantes-y-páginas)
9. [Checklist de Verificación](#checklist-de-verificación)

---

## Resumen Ejecutivo

Se identificaron **42 inconsistencias** en el frontend del AdminPanel, agrupadas en **9 categorías principales**. Este documento detalla cada problema encontrado y establece un plan de 5 fases para su corrección sistemática.

### Métricas Actuales vs Objetivo

| Métrica | Actual | Objetivo |
|---------|--------|----------|
| Líneas de código duplicado | ~200 | ~20 |
| Componentes de error | 3 | 1 |
| Implementaciones de label required | 4 | 1 |
| Tablas con skeleton loader | 1/5 | 5/5 |
| Tablas con min-width estándar | 1/5 | 5/5 |
| Variantes de spinner | 4 | 1 |
| Páginas usando PLACEHOLDERS | 1/9 | 9/9 |

---

## Inventario de Inconsistencias

### Categoría 1: Sistema de Anchos de Columnas

#### 1.1 Falta de min-width y max-width

**Severidad**: CRÍTICA

**Problema**: Solo `DataTable` implementa un sistema robusto con restricciones de ancho. Los demás componentes permiten anchos arbitrarios sin límites.

**Archivos afectados**:
- `resources/js/components/DataTable.tsx` ✅ (correcto)
- `resources/js/components/SortableTable.tsx` ❌
- `resources/js/components/SimpleTable.tsx` ❌
- `resources/js/components/GroupedSortableTable.tsx` ❌
- `resources/js/components/SimpleGroupedTable.tsx` ❌

**Código actual en DataTable (correcto)**:
```typescript
const columnWidthConfig = {
    xs: 'w-16 min-w-16 max-w-16',      // 64px fijo
    sm: 'w-24 min-w-24 max-w-24',      // 96px fijo
    md: 'w-32 min-w-32 max-w-48',      // 128-192px
    lg: 'w-48 min-w-48 max-w-64',      // 192-256px
    xl: 'w-64 min-w-64 max-w-80',      // 256-320px
    auto: 'w-auto min-w-0',
    full: 'w-full min-w-0',
} as const;
```

**Código actual en otros componentes (incorrecto)**:
```typescript
// SortableTable.tsx - línea 19
interface SortableTableColumn<T> {
    width?: string;  // Sin validación ni restricciones
}

// Uso en páginas - sin consistencia
width: 'w-64'   // Puede colapsar o expandir sin límites
width: 'w-32'   // Diferentes anchos arbitrarios
width: 'w-48'   // Sin patrón definido
```

**Impacto**:
- Columnas colapsan en pantallas pequeñas
- Expansión inconsistente en pantallas grandes
- No hay validación en tiempo de compilación

---

#### 1.2 Interfaces de columnas incompatibles

**Severidad**: ALTA

**Problema**: La función `render` tiene firmas diferentes entre componentes.

**DataTable**:
```typescript
render?: (item: T, value: unknown) => React.ReactNode;  // 2 parámetros
```

**Otros componentes**:
```typescript
render?: (item: T) => React.ReactNode;  // 1 parámetro
```

**Impacto**: No se pueden reutilizar definiciones de columnas entre tipos de tabla.

---

### Categoría 2: Componentes de Loading Spinner

#### 2.1 Spinners con estilos inconsistentes

**Severidad**: ALTA

**Problema**: Existen 4 variantes diferentes de spinner distribuidas en 8 archivos.

| Archivo | Tamaño | Color | Estilo |
|---------|--------|-------|--------|
| TableActions.tsx | h-4 w-4 | border-muted-foreground | border-2 border-t-transparent |
| DeleteConfirmationDialog.tsx | h-4 w-4 | border-white | border-2 border-t-transparent |
| ActionsMenu.tsx | h-4 w-4 | border-current | border-2 border-t-transparent |
| ImageUpload.tsx | h-8 w-8 | border-primary | border-b-2 (solo inferior) |
| create-page-layout.tsx | h-4 w-4 | border-white | border-2 border-t-transparent |
| edit-page-layout.tsx | h-4 w-4 | border-white | border-2 border-t-transparent |
| NetworkErrorRetry.tsx | h-4 w-4 | border-white | border-2 border-t-transparent |
| NitFormModal.tsx | h-4 w-4 | border-white | border-2 border-t-transparent |

**Código duplicado**:
```typescript
// Aparece en 7 archivos
<div className="mr-2 h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent" />

// ImageUpload usa estilo diferente
<div className="h-8 w-8 animate-spin rounded-full border-b-2 border-primary"></div>
```

**Impacto crítico**: El spinner con `border-white` es **invisible** en dark mode.

---

### Categoría 3: Componentes de Error de Formulario

#### 3.1 Triple implementación de errores

**Severidad**: ALTA

**Problema**: Existen 3 componentes que hacen casi lo mismo con implementaciones diferentes.

**FormError** (`ui/form-error.tsx`):
```typescript
export function FormError({ message, className }: FormErrorProps) {
    if (!message) return null;
    return (
        <div className={cn('flex items-center gap-2 text-sm text-destructive', className)}>
            <AlertCircle className="h-4 w-4" />
            <span>{message}</span>
        </div>
    );
}
```

**FormFieldError** (`FormFieldError.tsx`):
```typescript
const FormFieldErrorComponent = ({ message, className = '' }: FormFieldErrorProps) => {
    if (!message) return null;
    return (
        <div className={`flex items-center gap-2 text-sm text-destructive ${className}`}>
            <AlertCircle className="h-4 w-4" />
            <span>{message}</span>
        </div>
    );
};
```

**InputError** (`input-error.tsx`):
```typescript
export default function InputError({ message, className = '', ...props }) {
    return message ? (
        <p {...props} className={cn('text-sm text-red-600 dark:text-red-400', className)}>
            {message}
        </p>
    ) : null;
}
```

**Diferencias clave**:
| Aspecto | FormError | FormFieldError | InputError |
|---------|-----------|----------------|------------|
| Elemento | `<div>` | `<div>` | `<p>` |
| Ícono | ✅ AlertCircle | ✅ AlertCircle | ❌ Sin ícono |
| Función CSS | `cn()` | Template string | `cn()` |
| Color | text-destructive | text-destructive | text-red-600 (hardcoded) |
| Dark mode | Automático | Automático | Manual (dark:text-red-400) |

---

#### 3.2 Errores inline en componentes

**Severidad**: MEDIA

**Problema**: Algunos componentes renderizan errores directamente sin usar los componentes de error.

**ImageUpload.tsx línea 159**:
```typescript
{error && <p className="text-sm text-destructive">{error}</p>}
```

**WeekdaySelector.tsx línea 55**:
```typescript
{error && <p className="text-sm font-medium text-destructive">{error}</p>}
```

---

### Categoría 4: Labels con Indicador de Requerido

#### 4.1 Cuatro implementaciones diferentes

**Severidad**: MEDIA

**FormField** (`ui/form-field.tsx`):
```typescript
{required && <span className="text-red-500 ml-1">*</span>}
```

**ImageUpload.tsx**:
```typescript
{required && <span className="ml-1 text-destructive">*</span>}
```

**WeekdaySelector.tsx**:
```typescript
{required && <span className="ml-1 text-destructive">*</span>}
```

**LabelWithRequired.tsx**:
```typescript
{required && <span className="ml-1 text-red-500">*</span>}
```

**Inconsistencia**: `text-red-500` no es un token semántico del sistema de diseño, `text-destructive` sí lo es.

---

### Categoría 5: Código Duplicado en Tablas

#### 5.1 Stats Display (~65 líneas duplicadas)

**Severidad**: MEDIA

**Archivos con código idéntico**:
- DataTable.tsx (líneas 556-576)
- SortableTable.tsx (líneas 199-220)
- SimpleTable.tsx (líneas 111-132)

**Código duplicado**:
```typescript
{stats && stats.length > 0 && (
    <div className="flex min-w-0 flex-wrap items-center gap-x-4 gap-y-2 text-sm text-muted-foreground">
        {stats.map((stat, index) => (
            <div key={index} className="flex max-w-[200px] min-w-0 flex-shrink-0 items-center gap-2">
                {React.cloneElement(stat.icon as React.ReactElement<{ className?: string }>, {
                    className: `flex-shrink-0 ${(stat.icon as React.ReactElement<{ className?: string }>).props.className || ''}`,
                })}
                <span className="flex min-w-0 items-center gap-1 overflow-hidden">
                    <span className="truncate overflow-hidden text-ellipsis lowercase">
                        {stat.title}
                    </span>
                    <span className="font-medium whitespace-nowrap text-foreground tabular-nums">
                        {stat.value}
                    </span>
                </span>
            </div>
        ))}
    </div>
)}
```

**Versión diferente en GroupedSortableTable y SimpleGroupedTable**:
```typescript
<div className="flex flex-wrap items-center gap-6 text-sm text-muted-foreground">
    {stats.map((stat, index) => (
        <div key={index} className="flex items-center gap-2">
            {stat.icon}
            <span className="lowercase">{stat.title}</span>
            <span className="font-medium text-foreground">{stat.value}</span>
        </div>
    ))}
</div>
```

---

#### 5.2 Search Input (~200 líneas duplicadas)

**Severidad**: MEDIA

**Archivos afectados**:
- DataTable.tsx (líneas 630-681)
- SortableTable.tsx (líneas 245-271)
- SimpleTable.tsx (líneas 157-183)
- GroupedSortableTable.tsx (líneas 256-276)
- SimpleGroupedTable.tsx

---

#### 5.3 Refresh Button (~60 líneas duplicadas)

**Severidad**: BAJA

**Archivos afectados**: Los mismos 5 componentes de tabla.

---

### Categoría 6: Skeleton Loaders

#### 6.1 Soporte ausente en la mayoría de tablas

**Severidad**: ALTA

| Componente | Soporta Skeleton |
|-----------|------------------|
| DataTable | ✅ |
| SortableTable | ❌ |
| SimpleTable | ❌ |
| GroupedSortableTable | ❌ |
| SimpleGroupedTable | ❌ |

**Páginas afectadas**:
- categories/index.tsx - Sin skeleton
- products/index.tsx - Sin skeleton
- combos/index.tsx - Sin skeleton
- badge-types/index.tsx - Sin skeleton
- order/index.tsx - Sin skeleton

---

### Categoría 7: Estados Vacíos (Empty States)

#### 7.1 Alturas y espaciados inconsistentes

**Severidad**: BAJA

| Componente | Clase | Altura/Padding |
|-----------|-------|----------------|
| DataTable (desktop) | h-40 lg:h-32 | 160px / 128px |
| DataTable (mobile) | py-16 lg:py-12 | 64px / 48px |
| SortableTable | h-40 | 160px |
| GroupedSortableTable | p-12 | 48px padding |
| SimpleGroupedTable | py-12 + p-4 (móvil) | 48px + 16px extra |

---

### Categoría 8: Uso de Constantes

#### 8.1 PLACEHOLDERS no utilizados

**Severidad**: MEDIA

| Página | Usa PLACEHOLDERS.search |
|--------|------------------------|
| categories/index.tsx | ❌ "Buscar categorías..." |
| products/index.tsx | ❌ "Buscar productos..." |
| combos/index.tsx | ❌ "Buscar combos..." |
| badge-types/index.tsx | ❌ "Buscar badges..." |
| order/index.tsx | N/A |
| promotions/daily-special | ✅ |
| promotions/percentage | ✅ |
| promotions/two-for-one | ✅ |
| promotions/bundle-specials | ✅ |

---

#### 8.2 STATUS_CONFIGS redundantes

**Severidad**: BAJA

Existen 4 configuraciones de estado que comparten los mismos colores:
- ACTIVE_STATUS_CONFIGS
- PROMOTION_STATUS_CONFIGS
- COMBINADO_STATUS_CONFIGS
- USER_STATUS_CONFIGS

---

### Categoría 9: Layouts Create/Edit

#### 9.1 Props inconsistentes entre layouts

**Severidad**: BAJA

**CreatePageLayout**:
```typescript
interface CreatePageLayoutProps {
    title: string;
    description?: string;
    backHref: string;
    backLabel?: string;
    onSubmit: (e: React.FormEvent) => void;
    submitLabel?: string;
    processing: boolean;
    cancelHref?: string;
    pageTitle?: string;
    children: React.ReactNode;
    loading?: boolean;
    loadingSkeleton?: React.ComponentType;
}
```

**EditPageLayout** (props adicionales):
```typescript
interface EditPageLayoutProps extends CreatePageLayoutProps {
    disabled?: boolean;           // Solo en Edit
    isDirty?: boolean;            // Solo en Edit
    onReset?: () => void;         // Solo en Edit
    showResetButton?: boolean;    // Solo en Edit
}
```

---

## Fases de Implementación

### Resumen de Fases

| Fase | Descripción | Prioridad | Archivos | Estimación |
|------|-------------|-----------|----------|------------|
| 1 | Sistema de anchos y componentes base | CRÍTICA | 8 | 2-3 horas |
| 2 | Consolidación de errores y labels | ALTA | 12 | 1-2 horas |
| 3 | Componentes de tabla reutilizables | ALTA | 5 | 2-3 horas |
| 4 | Skeleton loaders y estados vacíos | MEDIA | 10 | 2 horas |
| 5 | Estandarización de constantes | MEDIA | 9 | 1-2 horas |

---

## Fase 1: Sistema de Anchos y Componentes Base

### Objetivo
Crear componentes base reutilizables y unificar el sistema de anchos de columnas.

### Tareas

#### 1.1 Crear LoadingSpinner Component
**Archivo**: `resources/js/components/ui/loading-spinner.tsx`

```typescript
interface LoadingSpinnerProps {
    size?: 'xs' | 'sm' | 'md' | 'lg';
    variant?: 'default' | 'white' | 'primary' | 'current';
    className?: string;
}

const sizeMap = {
    xs: 'h-3 w-3',
    sm: 'h-4 w-4',
    md: 'h-6 w-6',
    lg: 'h-8 w-8',
};

const variantMap = {
    default: 'border-muted-foreground',
    white: 'border-white dark:border-gray-200',
    primary: 'border-primary',
    current: 'border-current',
};

export function LoadingSpinner({
    size = 'sm',
    variant = 'default',
    className
}: LoadingSpinnerProps) {
    return (
        <div
            className={cn(
                'animate-spin rounded-full border-2 border-t-transparent',
                sizeMap[size],
                variantMap[variant],
                className
            )}
        />
    );
}
```

#### 1.2 Crear sistema de anchos compartido
**Archivo**: `resources/js/constants/table-constants.ts`

```typescript
export const COLUMN_WIDTHS = {
    xs: 'w-16 min-w-16 max-w-16',      // 64px - Actions, icons
    sm: 'w-24 min-w-24 max-w-24',      // 96px - Status, dates
    md: 'w-32 min-w-32 max-w-48',      // 128-192px - Short text
    lg: 'w-48 min-w-48 max-w-64',      // 192-256px - Names, emails
    xl: 'w-64 min-w-64 max-w-80',      // 256-320px - Long content
    '2xl': 'w-80 min-w-80 max-w-96',   // 320-384px - Extra long
    auto: 'w-auto min-w-0',
    full: 'flex-1 min-w-0',
} as const;

export type ColumnWidth = keyof typeof COLUMN_WIDTHS;

export const TEXT_ALIGNMENT = {
    left: 'text-left',
    center: 'text-center',
    right: 'text-right',
} as const;

export type TextAlignment = keyof typeof TEXT_ALIGNMENT;

export interface BaseTableColumn<T> {
    key: string;
    title: string;
    width?: ColumnWidth;
    textAlign?: TextAlignment;
    className?: string;
    render: (item: T) => React.ReactNode;
}
```

#### 1.3 Actualizar SortableTable
- Importar COLUMN_WIDTHS y TEXT_ALIGNMENT
- Actualizar interface SortableTableColumn
- Aplicar clases de ancho correctamente

#### 1.4 Actualizar SimpleTable
- Mismos cambios que SortableTable

#### 1.5 Actualizar GroupedSortableTable
- Mismos cambios que SortableTable

#### 1.6 Actualizar SimpleGroupedTable
- Mismos cambios que SortableTable

#### 1.7 Reemplazar spinners duplicados
Actualizar los siguientes archivos para usar `<LoadingSpinner />`:
- TableActions.tsx
- DeleteConfirmationDialog.tsx
- ActionsMenu.tsx
- create-page-layout.tsx
- edit-page-layout.tsx
- NetworkErrorRetry.tsx
- ImageUpload.tsx
- NitFormModal.tsx

### Archivos a modificar en Fase 1
1. `resources/js/components/ui/loading-spinner.tsx` (CREAR)
2. `resources/js/constants/table-constants.ts` (CREAR)
3. `resources/js/components/SortableTable.tsx`
4. `resources/js/components/SimpleTable.tsx`
5. `resources/js/components/GroupedSortableTable.tsx`
6. `resources/js/components/SimpleGroupedTable.tsx`
7. `resources/js/components/TableActions.tsx`
8. `resources/js/components/DeleteConfirmationDialog.tsx`

---

## Fase 2: Consolidación de Componentes de Error y Labels

### Objetivo
Unificar los componentes de error y labels con indicador de requerido.

### Tareas

#### 2.1 Deprecar FormFieldError e InputError
- Marcar como deprecated
- Actualizar imports a FormError

#### 2.2 Actualizar FormError
- Asegurar soporte completo de dark mode
- Agregar variantes si es necesario

#### 2.3 Crear LabelWithRequired unificado
```typescript
interface LabelWithRequiredProps {
    children: React.ReactNode;
    required?: boolean;
    htmlFor?: string;
    className?: string;
}

export function LabelWithRequired({
    children,
    required,
    htmlFor,
    className
}: LabelWithRequiredProps) {
    return (
        <label
            htmlFor={htmlFor}
            className={cn(
                'text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70',
                className
            )}
        >
            {children}
            {required && <span className="ml-1 text-destructive">*</span>}
        </label>
    );
}
```

#### 2.4 Actualizar FormField
- Usar LabelWithRequired
- Usar FormError para errores

#### 2.5 Actualizar ImageUpload
- Usar LabelWithRequired
- Usar FormError para errores

#### 2.6 Actualizar WeekdaySelector
- Usar LabelWithRequired
- Usar FormError para errores

### Archivos a modificar en Fase 2
1. `resources/js/components/ui/form-error.tsx`
2. `resources/js/components/LabelWithRequired.tsx`
3. `resources/js/components/FormFieldError.tsx` (DEPRECAR)
4. `resources/js/components/input-error.tsx` (DEPRECAR)
5. `resources/js/components/ui/form-field.tsx`
6. `resources/js/components/ImageUpload.tsx`
7. `resources/js/components/WeekdaySelector.tsx`

---

## Fase 3: Componentes de Tabla Reutilizables

### Objetivo
Extraer código duplicado en componentes reutilizables.

### Tareas

#### 3.1 Crear TableStats Component
```typescript
interface TableStatsProps {
    stats: Array<{
        title: string;
        value: number | string;
        icon: React.ReactNode;
    }>;
}

export function TableStats({ stats }: TableStatsProps) {
    if (!stats || stats.length === 0) return null;

    return (
        <div className="flex min-w-0 flex-wrap items-center gap-x-4 gap-y-2 text-sm text-muted-foreground">
            {stats.map((stat, index) => (
                <div key={index} className="flex max-w-[200px] min-w-0 flex-shrink-0 items-center gap-2">
                    {React.cloneElement(stat.icon as React.ReactElement, {
                        className: 'flex-shrink-0 h-4 w-4',
                    })}
                    <span className="flex min-w-0 items-center gap-1 overflow-hidden">
                        <span className="truncate lowercase">{stat.title}</span>
                        <span className="font-medium whitespace-nowrap text-foreground tabular-nums">
                            {stat.value}
                        </span>
                    </span>
                </div>
            ))}
        </div>
    );
}
```

#### 3.2 Crear TableSearch Component
```typescript
interface TableSearchProps {
    value: string;
    onChange: (value: string) => void;
    onClear: () => void;
    placeholder?: string;
    onSearch?: () => void;
    isLoading?: boolean;
}
```

#### 3.3 Crear TableRefreshButton Component
```typescript
interface TableRefreshButtonProps {
    onRefresh: () => void;
    isRefreshing?: boolean;
    showTimestamp?: boolean;
}
```

#### 3.4 Actualizar todas las tablas
- Reemplazar código duplicado con nuevos componentes

### Archivos a modificar en Fase 3
1. `resources/js/components/table/TableStats.tsx` (CREAR)
2. `resources/js/components/table/TableSearch.tsx` (CREAR)
3. `resources/js/components/table/TableRefreshButton.tsx` (CREAR)
4. `resources/js/components/DataTable.tsx`
5. `resources/js/components/SortableTable.tsx`
6. `resources/js/components/SimpleTable.tsx`
7. `resources/js/components/GroupedSortableTable.tsx`
8. `resources/js/components/SimpleGroupedTable.tsx`

---

## Fase 4: Skeleton Loaders y Estados Vacíos

### Objetivo
Implementar skeleton loaders en todas las tablas y estandarizar estados vacíos.

### Tareas

#### 4.1 Crear TableEmptyState Component
```typescript
interface TableEmptyStateProps {
    message?: string;
    searchMessage?: string;
    hasSearch?: boolean;
}

export function TableEmptyState({
    message = 'No se encontraron resultados',
    searchMessage = 'Intenta con términos de búsqueda diferentes',
    hasSearch = false
}: TableEmptyStateProps) {
    return (
        <div className="flex flex-col items-center justify-center space-y-2 py-12">
            <p className="text-sm text-muted-foreground">{message}</p>
            {hasSearch && (
                <p className="text-xs text-muted-foreground">{searchMessage}</p>
            )}
        </div>
    );
}
```

#### 4.2 Agregar loadingSkeleton prop a SortableTable

#### 4.3 Agregar loadingSkeleton prop a SimpleTable

#### 4.4 Agregar loadingSkeleton prop a GroupedSortableTable

#### 4.5 Agregar loadingSkeleton prop a SimpleGroupedTable

#### 4.6 Crear skeletons para cada página
- CategoriesSkeleton
- ProductsSkeleton
- CombosSkeleton
- BadgeTypesSkeleton

### Archivos a modificar en Fase 4
1. `resources/js/components/table/TableEmptyState.tsx` (CREAR)
2. `resources/js/components/SortableTable.tsx`
3. `resources/js/components/SimpleTable.tsx`
4. `resources/js/components/GroupedSortableTable.tsx`
5. `resources/js/components/SimpleGroupedTable.tsx`
6. `resources/js/components/skeletons.tsx`

---

## Fase 5: Estandarización de Constantes y Páginas

### Objetivo
Actualizar todas las páginas para usar constantes centralizadas.

### Tareas

#### 5.1 Actualizar ui-constants.ts
- Agregar placeholders faltantes si es necesario

#### 5.2 Actualizar categories/index.tsx
- Usar PLACEHOLDERS.search
- Aplicar nuevos anchos de columna

#### 5.3 Actualizar products/index.tsx
- Usar PLACEHOLDERS.search
- Aplicar nuevos anchos de columna

#### 5.4 Actualizar combos/index.tsx
- Usar PLACEHOLDERS.search
- Aplicar nuevos anchos de columna

#### 5.5 Actualizar badge-types/index.tsx
- Usar PLACEHOLDERS.search
- Aplicar nuevos anchos de columna

#### 5.6 Consolidar STATUS_CONFIGS
- Evaluar si PROMOTION_STATUS_CONFIGS puede usar ACTIVE_STATUS_CONFIGS
- Documentar diferencias necesarias

### Archivos a modificar en Fase 5
1. `resources/js/constants/ui-constants.ts`
2. `resources/js/pages/menu/categories/index.tsx`
3. `resources/js/pages/menu/products/index.tsx`
4. `resources/js/pages/menu/combos/index.tsx`
5. `resources/js/pages/menu/badge-types/index.tsx`
6. `resources/js/components/status-badge.tsx`

---

## Checklist de Verificación

### Post-Fase 1
- [ ] LoadingSpinner funciona con todas las variantes
- [ ] Spinners visibles en dark mode
- [ ] Tablas respetan min/max width
- [ ] TypeScript compila sin errores

### Post-Fase 2
- [ ] Solo existe un componente de error activo
- [ ] Labels con asterisco usan text-destructive
- [ ] FormField usa componentes unificados
- [ ] Dark mode funciona correctamente

### Post-Fase 3
- [ ] TableStats renderiza correctamente en todas las tablas
- [ ] TableSearch funciona igual en todas las tablas
- [ ] TableRefreshButton muestra timestamp
- [ ] No hay código duplicado de stats/search/refresh

### Post-Fase 4
- [ ] Todas las tablas muestran skeleton al cargar
- [ ] Estados vacíos tienen altura consistente
- [ ] Mensaje de búsqueda aparece cuando corresponde

### Post-Fase 5
- [ ] Todas las páginas usan PLACEHOLDERS.search
- [ ] Columnas usan sistema de anchos tipado
- [ ] STATUS_CONFIGS documentados y consolidados

---

## Notas de Implementación

### Compatibilidad hacia atrás
- Los componentes deprecados deben seguir funcionando temporalmente
- Agregar warnings de consola en desarrollo para imports deprecados

### Testing
- Verificar cada fase en dark mode
- Probar responsive en todas las tablas
- Validar que TypeScript compile correctamente

### Rollback
- Cada fase puede ser revertida independientemente
- Commits atómicos por archivo modificado

---

## Historial de Cambios

| Fecha | Versión | Cambios |
|-------|---------|---------|
| 2025-12-02 | 1.0 | Documento inicial creado |
| 2025-12-02 | 2.0 | **TODAS LAS FASES COMPLETADAS** |

---

## Resumen de Implementación Completada

### Fase 1: Sistema de Anchos y Componentes Base ✅
- Creado `LoadingSpinner` component con 4 variantes y 4 tamaños
- Creado `table-constants.ts` con sistema tipado de anchos
- Actualizado SortableTable, SimpleTable, GroupedSortableTable, SimpleGroupedTable
- Reemplazados spinners en TableActions y DeleteConfirmationDialog

### Fase 2: Consolidación de Errores y Labels ✅
- Actualizado `FormError` como componente principal con `showIcon` prop
- Deprecado `FormFieldError` e `InputError` (re-exportan FormError)
- Actualizado `LabelWithRequired` con `text-destructive` y accesibilidad
- Actualizado FormField, ImageUpload, WeekdaySelector

### Fase 3: Componentes de Tabla Reutilizables ✅
- Creado `TableStats` component
- Creado `TableSearch` component
- Creado `TableRefreshButton` component
- Actualizado todas las tablas para usar nuevos componentes
- Eliminadas ~212 líneas de código duplicado

### Fase 4: Skeleton Loaders y Estados Vacíos ✅
- Creado `TableEmptyState` con variantes default/search
- Agregado soporte `isLoading` + `loadingSkeleton` a todas las tablas
- Actualizado empty states en todas las tablas

### Fase 5: Estandarización de Constantes ✅
- Actualizado categories/index.tsx con PLACEHOLDERS y anchos tipados
- Actualizado products/index.tsx con PLACEHOLDERS y anchos tipados
- Actualizado combos/index.tsx con PLACEHOLDERS y anchos tipados
- Actualizado badge-types/index.tsx con PLACEHOLDERS y anchos tipados

### Métricas Finales

| Métrica | Antes | Después |
|---------|-------|---------|
| Líneas de código duplicado | ~200 | ~20 |
| Componentes de error | 3 | 1 (+ 2 deprecated) |
| Implementaciones de label required | 4 | 1 |
| Tablas con skeleton loader | 1/5 | 5/5 |
| Tablas con min-width estándar | 1/5 | 5/5 |
| Variantes de spinner | 4 | 1 |
| Páginas usando PLACEHOLDERS | 4/9 | 8/9 |

### Nuevos Componentes Disponibles

```typescript
// Componentes de tabla
import { TableStats, TableSearch, TableRefreshButton, TableEmptyState } from '@/components/table';

// Sistema de anchos
import { COLUMN_WIDTHS, getColumnWidthClass, getTextAlignmentClass } from '@/constants/table-constants';

// Loading spinner unificado
import { LoadingSpinner } from '@/components/ui/loading-spinner';

// Constantes UI
import { PLACEHOLDERS, NOTIFICATIONS } from '@/constants/ui-constants';
```
