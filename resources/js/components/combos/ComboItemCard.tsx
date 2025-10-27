import { ProductCombobox } from '@/components/ProductCombobox';
import { Button } from '@/components/ui/button';
import { FormField } from '@/components/ui/form-field';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { PLACEHOLDERS } from '@/constants/ui-constants';
import { useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { GripVertical, X } from 'lucide-react';
import { ChoiceGroupEditor } from './ChoiceGroupEditor';
import { ItemTypeSelector } from './ItemTypeSelector';

interface Product {
    id: number;
    name: string;
    has_variants: boolean;
    variants?: ProductVariant[];
    category?: {
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
    onUpdate: (field: string, value: any) => void;
    onBatchUpdate?: (updates: Partial<ComboItem>) => void;
    onRemove: () => void;
    canDelete: boolean;
    errors?: Record<string, string>;
}

export function ComboItemCard({ item, index, products, onUpdate, onBatchUpdate, onRemove, canDelete, errors = {} }: ComboItemCardProps) {
    const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({ id: item.id });

    const style = {
        transform: CSS.Transform.toString(transform),
        transition,
        opacity: isDragging ? 0.5 : 1,
    };

    const selectedProduct = products.find((p) => p.id === item.product_id);
    const hasVariants = selectedProduct?.has_variants && selectedProduct?.variants && selectedProduct.variants.length > 0;

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

    return (
        <div
            ref={setNodeRef}
            style={style}
            className={`space-y-4 rounded-lg border border-border p-4 ${isDragging ? 'bg-muted/50 shadow-lg' : 'bg-card'}`}
        >
            <div className="flex items-center gap-3">
                <button
                    type="button"
                    className="cursor-grab text-muted-foreground transition-colors hover:text-foreground active:cursor-grabbing"
                    {...attributes}
                    {...listeners}
                >
                    <GripVertical className="h-5 w-5" />
                </button>

                <h4 className="flex-1 text-sm font-medium">Item {index + 1}</h4>

                {canDelete && (
                    <Button type="button" variant="ghost" size="sm" onClick={onRemove} className="h-8 w-8 p-0">
                        <X className="h-4 w-4" />
                    </Button>
                )}
            </div>

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

            <FormField label="Cantidad" error={errors[`items.${index}.quantity`]} required>
                <Input type="number" min="1" max="10" value={item.quantity} onChange={(e) => onUpdate('quantity', Number(e.target.value))} />
            </FormField>
        </div>
    );
}
