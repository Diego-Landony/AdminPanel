/**
 * Componente reutilizable para campos de formulario de combos
 * Usado tanto en create como en edit
 */

import { closestCenter, DndContext, DragEndEvent, useSensors } from '@dnd-kit/core';
import { SortableContext, verticalListSortingStrategy } from '@dnd-kit/sortable';
import { useMemo, useState } from 'react';

import { CategoryCombobox } from '@/components/CategoryCombobox';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { ComboItemCard } from '@/components/combos/ComboItemCard';
import { FormSection } from '@/components/form-section';
import { ImageCropperUpload } from '@/components/ImageCropperUpload';
import { PriceFields } from '@/components/PriceFields';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { FormField } from '@/components/ui/form-field';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { COMBO_LABELS, CURRENCY } from '@/constants/ui-constants';
import { AlertCircle, Banknote, ChevronDown, ChevronUp, Eye, Gift, Layers, Package, Package2, Plus } from 'lucide-react';

import type { Category, Product, FormErrors, LocalComboItem } from '@/types/menu';
import type { ComboFormData, InactiveProductInfo } from '@/hooks/useComboForm';

export interface ComboFormFieldsProps {
    formData: ComboFormData;
    onInputChange: (field: keyof ComboFormData, value: string | boolean) => void;
    categories: Category[];
    products: Product[];
    imagePreview: string | null;
    onImageChange: (file: File | null, preview: string | null) => void;
    localItems: LocalComboItem[];
    onAddItem: (isChoiceGroup?: boolean) => void;
    onRemoveItem: (index: number) => void;
    onUpdateItem: (index: number, field: string, value: unknown) => void;
    onBatchUpdateItem: (index: number, updates: Partial<LocalComboItem>) => void;
    onDragEnd: (event: DragEndEvent) => void;
    sensors: ReturnType<typeof useSensors>;
    errors: FormErrors;
    inactiveItems?: InactiveProductInfo[];
    canDeleteItem: (itemsLength: number) => boolean;
    mode: 'create' | 'edit';
}

function ComboPreviewPanel({
    formData,
    localItems,
    products,
    categories,
}: {
    formData: ComboFormData;
    localItems: LocalComboItem[];
    products: Product[];
    categories: Category[];
}) {
    const [isOpen, setIsOpen] = useState(false);

    const selectedCategory = useMemo(() => {
        if (!formData.category_id) return null;
        return categories.find((c) => c.id === Number(formData.category_id));
    }, [formData.category_id, categories]);

    const basePrice = useMemo(() => {
        const price = parseFloat(formData.precio_pickup_capital);
        return isNaN(price) ? 0 : price;
    }, [formData.precio_pickup_capital]);

    const getProductName = (productId: number | null) => {
        if (!productId) return null;
        const product = products.find((p) => p.id === productId);
        return product?.name || null;
    };

    const itemsSummary = useMemo(() => {
        return localItems.map((item) => {
            if (item.is_choice_group) {
                const optionNames = item.options
                    .map((opt) => getProductName(opt.product_id))
                    .filter(Boolean);
                return {
                    type: 'choice' as const,
                    label: item.choice_label || COMBO_LABELS.itemTypes.choiceGroup,
                    options: optionNames,
                    quantity: item.quantity,
                };
            } else {
                return {
                    type: 'fixed' as const,
                    label: getProductName(item.product_id) || COMBO_LABELS.itemTypes.fixed,
                    options: [],
                    quantity: item.quantity,
                };
            }
        });
    }, [localItems, products]);

    return (
        <Card>
            <Collapsible open={isOpen} onOpenChange={setIsOpen}>
                <CardContent className="pt-6">
                    <CollapsibleTrigger asChild>
                        <button
                            type="button"
                            className="flex w-full items-center justify-between text-left"
                        >
                            <div className="flex items-center gap-2">
                                <Eye className="h-4 w-4 text-muted-foreground" />
                                <h3 className="text-sm font-medium">Vista previa del combo</h3>
                            </div>
                            {isOpen ? (
                                <ChevronUp className="h-4 w-4 text-muted-foreground" />
                            ) : (
                                <ChevronDown className="h-4 w-4 text-muted-foreground" />
                            )}
                        </button>
                    </CollapsibleTrigger>

                    <CollapsibleContent className="pt-4">
                        <div className="rounded-lg border bg-muted/30 p-4">
                            {/* Nombre y categoría */}
                            <div className="mb-4 border-b pb-4">
                                <h4 className="text-lg font-semibold">
                                    {formData.name || 'Sin nombre'}
                                </h4>
                                {selectedCategory && (
                                    <p className="text-sm text-muted-foreground">
                                        {selectedCategory.name}
                                    </p>
                                )}
                                {formData.description && (
                                    <p className="mt-2 text-sm text-muted-foreground">
                                        {formData.description}
                                    </p>
                                )}
                            </div>

                            {/* Items del combo */}
                            <div className="mb-4 space-y-3">
                                <h5 className="text-sm font-medium">Contenido del combo:</h5>
                                {itemsSummary.length > 0 ? (
                                    <ul className="space-y-2">
                                        {itemsSummary.map((item, index) => (
                                            <li
                                                key={index}
                                                className="rounded-md bg-background p-2 text-sm"
                                            >
                                                <div className="flex items-start gap-2">
                                                    <span className="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-primary/10 text-xs font-medium text-primary">
                                                        {item.quantity}
                                                    </span>
                                                    <div className="flex-1">
                                                        <span className="font-medium">{item.label}</span>
                                                        {item.type === 'choice' && item.options.length > 0 && (
                                                            <p className="mt-1 text-xs text-muted-foreground">
                                                                Opciones: {item.options.join(', ')}
                                                            </p>
                                                        )}
                                                    </div>
                                                </div>
                                            </li>
                                        ))}
                                    </ul>
                                ) : (
                                    <p className="text-sm text-muted-foreground">
                                        No hay items agregados
                                    </p>
                                )}
                            </div>

                            {/* Precio base */}
                            <div className="flex items-center justify-between border-t pt-4">
                                <span className="text-sm font-medium">Precio base (Pickup Capital):</span>
                                <span className="text-lg font-bold text-primary">
                                    {CURRENCY.symbol}
                                    {basePrice.toFixed(2)}
                                </span>
                            </div>
                        </div>
                    </CollapsibleContent>
                </CardContent>
            </Collapsible>
        </Card>
    );
}

export function ComboFormFields({
    formData,
    onInputChange,
    categories,
    products,
    imagePreview,
    onImageChange,
    localItems,
    onAddItem,
    onRemoveItem,
    onUpdateItem,
    onBatchUpdateItem,
    onDragEnd,
    sensors,
    errors,
    inactiveItems = [],
    canDeleteItem,
    mode,
}: ComboFormFieldsProps) {
    const hasInactiveProducts = inactiveItems.length > 0;

    return (
        <div className="space-y-8">
            {/* Alerta de productos inactivos - versión detallada para edit */}
            {mode === 'edit' && hasInactiveProducts && (
                <div className="rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-950/20">
                    <div className="flex gap-3">
                        <AlertCircle className="h-5 w-5 text-amber-800 dark:text-amber-200" />
                        <div className="flex-1">
                            <h3 className="font-semibold text-amber-800 dark:text-amber-200">
                                Productos Inactivos Detectados
                            </h3>
                            <p className="mt-1 text-sm text-amber-800 dark:text-amber-200">
                                Este combo tiene productos inactivos que no estarán disponibles para los clientes:
                            </p>
                            <ul className="mt-2 list-inside list-disc space-y-1 text-sm text-amber-800 dark:text-amber-200">
                                {inactiveItems.map((item, index) => (
                                    <li key={index}>
                                        <span className="font-medium">{item.productName}</span>
                                        {item.type === 'choice' && item.groupLabel && (
                                            <>
                                                {' en '}
                                                <span className="opacity-80">{item.groupLabel}</span>
                                            </>
                                        )}
                                        {item.type === 'fixed' && <span className="opacity-80"> (Item fijo)</span>}
                                    </li>
                                ))}
                            </ul>
                        </div>
                    </div>
                </div>
            )}

            {/* Alerta simple para create */}
            {mode === 'create' && hasInactiveProducts && (
                <div className="rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-950/20">
                    <p className="text-sm font-medium text-amber-800 dark:text-amber-200">
                        Advertencia: Este combo tiene productos inactivos seleccionados. El combo no estará
                        disponible para los clientes hasta que se activen todos los productos.
                    </p>
                </div>
            )}

            <Card>
                <CardContent className="pt-6">
                    <FormSection icon={Package2} title="Información Básica">
                        <div className="space-y-6">
                            <div className="flex items-center justify-between rounded-lg border p-4">
                                <Label htmlFor="is_active" className="cursor-pointer text-sm font-medium">
                                    Combo activo
                                </Label>
                                <Switch
                                    id="is_active"
                                    checked={formData.is_active}
                                    onCheckedChange={(checked) => onInputChange('is_active', checked as boolean)}
                                />
                            </div>

                            <CategoryCombobox
                                value={formData.category_id ? Number(formData.category_id) : null}
                                onChange={(value) => onInputChange('category_id', value ? String(value) : '')}
                                categories={categories}
                                label="Categoría"
                                error={errors.category_id}
                                required
                            />

                            <FormField label="Nombre" error={errors.name} required>
                                <Input
                                    id="name"
                                    type="text"
                                    value={formData.name}
                                    onChange={(e) => onInputChange('name', e.target.value)}
                                />
                            </FormField>

                            <FormField label="Descripción" error={errors.description}>
                                <Textarea
                                    id="description"
                                    value={formData.description}
                                    onChange={(e) => onInputChange('description', e.target.value)}
                                    rows={2}
                                />
                            </FormField>

                            <ImageCropperUpload
                                label="Imagen del Combo"
                                currentImage={imagePreview}
                                onImageChange={(file) => onImageChange(file, null)}
                                error={errors.image}
                                aspectRatio={5 / 3}
                                aspectLabel="5:3"
                            />
                        </div>
                    </FormSection>
                </CardContent>
            </Card>

            <Card>
                <CardContent className="pt-6">
                    <FormSection icon={Banknote} title="Precios del Combo">
                        <PriceFields
                            capitalPickup={formData.precio_pickup_capital}
                            capitalDomicilio={formData.precio_domicilio_capital}
                            interiorPickup={formData.precio_pickup_interior}
                            interiorDomicilio={formData.precio_domicilio_interior}
                            onChangeCapitalPickup={(value) => onInputChange('precio_pickup_capital', value)}
                            onChangeCapitalDomicilio={(value) => onInputChange('precio_domicilio_capital', value)}
                            onChangeInteriorPickup={(value) => onInputChange('precio_pickup_interior', value)}
                            onChangeInteriorDomicilio={(value) => onInputChange('precio_domicilio_interior', value)}
                            errors={{
                                capitalPickup: errors.precio_pickup_capital,
                                capitalDomicilio: errors.precio_domicilio_capital,
                                interiorPickup: errors.precio_pickup_interior,
                                interiorDomicilio: errors.precio_domicilio_interior,
                            }}
                        />
                    </FormSection>
                </CardContent>
            </Card>

            <Card>
                <CardContent className="pt-6">
                    <FormSection icon={Gift} title="Recompensas">
                        <div className="space-y-6">
                            <div className="flex items-center justify-between rounded-lg border p-4">
                                <Label htmlFor="is_redeemable" className="cursor-pointer text-sm font-medium">
                                    Canjeable por puntos
                                </Label>
                                <Switch
                                    id="is_redeemable"
                                    checked={formData.is_redeemable}
                                    onCheckedChange={(checked) => onInputChange('is_redeemable', checked as boolean)}
                                />
                            </div>

                            {formData.is_redeemable && (
                                <FormField label="Costo en puntos" error={errors.points_cost} required>
                                    <Input
                                        id="points_cost"
                                        type="number"
                                        min="1"
                                        step="1"
                                        value={formData.points_cost}
                                        onChange={(e) => onInputChange('points_cost', e.target.value)}
                                    />
                                </FormField>
                            )}
                        </div>
                    </FormSection>
                </CardContent>
            </Card>

            <Card>
                <CardContent className="pt-6">
                    <FormSection icon={Package} title="Items del Combo">
                        <div className="space-y-4">
                            {mode === 'create' && (
                                <div className="flex items-center justify-between rounded-lg border border-muted bg-muted/50 px-4 py-2">
                                    <p className="text-sm text-muted-foreground">Un combo debe tener al menos 2 items</p>
                                    <span className="text-xs font-medium text-muted-foreground">Actual: {localItems.length}</span>
                                </div>
                            )}

                            {localItems.length > 0 ? (
                                <DndContext sensors={sensors} collisionDetection={closestCenter} onDragEnd={onDragEnd}>
                                    <SortableContext items={localItems.map((item) => item.id)} strategy={verticalListSortingStrategy}>
                                        <div className="space-y-4">
                                            {localItems.map((item, index) => (
                                                <ComboItemCard
                                                    key={item.id}
                                                    item={item}
                                                    index={index}
                                                    products={products}
                                                    onUpdate={(field, value) => onUpdateItem(index, field, value)}
                                                    onBatchUpdate={(updates) => onBatchUpdateItem(index, updates)}
                                                    onRemove={() => onRemoveItem(index)}
                                                    errors={errors}
                                                    canDelete={canDeleteItem(localItems.length)}
                                                />
                                            ))}
                                        </div>
                                    </SortableContext>
                                </DndContext>
                            ) : (
                                <div className="rounded-lg border border-dashed border-muted-foreground/25 p-8 text-center">
                                    <p className="text-sm text-muted-foreground">No hay items en el combo</p>
                                    <p className="mt-1 text-xs text-muted-foreground">
                                        Agrega al menos 2 items para crear el combo
                                    </p>
                                </div>
                            )}

                            {/* Botones separados para agregar items */}
                            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => onAddItem(false)}
                                    className="w-full"
                                >
                                    <Plus className="mr-2 h-4 w-4" />
                                    {COMBO_LABELS.addFixedItem}
                                </Button>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => onAddItem(true)}
                                    className="w-full"
                                >
                                    <Layers className="mr-2 h-4 w-4" />
                                    {COMBO_LABELS.addChoiceGroup}
                                </Button>
                            </div>

                            {errors.items && <p className="mt-2 text-sm text-destructive">{errors.items}</p>}
                        </div>
                    </FormSection>
                </CardContent>
            </Card>

            {/* Vista previa del combo */}
            <ComboPreviewPanel
                formData={formData}
                localItems={localItems}
                products={products}
                categories={categories}
            />
        </div>
    );
}
