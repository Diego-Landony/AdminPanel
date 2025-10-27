import { Button } from '@/components/ui/button';
import { useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { GripVertical, X } from 'lucide-react';

interface Product {
    id: number;
    name: string;
    category?: {
        name: string;
    };
}

interface ProductVariant {
    id: number;
    name: string;
    size?: string;
}

interface ChoiceOption {
    id: string;
    product_id: number;
    variant_id?: number | null;
    sort_order: number;
}

interface SortableChoiceOptionProps {
    option: ChoiceOption;
    products: Product[];
    onRemove: () => void;
    canDelete: boolean;
}

export function SortableChoiceOption({ option, products, onRemove, canDelete }: SortableChoiceOptionProps) {
    const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({ id: option.id });

    const style = {
        transform: CSS.Transform.toString(transform),
        transition,
        opacity: isDragging ? 0.5 : 1,
    };

    const product = products.find((p) => p.id === option.product_id);
    const variant = product && 'variants' in product ? (product as any).variants?.find((v: ProductVariant) => v.id === option.variant_id) : null;

    return (
        <div
            ref={setNodeRef}
            style={style}
            className={`flex items-center gap-2 rounded-lg border bg-card p-3 ${isDragging ? 'bg-muted/50 shadow-lg' : ''}`}
        >
            <button
                type="button"
                className="cursor-grab text-muted-foreground transition-colors hover:text-foreground active:cursor-grabbing"
                {...attributes}
                {...listeners}
            >
                <GripVertical className="h-4 w-4" />
            </button>

            <div className="min-w-0 flex-1">
                <div className="flex flex-col">
                    <span className="truncate font-medium">{product?.name || 'Producto no encontrado'}</span>
                    {product?.category && <span className="truncate text-xs text-muted-foreground">{product.category.name}</span>}
                </div>
                {variant && <div className="truncate text-sm text-muted-foreground">{variant.name}</div>}
            </div>

            {canDelete && (
                <Button type="button" variant="ghost" size="sm" onClick={onRemove} className="h-8 w-8 p-0">
                    <X className="h-4 w-4" />
                </Button>
            )}
        </div>
    );
}
