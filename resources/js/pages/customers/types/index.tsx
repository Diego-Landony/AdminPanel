import { type BreadcrumbItem } from '@/types';
import { Head, router, Link } from '@inertiajs/react';
import { useState } from 'react';
import { toast } from 'sonner';

import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Plus, Shield, Users, Star, Edit, Trash2 } from 'lucide-react';
import { ActionsMenu } from '@/components/ActionsMenu';

/**
 * Breadcrumbs para la navegación de tipos de clientes
 */
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

/**
 * Interfaz para los tipos de cliente
 */
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

/**
 * Interfaz para las props de la página
 */
interface CustomerTypesPageProps {
    customer_types: CustomerType[];
    stats: {
        total_types: number;
        active_types: number;
    };
}

/**
 * Obtiene las clases de color según el color del tipo
 */
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

/**
 * Página principal de gestión de tipos de clientes
 */
export default function CustomerTypesIndex({ customer_types, stats }: CustomerTypesPageProps) {
    const [deletingType, setDeletingType] = useState<number | null>(null);
    const [selectedType, setSelectedType] = useState<CustomerType | null>(null);
    const [showDeleteDialog, setShowDeleteDialog] = useState(false);

    const openDeleteDialog = (type: CustomerType) => {
        setSelectedType(type);
        setShowDeleteDialog(true);
    };

    const closeDeleteDialog = () => {
        setSelectedType(null);
        setShowDeleteDialog(false);
        setDeletingType(null);
    };

    const handleDeleteType = async () => {
        if (!selectedType) return;

        setDeletingType(selectedType.id);
        router.delete(`/customer-types/${selectedType.id}`, {
            onSuccess: () => {
                closeDeleteDialog();
            },
            onError: (error) => {
                setDeletingType(null);
                if (error.message) {
                    toast.error(error.message);
                }
            }
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Tipos de Cliente" />
            
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-6">
                {/* Encabezado */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Tipos de Cliente</h1>
                        <p className="text-muted-foreground">
                            Gestiona los diferentes tipos de clientes y sus multiplicadores
                        </p>
                    </div>
                    <Link href="/customer-types/create">
                        <Button>
                            <Plus className="mr-2 h-4 w-4" />
                            Crear Tipo
                        </Button>
                    </Link>
                </div>

                {/* Tabla de tipos de clientes */}
                <Card className="border border-muted/50 shadow-sm">
                    <CardHeader className="pb-6">
                        <div className="flex flex-col space-y-4">
                            <div className="flex items-start justify-between">
                                {/* Estadísticas compactas integradas */}
                                <div className="flex items-center gap-4 text-sm text-muted-foreground">
                                    <span className="flex items-center gap-1">
                                        <Shield className="h-3 w-3 text-primary" />
                                        <span>tipos <span className="font-medium text-foreground">{stats.total_types}</span></span>
                                    </span>
                                    <span className="text-muted-foreground/50">•</span>
                                    <span className="flex items-center gap-1">
                                        <Star className="h-3 w-3 text-green-600" />
                                        <span>activos <span className="font-medium text-foreground">{stats.active_types}</span></span>
                                    </span>
                                </div>
                            </div>
                            
                            <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                                <div>
                                    <h2 className="text-xl font-semibold">Gestión de Tipos</h2>
                                    <p className="text-sm text-muted-foreground">
                                        Administra los tipos de cliente del sistema
                                    </p>
                                </div>
                            </div>
                        </div>
                    </CardHeader>

                    <CardContent>
                        {customer_types.length > 0 ? (
                            <>
                                <div className="hidden lg:block">
                                    {/* Tabla para desktop */}
                                    <div className="overflow-x-auto">
                                        <table className="w-full">
                                            <thead>
                                                <tr className="border-b border-border">
                                                    <th className="text-left py-3 px-4 font-medium text-sm text-muted-foreground">
                                                        Tipo
                                                    </th>
                                                    <th className="text-left py-3 px-4 font-medium text-sm text-muted-foreground">
                                                        Puntos Requeridos
                                                    </th>
                                                    <th className="text-left py-3 px-4 font-medium text-sm text-muted-foreground">
                                                        Multiplicador
                                                    </th>
                                                    <th className="text-left py-3 px-4 font-medium text-sm text-muted-foreground">
                                                        Clientes
                                                    </th>
                                                    <th className="text-left py-3 px-4 font-medium text-sm text-muted-foreground">
                                                        Estado
                                                    </th>
                                                    <th className="text-right py-3 px-4 font-medium text-sm text-muted-foreground">
                                                        Acciones
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody className="divide-y divide-border/50">
                                                {customer_types.map((type) => (
                                                    <tr key={type.id} className="hover:bg-muted/30 transition-colors">
                                                        {/* Columna Tipo */}
                                                        <td className="py-4 px-4">
                                                            <div className="flex items-center gap-3">
                                                                <div className="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0">
                                                                    <Shield className="w-5 h-5 text-primary" />
                                                                </div>
                                                                <div className="min-w-0">
                                                                    <div className="font-medium text-sm text-foreground truncate">
                                                                        {type.display_name}
                                                                    </div>
                                                                    <div className="text-sm text-muted-foreground truncate">
                                                                        {type.name}
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </td>

                                                        {/* Columna Puntos Requeridos */}
                                                        <td className="py-4 px-4">
                                                            <div className="text-sm font-medium text-foreground">
                                                                {type.points_required.toLocaleString()} pts
                                                            </div>
                                                        </td>

                                                        {/* Columna Multiplicador */}
                                                        <td className="py-4 px-4">
                                                            <Badge className={getColorClasses(type.color)}>
                                                                {type.multiplier}x
                                                            </Badge>
                                                        </td>

                                                        {/* Columna Clientes */}
                                                        <td className="py-4 px-4">
                                                            <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                                                <Users className="h-4 w-4" />
                                                                <span>{type.customers_count}</span>
                                                            </div>
                                                        </td>

                                                        {/* Columna Estado */}
                                                        <td className="py-4 px-4">
                                                            <Badge 
                                                                variant={type.is_active ? "default" : "secondary"}
                                                                className="text-sm"
                                                            >
                                                                {type.is_active ? 'Activo' : 'Inactivo'}
                                                            </Badge>
                                                        </td>

                                                        {/* Columna Acciones */}
                                                        <td className="py-4 px-4">
                                                            <div className="flex items-center justify-end">
                                                                <ActionsMenu
                                                                    editHref={`/customer-types/${type.id}/edit`}
                                                                    onDelete={() => openDeleteDialog(type)}
                                                                    isDeleting={deletingType === type.id}
                                                                    editTitle="Editar tipo"
                                                                    deleteTitle="Eliminar tipo"
                                                                    canDelete={type.customers_count === 0}
                                                                />
                                                            </div>
                                                        </td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                {/* Vista de cards para mobile/tablet */}
                                <div className="lg:hidden">
                                    <div className="grid gap-3 md:gap-4">
                                        {customer_types.map((type) => (
                                            <div key={type.id} className="bg-card border border-border rounded-lg p-4 space-y-3 hover:bg-muted/50 transition-colors">
                                                {/* Header con tipo y estado */}
                                                <div className="flex items-center justify-between">
                                                    <div className="flex items-center space-x-3 flex-1 min-w-0">
                                                        <div className="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0">
                                                            <Shield className="w-4 h-4 text-primary" />
                                                        </div>
                                                        <div className="min-w-0 flex-1">
                                                            <div className="font-medium text-sm break-words">{type.display_name}</div>
                                                            <div className="text-sm text-muted-foreground break-words">{type.name}</div>
                                                        </div>
                                                    </div>
                                                    <Badge 
                                                        variant={type.is_active ? "default" : "secondary"}
                                                        className="text-sm ml-2 flex-shrink-0"
                                                    >
                                                        {type.is_active ? 'Activo' : 'Inactivo'}
                                                    </Badge>
                                                </div>

                                                {/* Información del tipo */}
                                                <div className="grid grid-cols-3 gap-4 text-sm">
                                                    <div>
                                                        <div className="text-muted-foreground">Puntos</div>
                                                        <div className="font-medium">{type.points_required.toLocaleString()}</div>
                                                    </div>
                                                    <div>
                                                        <div className="text-muted-foreground">Multiplicador</div>
                                                        <Badge className={getColorClasses(type.color)}>
                                                            {type.multiplier}x
                                                        </Badge>
                                                    </div>
                                                    <div>
                                                        <div className="text-muted-foreground">Clientes</div>
                                                        <div className="flex items-center gap-1">
                                                            <Users className="h-4 w-4" />
                                                            <span>{type.customers_count}</span>
                                                        </div>
                                                    </div>
                                                </div>

                                                {/* Acciones */}
                                                <div className="flex items-center justify-end pt-2 border-t border-border">
                                                    <ActionsMenu
                                                        editHref={`/customer-types/${type.id}/edit`}
                                                        onDelete={() => openDeleteDialog(type)}
                                                        isDeleting={deletingType === type.id}
                                                        editTitle="Editar tipo"
                                                        deleteTitle="Eliminar tipo"
                                                        canDelete={type.customers_count === 0}
                                                    />
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            </>
                        ) : (
                            <div className="text-center py-12 text-muted-foreground">
                                <div className="flex flex-col items-center space-y-3">
                                    <Shield className="w-12 h-12 text-muted-foreground/50" />
                                    <div className="space-y-1">
                                        <p className="text-lg font-medium">No hay tipos de cliente</p>
                                        <p className="text-sm">Crea el primer tipo de cliente del sistema</p>
                                    </div>
                                    <Link href="/customer-types/create">
                                        <Button>
                                            <Plus className="mr-2 h-4 w-4" />
                                            Crear Tipo
                                        </Button>
                                    </Link>
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Dialog de confirmación para eliminar */}
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
            </div>
        </AppLayout>
    );
}