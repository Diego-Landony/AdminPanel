import { Link } from '@inertiajs/react';
import { Plus, RefreshCw, Search, X } from 'lucide-react';
import React, { useState, useEffect } from 'react';
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
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { GripVertical, Save } from 'lucide-react';

interface GroupedSortableTableColumn<T> {
    key: string;
    title: string;
    width?: string;
    textAlign?: 'left' | 'center' | 'right';
    render?: (item: T) => React.ReactNode;
    className?: string;
}

interface GroupedSortableTableStat {
    title: string;
    value: number | string;
    icon: React.ReactNode;
}

interface CategoryGroup<T> {
    category: {
        id: number | null;
        name: string;
    };
    products: T[];
}

interface GroupedSortableTableProps<T extends { id: number | string; sort_order?: number }> {
    title: string;
    description?: string;
    groupedData: CategoryGroup<T>[];
    columns: GroupedSortableTableColumn<T>[];
    stats?: GroupedSortableTableStat[];
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
    columns: GroupedSortableTableColumn<T>[];
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
 * Componente de tabla agrupada con drag & drop independiente por grupo
 *
 * Características:
 * - Drag and drop para reordenar filas DENTRO de cada grupo
 * - Agrupación por categorías con headers/divisores
 * - Búsqueda simple (sin filtros complejos)
 * - Sin paginación (muestra todos los items)
 * - Estadísticas opcionales
 * - Botón de creación opcional
 * - Indicador de cambios sin guardar
 */
function GroupedSortableTableComponent<T extends { id: number | string; sort_order?: number }>({
    title,
    description,
    groupedData,
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
}: GroupedSortableTableProps<T>) {
    const [search, setSearch] = useState('');
    const [localGroups, setLocalGroups] = useState<CategoryGroup<T>[]>(groupedData);
    const [changedCategories, setChangedCategories] = useState<Set<number | null>>(new Set());

    const breakpointClass = breakpoint + ':';

    const sensors = useSensors(
        useSensor(PointerSensor),
        useSensor(KeyboardSensor, {
            coordinateGetter: sortableKeyboardCoordinates,
        })
    );

    // Sincronizar cuando cambian los datos externos
    useEffect(() => {
        setLocalGroups(groupedData);
        setChangedCategories(new Set());
    }, [groupedData]);

    /**
     * Maneja el drag end para un grupo específico
     */
    const handleDragEnd = (event: DragEndEvent, categoryId: number | null) => {
        const { active, over } = event;

        if (over && active.id !== over.id) {
            setLocalGroups((groups) => {
                return groups.map((group) => {
                    if (group.category.id !== categoryId) {
                        return group;
                    }

                    const oldIndex = group.products.findIndex((item) => item.id === active.id);
                    const newIndex = group.products.findIndex((item) => item.id === over.id);

                    return {
                        ...group,
                        products: arrayMove(group.products, oldIndex, newIndex),
                    };
                });
            });

            // Marcar esta categoría como modificada
            setChangedCategories((prev) => new Set(prev).add(categoryId));
        }
    };

    /**
     * Guarda el nuevo orden para una categoría específica
     */
    const handleSaveOrder = (categoryId: number | null) => {
        // Encontrar la categoría modificada
        const categoryGroup = localGroups.find((group) => group.category.id === categoryId);
        if (!categoryGroup) return;

        // Preparar productos de esta categoría con su nuevo sort_order
        const productsToSave: T[] = categoryGroup.products.map((product, index) => ({
            ...product,
            sort_order: index + 1,
        } as T));

        // Enviar al backend
        onReorder(productsToSave);

        // Remover esta categoría de las modificadas
        setChangedCategories((prev) => {
            const newSet = new Set(prev);
            newSet.delete(categoryId);
            return newSet;
        });
    };

    /**
     * Filtrar grupos por búsqueda
     */
    const filteredGroups = searchable && search
        ? localGroups.map((group) => ({
            ...group,
            products: group.products.filter((item: T) =>
                (item as { name?: string }).name?.toLowerCase().includes(search.toLowerCase())
            ),
        })).filter((group) => group.products.length > 0)
        : localGroups;

    return (
        <ErrorBoundary>
            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                {/* Page Header */}
                <div className="flex items-center justify-between">
                    <div className="space-y-1">
                        <h1 className="text-3xl font-bold tracking-tight">{title}</h1>
                        {description && <p className="text-muted-foreground">{description}</p>}
                    </div>
                    <div className="flex items-center gap-2">
                        {onRefresh && (
                            <Button variant="outline" size="sm" onClick={onRefresh} disabled={isRefreshing}>
                                <RefreshCw className={`h-4 w-4 ${isRefreshing ? 'animate-spin' : ''}`} />
                            </Button>
                        )}
                        {createUrl && (
                            <Link href={createUrl}>
                                <Button>
                                    <Plus className="mr-2 h-4 w-4" />
                                    {createLabel}
                                </Button>
                            </Link>
                        )}
                    </div>
                </div>

                {/* Data Table Card */}
                <Card>
                    <CardHeader className="pb-6">
                        <div className="flex flex-col space-y-4">

                            {/* Stats */}
                            {stats && stats.length > 0 && (
                                <div className="flex flex-wrap items-center gap-6 text-sm text-muted-foreground">
                                    {stats.map((stat, index) => (
                                        <div key={index} className="flex items-center gap-2">
                                            {stat.icon}
                                            <span className="lowercase">{stat.title}</span>
                                            <span className="font-medium text-foreground">{stat.value}</span>
                                        </div>
                                    ))}
                                </div>
                            )}

                            {/* Search */}
                            {searchable && (
                                <div className="relative">
                                    <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                                    <Input
                                        type="text"
                                        placeholder={searchPlaceholder}
                                        value={search}
                                        onChange={(e) => setSearch(e.target.value)}
                                        className="pl-10 pr-10"
                                    />
                                    {search && (
                                        <button
                                            onClick={() => setSearch('')}
                                            className="absolute right-3 top-1/2 transform -translate-y-1/2 text-muted-foreground hover:text-foreground transition-colors"
                                        >
                                            <X className="h-4 w-4" />
                                        </button>
                                    )}
                                </div>
                            )}
                        </div>
                    </CardHeader>

                    <CardContent className="p-0">
                        {/* Desktop view */}
                        <div className={`hidden ${breakpointClass}block`}>
                            {filteredGroups.map((group) => (
                                <div key={group.category.id ?? 'no-category'} className="border-b last:border-b-0">
                                    {/* Category Header */}
                                    <div className="bg-muted/50 px-6 py-3 border-b">
                                        <h3 className="font-semibold text-sm text-foreground">{group.category.name}</h3>
                                    </div>

                                    {/* Products in this category */}
                                    <DndContext
                                        sensors={sensors}
                                        collisionDetection={closestCenter}
                                        onDragEnd={(e) => handleDragEnd(e, group.category.id)}
                                    >
                                        <Table>
                                            <TableHeader>
                                                <TableRow>
                                                    <TableHead className="w-16 text-center"></TableHead>
                                                    {columns.map((column) => (
                                                        <TableHead
                                                            key={column.key}
                                                            className={`${column.width || ''} ${column.textAlign ? `text-${column.textAlign}` : 'text-left'}`}
                                                        >
                                                            {column.title}
                                                        </TableHead>
                                                    ))}
                                                </TableRow>
                                            </TableHeader>
                                            <TableBody>
                                                <SortableContext items={group.products.map((p) => p.id)} strategy={verticalListSortingStrategy}>
                                                    {group.products.map((product) => (
                                                        <SortableRow key={product.id} item={product} columns={columns} />
                                                    ))}
                                                </SortableContext>
                                            </TableBody>
                                        </Table>
                                    </DndContext>

                                    {/* Save Button for this group - solo si esta categoría cambió */}
                                    {changedCategories.has(group.category.id) && (
                                        <div className="flex items-center justify-end gap-4 px-6 py-3 bg-muted/30">
                                            <Button onClick={() => handleSaveOrder(group.category.id)} disabled={isSaving} size="sm">
                                                <Save className="mr-2 h-4 w-4" />
                                                {isSaving ? 'Guardando...' : 'Guardar Orden'}
                                            </Button>
                                        </div>
                                    )}
                                </div>
                            ))}

                            {filteredGroups.length === 0 && (
                                <div className="p-12 text-center text-muted-foreground">
                                    <p>No se encontraron resultados</p>
                                </div>
                            )}
                        </div>

                        {/* Mobile view */}
                        {renderMobileCard && (
                            <div className={`${breakpointClass}hidden`}>
                                <div className="space-y-6">
                                    {filteredGroups.map((group) => (
                                        <div key={group.category.id ?? 'no-category'} className="space-y-4">
                                            {/* Category Header */}
                                            <div className="bg-muted/50 px-4 py-2 rounded-md">
                                                <h3 className="font-semibold text-sm text-foreground">{group.category.name}</h3>
                                            </div>

                                            {/* Products */}
                                            <div className="grid gap-4">
                                                {group.products.map((product) => (
                                                    <div key={product.id}>{renderMobileCard(product)}</div>
                                                ))}
                                            </div>

                                            {/* Save Button for this group (mobile) - solo si esta categoría cambió */}
                                            {changedCategories.has(group.category.id) && (
                                                <div className="flex justify-end">
                                                    <Button onClick={() => handleSaveOrder(group.category.id)} disabled={isSaving} size="sm">
                                                        <Save className="mr-2 h-4 w-4" />
                                                        {isSaving ? 'Guardando...' : 'Guardar Orden'}
                                                    </Button>
                                                </div>
                                            )}
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </ErrorBoundary>
    );
}

export function GroupedSortableTable<T extends { id: number | string; sort_order?: number }>(
    props: GroupedSortableTableProps<T>
) {
    return <GroupedSortableTableComponent {...props} />;
}
