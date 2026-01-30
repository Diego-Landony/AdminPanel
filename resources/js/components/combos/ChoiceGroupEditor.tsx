import { ProductCombobox } from '@/components/ProductCombobox';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { FormField } from '@/components/ui/form-field';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { COMBO_LABELS, PLACEHOLDERS } from '@/constants/ui-constants';
import { closestCenter, DndContext, DragEndEvent, KeyboardSensor, PointerSensor, useSensor, useSensors } from '@dnd-kit/core';
import { arrayMove, SortableContext, sortableKeyboardCoordinates, verticalListSortingStrategy } from '@dnd-kit/sortable';
import { AlertCircle } from 'lucide-react';
import { useState } from 'react';
import { SortableChoiceOption } from './SortableChoiceOption';

interface Product {
    id: number;
    name: string;
    has_variants: boolean;
    is_active: boolean;
    variants?: ProductVariant[];
    category?: {
        id: number;
        name: string;
    };
}

interface ProductVariant {
    id: number;
    name: string;
    size: string;
}

interface ChoiceOption {
    id: string;
    product_id: number;
    variant_id?: number | null;
    sort_order: number;
}

interface ChoiceGroupEditorProps {
    label: string;
    options: ChoiceOption[];
    onLabelChange: (label: string) => void;
    onOptionsChange: (options: ChoiceOption[]) => void;
    products: Product[];
    errors?: {
        choice_label?: string;
        options?: string;
    };
}

export function ChoiceGroupEditor({ label, options, onLabelChange, onOptionsChange, products, errors = {} }: ChoiceGroupEditorProps) {
    const [selectedProductId, setSelectedProductId] = useState<number | null>(null);
    const [selectedVariantId, setSelectedVariantId] = useState<number | null>(null);

    const sensors = useSensors(
        useSensor(PointerSensor),
        useSensor(KeyboardSensor, {
            coordinateGetter: sortableKeyboardCoordinates,
        }),
    );

    // Productos disponibles (excluir los ya agregados)
    const availableProducts = products.filter((product) => {
        if (!product.is_active) return false;

        // Si el producto tiene variantes, verificar si todas están usadas
        if (product.has_variants && product.variants) {
            const usedVariantIds = options
                .filter((opt) => opt.product_id === product.id)
                .map((opt) => opt.variant_id);
            // Mostrar si hay al menos una variante no usada
            return product.variants.some((v) => !usedVariantIds.includes(v.id));
        }

        // Si no tiene variantes, verificar si ya está agregado
        return !options.some((opt) => opt.product_id === product.id && !opt.variant_id);
    });

    const selectedProduct = products.find((p) => p.id === selectedProductId);
    const hasVariants = selectedProduct?.has_variants && selectedProduct?.variants && selectedProduct.variants.length > 0;

    // Variantes disponibles del producto seleccionado
    const availableVariants = hasVariants
        ? selectedProduct.variants?.filter((v) => {
              return !options.some((opt) => opt.product_id === selectedProductId && opt.variant_id === v.id);
          })
        : [];

    const handleProductSelect = (productId: number | null) => {
        setSelectedProductId(productId);
        setSelectedVariantId(null);

        // Si el producto no tiene variantes, agregarlo directamente
        if (productId) {
            const product = products.find((p) => p.id === productId);
            if (product && !product.has_variants) {
                addOption(productId, null);
                setSelectedProductId(null);
            }
        }
    };

    const handleVariantSelect = (variantId: string) => {
        if (selectedProductId && variantId) {
            addOption(selectedProductId, Number(variantId));
            setSelectedProductId(null);
            setSelectedVariantId(null);
        }
    };

    const addOption = (productId: number, variantId: number | null) => {
        const newOption: ChoiceOption = {
            id: `option-${Date.now()}-${Math.random()}`,
            product_id: productId,
            variant_id: variantId,
            sort_order: options.length + 1,
        };
        onOptionsChange([...options, newOption]);
    };

    const handleRemoveOption = (optionId: string) => {
        const updated = options.filter((opt) => opt.id !== optionId);
        onOptionsChange(
            updated.map((opt, index) => ({
                ...opt,
                sort_order: index + 1,
            })),
        );
    };

    const handleDragEnd = (event: DragEndEvent) => {
        const { active, over } = event;

        if (over && active.id !== over.id) {
            const oldIndex = options.findIndex((opt) => opt.id === active.id);
            const newIndex = options.findIndex((opt) => opt.id === over.id);

            const reordered = arrayMove(options, oldIndex, newIndex);
            onOptionsChange(
                reordered.map((opt, index) => ({
                    ...opt,
                    sort_order: index + 1,
                })),
            );
        }
    };

    return (
        <div className="space-y-4">
            <FormField label="Etiqueta del grupo" error={errors.choice_label} required>
                <Input value={label} onChange={(e) => onLabelChange(e.target.value)} placeholder={PLACEHOLDERS.choiceGroupLabel} />
                <p className="mt-1 text-xs text-muted-foreground">Esta etiqueta la verá el cliente al hacer su pedido</p>
            </FormField>

            <div className="space-y-3">
                <div className="flex items-center justify-between">
                    <Label>Opciones disponibles</Label>
                    <span className="text-xs text-muted-foreground">Mínimo 2 opciones</span>
                </div>

                {errors.options && (
                    <Alert variant="destructive">
                        <AlertCircle className="h-4 w-4" />
                        <AlertDescription>{errors.options}</AlertDescription>
                    </Alert>
                )}

                {options.length > 0 && (
                    <DndContext sensors={sensors} collisionDetection={closestCenter} onDragEnd={handleDragEnd}>
                        <SortableContext items={options.map((opt) => opt.id)} strategy={verticalListSortingStrategy}>
                            <div className="space-y-2">
                                {options.map((option) => (
                                    <SortableChoiceOption
                                        key={option.id}
                                        option={option}
                                        products={products}
                                        onRemove={() => handleRemoveOption(option.id)}
                                        canDelete={true}
                                    />
                                ))}
                            </div>
                        </SortableContext>
                    </DndContext>
                )}

                {/* Selector inline de productos */}
                <div className="rounded-lg border border-dashed border-muted-foreground/25 p-4">
                    <p className="mb-3 text-sm font-medium">{COMBO_LABELS.addOption}</p>
                    <div className="space-y-3">
                        <ProductCombobox
                            value={selectedProductId}
                            onChange={handleProductSelect}
                            products={availableProducts}
                            label=""
                            placeholder="Buscar producto..."
                        />

                        {hasVariants && availableVariants && availableVariants.length > 0 && (
                            <Select value={selectedVariantId ? String(selectedVariantId) : ''} onValueChange={handleVariantSelect}>
                                <SelectTrigger>
                                    <SelectValue placeholder={PLACEHOLDERS.selectVariant} />
                                </SelectTrigger>
                                <SelectContent>
                                    {availableVariants.map((variant) => (
                                        <SelectItem key={variant.id} value={String(variant.id)}>
                                            {variant.name} {variant.size && `- ${variant.size}`}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        )}

                        {availableProducts.length === 0 && (
                            <p className="text-center text-sm text-muted-foreground">No hay más productos disponibles</p>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}
