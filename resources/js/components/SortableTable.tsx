import { Link } from '@inertiajs/react';
import { Plus, RefreshCw, Search, X } from 'lucide-react';
import React, { useState } from 'react';
import {
    DndContext,
    closestCenter,
    KeyboardSensor,
    PointerSensor,
    useSensor,
    useSensors,
    DragEndEvent,
} from '@dnd-kit/core';
import {
    arrayMove,
    SortableContext,
    sortableKeyboardCoordinates,
    useSortable,
    verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';

import { ErrorBoundary } from '@/components/ErrorBoundary';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { GripVertical, Save } from 'lucide-react';

interface SortableTableColumn<T> {
    key: string;
    title: string;
    width?: string;
    textAlign?: 'left' | 'center' | 'right';
    render?: (item: T) => React.ReactNode;
    className?: string;
}

interface SortableTableStat {
    title: string;
    value: number | string;
    icon: React.ReactNode;
}

interface SortableTableProps<T extends { id: number | string; sort_order?: number }> {
    title: string;
    description?: string;
    data: T[];
    columns: SortableTableColumn<T>[];
    stats?: SortableTableStat[];
    createUrl?: string;
    createLabel?: string;
    searchable?: boolean;
    searchPlaceholder?: string;
    onReorder: (items: T[]) => void;
    onRefresh?: () => void;
    isRefreshing?: boolean;
    isSaving?: boolean;
    renderMobileCard?: (item: T) => React.ReactNode;
    breakpoint?: 'sm' | 'md' | 'lg' | 'xl';
}

interface SortableRowProps<T> {
    item: T;
    columns: SortableTableColumn<T>[];
}

/**
 * Fila sortable individual
 */
function SortableRow<T extends { id: number | string }>({ item, columns }: SortableRowProps<T>) {
    const {
        attributes,
        listeners,
        setNodeRef,
        transform,
        transition,
        isDragging,
    } = useSortable({ id: item.id });

    const style = {
        transform: CSS.Transform.toString(transform),
        transition,
        opacity: isDragging ? 0.5 : 1,
    };

    return (
        <TableRow ref={setNodeRef} style={style} className={isDragging ? 'shadow-lg bg-muted/50' : ''}>
            <TableCell className="text-center w-16">
                <button
                    className="cursor-grab active:cursor-grabbing text-muted-foreground hover:text-foreground transition-colors"
                    {...attributes}
                    {...listeners}
                >
                    <GripVertical className="h-5 w-5" />
                </button>
            </TableCell>
            {columns.map((column) => (
                <TableCell
                    key={column.key}
                    className={`${column.width || ''} ${column.textAlign ? `text-${column.textAlign}` : 'text-left'} ${column.className || ''}`}
                >
                    {column.render ? column.render(item) : (item as Record<string, unknown>)[column.key]}
                </TableCell>
            ))}
        </TableRow>
    );
}

/**
 * Componente de tabla con drag & drop para ordenamiento manual
 *
 * Características:
 * - Drag and drop para reordenar filas
 * - Búsqueda simple (sin filtros complejos)
 * - Sin paginación (muestra todos los items)
 * - Estadísticas opcionales
 * - Botón de creación opcional
 * - Indicador de cambios sin guardar
 */
function SortableTableComponent<T extends { id: number | string; sort_order?: number }>({
    title,
    description,
    data,
    columns,
    stats,
    createUrl,
    createLabel = 'Crear',
    searchable = true,
    searchPlaceholder = 'Buscar...',
    onReorder,
    onRefresh,
    isRefreshing = false,
    isSaving = false,
    renderMobileCard,
    breakpoint = 'lg',
}: SortableTableProps<T>) {
    const [search, setSearch] = useState('');
    const [localItems, setLocalItems] = useState<T[]>(data);
    const [hasChanges, setHasChanges] = useState(false);

    const breakpointClass = breakpoint + ':';

    const sensors = useSensors(
        useSensor(PointerSensor),
        useSensor(KeyboardSensor, {
            coordinateGetter: sortableKeyboardCoordinates,
        })
    );

    // Filtrar items por búsqueda
    const filteredItems = searchable && search.trim()
        ? localItems.filter((item) => {
            const searchLower = search.toLowerCase();
            return Object.values(item).some((value) =>
                String(value).toLowerCase().includes(searchLower)
            );
        })
        : localItems;

    // Sincronizar con data prop cuando cambia
    React.useEffect(() => {
        setLocalItems(data);
        setHasChanges(false);
    }, [data]);

    const handleDragEnd = (event: DragEndEvent) => {
        const { active, over } = event;

        if (over && active.id !== over.id) {
            setLocalItems((items) => {
                const oldIndex = items.findIndex((item) => item.id === active.id);
                const newIndex = items.findIndex((item) => item.id === over.id);

                const newItems = arrayMove(items, oldIndex, newIndex);

                // Actualizar sort_order si existe
                return newItems.map((item, index) => ({
                    ...item,
                    ...(item.sort_order !== undefined ? { sort_order: index + 1 } : {}),
                }));
            });
            setHasChanges(true);
        }
    };

    const handleSaveOrder = () => {
        onReorder(localItems);
    };

    const handleClearSearch = () => {
        setSearch('');
    };

    return (
        <ErrorBoundary context="tabla ordenable" showRetry={true}>
            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                {/* Page Header */}
                <div className="flex items-center justify-between">
                    <div className="space-y-1">
                        <h1 className="text-3xl font-bold tracking-tight">{title}</h1>
                        {description && <p className="text-muted-foreground">{description}</p>}
                    </div>
                    {createUrl && (
                        <Link href={createUrl}>
                            <Button>
                                <Plus className="mr-2 h-4 w-4" />
                                {createLabel}
                            </Button>
                        </Link>
                    )}
                </div>

                {/* Data Table Card */}
                <Card>
                    <CardHeader className="pb-6">
                        <div className="flex flex-col space-y-4">
                            {/* Stats and Actions Row */}
                            <div className="flex flex-wrap items-start justify-between gap-4">
                                {/* Statistics */}
                                {stats && stats.length > 0 && (
                                    <div className="flex min-w-0 flex-wrap items-center gap-x-4 gap-y-2 text-sm text-muted-foreground">
                                        {stats.map((stat, index) => (
                                            <div key={index} className="flex max-w-[200px] min-w-0 flex-shrink-0 items-center gap-2">
                                                {React.cloneElement(stat.icon as React.ReactElement<{ className?: string }>, {
                                                    className: `flex-shrink-0 ${(stat.icon as React.ReactElement<{ className?: string }>).props.className || ''}`,
                                                })}
                                                <span className="flex min-w-0 items-center gap-1 overflow-hidden">
                                                    <span className="truncate overflow-hidden text-ellipsis lowercase" title={stat.title}>
                                                        {stat.title}
                                                    </span>
                                                    <span className="font-medium whitespace-nowrap text-foreground tabular-nums" title={String(stat.value)}>
                                                        {stat.value}
                                                    </span>
                                                </span>
                                            </div>
                                        ))}
                                    </div>
                                )}

                                {/* Refresh Button */}
                                {onRefresh && (
                                    <div className="flex flex-shrink-0 flex-col items-end gap-1">
                                        <Button variant="ghost" size="sm" onClick={onRefresh} disabled={isRefreshing} className="h-8 px-2">
                                            <RefreshCw className={`mr-1 h-4 w-4 ${isRefreshing ? 'animate-spin' : ''}`} />
                                            {isRefreshing ? 'Sincronizando...' : 'Sincronizar'}
                                        </Button>
                                        <span className="text-xs text-muted-foreground">
                                            Última:{' '}
                                            {new Date().toLocaleString('es-GT', {
                                                hour: '2-digit',
                                                minute: '2-digit',
                                                second: '2-digit',
                                                hour12: true,
                                            })}
                                        </span>
                                    </div>
                                )}
                            </div>
                        </div>
                    </CardHeader>

                    <CardContent>
                        {/* Search */}
                        {searchable && (
                            <div className="mb-6">
                                <Label htmlFor="search" className="sr-only">
                                    Buscar
                                </Label>
                                <div className="relative">
                                    <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                    <Input
                                        id="search"
                                        placeholder={searchPlaceholder}
                                        value={search}
                                        onChange={(e) => setSearch(e.target.value)}
                                        className="pl-10 pr-10"
                                    />
                                    {search && (
                                        <button
                                            type="button"
                                            onClick={handleClearSearch}
                                            className="absolute top-1/2 right-3 -translate-y-1/2 text-muted-foreground hover:text-foreground transition-colors"
                                        >
                                            <X className="h-4 w-4" />
                                        </button>
                                    )}
                                </div>
                            </div>
                        )}

                        {/* Desktop Table View */}
                        <div className={`hidden ${breakpointClass}block`}>
                            <div className="rounded-md border">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead className="w-16 text-center"></TableHead>
                                            {columns.map((column) => (
                                                <TableHead
                                                    key={column.key}
                                                    className={`${column.width || ''} ${column.textAlign ? `text-${column.textAlign}` : 'text-left'} ${column.className || ''}`}
                                                >
                                                    {column.title}
                                                </TableHead>
                                            ))}
                                        </TableRow>
                                    </TableHeader>
                                    <DndContext sensors={sensors} collisionDetection={closestCenter} onDragEnd={handleDragEnd}>
                                        <SortableContext items={filteredItems.map(item => item.id)} strategy={verticalListSortingStrategy}>
                                            <TableBody>
                                                {filteredItems.length === 0 ? (
                                                    <TableRow>
                                                        <TableCell colSpan={columns.length + 1} className="h-40 text-center">
                                                            <div className="flex flex-col items-center justify-center space-y-2">
                                                                <p className="text-sm text-muted-foreground">No se encontraron resultados</p>
                                                                {search && (
                                                                    <p className="text-xs text-muted-foreground">
                                                                        Intenta con términos de búsqueda diferentes
                                                                    </p>
                                                                )}
                                                            </div>
                                                        </TableCell>
                                                    </TableRow>
                                                ) : (
                                                    filteredItems.map((item) => (
                                                        <SortableRow key={item.id} item={item} columns={columns} />
                                                    ))
                                                )}
                                            </TableBody>
                                        </SortableContext>
                                    </DndContext>
                                </Table>
                            </div>

                            {/* Info message and Save Button */}
                            <div className="mt-4 flex items-center justify-between gap-4">
                                <div className="text-sm text-muted-foreground">
                                    Arrastra y suelta las filas para cambiar el orden
                                </div>

                                {hasChanges && (
                                    <Button onClick={handleSaveOrder} disabled={isSaving}>
                                        <Save className="mr-2 h-4 w-4" />
                                        {isSaving ? 'Guardando...' : 'Guardar Orden'}
                                    </Button>
                                )}
                            </div>
                        </div>

                        {/* Mobile Card View */}
                        {renderMobileCard && (
                            <div className={`${breakpointClass}hidden`}>
                                <div className="grid gap-4">
                                    {filteredItems.length === 0 ? (
                                        <div className="flex flex-col items-center justify-center space-y-3 py-16">
                                            <p className="text-base text-muted-foreground">No se encontraron resultados</p>
                                            {search && (
                                                <p className="text-center text-sm text-muted-foreground">
                                                    Intenta con términos de búsqueda diferentes
                                                </p>
                                            )}
                                        </div>
                                    ) : (
                                        filteredItems.map((item) => <div key={item.id}>{renderMobileCard(item)}</div>)
                                    )}
                                </div>

                                {/* Info message - Mobile only */}
                                <div className="mt-4 text-center text-sm text-muted-foreground">
                                    El reordenamiento está disponible en la vista de escritorio
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </ErrorBoundary>
    );
}

// Export con tipado genérico
export const SortableTable = SortableTableComponent as <T extends { id: number | string; sort_order?: number }>(
    props: SortableTableProps<T>
) => React.ReactElement;
