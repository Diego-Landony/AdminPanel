import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { useState, useCallback } from 'react';
import { toast } from 'sonner';

import AppLayout from '@/layouts/app-layout';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Shield, Users, Star } from 'lucide-react';
import { ActionsMenu } from '@/components/ActionsMenu';
import { CustomerTypesSkeleton } from '@/components/skeletons';
import { DataTable } from '@/components/DataTable';
import { ResponsiveCard, ResponsiveCardHeader, ResponsiveCardContent, DataField, CardActions } from '@/components/CardLayout';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Clientes',
        href: '/customers',
    },
    {
        title: 'Tipos de Cliente',
        href: '/customer-types',
    },
];

interface CustomerType {
    id: number;
    name: string;
    display_name: string;
    points_required: number;
    multiplier: number;
    color: string | null;
    is_active: boolean;
    sort_order: number;
    customers_count: number;
    created_at: string;
    updated_at: string;
}

interface CustomerTypesPageProps {
    customer_types: {
        data: CustomerType[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number;
        to: number;
    };
    stats: {
        total_types: number;
        active_types: number;
    };
    filters: {
        search: string | null;
        per_page: number;
        sort_field?: string;
        sort_direction?: 'asc' | 'desc';
    };
}

const getColorClasses = (color: string | null): string => {
    switch (color) {
        case 'gray':
            return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300 border border-gray-200 dark:border-gray-700';
        case 'orange':
            return 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300 border border-orange-200 dark:border-orange-700';
        case 'slate':
            return 'bg-slate-100 text-slate-800 dark:bg-slate-900 dark:text-slate-300 border border-slate-200 dark:border-slate-700';
        case 'yellow':
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300 border border-yellow-200 dark:border-yellow-700';
        case 'purple':
            return 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300 border border-purple-200 dark:border-purple-700';
        default:
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300 border border-blue-200 dark:border-blue-700';
    }
};

const CustomerTypeInfoCell: React.FC<{ type: CustomerType }> = ({ type }) => (
    <div className="flex items-center gap-3">
        <div className="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0">
            <Shield className="w-5 h-5 text-primary" />
        </div>
        <div className="min-w-0">
            <div className="font-medium text-sm text-foreground break-words">
                {type.display_name}
            </div>
            <div className="text-sm text-muted-foreground break-words">
                {type.name}
            </div>
        </div>
    </div>
);

const CustomerTypeMobileCard: React.FC<{ type: CustomerType; onDelete: (type: CustomerType) => void; isDeleting: boolean }> = ({
    type,
    onDelete,
    isDeleting
}) => (
    <ResponsiveCard>
        <ResponsiveCardHeader
            icon={<Shield className="w-4 h-4 text-primary" />}
            title={type.display_name}
            subtitle={type.name}
            badge={{
                children: type.is_active ? 'Activo' : 'Inactivo',
                variant: type.is_active ? "default" : "secondary"
            }}
        />

        <ResponsiveCardContent>
            <DataField
                label="Puntos Requeridos"
                value={`${type.points_required.toLocaleString()} pts`}
            />

            <DataField
                label="Multiplicador"
                value={
                    <Badge className={getColorClasses(type.color)}>
                        {type.multiplier}x
                    </Badge>
                }
            />

            <DataField
                label="Clientes"
                value={
                    <div className="flex items-center gap-2">
                        <Users className="h-4 w-4 text-muted-foreground" />
                        <span>{type.customers_count}</span>
                    </div>
                }
            />
        </ResponsiveCardContent>

        <CardActions>
            <ActionsMenu
                editHref={`/customer-types/${type.id}/edit`}
                onDelete={() => onDelete(type)}
                isDeleting={isDeleting}
                editTitle="Editar tipo"
                deleteTitle="Eliminar tipo"
                canDelete={type.customers_count === 0}
            />
        </CardActions>
    </ResponsiveCard>
);

export default function CustomerTypesIndex({ customer_types, stats, filters }: CustomerTypesPageProps) {
    const [deletingType, setDeletingType] = useState<number | null>(null);
    const [selectedType, setSelectedType] = useState<CustomerType | null>(null);
    const [showDeleteDialog, setShowDeleteDialog] = useState(false);

    const openDeleteDialog = useCallback((type: CustomerType) => {
        setSelectedType(type);
        setShowDeleteDialog(true);
    }, []);

    const closeDeleteDialog = useCallback(() => {
        setSelectedType(null);
        setShowDeleteDialog(false);
        setDeletingType(null);
    }, []);

    const handleDeleteType = async () => {
        if (!selectedType) return;

        setDeletingType(selectedType.id);
        router.delete(`/customer-types/${selectedType.id}`, {
            onSuccess: () => {
                closeDeleteDialog();
                toast.success('Tipo de cliente eliminado correctamente');
            },
            onError: (error) => {
                setDeletingType(null);
                if (error.message) {
                    toast.error(error.message);
                }
            }
        });
    };

    const columns = [
        {
            key: 'type',
            title: 'Tipo',
            width: 'lg' as const,
            sortable: true,
            render: (type: CustomerType) => <CustomerTypeInfoCell type={type} />
        },
        {
            key: 'points_required',
            title: 'Puntos Requeridos',
            width: 'md' as const,
            sortable: true,
            render: (type: CustomerType) => (
                <span className="text-sm font-medium text-foreground">
                    {type.points_required.toLocaleString()} pts
                </span>
            )
        },
        {
            key: 'multiplier',
            title: 'Multiplicador',
            width: 'sm' as const,
            sortable: true,
            render: (type: CustomerType) => (
                <Badge className={getColorClasses(type.color)}>
                    {type.multiplier}x
                </Badge>
            )
        },
        {
            key: 'customers_count',
            title: 'Clientes',
            width: 'sm' as const,
            textAlign: 'center' as const,
            sortable: true,
            render: (type: CustomerType) => (
                <div className="flex items-center justify-center gap-2 text-sm text-muted-foreground">
                    <Users className="h-4 w-4" />
                    <span>{type.customers_count}</span>
                </div>
            )
        },
        {
            key: 'is_active',
            title: 'Estado',
            width: 'sm' as const,
            textAlign: 'center' as const,
            sortable: true,
            render: (type: CustomerType) => (
                <Badge variant={type.is_active ? "default" : "secondary"}>
                    {type.is_active ? 'Activo' : 'Inactivo'}
                </Badge>
            )
        },
        {
            key: 'actions',
            title: 'Acciones',
            width: 'xs' as const,
            textAlign: 'right' as const,
            render: (type: CustomerType) => (
                <ActionsMenu
                    editHref={`/customer-types/${type.id}/edit`}
                    onDelete={() => openDeleteDialog(type)}
                    isDeleting={deletingType === type.id}
                    editTitle="Editar tipo"
                    deleteTitle="Eliminar tipo"
                    canDelete={type.customers_count === 0}
                />
            )
        }
    ];

    const customerTypeStats = [
        {
            title: 'tipos',
            value: stats.total_types,
            icon: <Shield className="h-3 w-3 text-primary" />
        },
        {
            title: 'activos',
            value: stats.active_types,
            icon: <Star className="h-3 w-3 text-green-600" />
        },
        {
            title: 'inactivos',
            value: stats.total_types - stats.active_types,
            icon: <Users className="h-3 w-3 text-red-600" />
        }
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Tipos de Cliente" />

            <DataTable
                title="Tipos de Cliente"
                description="Gestiona los diferentes tipos de clientes y sus multiplicadores."
                data={customer_types}
                columns={columns}
                stats={customerTypeStats}
                filters={filters}
                createUrl="/customer-types/create"
                createLabel="Crear Tipo"
                searchPlaceholder="Buscar tipos de cliente..."
                loadingSkeleton={CustomerTypesSkeleton}
                renderMobileCard={(type) => (
                    <CustomerTypeMobileCard
                        type={type}
                        onDelete={openDeleteDialog}
                        isDeleting={deletingType === type.id}
                    />
                )}
                routeName="/customer-types"
                breakpoint="lg"
            />

            <Dialog open={showDeleteDialog} onOpenChange={closeDeleteDialog}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Eliminar Tipo de Cliente</DialogTitle>
                        <DialogDescription>
                            ¿Estás seguro de que deseas eliminar el tipo <strong>"{selectedType?.display_name}"</strong>?
                            {selectedType?.customers_count > 0 && (
                                <span className="text-destructive block mt-2">
                                    Este tipo tiene {selectedType.customers_count} clientes asignados y no se puede eliminar.
                                </span>
                            )}
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" onClick={closeDeleteDialog}>
                            Cancelar
                        </Button>
                        <Button
                            variant="destructive"
                            onClick={handleDeleteType}
                            disabled={deletingType !== null || (selectedType?.customers_count ?? 0) > 0}
                        >
                            {deletingType ? 'Eliminando...' : 'Eliminar'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}