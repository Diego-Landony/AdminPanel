import { ProductCombobox } from '@/components/ProductCombobox';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { FormField } from '@/components/ui/form-field';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { COMBO_LABELS, PLACEHOLDERS } from '@/constants/ui-constants';
import { useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { ChevronDown, ChevronUp, GripVertical, ListChecks, Package, X } from 'lucide-react';
import { useState } from 'react';
import { ChoiceGroupEditor } from './ChoiceGroupEditor';
import { ItemTypeSelector } from './ItemTypeSelector';

interface Product {
    id: number;
    name: string;
    image?: string | null;
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

interface ComboItem {
    id: string;
    is_choice_group: boolean;
    choice_label?: string;
    product_id?: number | null;
    variant_id?: number | null;
    quantity: number;
    sort_order: number;
    options?: ChoiceOption[];
}

interface ComboItemCardProps {
    item: ComboItem;
    index: number;
    products: Product[];
    onUpdate: (field: string, value: string | number | boolean | ChoiceOption[] | null) => void;
    onBatchUpdate?: (updates: Partial<ComboItem>) => void;
    onRemove: () => void;
    canDelete: boolean;
    errors?: Record<string, string>;
    defaultExpanded?: boolean;
}

export function ComboItemCard({
    item,
    index,
    products,
    onUpdate,
    onBatchUpdate,
    onRemove,
    canDelete,
    errors = {},
    defaultExpanded = false,
}: ComboItemCardProps) {
    const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({ id: item.id });

    const selectedProduct = products.find((p) => p.id === item.product_id);
    const hasVariants = selectedProduct?.has_variants && selectedProduct?.variants && selectedProduct.variants.length > 0;

    // Detectar si el item está incompleto (necesita configuración)
    const isIncomplete = item.is_choice_group
        ? !item.choice_label || (item.options?.length || 0) < 2
        : !item.product_id;

    // Auto-expandir si el item está incompleto o si se especifica defaultExpanded
    const [isExpanded, setIsExpanded] = useState(() => defaultExpanded || isIncomplete);

    const style = {
        transform: CSS.Transform.toString(transform),
        transition,
        opacity: isDragging ? 0.5 : 1,
    };

    const handleTypeChange = (type: 'fixed' | 'choice') => {
        const isChoice = type === 'choice';

        if (onBatchUpdate) {
            if (isChoice) {
                onBatchUpdate({
                    is_choice_group: true,
                    product_id: null,
                    variant_id: null,
                    options: [],
                    choice_label: '',
                });
            } else {
                onBatchUpdate({
                    is_choice_group: false,
                    options: [],
                    choice_label: '',
                });
            }
        } else {
            onUpdate('is_choice_group', isChoice);
            if (isChoice) {
                onUpdate('product_id', null);
                onUpdate('variant_id', null);
                onUpdate('options', []);
                onUpdate('choice_label', '');
            } else {
                onUpdate('options', []);
                onUpdate('choice_label', '');
            }
        }
    };

    const handleProductChange = (productId: number | null) => {
        if (onBatchUpdate) {
            onBatchUpdate({
                product_id: productId,
                variant_id: null,
            });
        } else {
            onUpdate('product_id', productId);
            onUpdate('variant_id', null);
        }
    };

    const handleQuantityChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const value = e.target.value;
        if (value === '') {
            onUpdate('quantity', '');
        } else {
            const numValue = parseInt(value, 10);
            if (!isNaN(numValue) && numValue >= 0) {
                onUpdate('quantity', numValue);
            }
        }
    };

    const getHeaderTitle = (): string => {
        if (item.is_choice_group) {
            return item.choice_label || COMBO_LABELS.itemTypes.choiceGroup;
        }
        if (selectedProduct) {
            return selectedProduct.name;
        }
        return COMBO_LABELS.itemTypes.fixed;
    };

    const getHeaderSubtitle = (): string | null => {
        if (item.is_choice_group) {
            const optionsCount = item.options?.length || 0;
            return optionsCount > 0 ? `${optionsCount} opciones` : 'Sin opciones';
        }
        if (selectedProduct && item.variant_id) {
            const variant = selectedProduct.variants?.find((v) => v.id === item.variant_id);
            if (variant) {
                return variant.name + (variant.size ? ` - ${variant.size}` : '');
            }
        }
        return null;
    };

    const HeaderIcon = () => {
        if (item.is_choice_group) {
            return (
                <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-muted">
                    <ListChecks className="h-5 w-5 text-muted-foreground" />
                </div>
            );
        }

        if (selectedProduct?.image) {
            return (
                <div className="h-10 w-10 shrink-0 overflow-hidden rounded-lg">
                    <img src={selectedProduct.image} alt={selectedProduct.name} className="h-full w-full object-cover" />
                </div>
            );
        }

        return (
            <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-muted">
                <Package className="h-5 w-5 text-muted-foreground" />
            </div>
        );
    };

    return (
        <div
            ref={setNodeRef}
            style={style}
            className={`rounded-lg border ${isIncomplete ? 'border-amber-300 dark:border-amber-700' : 'border-border'} ${isDragging ? 'bg-muted/50 shadow-lg' : 'bg-card'}`}
        >
            <Collapsible open={isExpanded} onOpenChange={setIsExpanded}>
                {/* Header - siempre visible */}
                <div className="flex items-center gap-3 p-4">
                    <button
                        type="button"
                        className="cursor-grab text-muted-foreground transition-colors hover:text-foreground active:cursor-grabbing"
                        {...attributes}
                        {...listeners}
                    >
                        <GripVertical className="h-5 w-5" />
                    </button>

                    <HeaderIcon />

                    <div className="min-w-0 flex-1">
                        <div className="flex items-center gap-2">
                            <h4 className="truncate text-sm font-medium">{getHeaderTitle()}</h4>
                            {isIncomplete && (
                                <Badge variant="outline" className="border-amber-300 bg-amber-50 text-amber-700 dark:border-amber-700 dark:bg-amber-950/30 dark:text-amber-400">
                                    Incompleto
                                </Badge>
                            )}
                        </div>
                        <p className="truncate text-xs text-muted-foreground">
                            {getHeaderSubtitle()}
                            {item.quantity > 1 && ` · x${item.quantity}`}
                        </p>
                    </div>

                    <CollapsibleTrigger asChild>
                        <Button type="button" variant="ghost" size="sm" className="h-8 w-8 shrink-0 p-0">
                            {isExpanded ? <ChevronUp className="h-4 w-4" /> : <ChevronDown className="h-4 w-4" />}
                        </Button>
                    </CollapsibleTrigger>

                    {canDelete && (
                        <Button type="button" variant="ghost" size="sm" onClick={onRemove} className="h-8 w-8 shrink-0 p-0 text-muted-foreground hover:text-destructive">
                            <X className="h-4 w-4" />
                        </Button>
                    )}
                </div>

                {/* Contenido colapsable */}
                <CollapsibleContent>
                    <div className="space-y-4 border-t px-4 pb-4 pt-4">
                        <ItemTypeSelector value={item.is_choice_group ? 'choice' : 'fixed'} onChange={handleTypeChange} id={item.id} />

                        {item.is_choice_group ? (
                            <ChoiceGroupEditor
                                label={item.choice_label || ''}
                                options={item.options || []}
                                onLabelChange={(label) => onUpdate('choice_label', label)}
                                onOptionsChange={(options) => onUpdate('options', options)}
                                products={products}
                                errors={{
                                    choice_label: errors[`items.${index}.choice_label`],
                                    options: errors[`items.${index}.options`],
                                }}
                            />
                        ) : (
                            <>
                                <ProductCombobox
                                    value={item.product_id || null}
                                    onChange={handleProductChange}
                                    products={products}
                                    label="Producto"
                                    error={errors[`items.${index}.product_id`]}
                                    required
                                />

                                {hasVariants && (
                                    <FormField label="Variante" error={errors[`items.${index}.variant_id`]} required>
                                        <Select
                                            value={item.variant_id ? String(item.variant_id) : ''}
                                            onValueChange={(value) => onUpdate('variant_id', Number(value))}
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder={PLACEHOLDERS.selectVariant} />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {selectedProduct.variants?.map((variant) => (
                                                    <SelectItem key={variant.id} value={String(variant.id)}>
                                                        {variant.name} {variant.size && `- ${variant.size}`}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </FormField>
                                )}
                            </>
                        )}

                        {/* Campo de cantidad */}
                        <FormField label={COMBO_LABELS.quantityBadge} error={errors[`items.${index}.quantity`]} required>
                            <Input
                                type="number"
                                min="1"
                                max="10"
                                value={item.quantity === 0 ? '' : item.quantity}
                                onChange={handleQuantityChange}
                            />
                        </FormField>
                    </div>
                </CollapsibleContent>
            </Collapsible>
        </div>
    );
}
