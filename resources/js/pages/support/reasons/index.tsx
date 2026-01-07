import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Switch } from '@/components/ui/switch';
import { showNotification } from '@/hooks/useNotifications';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { closestCenter, DndContext, DragEndEvent, KeyboardSensor, PointerSensor, useSensor, useSensors } from '@dnd-kit/core';
import { arrayMove, SortableContext, sortableKeyboardCoordinates, useSortable, verticalListSortingStrategy } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { ArrowLeft, GripVertical, Pencil, Plus, Save, Trash2, X } from 'lucide-react';
import { useState } from 'react';

interface SupportReason {
    id: number;
    name: string;
    slug: string;
    sort_order: number;
    is_active: boolean;
    tickets_count: number;
}

interface ReasonsPageProps {
    reasons: SupportReason[];
}

function SortableReasonItem({
    reason,
    onEdit,
    onToggleActive,
    onDelete,
}: {
    reason: SupportReason;
    onEdit: (reason: SupportReason) => void;
    onToggleActive: (reason: SupportReason) => void;
    onDelete: (reason: SupportReason) => void;
}) {
    const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({ id: reason.id });

    const style = {
        transform: CSS.Transform.toString(transform),
        transition,
    };

    return (
        <div
            ref={setNodeRef}
            style={style}
            className={`flex items-center gap-3 rounded-lg border bg-card p-3 ${isDragging ? 'opacity-50 shadow-lg' : ''}`}
        >
            <button type="button" className="cursor-grab touch-none text-muted-foreground hover:text-foreground" {...attributes} {...listeners}>
                <GripVertical className="h-5 w-5" />
            </button>

            <div className="flex-1">
                <span className="font-medium">{reason.name}</span>
                <span className="ml-2 text-xs text-muted-foreground">({reason.slug})</span>
            </div>

            <Badge variant="outline" className="text-xs">
                {reason.tickets_count} tickets
            </Badge>

            <Switch checked={reason.is_active} onCheckedChange={() => onToggleActive(reason)} />

            <Button variant="ghost" size="icon" onClick={() => onEdit(reason)}>
                <Pencil className="h-4 w-4" />
            </Button>

            <Button
                variant="ghost"
                size="icon"
                onClick={() => onDelete(reason)}
                disabled={reason.tickets_count > 0}
                className="text-destructive hover:text-destructive"
            >
                <Trash2 className="h-4 w-4" />
            </Button>
        </div>
    );
}

export default function ReasonsIndex({ reasons: initialReasons }: ReasonsPageProps) {
    const [reasons, setReasons] = useState(initialReasons);
    const [editingReason, setEditingReason] = useState<SupportReason | null>(null);
    const [editName, setEditName] = useState('');
    const [isAdding, setIsAdding] = useState(false);

    const addForm = useForm({ name: '' });

    const sensors = useSensors(
        useSensor(PointerSensor),
        useSensor(KeyboardSensor, {
            coordinateGetter: sortableKeyboardCoordinates,
        }),
    );

    const handleDragEnd = (event: DragEndEvent) => {
        const { active, over } = event;

        if (over && active.id !== over.id) {
            const oldIndex = reasons.findIndex((r) => r.id === active.id);
            const newIndex = reasons.findIndex((r) => r.id === over.id);

            const newOrder = arrayMove(reasons, oldIndex, newIndex);
            setReasons(newOrder);

            router.post(
                '/support/reasons/order',
                { order: newOrder.map((r) => r.id) },
                {
                    preserveScroll: true,
                    onError: () => {
                        setReasons(initialReasons);
                        showNotification.error('Error al actualizar el orden');
                    },
                },
            );
        }
    };

    const handleAdd = (e: React.FormEvent) => {
        e.preventDefault();
        addForm.post('/support/reasons', {
            preserveScroll: true,
            onSuccess: () => {
                addForm.reset();
                setIsAdding(false);
            },
            onError: () => showNotification.error('Error al crear el motivo'),
        });
    };

    const handleEdit = (reason: SupportReason) => {
        setEditingReason(reason);
        setEditName(reason.name);
    };

    const handleSaveEdit = () => {
        if (!editingReason) return;

        router.put(
            `/support/reasons/${editingReason.id}`,
            { name: editName, is_active: editingReason.is_active },
            {
                preserveScroll: true,
                onSuccess: () => setEditingReason(null),
                onError: () => showNotification.error('Error al actualizar el motivo'),
            },
        );
    };

    const handleToggleActive = (reason: SupportReason) => {
        router.put(
            `/support/reasons/${reason.id}`,
            { name: reason.name, is_active: !reason.is_active },
            {
                preserveScroll: true,
                onError: () => showNotification.error('Error al actualizar el estado'),
            },
        );
    };

    const handleDelete = (reason: SupportReason) => {
        if (reason.tickets_count > 0) {
            showNotification.error('No se puede eliminar un motivo con tickets asociados');
            return;
        }

        if (!confirm(`¿Eliminar "${reason.name}"?`)) return;

        router.delete(`/support/reasons/${reason.id}`, {
            preserveScroll: true,
            onError: () => showNotification.error('Error al eliminar el motivo'),
        });
    };

    return (
        <AppLayout>
            <Head title="Motivos de Soporte" />

            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Button asChild variant="ghost" size="icon">
                            <Link href="/support/tickets">
                                <ArrowLeft className="h-4 w-4" />
                            </Link>
                        </Button>
                        <div className="space-y-1">
                            <h1 className="text-3xl font-bold tracking-tight">Motivos de Soporte</h1>
                            <p className="text-muted-foreground">Administra los motivos que los clientes pueden seleccionar al crear un ticket</p>
                        </div>
                    </div>
                    {!isAdding && (
                        <Button onClick={() => setIsAdding(true)}>
                            <Plus className="mr-2 h-4 w-4" />
                            Agregar motivo
                        </Button>
                    )}
                </div>

                <Card>
                    <CardContent className="pt-6">
                        {/* Add form */}
                        {isAdding && (
                            <form onSubmit={handleAdd} className="mb-4 flex gap-2">
                                <Input
                                    placeholder="Nombre del motivo..."
                                    value={addForm.data.name}
                                    onChange={(e) => addForm.setData('name', e.target.value)}
                                    autoFocus
                                />
                                <Button type="submit" disabled={addForm.processing || !addForm.data.name.trim()}>
                                    <Save className="mr-2 h-4 w-4" />
                                    Guardar
                                </Button>
                                <Button type="button" variant="ghost" onClick={() => setIsAdding(false)}>
                                    <X className="h-4 w-4" />
                                </Button>
                            </form>
                        )}

                        {/* Edit modal inline */}
                        {editingReason && (
                            <div className="mb-4 flex gap-2 rounded-lg border bg-muted/50 p-3">
                                <Input value={editName} onChange={(e) => setEditName(e.target.value)} autoFocus />
                                <Button onClick={handleSaveEdit} disabled={!editName.trim()}>
                                    <Save className="mr-2 h-4 w-4" />
                                    Guardar
                                </Button>
                                <Button variant="ghost" onClick={() => setEditingReason(null)}>
                                    <X className="h-4 w-4" />
                                </Button>
                            </div>
                        )}

                        {/* Sortable list */}
                        <DndContext sensors={sensors} collisionDetection={closestCenter} onDragEnd={handleDragEnd}>
                            <SortableContext items={reasons.map((r) => r.id)} strategy={verticalListSortingStrategy}>
                                <div className="space-y-2">
                                    {reasons.map((reason) => (
                                        <SortableReasonItem
                                            key={reason.id}
                                            reason={reason}
                                            onEdit={handleEdit}
                                            onToggleActive={handleToggleActive}
                                            onDelete={handleDelete}
                                        />
                                    ))}
                                </div>
                            </SortableContext>
                        </DndContext>

                        {reasons.length === 0 && (
                            <div className="py-12 text-center text-muted-foreground">
                                No hay motivos configurados. Agrega uno para comenzar.
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
