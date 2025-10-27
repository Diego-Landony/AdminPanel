import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { FormField } from '@/components/ui/form-field';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { COMBO_LABELS, NOTIFICATIONS, PLACEHOLDERS } from '@/constants/ui-constants';
import { showNotification } from '@/hooks/useNotifications';
import { closestCenter, DndContext, DragEndEvent, KeyboardSensor, PointerSensor, useSensor, useSensors } from '@dnd-kit/core';
import { arrayMove, SortableContext, sortableKeyboardCoordinates, verticalListSortingStrategy } from '@dnd-kit/sortable';
import { AlertCircle, Plus } from 'lucide-react';
import { useState } from 'react';
import { ProductSelectorModal } from './ProductSelectorModal';
import { SortableChoiceOption } from './SortableChoiceOption';

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
    const [isModalOpen, setIsModalOpen] = useState(false);

    const sensors = useSensors(
        useSensor(PointerSensor),
        useSensor(KeyboardSensor, {
            coordinateGetter: sortableKeyboardCoordinates,
        }),
    );

    const handleAddOption = (product: Product, variant?: ProductVariant) => {
        const newOption: ChoiceOption = {
            id: `option-${Date.now()}-${Math.random()}`,
            product_id: product.id,
            variant_id: variant?.id || null,
            sort_order: options.length + 1,
        };

        onOptionsChange([...options, newOption]);
        showNotification.success(NOTIFICATIONS.success.optionAdded);
    };

    const handleRemoveOption = (optionId: string) => {
        const updated = options.filter((opt) => opt.id !== optionId);
        onOptionsChange(
            updated.map((opt, index) => ({
                ...opt,
                sort_order: index + 1,
            })),
        );
        showNotification.success(NOTIFICATIONS.success.optionRemoved);
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

    const excludeIds = options.map((opt) => ({
        productId: opt.product_id,
        variantId: opt.variant_id || null,
    }));

    const hasMinOptions = options.length >= 2;

    return (
        <div className="space-y-4">
            <FormField label="Etiqueta del grupo" error={errors.choice_label} required>
                <Input value={label} onChange={(e) => onLabelChange(e.target.value)} placeholder={PLACEHOLDERS.choiceGroupLabel} />
                <p className="mt-1 text-xs text-muted-foreground">Esta etiqueta la verá el cliente al hacer su pedido</p>
            </FormField>

            <div className="space-y-2">
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

                {options.length > 0 ? (
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
                ) : (
                    <div className="rounded-lg border border-dashed border-muted-foreground/25 p-8 text-center">
                        <p className="text-sm text-muted-foreground">No hay opciones agregadas</p>
                    </div>
                )}

                <Button type="button" variant="outline" onClick={() => setIsModalOpen(true)} className="w-full">
                    <Plus className="mr-2 h-4 w-4" />
                    {COMBO_LABELS.addOption}
                </Button>
            </div>

            <ProductSelectorModal
                isOpen={isModalOpen}
                onClose={() => setIsModalOpen(false)}
                onSelect={handleAddOption}
                products={products}
                excludeIds={excludeIds}
            />
        </div>
    );
}
