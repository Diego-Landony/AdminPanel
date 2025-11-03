import { PLACEHOLDERS } from '@/constants/ui-constants';
import { Trash2 } from 'lucide-react';
import React, { useMemo } from 'react';

import { CategoryCombobox } from '@/components/CategoryCombobox';
import { ComboCheckboxList } from '@/components/promotions/ComboCheckboxList';
import { ProductCheckboxList } from '@/components/promotions/ProductCheckboxList';
import { VariantSelector } from '@/components/promotions/VariantSelector';
import { Button } from '@/components/ui/button';
import { FormField } from '@/components/ui/form-field';
import { Input } from '@/components/ui/input';

interface ProductVariant {
    id: number;
    name: string;
    size: string;
    precio_pickup_capital: number;
    precio_pickup_interior: number;
}

interface Product {
    id: number;
    name: string;
    category_id: number;
    has_variants: boolean;
    is_active: boolean;
    category?: {
        id: number;
        name: string;
    };
    variants?: ProductVariant[];
}

interface Category {
    id: number;
    name: string;
    is_combo_category?: boolean;
}

interface Combo {
    id: number;
    name: string;
    category_id: number;
    is_active: boolean;
    category?: {
        id: number;
        name: string;
    };
}

interface LocalPromotionItem {
    id: string;
    category_id: number | null;
    variant_id: number | null;
    selected_product_ids: number[];
    selected_combo_ids?: number[];
    discount_percentage: string;
}

export interface PromotionItemEditorProps {
    item: LocalPromotionItem;
    index: number;
    categories: Category[];
    products: Product[];
    combos?: Combo[];
    onUpdate: (id: string, field: keyof LocalPromotionItem, value: number | number[] | string | null) => void;
    onRemove: (id: string) => void;
    canRemove: boolean;
    getItemError: (index: number, field: string) => string | undefined;
    excludedVariantIds?: number[];
    showDiscount?: boolean;
}

function PromotionItemEditorComponent({
    item,
    index,
    categories,
    products,
    combos = [],
    onUpdate,
    onRemove,
    canRemove,
    getItemError,
    excludedVariantIds = [],
    showDiscount = true,
}: PromotionItemEditorProps) {
    const categoryProducts = useMemo(() => {
        if (!item.category_id) return [];
        return products.filter((p) => p.category_id === item.category_id);
    }, [products, item.category_id]);

    const selectedCategory = useMemo(() => {
        return categories.find((c) => c.id === item.category_id);
    }, [categories, item.category_id]);

    const categoryHasVariants = useMemo(() => {
        // Si es categoría de combos, no tiene variantes
        if (selectedCategory?.is_combo_category) {
            return false;
        }
        return categoryProducts.some((p) => p.variants && p.variants.length > 0);
    }, [categoryProducts, selectedCategory]);

    const availableVariants = useMemo(() => {
        if (!item.category_id || !categoryHasVariants) return [];

        const variantsMap = new Map<string, ProductVariant>();

        categoryProducts.forEach((product) => {
            product.variants?.forEach((variant) => {
                const key = `${variant.name}-${variant.size}`;
                if (!variantsMap.has(key)) {
                    variantsMap.set(key, variant);
                }
            });
        });

        const allVariants = Array.from(variantsMap.values());

        return allVariants.filter((variant) => !excludedVariantIds.includes(variant.id) || variant.id === item.variant_id);
    }, [item.category_id, categoryHasVariants, categoryProducts, excludedVariantIds, item.variant_id]);

    const filteredProducts = useMemo(() => {
        let filtered = categoryProducts;

        if (item.variant_id && categoryHasVariants) {
            const selectedVariant = availableVariants.find((v) => v.id === item.variant_id);
            if (selectedVariant) {
                filtered = filtered.filter((p) =>
                    p.variants?.some((v) => v.name === selectedVariant.name && v.size === selectedVariant.size)
                );
            }
        }

        return filtered;
    }, [categoryProducts, item.variant_id, categoryHasVariants, availableVariants]);

    const categoryCombos = useMemo(() => {
        if (!item.category_id) return [];
        // Solo mostrar combos si la categoría seleccionada es una categoría de combos
        if (!selectedCategory?.is_combo_category) return [];
        // Filtrar combos que pertenecen a la categoría seleccionada
        return combos.filter((c) => Number(c.category_id) === Number(item.category_id));
    }, [combos, item.category_id, selectedCategory]);

    return (
        <div className="relative rounded-lg border border-border bg-card p-6">
            {canRemove && (
                <Button type="button" variant="ghost" size="sm" onClick={() => onRemove(item.id)} className="absolute top-2 right-2">
                    <Trash2 className="h-4 w-4" />
                </Button>
            )}

            <div className="space-y-4">
                <h4 className="font-medium">Item {index + 1}</h4>

                <CategoryCombobox
                    value={item.category_id}
                    onChange={(value) => onUpdate(item.id, 'category_id', value)}
                    categories={categories}
                    label="Categoría"
                    placeholder="Buscar categoría..."
                    error={getItemError(index, 'category_id')}
                    required
                />

                {item.category_id && categoryHasVariants && (
                    <VariantSelector
                        variants={availableVariants}
                        value={item.variant_id}
                        onChange={(variantId) => onUpdate(item.id, 'variant_id', variantId)}
                        error={getItemError(index, 'variant_id')}
                        required
                    />
                )}

                <div className="space-y-4">
                    {/* Productos: solo si hay categoría y (no tiene variantes o ya se seleccionó variante) */}
                    {item.category_id && (!categoryHasVariants || item.variant_id) && filteredProducts.length > 0 && (
                        <ProductCheckboxList
                            products={filteredProducts}
                            selectedIds={item.selected_product_ids}
                            onChange={(selectedIds) => onUpdate(item.id, 'selected_product_ids', selectedIds)}
                        />
                    )}

                    {/* Combos: siempre visibles (independiente de categoría seleccionada) */}
                    {categoryCombos.length > 0 && (
                        <div className="space-y-2">
                            <div className="flex items-center gap-2 text-sm font-medium text-muted-foreground">
                                <div className="h-px flex-1 bg-border" />
                                <span>Combos</span>
                                <div className="h-px flex-1 bg-border" />
                            </div>
                            <ComboCheckboxList
                                combos={categoryCombos}
                                selectedIds={item.selected_combo_ids || []}
                                onChange={(selectedIds) => onUpdate(item.id, 'selected_combo_ids', selectedIds)}
                            />
                        </div>
                    )}

                    {/* Empty state: solo si no hay productos NI combos disponibles */}
                    {(!item.category_id || (item.category_id && (!categoryHasVariants || item.variant_id) && filteredProducts.length === 0)) &&
                     categoryCombos.length === 0 && (
                        <div className="rounded-lg border border-dashed p-8 text-center">
                            <p className="text-sm font-medium text-muted-foreground">No hay productos ni combos disponibles</p>
                            <p className="text-xs text-muted-foreground">
                                {!item.category_id ? 'Selecciona una categoría para ver productos' : 'Selecciona una categoría diferente'}
                            </p>
                        </div>
                    )}
                </div>

                {showDiscount && (
                    <FormField label="Porcentaje de Descuento" required error={getItemError(index, 'discount_percentage')}>
                        <div className="relative">
                            <Input
                                type="number"
                                min="1"
                                max="100"
                                step="0.01"
                                value={item.discount_percentage}
                                onChange={(e) => onUpdate(item.id, 'discount_percentage', e.target.value)}
                                placeholder={PLACEHOLDERS.percentage}
                                className="pr-8"
                                required
                            />
                            <div className="absolute top-1/2 right-3 -translate-y-1/2 text-sm text-muted-foreground">%</div>
                        </div>
                    </FormField>
                )}
            </div>
        </div>
    );
}

export const PromotionItemEditor = React.memo(PromotionItemEditorComponent);
