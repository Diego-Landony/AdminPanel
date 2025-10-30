# UX-UI Guidelines

**Guía de diseño y experiencia de usuario para el Panel de Administración**

Esta guía documenta todos los patrones, estándares y convenciones establecidos en la aplicación. Todo nuevo desarrollo debe seguir estas directrices para mantener la consistencia y calidad del sistema.

---

## Tabla de Contenidos

1. [Filosofía de Diseño](#filosofía-de-diseño)
2. [Constantes Centralizadas](#constantes-centralizadas)
3. [Componentes de Tabla](#componentes-de-tabla)
4. [Layouts de Página](#layouts-de-página)
5. [Formularios](#formularios)
6. [Estados y Badges](#estados-y-badges)
7. [Responsividad Mobile](#responsividad-mobile)
8. [Tipografía y Espaciado](#tipografía-y-espaciado)
9. [Patrones de Navegación](#patrones-de-navegación)
10. [Gestión de Errores](#gestión-de-errores)
11. [Checklist de Nuevas Páginas](#checklist-de-nuevas-páginas)
12. [Errores Comunes](#errores-comunes)

---

## Filosofía de Diseño

### Principios Core

1. **Minimalismo**: Sin texto verboso, sin descripciones innecesarias
2. **Consistencia**: Mismo patrón en todo el sistema
3. **Reutilización**: Componentes centralizados, sin duplicación
4. **Accesibilidad**: Dark mode, tooltips, estados de carga
5. **Predictibilidad**: Usuarios deben saber qué esperar

### Do's and Don'ts

#### ✅ DO
- Usar constantes de `ui-constants.ts` para todos los textos
- Reutilizar componentes existentes antes de crear nuevos
- Implementar skeleton loading states
- Mantener mobile-first responsive design
- Usar iconos de Lucide React
- Implementar estados vacíos informativos

#### ❌ DON'T
- NO crear texto hardcodeado en componentes
- NO duplicar componentes existentes
- NO usar verbose descriptions
- NO ignorar estados de carga
- NO crear estilos inline sin justificación
- NO omitir mobile cards en tablas

---

## Constantes Centralizadas

### Ubicación
`resources/js/constants/ui-constants.ts`

### Categorías de Constantes

#### PLACEHOLDERS

**Filosofía**: Solo usar placeholders cuando agreguen valor técnico o de formato. Labels autoexplicativos NO necesitan placeholder.

Placeholders están centralizados para **valores técnicos específicos**:

```typescript
import { PLACEHOLDERS } from '@/constants/ui-constants';

// ✅ USAR - Tiene formato específico
<Input placeholder={PLACEHOLDERS.email} />      // correo@ejemplo.com
<Input placeholder={PLACEHOLDERS.phone} />      // +502 1234-5678
<Input placeholder={PLACEHOLDERS.price} />      // 0.00
<Input placeholder={PLACEHOLDERS.nit} />        // 12345678-9

// ✅ USAR - Valor auto-generado
<Input placeholder={PLACEHOLDERS.sku} />        // Se genera automáticamente
<Input placeholder={PLACEHOLDERS.slug} />       // Se genera automáticamente

// ❌ NO USAR - Label es suficiente
<FormField label="Nombre">
  <Input />  {/* Sin placeholder - obvio por el label */}
</FormField>

<FormField label="Descripción">
  <Textarea />  {/* Sin placeholder - obvio por el label */}
</FormField>
```

**Regla de oro**: ¿El label por sí solo es claro? → NO usar placeholder

**Constantes disponibles** (18 total):
- **Credenciales**: `email`, `password`
- **Contacto**: `phone`, `address`, `location`, `latitude`, `longitude`
- **Identificación**: `nit`, `subwayCard`
- **Numéricos**: `price`, `percentage`, `amount`, `deliveryFee`, `estimatedTime`
- **Auto-generados**: `sku`, `slug`, `sortOrder`
- **Búsqueda**: `search` (universal para todos los contextos)

#### NOTIFICATIONS
Mensajes de notificación estandarizados:

```typescript
import { NOTIFICATIONS } from '@/constants/ui-constants';

// Success
showNotification.success(NOTIFICATIONS.success.created);
showNotification.success(NOTIFICATIONS.success.updated);

// Error
showNotification.error(NOTIFICATIONS.error.server);
showNotification.error(NOTIFICATIONS.error.connectionError);
```

#### FORM_SECTIONS
Títulos y descripciones para FormSection:

```typescript
import { FORM_SECTIONS } from '@/constants/ui-constants';

<FormSection
    icon={Package}
    title={FORM_SECTIONS.basicInfo.title}
    description={FORM_SECTIONS.basicInfo.description}
>
```

#### FIELD_DESCRIPTIONS
Descripciones de validación de campos:

```typescript
import { FIELD_DESCRIPTIONS } from '@/constants/ui-constants';

<FormField
    label="Contraseña"
    description={FIELD_DESCRIPTIONS.passwordMinimum6}
/>
```

#### CURRENCY
Configuración de moneda:

```typescript
import { CURRENCY } from '@/constants/ui-constants';

<span>{CURRENCY.symbol}{price}</span>
```

#### AUTOCOMPLETE
Valores de autocomplete estandarizados:

```typescript
import { AUTOCOMPLETE } from '@/constants/ui-constants';

<Input autoComplete={AUTOCOMPLETE.email} />
<Input autoComplete={AUTOCOMPLETE.newPassword} />
```

---

## Componentes de Tabla

### Tipos de Tablas

La aplicación tiene **3 componentes de tabla** especializados:

#### 1. DataTable (Paginada con Filtros)
**Ubicación**: `resources/js/components/DataTable.tsx`

**Cuándo usar**: Listados con **paginación** y datos del servidor.

**Características**:
- Paginación server-side
- Búsqueda con debounce
- Sorting de múltiples columnas
- Filtros activos con chips removibles
- Selector de items por página (10, 25, 50, 100)
- Refresh manual
- Loading skeletons

**Ejemplo de uso**:
```typescript
<DataTable
    title="Clientes"
    description="Gestiona los clientes del sistema"
    data={customers} // PaginatedData<Customer>
    columns={columns}
    stats={stats}
    filters={filters}
    createUrl="/customers/create"
    createLabel="Crear"
    searchPlaceholder="Buscar por nombre, email..."
    loadingSkeleton={CustomersSkeleton}
    renderMobileCard={(customer) => <CustomerMobileCard customer={customer} />}
    routeName="/customers"
    breakpoint="md"
/>
```

**Configuración de Columnas**:
```typescript
const columns = [
    {
        key: 'customer',
        title: 'Cliente',
        width: 'lg' as const,     // xs, sm, md, lg, xl, auto, full
        sortable: true,
        render: (customer: Customer) => (
            <EntityInfoCell
                icon={Users}
                primaryText={customer.full_name}
                secondaryText={customer.email}
            />
        ),
    },
    {
        key: 'status',
        title: 'Estado',
        width: 'sm' as const,
        textAlign: 'center' as const,
        render: (customer: Customer) => (
            <StatusBadge
                status={customer.status}
                configs={CONNECTION_STATUS_CONFIGS}
            />
        ),
    },
    {
        key: 'actions',
        title: 'Acciones',
        width: 'xs' as const,
        textAlign: 'right' as const,
        render: (customer: Customer) => (
            <TableActions
                editHref={`/customers/${customer.id}/edit`}
                onDelete={() => openDeleteDialog(customer)}
                isDeleting={deletingCustomer === customer.id}
            />
        ),
    },
];
```

**Column Width System**:
```typescript
xs: 'w-16'     // 64px  - Actions, icons
sm: 'w-24'     // 96px  - Status, dates
md: 'w-32'     // 128px - Short text
lg: 'w-48'     // 192px - Names, emails
xl: 'w-64'     // 256px - Long content
auto: 'w-auto' // Content-based
full: 'w-full' // Full width
```

#### 2. SortableTable (Drag & Drop Sin Paginación)
**Ubicación**: `resources/js/components/SortableTable.tsx`

**Cuándo usar**: Listados **sin paginación** con ordenamiento manual (ej: categorías, secciones).

**Características**:
- Drag & drop para reordenar
- Sin paginación (muestra todo)
- Búsqueda simple local
- Indicador de cambios sin guardar
- Botón "Guardar Orden"

**Ejemplo de uso**:
```typescript
<SortableTable
    title="Categorías de Menú"
    description="Administra las categorías del menú"
    data={categories}
    columns={columns}
    stats={categoryStats}
    createUrl="/menu/categories/create"
    createLabel="Crear"
    searchable={true}
    searchPlaceholder="Buscar categorías..."
    onReorder={handleReorder}
    onRefresh={handleRefresh}
    isSaving={isSaving}
    renderMobileCard={renderMobileCard}
    breakpoint="lg"
/>
```

**Características de onReorder**:
```typescript
const handleReorder = (reorderedItems: Category[]) => {
    setIsSaving(true);

    const orderData = reorderedItems.map((item, index) => ({
        id: item.id,
        sort_order: index + 1,
    }));

    router.post(route('menu.categories.reorder'), { categories: orderData }, {
        preserveState: true,
        onSuccess: () => showNotification.success('Orden guardado'),
        onFinish: () => setIsSaving(false),
    });
};
```

#### 3. GroupedSortableTable (Agrupada con Drag & Drop)
**Ubicación**: `resources/js/components/GroupedSortableTable.tsx`

**Cuándo usar**: Listados **agrupados por categoría** con drag & drop independiente por grupo (ej: productos).

**Características**:
- Grupos con headers visuales
- Drag & drop independiente por grupo
- Sin paginación
- Headers de categoría con estilo `bg-muted/50`

**Ejemplo de uso**:
```typescript
<GroupedSortableTable
    title="Productos de Menú"
    description="Gestiona los productos de tu menú, agrupados por categoría"
    groupedData={groupedProducts}
    columns={columns}
    stats={productStats}
    createUrl="/menu/products/create"
    createLabel="Crear"
    searchable={true}
    searchPlaceholder="Buscar productos..."
    onReorder={handleReorder}
    onRefresh={handleRefresh}
    isSaving={isSaving}
    renderMobileCard={renderMobileCard}
    breakpoint="lg"
/>
```

**Estructura de datos agrupados**:
```typescript
interface CategoryGroup<T> {
    category: {
        id: number | null;
        name: string;
    };
    products: T[];
}
```

### Breakpoints para Tablas

Todas las tablas soportan configuración de breakpoint:

```typescript
breakpoint="sm"  // Cambia a mobile en < 640px
breakpoint="md"  // Cambia a mobile en < 768px (DEFAULT)
breakpoint="lg"  // Cambia a mobile en < 1024px
breakpoint="xl"  // Cambia a mobile en < 1280px
```

**Regla**: Usar `"lg"` para tablas simples (2-4 columnas), `"md"` para tablas complejas (5+ columnas).

### Stats (Estadísticas)

Todas las tablas pueden mostrar stats en el header:

```typescript
const stats = [
    {
        title: 'usuarios',        // lowercase
        value: total_users,
        icon: <Users className="h-3 w-3 text-primary" />,
    },
    {
        title: 'en línea',
        value: online_users,
        icon: <Clock className="h-3 w-3 text-green-600" />,
    },
];
```

**Reglas de Stats**:
- Título siempre en **lowercase**
- Iconos de tamaño `h-3 w-3` o `h-4 w-4`
- Usar colores semánticos: `text-primary`, `text-green-600`, `text-red-600`
- Máximo 3-4 stats para no saturar

---

## Layouts de Página

### Tipos de Layouts

#### 1. CreatePageLayout
**Ubicación**: `resources/js/components/create-page-layout.tsx`

**Cuándo usar**: Todas las páginas de creación de entidades.

**Estructura**:
```typescript
<CreatePageLayout
    title="Nueva Categoría"
    description="Crea una nueva categoría de menú"
    backHref={route('menu.categories.index')}
    backLabel="Volver"
    onSubmit={handleSubmit}
    submitLabel="Guardar"
    processing={processing}
    pageTitle="Crear Categoría"  // Para <Head>
    loading={processing}
    loadingSkeleton={CreateCategoriesSkeleton}
>
    <FormSection icon={Layers} title="Información Básica">
        {/* Campos del formulario */}
    </FormSection>
</CreatePageLayout>
```

**Características**:
- Header con título y botón "Volver"
- Form wrapper automático
- Botones "Cancelar" y "Guardar" en footer
- Loading state con skeleton
- Max-width de `2xl` para el contenido
- Responsive: botones en columna en mobile

#### 2. EditPageLayout
**Ubicación**: `resources/js/components/edit-page-layout.tsx`

**Cuándo usar**: Todas las páginas de edición de entidades.

**Diferencias con Create**:
- `submitLabel` por defecto es "Actualizar"
- Soporta `isDirty` para tracking de cambios
- Soporta `onReset` para descartar cambios
- Puede ser `disabled` para entidades no editables

**Ejemplo con tracking de cambios**:
```typescript
const [isDirty, setIsDirty] = useState(false);

<EditPageLayout
    title="Editar Categoría"
    description={`Modifica los datos de la categoría "${category.name}"`}
    backHref={route('menu.categories.index')}
    onSubmit={handleSubmit}
    processing={isSubmitting}
    isDirty={isDirty}
    showResetButton={true}
    onReset={() => {
        setFormData(initialData);
        setIsDirty(false);
    }}
>
```

### Estructura Común de Layouts

**Header**:
```typescript
// Título y descripción a la izquierda
// Botón de acción a la derecha
<div className="flex items-center justify-between">
    <div className="space-y-1">
        <h1 className="text-3xl font-bold tracking-tight">{title}</h1>
        <p className="text-muted-foreground">{description}</p>
    </div>
    <Button>Acción</Button>
</div>
```

**Footer de formularios**:
```typescript
<div className="mt-8 flex flex-col items-stretch justify-end gap-3 sm:flex-row">
    <Button variant="outline">Cancelar</Button>
    <Button type="submit" disabled={processing}>
        {processing ? 'Guardando...' : 'Guardar'}
    </Button>
</div>
```

**Regla**: Usar `gap-3` en mobile (flex-col), `gap-4` en desktop (flex-row).

---

## Formularios

### FormSection Component

**Ubicación**: `resources/js/components/form-section.tsx`

Agrupa campos relacionados con título, icono y descripción.

**Estructura básica**:
```typescript
<FormSection
    icon={Package}
    title="Información Básica"
    description="Datos principales del producto"
>
    <FormField label="Nombre" error={errors.name} required>
        <Input
            value={data.name}
            onChange={(e) => setData('name', e.target.value)}
            placeholder={PLACEHOLDERS.productName}
        />
    </FormField>
</FormSection>
```

**Iconos estandarizados por sección**:
```typescript
import {
    Package,      // Información Básica de productos
    Layers,       // Información Básica de categorías
    Users,        // Información Personal/Usuario
    MapPin,       // Información de Contacto
    Lock,         // Seguridad
    Banknote,     // Precios
    ListChecks,   // Secciones/Opciones
} from 'lucide-react';
```

### FormField Component

**Ubicación**: `resources/js/components/ui/form-field.tsx`

Wrapper para inputs con label, error y descripción.

```typescript
<FormField
    label="Email"
    error={errors.email}
    description={FIELD_DESCRIPTIONS.email}
    required
>
    <Input type="email" {...props} />
</FormField>
```

**Props importantes**:
- `label`: Texto del label
- `error`: Mensaje de error (opcional)
- `description`: Texto de ayuda bajo el input (opcional)
- `required`: Muestra asterisco rojo

### Campos Comunes

#### Input de Texto
```typescript
<FormField label="Nombre" error={errors.name} required>
    <Input
        id="name"
        type="text"
        value={data.name}
        onChange={(e) => setData('name', e.target.value)}
        placeholder={PLACEHOLDERS.categoryName}
        autoComplete={AUTOCOMPLETE.name}
    />
</FormField>
```

#### Textarea
```typescript
<FormField label="Descripción" error={errors.description}>
    <Textarea
        id="description"
        value={data.description}
        onChange={(e) => setData('description', e.target.value)}
        placeholder={PLACEHOLDERS.productDescription}
        rows={2}  // Default: 2-3 filas
    />
</FormField>
```

#### Select
```typescript
<FormField label="Categoría" error={errors.category_id} required>
    <Select
        value={data.category_id}
        onValueChange={(value) => setData('category_id', value)}
    >
        <SelectTrigger>
            <SelectValue placeholder={PLACEHOLDERS.selectCategory} />
        </SelectTrigger>
        <SelectContent>
            {categories.map((category) => (
                <SelectItem key={category.id} value={String(category.id)}>
                    {category.name}
                </SelectItem>
            ))}
        </SelectContent>
    </Select>
</FormField>
```

#### Checkbox
```typescript
<div className="flex items-center space-x-2">
    <Checkbox
        id="is_active"
        checked={data.is_active}
        onCheckedChange={(checked) => setData('is_active', checked as boolean)}
    />
    <Label
        htmlFor="is_active"
        className="text-sm leading-none font-medium cursor-pointer"
    >
        Categoría activa
    </Label>
</div>
```

**Regla**: Siempre usar `cursor-pointer` en el Label para mejor UX.

### Componentes Especializados

#### ImageUpload
**Ubicación**: `resources/js/components/ImageUpload.tsx`

```typescript
<ImageUpload
    label="Imagen del Producto"
    currentImage={data.image}
    onImageChange={(url) => setData('image', url || '')}
    error={errors.image}
/>
```

#### PriceFields
**Ubicación**: `resources/js/components/PriceFields.tsx`

Componente reutilizable para los 4 precios del sistema (capital/interior, pickup/delivery):

```typescript
<PriceFields
    capitalPickup={data.precio_pickup_capital}
    capitalDomicilio={data.precio_domicilio_capital}
    interiorPickup={data.precio_pickup_interior}
    interiorDomicilio={data.precio_domicilio_interior}
    onChangeCapitalPickup={(value) => setData('precio_pickup_capital', value)}
    onChangeCapitalDomicilio={(value) => setData('precio_domicilio_capital', value)}
    onChangeInteriorPickup={(value) => setData('precio_pickup_interior', value)}
    onChangeInteriorDomicilio={(value) => setData('precio_domicilio_interior', value)}
    errors={{
        capitalPickup: errors.precio_pickup_capital,
        capitalDomicilio: errors.precio_domicilio_capital,
        interiorPickup: errors.precio_pickup_interior,
        interiorDomicilio: errors.precio_domicilio_interior,
    }}
/>
```

#### WeekdaySelector
**Ubicación**: `resources/js/components/WeekdaySelector.tsx`

Selector de días de la semana para promociones:

```typescript
<WeekdaySelector
    selectedDays={selectedDays}
    onChange={setSelectedDays}
/>
```

### Drag & Drop en Formularios

Para listas reordenables dentro de formularios (ej: variantes de productos, opciones de secciones):

```typescript
import { DndContext, closestCenter } from '@dnd-kit/core';
import { SortableContext, verticalListSortingStrategy } from '@dnd-kit/sortable';

const sensors = useSensors(
    useSensor(PointerSensor),
    useSensor(KeyboardSensor, {
        coordinateGetter: sortableKeyboardCoordinates,
    })
);

<DndContext
    sensors={sensors}
    collisionDetection={closestCenter}
    onDragEnd={handleDragEnd}
>
    <SortableContext
        items={items.map(v => v.id)}
        strategy={verticalListSortingStrategy}
    >
        {items.map((item) => (
            <SortableItem key={item.id} item={item} />
        ))}
    </SortableContext>
</DndContext>
```

**SortableItem típico**:
```typescript
function SortableItem({ item }) {
    const { attributes, listeners, setNodeRef, transform, transition, isDragging } =
        useSortable({ id: item.id });

    const style = {
        transform: CSS.Transform.toString(transform),
        transition,
        opacity: isDragging ? 0.5 : 1,
    };

    return (
        <div
            ref={setNodeRef}
            style={style}
            className={`border rounded-lg p-4 ${isDragging ? 'shadow-lg bg-muted/50' : ''}`}
        >
            <button
                type="button"
                className="cursor-grab active:cursor-grabbing"
                {...attributes}
                {...listeners}
            >
                <GripVertical className="h-5 w-5" />
            </button>
            {/* Contenido del item */}
        </div>
    );
}
```

---

## Estados y Badges

### StatusBadge Component

**Ubicación**: `resources/js/components/status-badge.tsx`

Componente estandarizado para mostrar estados.

**Uso básico**:
```typescript
<StatusBadge
    status={category.is_active ? 'active' : 'inactive'}
    configs={ACTIVE_STATUS_CONFIGS}
    showIcon={false}
/>
```

### Configuraciones de Estado Predefinidas

#### ACTIVE_STATUS_CONFIGS
Para entidades con estado activo/inactivo:

```typescript
import { ACTIVE_STATUS_CONFIGS } from '@/components/status-badge';

<StatusBadge
    status={item.is_active ? 'active' : 'inactive'}
    configs={ACTIVE_STATUS_CONFIGS}
/>
```

#### CONNECTION_STATUS_CONFIGS
Para estados de conexión de usuarios/clientes:

```typescript
import { CONNECTION_STATUS_CONFIGS } from '@/components/status-badge';

<StatusBadge
    status={customer.status} // 'online' | 'recent' | 'inactive'
    configs={CONNECTION_STATUS_CONFIGS}
/>
```

#### USER_STATUS_CONFIGS
Específico para usuarios:

```typescript
import { USER_STATUS_CONFIGS } from '@/components/status-badge';

<StatusBadge status={user.status} configs={USER_STATUS_CONFIGS} />
```

#### PROMOTION_STATUS_CONFIGS
Para promociones:

```typescript
import { PROMOTION_STATUS_CONFIGS } from '@/components/status-badge';

<StatusBadge
    status={promotion.is_active ? 'active' : 'inactive'}
    configs={PROMOTION_STATUS_CONFIGS}
/>
```

#### CUSTOMER_TYPE_COLORS
Para badges de tipos de cliente con colores:

```typescript
import { CUSTOMER_TYPE_COLORS } from '@/components/status-badge';

const getClientTypeColor = (customerType) => {
    if (customerType?.color && CUSTOMER_TYPE_COLORS[customerType.color]) {
        return CUSTOMER_TYPE_COLORS[customerType.color].color;
    }
    return CUSTOMER_TYPE_COLORS.gray.color;
};

<Badge className={getClientTypeColor(customer.customer_type)}>
    {customer.customer_type?.name}
</Badge>
```

### Colores Disponibles
- `gray` - Default/sin tipo
- `orange` - Alerta/warning
- `yellow` - Precaución
- `purple` - Especial
- `green` - Éxito/activo
- `blue` - Información
- `red` - Error/inactivo
- `slate` - Neutral

### Badge Estándar (shadcn/ui)

Para badges personalizados sin StatusBadge:

```typescript
import { Badge } from '@/components/ui/badge';

// Variant outline con colores custom
<Badge
    variant="outline"
    className="border-green-200 bg-green-50 text-green-700"
>
    Verificado
</Badge>

// Con icono
<Badge variant="outline" className="flex items-center gap-1">
    <Check className="h-3 w-3" />
    Completado
</Badge>
```

**Regla**: Usar `StatusBadge` para estados del sistema, `Badge` para metadata adicional.

---

## Responsividad Mobile

### Mobile Cards (StandardMobileCard)

**Ubicación**: `resources/js/components/StandardMobileCard.tsx`

Todas las tablas DEBEN tener versión mobile usando `StandardMobileCard`.

**Estructura básica**:
```typescript
const renderMobileCard = (customer: Customer) => (
    <StandardMobileCard
        icon={Users}
        title={customer.full_name}
        subtitle={customer.email}
        badge={{
            children: <StatusBadge status={customer.status} configs={CONNECTION_STATUS_CONFIGS} />,
        }}
        dataFields={[
            {
                label: 'Tarjeta Subway',
                value: <code>{customer.subway_card}</code>,
            },
            {
                label: 'Puntos',
                value: <span className="text-blue-600">{customer.points}</span>,
            },
            {
                label: 'Teléfono',
                value: customer.phone,
                condition: !!customer.phone, // Opcional: solo si existe
            },
        ]}
        actions={{
            editHref: `/customers/${customer.id}/edit`,
            onDelete: () => openDeleteDialog(customer),
            isDeleting: deletingCustomer === customer.id,
            editTooltip: 'Editar cliente',
            deleteTooltip: 'Eliminar cliente',
        }}
    />
);
```

**Props del StandardMobileCard**:

```typescript
interface StandardMobileCardProps {
    icon?: LucideIcon;           // Icono de la entidad
    image?: string | null;        // O imagen (ej: productos)
    title: React.ReactNode;       // Título principal
    subtitle: React.ReactNode;    // Subtítulo
    badge?: {                     // Badge de estado (opcional)
        children: React.ReactNode;
        variant?: 'default' | 'secondary' | 'destructive' | 'outline';
    };
    dataFields?: DataFieldConfig[]; // Lista de campos
    actions?: {                    // Acciones (edit/delete)
        editHref?: string;
        onDelete?: () => void;
        isDeleting?: boolean;
        editTooltip?: string;
        deleteTooltip?: string;
    };
    additionalContent?: React.ReactNode;
}

interface DataFieldConfig {
    label: string;
    value: React.ReactNode;
    condition?: boolean;  // Solo mostrar si true
}
```

### Breakpoints

Sistema de breakpoints Tailwind:
```typescript
sm: '640px'   // Tablets pequeñas
md: '768px'   // Tablets
lg: '1024px'  // Laptops
xl: '1280px'  // Desktops
```

**Uso en clases**:
```typescript
// Ocultar en mobile, mostrar en desktop
<div className="hidden md:block">Desktop Only</div>

// Mostrar en mobile, ocultar en desktop
<div className="md:hidden">Mobile Only</div>

// Responsive columns
<div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
```

### Patrones Responsive Comunes

#### Flex Direction
```typescript
// Vertical en mobile, horizontal en desktop
<div className="flex flex-col md:flex-row gap-4">
```

#### Text Size
```typescript
<h1 className="text-2xl md:text-3xl font-bold">
```

#### Padding/Margin
```typescript
<div className="p-4 md:p-6">
```

#### Grid Columns
```typescript
// 1 columna en mobile, 2 en tablet, 3 en desktop
<div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
```

---

## Tipografía y Espaciado

### Jerarquía de Títulos

```typescript
// Page Title (H1)
<h1 className="text-3xl font-bold tracking-tight">Título Principal</h1>

// Section Title (H2) - dentro de FormSection
<h2 className="text-lg font-semibold">Sección</h2>

// Card Title (H3)
<h3 className="text-lg font-semibold">Card Title</h3>

// Subsection (H4)
<h4 className="text-sm font-medium">Subsección</h4>
```

### Text Sizes

```typescript
text-xs    // 12px - Metadata, timestamps
text-sm    // 14px - Body text, descriptions
text-base  // 16px - Default body
text-lg    // 18px - Section titles
text-xl    // 20px - Card titles
text-2xl   // 24px - Page titles mobile
text-3xl   // 30px - Page titles desktop
```

### Font Weights

```typescript
font-normal   // 400 - Body text
font-medium   // 500 - Emphasis, labels
font-semibold // 600 - Titles, headers
font-bold     // 700 - Main titles
```

### Espaciado (Gap/Space)

```typescript
// Stack vertical de elementos
<div className="space-y-4">   // 16px entre elementos

// Stack horizontal
<div className="flex gap-4">  // 16px entre elementos

// Secciones principales
<div className="space-y-6">   // 24px entre secciones
```

**Escala de espaciado**:
```typescript
space-y-1  // 4px   - Items muy relacionados
space-y-2  // 8px   - Items relacionados
space-y-3  // 12px  - Grupos pequeños
space-y-4  // 16px  - Default para forms
space-y-6  // 24px  - Secciones
space-y-8  // 32px  - Separación mayor
```

### Colores de Texto

```typescript
text-foreground        // Texto principal
text-muted-foreground  // Texto secundario/descripción
text-primary           // Enlaces, acciones
text-destructive       // Errores
text-green-600         // Success
text-blue-600          // Info
text-orange-600        // Warning
```

### Truncate Text

```typescript
// Truncar en una línea
<div className="truncate">Long text...</div>

// Truncar en múltiples líneas
<div className="line-clamp-2">Long text...</div>
<div className="line-clamp-3">Long text...</div>
```

---

## Patrones de Navegación

### Botones de Acción Principales

#### Crear
```typescript
import { Plus } from 'lucide-react';

<Link href={createUrl}>
    <Button>
        <Plus className="mr-2 h-4 w-4" />
        Crear
    </Button>
</Link>
```

#### Volver
```typescript
import { ArrowLeft } from 'lucide-react';

<Link href={backHref}>
    <Button variant="outline">
        <ArrowLeft className="mr-2 h-4 w-4" />
        Volver
    </Button>
</Link>
```

#### Guardar
```typescript
import { Save } from 'lucide-react';

<Button type="submit" disabled={processing}>
    {processing ? (
        <Loader className="mr-2 h-4 w-4 animate-spin" />
    ) : (
        <Save className="mr-2 h-4 w-4" />
    )}
    {processing ? 'Guardando...' : 'Guardar'}
</Button>
```

#### Refresh
```typescript
import { RefreshCw } from 'lucide-react';

<Button variant="ghost" size="sm" onClick={refreshData} disabled={isRefreshing}>
    <RefreshCw className={`mr-1 h-4 w-4 ${isRefreshing ? 'animate-spin' : ''}`} />
    {isRefreshing ? 'Sincronizando...' : 'Sincronizar'}
</Button>
```

### TableActions Component

**Ubicación**: `resources/js/components/TableActions.tsx`

Componente estandarizado para acciones de fila (editar/eliminar):

```typescript
<TableActions
    editHref={`/customers/${customer.id}/edit`}
    onDelete={() => openDeleteDialog(customer)}
    isDeleting={deletingCustomer === customer.id}
    editTooltip="Editar cliente"
    deleteTooltip="Eliminar cliente"
    showEdit={true}      // opcional, default true
    showDelete={true}    // opcional, default true
    canDelete={true}     // opcional, default true
/>
```

### DeleteConfirmationDialog

**Ubicación**: `resources/js/components/DeleteConfirmationDialog.tsx`

Dialog de confirmación estandarizado:

```typescript
const [showDeleteDialog, setShowDeleteDialog] = useState(false);
const [selectedEntity, setSelectedEntity] = useState<Entity | null>(null);

const openDeleteDialog = (entity: Entity) => {
    setSelectedEntity(entity);
    setShowDeleteDialog(true);
};

<DeleteConfirmationDialog
    isOpen={showDeleteDialog}
    onClose={closeDeleteDialog}
    onConfirm={handleDelete}
    isDeleting={deletingEntity !== null}
    entityName={selectedEntity?.name || ''}
    entityType="categoría"
/>
```

### Navegación con Inertia

```typescript
import { router } from '@inertiajs/react';

// Navegación simple
router.visit('/customers');

// Con datos
router.post('/customers', formData, {
    onSuccess: () => console.log('Success'),
    onError: (errors) => console.log('Errors', errors),
    preserveState: true,
});

// Delete
router.delete(`/customers/${id}`, {
    onSuccess: () => showNotification.success('Eliminado'),
});

// Reload actual
router.reload();
```

---

## Gestión de Errores

### Manejo de Errores en Forms

#### Con useForm (Create)
```typescript
const { data, setData, post, processing, errors, reset } = useForm({
    name: '',
    is_active: true,
});

const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();

    post(route('menu.categories.store'), {
        onSuccess: () => {
            reset();
        },
        onError: (errors) => {
            if (Object.keys(errors).length === 0) {
                showNotification.error(NOTIFICATIONS.error.server);
            }
        },
    });
};
```

#### Con useState (Edit)
```typescript
const [formData, setFormData] = useState({...});
const [errors, setErrors] = useState<Record<string, string>>({});
const [isSubmitting, setIsSubmitting] = useState(false);

const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setIsSubmitting(true);

    router.put(`/menu/categories/${id}`, formData, {
        onSuccess: () => {
            // Redirección automática del controlador
        },
        onError: (errors) => {
            setErrors(errors as Record<string, string>);
            setIsSubmitting(false);
        },
    });
};
```

### Mostrar Errores en Campos

```typescript
<FormField label="Nombre" error={errors.name} required>
    <Input
        value={data.name}
        onChange={(e) => {
            setData('name', e.target.value);
            // Limpiar error al escribir
            if (errors.name) {
                setErrors(prev => {
                    const newErrors = {...prev};
                    delete newErrors.name;
                    return newErrors;
                });
            }
        }}
    />
</FormField>
```

### Empty States

**Ubicación**: `resources/js/components/EmptyState.tsx`

Para estados vacíos en tablas o listas:

```typescript
import { EmptyState } from '@/components/EmptyState';

// Sin datos
<EmptyState
    variant="no-data"
    title="No hay categorías"
    description="Comienza creando tu primera categoría"
    action={{
        label: "Crear categoría",
        onClick: () => router.visit('/menu/categories/create'),
        icon: <Plus className="h-4 w-4" />
    }}
/>

// Sin resultados de búsqueda
<EmptyState
    variant="no-results"
    description="No encontramos resultados para tu búsqueda"
    secondaryAction={{
        label: "Limpiar filtros",
        onClick: clearFilters
    }}
/>

// Error
<EmptyState
    variant="error"
    title="Error al cargar"
    description="Ocurrió un error al cargar los datos"
    action={{
        label: "Reintentar",
        onClick: () => router.reload()
    }}
/>
```

### Loading Skeletons

Todas las páginas deben tener skeleton loading:

```typescript
// En index pages (DataTable)
loadingSkeleton={CustomersSkeleton}

// En create/edit pages
loadingSkeleton={CreateCategoriesSkeleton}
```

**Ubicación de skeletons**: `resources/js/components/skeletons.tsx`

---

## Checklist de Nuevas Páginas

### Index Page (Listado)

- [ ] Determinar tipo de tabla: DataTable, SortableTable, o GroupedSortableTable u otro.
- [ ] Implementar columnas con `width`, `sortable`, `textAlign`
- [ ] Configurar stats con iconos y colores semánticos
- [ ] Crear `renderMobileCard` con `StandardMobileCard`
- [ ] Configurar breakpoint apropiado
- [ ] Implementar `DeleteConfirmationDialog`
- [ ] Definir `searchPlaceholder` en `PLACEHOLDERS`
- [ ] Agregar loading skeleton
- [ ] Usar `showNotification` para feedback
- [ ] Verificar mobile responsiveness

### Create Page

- [ ] Usar `CreatePageLayout`
- [ ] Usar `useForm` de Inertia
- [ ] Organizar campos en `FormSection` con iconos
- [ ] Usar `PLACEHOLDERS` para todos los inputs
- [ ] Usar `AUTOCOMPLETE` cuando aplique
- [ ] Implementar validación con `errors`
- [ ] Agregar `loadingSkeleton`
- [ ] Configurar `onError` para mostrar notificaciones
- [ ] Verificar mobile responsiveness

### Edit Page

- [ ] Usar `EditPageLayout`
- [ ] Usar `useState` para formData y errors
- [ ] Implementar `handleInputChange` que limpia errores
- [ ] Opcional: tracking de cambios con `isDirty`
- [ ] Mismo patrón de `FormSection` que Create
- [ ] Agregar `loadingSkeleton`
- [ ] Verificar mobile responsiveness

### Común a Todas

- [ ] Usar constantes de `ui-constants.ts`
- [ ] NO hardcodear strings
- [ ] Usar iconos de Lucide React
- [ ] Implementar dark mode support
- [ ] Verificar accesibilidad (tooltips, labels)
- [ ] Testing en mobile y desktop
- [ ] Verificar estado de loading
- [ ] Verificar estado vacío

---

## Errores Comunes

### ❌ Texto Hardcodeado

**MAL**:
```typescript
<Input placeholder="Ingresa el nombre de la categoría" />
```

**BIEN**:
```typescript
import { PLACEHOLDERS } from '@/constants/ui-constants';
<Input placeholder={PLACEHOLDERS.categoryName} />
```

### ❌ Notificaciones Inconsistentes

**MAL**:
```typescript
showNotification.success('Se guardó correctamente');
```

**BIEN**:
```typescript
import { NOTIFICATIONS } from '@/constants/ui-constants';
showNotification.success(NOTIFICATIONS.success.created);
```

### ❌ No Implementar Mobile Card

**MAL**:
```typescript
<DataTable
    data={customers}
    columns={columns}
    // Sin renderMobileCard
/>
```

**BIEN**:
```typescript
<DataTable
    data={customers}
    columns={columns}
    renderMobileCard={(customer) => (
        <StandardMobileCard {...customer} />
    )}
/>
```

### ❌ Iconos de Tamaño Incorrecto

**MAL**:
```typescript
<Plus className="h-6 w-6" />  // Muy grande para botón
```

**BIEN**:
```typescript
<Plus className="h-4 w-4" />  // Tamaño estándar
```

### ❌ Stats sin Lowercase

**MAL**:
```typescript
const stats = [
    { title: 'Total Usuarios', value: 100 }
];
```

**BIEN**:
```typescript
const stats = [
    { title: 'usuarios', value: 100 }
];
```

### ❌ No Usar FormSection

**MAL**:
```typescript
<h2>Información Básica</h2>
<FormField label="Nombre">
```

**BIEN**:
```typescript
<FormSection icon={Package} title="Información Básica">
    <FormField label="Nombre">
```

### ❌ Botones sin Estados de Loading

**MAL**:
```typescript
<Button type="submit">Guardar</Button>
```

**BIEN**:
```typescript
<Button type="submit" disabled={processing}>
    {processing ? 'Guardando...' : 'Guardar'}
</Button>
```

### ❌ No Limpiar Errores al Editar

**MAL**:
```typescript
<Input
    value={data.name}
    onChange={(e) => setData('name', e.target.value)}
/>
```

**BIEN**:
```typescript
<Input
    value={data.name}
    onChange={(e) => {
        setData('name', e.target.value);
        if (errors.name) {
            setErrors(prev => {
                const newErrors = {...prev};
                delete newErrors.name;
                return newErrors;
            });
        }
    }}
/>
```

### ❌ StatusBadge sin Configuración

**MAL**:
```typescript
<StatusBadge status="active" />  // Sin configs
```

**BIEN**:
```typescript
<StatusBadge
    status="active"
    configs={ACTIVE_STATUS_CONFIGS}
/>
```

---

## Ejemplos Completos

### Ejemplo: Index Page Simple (SortableTable)

```typescript
import { showNotification } from '@/hooks/useNotifications';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';

import { DeleteConfirmationDialog } from '@/components/DeleteConfirmationDialog';
import { SortableTable } from '@/components/SortableTable';
import { StandardMobileCard } from '@/components/StandardMobileCard';
import { TableActions } from '@/components/TableActions';
import { ACTIVE_STATUS_CONFIGS, StatusBadge } from '@/components/status-badge';
import AppLayout from '@/layouts/app-layout';
import { Layers, Package, Star } from 'lucide-react';

interface Category {
    id: number;
    name: string;
    is_active: boolean;
    sort_order: number;
}

interface CategoriesPageProps {
    categories: Category[];
    stats: {
        total_categories: number;
        active_categories: number;
    };
}

export default function CategoriesIndex({ categories, stats }: CategoriesPageProps) {
    const [deletingCategory, setDeletingCategory] = useState<number | null>(null);
    const [selectedCategory, setSelectedCategory] = useState<Category | null>(null);
    const [showDeleteDialog, setShowDeleteDialog] = useState(false);
    const [isSaving, setIsSaving] = useState(false);

    const handleReorder = (reorderedCategories: Category[]) => {
        setIsSaving(true);
        const orderData = reorderedCategories.map((category, index) => ({
            id: category.id,
            sort_order: index + 1,
        }));

        router.post(route('menu.categories.reorder'), { categories: orderData }, {
            preserveState: true,
            onSuccess: () => showNotification.success('Orden guardado correctamente'),
            onError: (error) => {
                if (error.message) showNotification.error(error.message);
            },
            onFinish: () => setIsSaving(false),
        });
    };

    const openDeleteDialog = (category: Category) => {
        setSelectedCategory(category);
        setShowDeleteDialog(true);
    };

    const handleDeleteCategory = () => {
        if (!selectedCategory) return;

        setDeletingCategory(selectedCategory.id);
        router.delete(`/menu/categories/${selectedCategory.id}`, {
            onSuccess: () => {
                setShowDeleteDialog(false);
                setSelectedCategory(null);
                setDeletingCategory(null);
            },
        });
    };

    const categoryStats = [
        {
            title: 'categorías',
            value: stats.total_categories,
            icon: <Layers className="h-4 w-4 text-primary" />,
        },
        {
            title: 'activas',
            value: stats.active_categories,
            icon: <Star className="h-4 w-4 text-green-600" />,
        },
    ];

    const columns = [
        {
            key: 'name',
            title: 'Categoría',
            width: 'flex-1',
            render: (category: Category) => (
                <div className="text-sm font-medium">{category.name}</div>
            ),
        },
        {
            key: 'status',
            title: 'Estado',
            width: 'w-32',
            textAlign: 'center' as const,
            render: (category: Category) => (
                <StatusBadge
                    status={category.is_active ? 'active' : 'inactive'}
                    configs={ACTIVE_STATUS_CONFIGS}
                    showIcon={false}
                />
            ),
        },
        {
            key: 'actions',
            title: 'Acciones',
            width: 'w-24',
            textAlign: 'right' as const,
            render: (category: Category) => (
                <TableActions
                    editHref={`/menu/categories/${category.id}/edit`}
                    onDelete={() => openDeleteDialog(category)}
                    isDeleting={deletingCategory === category.id}
                />
            ),
        },
    ];

    const renderMobileCard = (category: Category) => (
        <StandardMobileCard
            title={category.name}
            badge={{
                children: <StatusBadge
                    status={category.is_active ? 'active' : 'inactive'}
                    configs={ACTIVE_STATUS_CONFIGS}
                    showIcon={false}
                />,
            }}
            actions={{
                editHref: `/menu/categories/${category.id}/edit`,
                onDelete: () => openDeleteDialog(category),
                isDeleting: deletingCategory === category.id,
            }}
        />
    );

    return (
        <AppLayout>
            <Head title="Categorías" />

            <SortableTable
                title="Categorías de Menú"
                description="Administra las categorías del menú"
                data={categories}
                columns={columns}
                stats={categoryStats}
                createUrl="/menu/categories/create"
                createLabel="Crear"
                searchable={true}
                searchPlaceholder="Buscar categorías..."
                onReorder={handleReorder}
                onRefresh={() => router.reload()}
                isSaving={isSaving}
                renderMobileCard={renderMobileCard}
                breakpoint="lg"
            />

            <DeleteConfirmationDialog
                isOpen={showDeleteDialog}
                onClose={() => setShowDeleteDialog(false)}
                onConfirm={handleDeleteCategory}
                isDeleting={deletingCategory !== null}
                entityName={selectedCategory?.name || ''}
                entityType="categoría"
            />
        </AppLayout>
    );
}
```

### Ejemplo: Create Page

```typescript
import { showNotification } from '@/hooks/useNotifications';
import { useForm } from '@inertiajs/react';
import React from 'react';
import { PLACEHOLDERS, NOTIFICATIONS } from '@/constants/ui-constants';

import { CreatePageLayout } from '@/components/create-page-layout';
import { FormSection } from '@/components/form-section';
import { CreateCategoriesSkeleton } from '@/components/skeletons';
import { Checkbox } from '@/components/ui/checkbox';
import { FormField } from '@/components/ui/form-field';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Layers } from 'lucide-react';

export default function CategoryCreate() {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        is_active: true,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        post(route('menu.categories.store'), {
            onSuccess: () => {
                reset();
            },
            onError: (errors) => {
                if (Object.keys(errors).length === 0) {
                    showNotification.error(NOTIFICATIONS.error.server);
                }
            },
        });
    };

    return (
        <CreatePageLayout
            title="Nueva Categoría"
            backHref={route('menu.categories.index')}
            backLabel="Volver"
            onSubmit={handleSubmit}
            submitLabel="Guardar"
            processing={processing}
            pageTitle="Crear Categoría"
            loading={processing}
            loadingSkeleton={CreateCategoriesSkeleton}
        >
            <FormSection
                icon={Layers}
                title="Información Básica"
                description="Datos principales de la categoría"
            >
                <FormField label="Nombre" error={errors.name} required>
                    <Input
                        id="name"
                        type="text"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        placeholder={PLACEHOLDERS.categoryName}
                    />
                </FormField>

                <div className="flex items-center space-x-2">
                    <Checkbox
                        id="is_active"
                        checked={data.is_active}
                        onCheckedChange={(checked) => setData('is_active', checked as boolean)}
                    />
                    <Label
                        htmlFor="is_active"
                        className="text-sm leading-none font-medium cursor-pointer"
                    >
                        Categoría activa
                    </Label>
                </div>
            </FormSection>
        </CreatePageLayout>
    );
}
```

---

## Resumen Final

Esta guía documenta **TODOS** los patrones establecidos en la aplicación. Al crear una nueva página:

1. **Revisa esta guía** antes de empezar
2. **Busca páginas similares** en el codebase
3. **Reutiliza componentes** existentes
4. **Usa constantes** de `ui-constants.ts`
5. **Implementa mobile** con `StandardMobileCard`
6. **Prueba en mobile y desktop**
7. **Verifica dark mode**

**El objetivo es mantener una experiencia de usuario consistente, predecible y de alta calidad en toda la aplicación.**
