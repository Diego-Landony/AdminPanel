import { showNotification } from '@/hooks/useNotifications';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';

import { DeleteConfirmationDialog } from '@/components/DeleteConfirmationDialog';
import { SortableTable } from '@/components/SortableTable';
import { StandardMobileCard } from '@/components/StandardMobileCard';
import { TableActions } from '@/components/TableActions';
import { ACTIVE_STATUS_CONFIGS, StatusBadge } from '@/components/status-badge';
import AppLayout from '@/layouts/app-layout';
import { Badge } from '@/components/ui/badge';
import { Package2, CheckCircle2 } from 'lucide-react';

interface Category {
    id: number;
    name: string;
}

interface ComboItem {
    id: number;
    product_id: number;
    variant_id: number | null;
    quantity: number;
    sort_order: number;
    product: {
        id: number;
        name: string;
    };
    variant?: {
        id: number;
        name: string;
        size: string;
    } | null;
}

interface Combo {
    id: number;
    name: string;
    description: string | null;
    image: string | null;
    precio_pickup_capital: number;
    precio_domicilio_capital: number;
    precio_pickup_interior: number;
    precio_domicilio_interior: number;
    is_active: boolean;
    sort_order: number;
    items_count: number;
    items?: ComboItem[];
    categories?: Category[];
}

interface CombosPageProps {
    combos: Combo[];
    stats: {
        total_combos: number;
        active_combos: number;
        available_combos: number;
    };
}

export default function CombosIndex({ combos, stats }: CombosPageProps) {
    const [deletingCombo, setDeletingCombo] = useState<number | null>(null);
    const [selectedCombo, setSelectedCombo] = useState<Combo | null>(null);
    const [showDeleteDialog, setShowDeleteDialog] = useState(false);
    const [togglingCombo, setTogglingCombo] = useState<number | null>(null);
    const [isSaving, setIsSaving] = useState(false);

    const handleReorder = (reorderedCombos: Combo[]) => {
        setIsSaving(true);

        const orderData = reorderedCombos.map((combo, index) => ({
            id: combo.id,
            sort_order: index + 1,
        }));

        router.post(
            route('menu.combos.reorder'),
            { combos: orderData },
            {
                preserveState: true,
                onSuccess: () => {
                    showNotification.success('Orden guardado correctamente');
                },
                onError: (error) => {
                    if (error.message) {
                        showNotification.error(error.message);
                    }
                },
                onFinish: () => {
                    setIsSaving(false);
                },
            }
        );
    };

    const handleRefresh = () => {
        router.reload();
    };

    const openDeleteDialog = (combo: Combo) => {
        setSelectedCombo(combo);
        setShowDeleteDialog(true);
    };

    const closeDeleteDialog = () => {
        setSelectedCombo(null);
        setShowDeleteDialog(false);
        setDeletingCombo(null);
    };

    const handleDeleteCombo = () => {
        if (!selectedCombo) return;

        setDeletingCombo(selectedCombo.id);
        router.delete(route('menu.combos.destroy', selectedCombo.id), {
            onSuccess: () => {
                closeDeleteDialog();
            },
            onError: (error) => {
                setDeletingCombo(null);
                if (error.message) {
                    showNotification.error(error.message);
                }
            },
        });
    };

    const handleToggleActive = (combo: Combo) => {
        setTogglingCombo(combo.id);
        router.post(
            route('menu.combos.toggle', combo.id),
            {},
            {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    showNotification.success(`Combo ${combo.is_active ? 'desactivado' : 'activado'} correctamente`);
                },
                onError: (error) => {
                    if (error.message) {
                        showNotification.error(error.message);
                    }
                },
                onFinish: () => {
                    setTogglingCombo(null);
                },
            }
        );
    };

    const columns = [
        {
            key: 'name',
            title: 'Combo',
            width: 'w-72',
            render: (combo: Combo) => (
                <div className="flex items-center gap-3">
                    {combo.image && (
                        <img
                            src={combo.image}
                            alt={combo.name}
                            className="h-10 w-10 rounded-md object-cover flex-shrink-0"
                        />
                    )}
                    <div className="min-w-0">
                        <div className="text-sm font-medium text-foreground truncate">{combo.name}</div>
                        {combo.description && (
                            <div className="text-xs text-muted-foreground truncate">{combo.description}</div>
                        )}
                    </div>
                </div>
            ),
        },
        {
            key: 'items',
            title: 'Productos',
            width: 'w-24',
            textAlign: 'center' as const,
            render: (combo: Combo) => (
                <div className="flex justify-center">
                    <Badge variant="secondary" className="font-mono">
                        {combo.items_count}
                    </Badge>
                </div>
            ),
        },
        {
            key: 'prices',
            title: 'Precios',
            width: 'w-56',
            render: (combo: Combo) => (
                <div className="text-xs space-y-1">
                    <div className="flex items-center justify-between gap-2">
                        <span className="text-muted-foreground">Capital:</span>
                        <span className="font-medium tabular-nums">
                            Q{Number(combo.precio_pickup_capital).toFixed(2)} / Q{Number(combo.precio_domicilio_capital).toFixed(2)}
                        </span>
                    </div>
                    <div className="flex items-center justify-between gap-2">
                        <span className="text-muted-foreground">Interior:</span>
                        <span className="font-medium tabular-nums">
                            Q{Number(combo.precio_pickup_interior).toFixed(2)} / Q{Number(combo.precio_domicilio_interior).toFixed(2)}
                        </span>
                    </div>
                </div>
            ),
        },
        {
            key: 'status',
            title: 'Estado',
            width: 'w-32',
            textAlign: 'center' as const,
            render: (combo: Combo) => (
                <div className="flex justify-center">
                    <button
                        onClick={() => handleToggleActive(combo)}
                        disabled={togglingCombo === combo.id}
                        className="transition-opacity hover:opacity-70 disabled:opacity-50"
                    >
                        <StatusBadge
                            status={combo.is_active ? 'active' : 'inactive'}
                            configs={ACTIVE_STATUS_CONFIGS}
                            showIcon={false}
                        />
                    </button>
                </div>
            ),
        },
        {
            key: 'actions',
            title: 'Acciones',
            width: 'w-24',
            textAlign: 'right' as const,
            render: (combo: Combo) => (
                <TableActions
                    editHref={route('menu.combos.edit', combo.id)}
                    onDelete={() => openDeleteDialog(combo)}
                    isDeleting={deletingCombo === combo.id}
                    editTooltip="Editar combo"
                    deleteTooltip="Eliminar combo"
                />
            ),
        },
    ];

    const renderMobileCard = (combo: Combo) => (
        <StandardMobileCard
            title={combo.name}
            subtitle={combo.description || `${combo.items_count} productos`}
            imageUrl={combo.image || undefined}
            badge={{
                children: (
                    <button
                        onClick={() => handleToggleActive(combo)}
                        disabled={togglingCombo === combo.id}
                        className="transition-opacity hover:opacity-70 disabled:opacity-50"
                    >
                        <StatusBadge
                            status={combo.is_active ? 'active' : 'inactive'}
                            configs={ACTIVE_STATUS_CONFIGS}
                            showIcon={false}
                        />
                    </button>
                ),
            }}
            actions={{
                editHref: route('menu.combos.edit', combo.id),
                onDelete: () => openDeleteDialog(combo),
                isDeleting: deletingCombo === combo.id,
                editTooltip: 'Editar combo',
                deleteTooltip: 'Eliminar combo',
            }}
            dataFields={[
                { label: 'Productos', value: combo.items_count.toString() },
                {
                    label: 'Precios',
                    value: (
                        <div className="space-y-1 text-xs">
                            <div className="flex justify-between gap-2">
                                <span className="text-muted-foreground">Capital:</span>
                                <span className="font-medium tabular-nums">
                                    Q{Number(combo.precio_pickup_capital).toFixed(2)} / Q{Number(combo.precio_domicilio_capital).toFixed(2)}
                                </span>
                            </div>
                            <div className="flex justify-between gap-2">
                                <span className="text-muted-foreground">Interior:</span>
                                <span className="font-medium tabular-nums">
                                    Q{Number(combo.precio_pickup_interior).toFixed(2)} / Q{Number(combo.precio_domicilio_interior).toFixed(2)}
                                </span>
                            </div>
                        </div>
                    ),
                },
            ]}
        />
    );

    const comboStats = [
        {
            title: 'combos',
            value: stats.total_combos,
            icon: <Package2 className="h-4 w-4 text-primary" />,
        },
        {
            title: 'activos',
            value: stats.active_combos,
            icon: <CheckCircle2 className="h-4 w-4 text-green-600" />,
        },
        {
            title: 'disponibles',
            value: stats.available_combos,
            icon: <CheckCircle2 className="h-4 w-4 text-blue-600" />,
        },
    ];

    return (
        <AppLayout>
            <Head title="Combos" />

            <SortableTable
                title="Combos de Menú"
                data={combos}
                columns={columns}
                stats={comboStats}
                createUrl={route('menu.combos.create')}
                createLabel="Crear"
                searchable={true}
                searchPlaceholder="Buscar combos..."
                onReorder={handleReorder}
                onRefresh={handleRefresh}
                isSaving={isSaving}
                renderMobileCard={renderMobileCard}
                breakpoint="lg"
            />

            <DeleteConfirmationDialog
                isOpen={showDeleteDialog}
                onClose={closeDeleteDialog}
                onConfirm={handleDeleteCombo}
                isDeleting={deletingCombo !== null}
                entityName={selectedCombo?.name || ''}
                entityType="combo"
            />
        </AppLayout>
    );
}
