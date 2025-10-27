import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { closestCenter, DndContext, DragEndEvent, KeyboardSensor, PointerSensor, useSensor, useSensors } from '@dnd-kit/core';
import { arrayMove, SortableContext, sortableKeyboardCoordinates, useSortable, verticalListSortingStrategy } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { AlertCircle, GripVertical, Plus, X } from 'lucide-react';
import { useState } from 'react';

interface VariantDefinitionsInputProps {
    variants: string[];
    onChange: (variants: string[]) => void;
    error?: string;
}

interface SortableVariantItemProps {
    variant: string;
    index: number;
    onUpdate: (index: number, value: string) => void;
    onRemove: (index: number) => void;
    canDelete: boolean;
    hasDuplicate: boolean;
}

function SortableVariantItem({ variant, index, onUpdate, onRemove, canDelete, hasDuplicate }: SortableVariantItemProps) {
    const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({
        id: `variant-${index}`,
    });

    const style = {
        transform: CSS.Transform.toString(transform),
        transition,
        opacity: isDragging ? 0.5 : 1,
    };

    return (
        <div
            ref={setNodeRef}
            style={style}
            className={`flex items-center gap-3 rounded-lg border p-3 ${
                isDragging ? 'bg-muted/50 shadow-lg' : ''
            } ${hasDuplicate ? 'border-destructive' : 'border-border'}`}
        >
            <button
                type="button"
                className="cursor-grab text-muted-foreground transition-colors hover:text-foreground active:cursor-grabbing"
                {...attributes}
                {...listeners}
            >
                <GripVertical className="h-5 w-5" />
            </button>

            <Input
                type="text"
                value={variant}
                onChange={(e) => onUpdate(index, e.target.value)}
                placeholder="Ej: 15cm, 30cm, Grande"
                className={hasDuplicate ? 'border-destructive' : ''}
            />

            {canDelete && (
                <Button type="button" variant="ghost" size="sm" onClick={() => onRemove(index)} className="h-9 w-9 p-0">
                    <X className="h-4 w-4" />
                </Button>
            )}
        </div>
    );
}

export function VariantDefinitionsInput({ variants, onChange, error }: VariantDefinitionsInputProps) {
    const [localVariants, setLocalVariants] = useState<string[]>(variants);

    const sensors = useSensors(
        useSensor(PointerSensor),
        useSensor(KeyboardSensor, {
            coordinateGetter: sortableKeyboardCoordinates,
        }),
    );

    // Detectar duplicados
    const duplicates = new Set<string>();
    const seen = new Set<string>();
    localVariants.forEach((v) => {
        const trimmed = v.trim().toLowerCase();
        if (trimmed && seen.has(trimmed)) {
            duplicates.add(trimmed);
        }
        if (trimmed) {
            seen.add(trimmed);
        }
    });

    const handleDragEnd = (event: DragEndEvent) => {
        const { active, over } = event;

        if (over && active.id !== over.id) {
            const oldIndex = localVariants.findIndex((_, i) => `variant-${i}` === active.id);
            const newIndex = localVariants.findIndex((_, i) => `variant-${i}` === over.id);

            const reordered = arrayMove(localVariants, oldIndex, newIndex);
            setLocalVariants(reordered);
            onChange(reordered);
        }
    };

    const handleUpdate = (index: number, value: string) => {
        const updated = [...localVariants];
        updated[index] = value;
        setLocalVariants(updated);
        onChange(updated);
    };

    const handleRemove = (index: number) => {
        const updated = localVariants.filter((_, i) => i !== index);
        setLocalVariants(updated);
        onChange(updated);
    };

    const handleAdd = () => {
        const updated = [...localVariants, ''];
        setLocalVariants(updated);
        onChange(updated);
    };

    // Determinar si una variante es duplicada
    const isDuplicate = (variant: string) => {
        const trimmed = variant.trim().toLowerCase();
        return trimmed && duplicates.has(trimmed);
    };

    return (
        <div className="space-y-4">
            {localVariants.length === 0 ? (
                <div className="rounded-lg border-2 border-dashed border-border p-8 text-center">
                    <p className="mb-4 text-sm text-muted-foreground">Agrega al menos una variante.</p>
                    <Button type="button" variant="outline" size="sm" onClick={handleAdd}>
                        <Plus className="mr-2 h-4 w-4" />
                        Agregar variante
                    </Button>
                </div>
            ) : (
                <>
                    <DndContext sensors={sensors} collisionDetection={closestCenter} onDragEnd={handleDragEnd}>
                        <SortableContext items={localVariants.map((_, i) => `variant-${i}`)} strategy={verticalListSortingStrategy}>
                            <div className="space-y-2">
                                {localVariants.map((variant, index) => (
                                    <SortableVariantItem
                                        key={`variant-${index}`}
                                        variant={variant}
                                        index={index}
                                        onUpdate={handleUpdate}
                                        onRemove={handleRemove}
                                        canDelete={localVariants.length > 1}
                                        hasDuplicate={isDuplicate(variant)}
                                    />
                                ))}
                            </div>
                        </SortableContext>
                    </DndContext>

                    <Button type="button" variant="outline" size="sm" onClick={handleAdd} className="w-full">
                        <Plus className="mr-2 h-4 w-4" />
                        Agregar variante
                    </Button>
                </>
            )}

            {duplicates.size > 0 && (
                <Alert variant="destructive">
                    <AlertCircle className="h-4 w-4" />
                    <AlertDescription>Las variantes no pueden tener nombres duplicados. Revisa y corrige.</AlertDescription>
                </Alert>
            )}

            {error && (
                <Alert variant="destructive">
                    <AlertCircle className="h-4 w-4" />
                    <AlertDescription>{error}</AlertDescription>
                </Alert>
            )}
        </div>
    );
}
