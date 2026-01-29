import { closestCenter, DndContext, DragEndEvent, KeyboardSensor, PointerSensor, useSensor, useSensors } from '@dnd-kit/core';
import { arrayMove, SortableContext, sortableKeyboardCoordinates, useSortable, verticalListSortingStrategy } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { format } from 'date-fns';
import { Box, Calendar, CalendarDays, ChevronDown, ChevronRight, Clock, GripVertical, Layers, Package, Pencil, Save } from 'lucide-react';
import React, { useEffect, useState } from 'react';

import { ErrorBoundary } from '@/components/ErrorBoundary';
import { ACTIVE_STATUS_CONFIGS, StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { DatePicker } from '@/components/ui/date-picker';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Switch } from '@/components/ui/switch';

const WEEKDAYS = [
    { value: 1, label: 'L', fullName: 'Lunes' },
    { value: 2, label: 'M', fullName: 'Martes' },
    { value: 3, label: 'X', fullName: 'Miércoles' },
    { value: 4, label: 'J', fullName: 'Jueves' },
    { value: 5, label: 'V', fullName: 'Viernes' },
    { value: 6, label: 'S', fullName: 'Sábado' },
    { value: 7, label: 'D', fullName: 'Domingo' },
];

interface MenuCategory {
    id: number;
    name: string;
    is_active: boolean;
    is_combo_category: boolean;
    sort_order: number;
}

interface BadgeTypeData {
    id: number;
    name: string;
    color: string;
    is_active?: boolean;
}

interface ItemBadge {
    id?: number;
    badge_type_id: number;
    validity_type: 'permanent' | 'date_range' | 'weekdays';
    valid_from: string | null;
    valid_until: string | null;
    weekdays: number[] | null;
    badge_type: BadgeTypeData;
}

interface MenuItem {
    id: number;
    name: string;
    image: string | null;
    is_active: boolean;
    sort_order: number;
    badges?: ItemBadge[];
}

interface MenuGroup {
    category: MenuCategory;
    items: MenuItem[];
    item_type: 'product' | 'combo';
}

interface Stats {
    total_categories: number;
    active_categories: number;
    total_products: number;
    total_combos: number;
}

interface HierarchicalSortableMenuProps {
    menuStructure: MenuGroup[];
    badgeTypes: BadgeTypeData[];
    stats: Stats;
    onReorderCategories: (categories: MenuCategory[]) => void;
    onReorderItems: (items: MenuItem[], categoryId: number, itemType: 'product' | 'combo') => void;
    onUpdateBadges: (itemId: number, itemType: 'product' | 'combo', badges: Omit<ItemBadge, 'badge_type'>[]) => void;
    onToggleItem?: (itemId: number, itemType: 'product' | 'combo') => void;
    onToggleCategory?: (categoryId: number) => void;
    isSavingCategories?: boolean;
    isSavingItems?: boolean;
    isSavingBadges?: boolean;
}

/**
 * Item sortable individual (producto o combo)
 */
function SortableMenuItem({
    item,
    onBadgeClick,
    onToggle,
}: {
    item: MenuItem;
    onBadgeClick: () => void;
    onToggle?: () => void;
}) {
    const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({ id: `item-${item.id}` });

    const style = {
        transform: CSS.Transform.toString(transform),
        transition,
        opacity: isDragging ? 0.5 : 1,
    };

    return (
        <div
            ref={setNodeRef}
            style={style}
            className={`flex items-center gap-3 border-b px-6 py-3 last:border-b-0 ${isDragging ? 'bg-muted/50 shadow-lg' : 'hover:bg-muted/30'}`}
        >
            <button
                className="cursor-grab text-muted-foreground transition-colors hover:text-foreground active:cursor-grabbing"
                {...attributes}
                {...listeners}
            >
                <GripVertical className="h-4 w-4" />
            </button>
            {item.image && <img src={item.image} alt={item.name} className="h-8 w-8 rounded object-cover" />}
            <span className="text-sm font-medium">{item.name}</span>

            {/* Badges display - junto al nombre */}
            {item.badges && item.badges.length > 0 && (
                <div className="flex items-center gap-1">
                    {item.badges.map((badge) => {
                        const getValidityTitle = () => {
                            if (badge.validity_type === 'permanent') return 'Permanente';
                            if (badge.validity_type === 'date_range') return `${badge.valid_from} - ${badge.valid_until}`;
                            if (badge.validity_type === 'weekdays' && badge.weekdays) {
                                return badge.weekdays.map((d) => WEEKDAYS.find((w) => w.value === d)?.label).join(', ');
                            }
                            return '';
                        };

                        const ValidityIcon = badge.validity_type === 'date_range' ? Calendar : badge.validity_type === 'weekdays' ? CalendarDays : Clock;
                        const isInactive = badge.badge_type.is_active === false;

                        return (
                            <span
                                key={badge.badge_type_id}
                                className={`inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-medium ${isInactive ? 'line-through opacity-50' : 'text-white'}`}
                                style={{ backgroundColor: badge.badge_type.color }}
                                title={isInactive ? `${getValidityTitle()} (Badge inactivo)` : getValidityTitle()}
                            >
                                {ValidityIcon && <ValidityIcon className="h-2.5 w-2.5" />}
                                {badge.badge_type.name}
                            </span>
                        );
                    })}
                </div>
            )}

            {/* Spacer */}
            <div className="flex-1" />

            <button
                onClick={onBadgeClick}
                className="rounded p-1 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                title={item.badges && item.badges.length > 0 ? 'Editar badges' : 'Agregar badges'}
            >
                <Pencil className="h-3.5 w-3.5" />
            </button>

            {onToggle && <Switch checked={item.is_active} onCheckedChange={onToggle} />}
        </div>
    );
}

interface BadgeConfig {
    validity_type: 'permanent' | 'date_range' | 'weekdays';
    valid_from: string;
    valid_until: string;
    weekdays: number[];
}

function getValiditySummary(config: BadgeConfig): string {
    if (config.validity_type === 'permanent') return 'Permanente';
    if (config.validity_type === 'date_range') {
        if (config.valid_from && config.valid_until) {
            return `${config.valid_from} → ${config.valid_until}`;
        }
        if (config.valid_from) return `Desde ${config.valid_from}`;
        if (config.valid_until) return `Hasta ${config.valid_until}`;
        return 'Fechas no definidas';
    }
    if (config.validity_type === 'weekdays') {
        if (config.weekdays.length === 0) return 'Sin días seleccionados';
        if (config.weekdays.length === 7) return 'Todos los días';
        return config.weekdays
            .sort((a, b) => a - b)
            .map((d) => WEEKDAYS.find((w) => w.value === d)?.label)
            .join(' ');
    }
    return '';
}

/**
 * Modal para gestionar badges de un item
 */
function BadgeManagerDialog({
    isOpen,
    onClose,
    item,
    badgeTypes,
    onSave,
    isSaving,
}: {
    isOpen: boolean;
    onClose: () => void;
    item: MenuItem | null;
    badgeTypes: BadgeTypeData[];
    onSave: (badges: Omit<ItemBadge, 'badge_type'>[]) => void;
    isSaving: boolean;
}) {
    const [selectedBadges, setSelectedBadges] = useState<Map<number, BadgeConfig>>(new Map());
    const [editingBadgeId, setEditingBadgeId] = useState<number | null>(null);

    useEffect(() => {
        if (item?.badges) {
            const badgeMap = new Map<number, BadgeConfig>();
            item.badges.forEach((b) => {
                badgeMap.set(b.badge_type_id, {
                    validity_type: b.validity_type,
                    valid_from: b.valid_from || '',
                    valid_until: b.valid_until || '',
                    weekdays: b.weekdays || [],
                });
            });
            setSelectedBadges(badgeMap);
        } else {
            setSelectedBadges(new Map());
        }
        setEditingBadgeId(null);
    }, [item]);

    const toggleBadge = (badgeTypeId: number) => {
        setSelectedBadges((prev) => {
            const newMap = new Map(prev);
            if (newMap.has(badgeTypeId)) {
                newMap.delete(badgeTypeId);
                if (editingBadgeId === badgeTypeId) {
                    setEditingBadgeId(null);
                }
            } else {
                newMap.set(badgeTypeId, { validity_type: 'permanent', valid_from: '', valid_until: '', weekdays: [] });
                setEditingBadgeId(badgeTypeId);
            }
            return newMap;
        });
    };

    const updateBadgeConfig = (badgeTypeId: number, field: keyof BadgeConfig, value: string | number[]) => {
        setSelectedBadges((prev) => {
            const newMap = new Map(prev);
            const current = newMap.get(badgeTypeId);
            if (current) {
                newMap.set(badgeTypeId, { ...current, [field]: value });
            }
            return newMap;
        });
    };

    const toggleWeekday = (badgeTypeId: number, day: number) => {
        const current = selectedBadges.get(badgeTypeId);
        if (!current) return;

        const newWeekdays = current.weekdays.includes(day) ? current.weekdays.filter((d) => d !== day) : [...current.weekdays, day].sort((a, b) => a - b);

        updateBadgeConfig(badgeTypeId, 'weekdays', newWeekdays);
    };

    const getValidationErrors = (): Map<number, string> => {
        const errors = new Map<number, string>();
        selectedBadges.forEach((config, badgeTypeId) => {
            if (config.validity_type === 'date_range') {
                if (!config.valid_from || !config.valid_until) {
                    errors.set(badgeTypeId, 'Fecha inicio y fin son obligatorias');
                }
            } else if (config.validity_type === 'weekdays') {
                if (config.weekdays.length === 0) {
                    errors.set(badgeTypeId, 'Selecciona al menos un día');
                }
            }
        });
        return errors;
    };

    const validationErrors = getValidationErrors();
    const hasErrors = validationErrors.size > 0;

    const handleSave = () => {
        if (hasErrors) return;

        const badges: Omit<ItemBadge, 'badge_type'>[] = [];
        selectedBadges.forEach((config, badgeTypeId) => {
            badges.push({
                badge_type_id: badgeTypeId,
                validity_type: config.validity_type,
                valid_from: config.validity_type === 'date_range' ? config.valid_from || null : null,
                valid_until: config.validity_type === 'date_range' ? config.valid_until || null : null,
                weekdays: config.validity_type === 'weekdays' ? config.weekdays : null,
            });
        });
        onSave(badges);
    };

    const editingBadge = editingBadgeId ? badgeTypes.find((b) => b.id === editingBadgeId) : null;
    const editingConfig = editingBadgeId ? selectedBadges.get(editingBadgeId) : null;

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="max-w-lg">
                <DialogHeader>
                    <DialogTitle className="text-base">Badges: {item?.name}</DialogTitle>
                </DialogHeader>

                <div className="space-y-4">
                    {badgeTypes.length === 0 ? (
                        <p className="text-center text-sm text-muted-foreground">
                            No hay badges.{' '}
                            <a href="/menu/badge-types/create" className="text-primary hover:underline">
                                Crear uno
                            </a>
                        </p>
                    ) : (
                        <>
                            {/* Badges disponibles */}
                            <div>
                                <p className="mb-2 text-xs font-medium text-muted-foreground">Selecciona badges:</p>
                                <div className="flex flex-wrap gap-2">
                                    {badgeTypes.map((badgeType) => {
                                        const isSelected = selectedBadges.has(badgeType.id);
                                        const isEditing = editingBadgeId === badgeType.id;
                                        const config = selectedBadges.get(badgeType.id);

                                        const ValidityIcon =
                                            config?.validity_type === 'date_range' ? Calendar : config?.validity_type === 'weekdays' ? CalendarDays : Clock;

                                        return (
                                            <button
                                                key={badgeType.id}
                                                onClick={() => (isSelected ? setEditingBadgeId(isEditing ? null : badgeType.id) : toggleBadge(badgeType.id))}
                                                className={`relative flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium transition-all ${
                                                    isSelected
                                                        ? 'text-white ring-2 ring-offset-2 ring-offset-background'
                                                        : 'bg-muted text-muted-foreground hover:bg-muted/80'
                                                } ${isEditing ? 'ring-primary' : 'ring-transparent'}`}
                                                style={isSelected ? { backgroundColor: badgeType.color } : {}}
                                            >
                                                {isSelected && <ValidityIcon className="h-3 w-3" />}
                                                {badgeType.name}
                                                {isSelected && (
                                                    <span
                                                        onClick={(e) => {
                                                            e.stopPropagation();
                                                            toggleBadge(badgeType.id);
                                                        }}
                                                        className="ml-0.5 inline-flex cursor-pointer hover:opacity-70"
                                                    >
                                                        ×
                                                    </span>
                                                )}
                                            </button>
                                        );
                                    })}
                                </div>
                            </div>

                            {/* Resumen de badges seleccionados */}
                            {selectedBadges.size > 0 && !editingBadgeId && (
                                <div className="space-y-2">
                                    <p className="text-xs font-medium text-muted-foreground">Configuración actual:</p>
                                    <div className="space-y-1.5">
                                        {Array.from(selectedBadges.entries()).map(([badgeTypeId, config]) => {
                                            const badgeType = badgeTypes.find((b) => b.id === badgeTypeId);
                                            if (!badgeType) return null;

                                            const ValidityIcon =
                                                config.validity_type === 'date_range'
                                                    ? Calendar
                                                    : config.validity_type === 'weekdays'
                                                      ? CalendarDays
                                                      : Clock;

                                            return (
                                                <div
                                                    key={badgeTypeId}
                                                    onClick={() => setEditingBadgeId(badgeTypeId)}
                                                    className="flex cursor-pointer items-center gap-2 rounded-md border bg-background p-2 transition-colors hover:bg-muted/50"
                                                >
                                                    <span
                                                        className="inline-flex h-5 items-center gap-1 rounded-full px-2 text-[10px] font-medium text-white"
                                                        style={{ backgroundColor: badgeType.color }}
                                                    >
                                                        {badgeType.name}
                                                    </span>
                                                    <ValidityIcon className="h-3.5 w-3.5 text-muted-foreground" />
                                                    <span className={`flex-1 text-xs ${validationErrors.has(badgeTypeId) ? 'text-destructive' : 'text-muted-foreground'}`}>
                                                        {validationErrors.get(badgeTypeId) || getValiditySummary(config)}
                                                    </span>
                                                    <Pencil className="h-3 w-3 text-muted-foreground" />
                                                </div>
                                            );
                                        })}
                                    </div>
                                </div>
                            )}

                            {/* Config del badge seleccionado */}
                            {editingBadge && editingConfig && (
                                <div className="rounded-lg border bg-muted/30 p-4">
                                    <div className="mb-3 flex items-center justify-between">
                                        <div className="flex items-center gap-2">
                                            <span className="inline-block h-3 w-3 rounded-full" style={{ backgroundColor: editingBadge.color }} />
                                            <span className="text-sm font-medium">{editingBadge.name}</span>
                                        </div>
                                        <button
                                            onClick={() => setEditingBadgeId(null)}
                                            className="text-xs text-muted-foreground hover:text-foreground"
                                        >
                                            Cerrar
                                        </button>
                                    </div>

                                    <div className="space-y-4">
                                        {/* Tipo de vigencia */}
                                        <div className="grid grid-cols-3 gap-2">
                                            <button
                                                type="button"
                                                onClick={() => updateBadgeConfig(editingBadgeId!, 'validity_type', 'permanent')}
                                                className={`flex flex-col items-center gap-1 rounded-md p-2 text-xs font-medium transition-colors ${
                                                    editingConfig.validity_type === 'permanent'
                                                        ? 'bg-primary text-primary-foreground'
                                                        : 'bg-muted text-muted-foreground hover:bg-muted/80'
                                                }`}
                                            >
                                                <Clock className="h-4 w-4" />
                                                Permanente
                                            </button>
                                            <button
                                                type="button"
                                                onClick={() => updateBadgeConfig(editingBadgeId!, 'validity_type', 'date_range')}
                                                className={`flex flex-col items-center gap-1 rounded-md p-2 text-xs font-medium transition-colors ${
                                                    editingConfig.validity_type === 'date_range'
                                                        ? 'bg-primary text-primary-foreground'
                                                        : 'bg-muted text-muted-foreground hover:bg-muted/80'
                                                }`}
                                            >
                                                <Calendar className="h-4 w-4" />
                                                Fechas
                                            </button>
                                            <button
                                                type="button"
                                                onClick={() => updateBadgeConfig(editingBadgeId!, 'validity_type', 'weekdays')}
                                                className={`flex flex-col items-center gap-1 rounded-md p-2 text-xs font-medium transition-colors ${
                                                    editingConfig.validity_type === 'weekdays'
                                                        ? 'bg-primary text-primary-foreground'
                                                        : 'bg-muted text-muted-foreground hover:bg-muted/80'
                                                }`}
                                            >
                                                <CalendarDays className="h-4 w-4" />
                                                Días
                                            </button>
                                        </div>

                                        {/* Campos de fecha */}
                                        {editingConfig.validity_type === 'date_range' && (
                                            <div className="space-y-2">
                                                <div className="grid grid-cols-2 gap-2">
                                                    <DatePicker
                                                        value={editingConfig.valid_from || undefined}
                                                        onChange={(date) =>
                                                            updateBadgeConfig(editingBadgeId!, 'valid_from', date ? format(date, 'yyyy-MM-dd') : '')
                                                        }
                                                        placeholder="Desde *"
                                                    />
                                                    <DatePicker
                                                        value={editingConfig.valid_until || undefined}
                                                        onChange={(date) =>
                                                            updateBadgeConfig(editingBadgeId!, 'valid_until', date ? format(date, 'yyyy-MM-dd') : '')
                                                        }
                                                        placeholder="Hasta *"
                                                    />
                                                </div>
                                                {(!editingConfig.valid_from || !editingConfig.valid_until) && (
                                                    <p className="text-xs text-destructive">Ambas fechas son obligatorias</p>
                                                )}
                                            </div>
                                        )}

                                        {/* Selector de días de la semana */}
                                        {editingConfig.validity_type === 'weekdays' && (
                                            <div className="space-y-2">
                                                <div className="flex justify-center gap-1.5">
                                                    {WEEKDAYS.map((day) => (
                                                        <button
                                                            key={day.value}
                                                            type="button"
                                                            onClick={() => toggleWeekday(editingBadgeId!, day.value)}
                                                            title={day.fullName}
                                                            className={`flex h-9 w-9 items-center justify-center rounded-md border-2 text-xs font-semibold transition-colors ${
                                                                editingConfig.weekdays.includes(day.value)
                                                                    ? 'border-primary bg-primary text-primary-foreground'
                                                                    : 'border-input bg-background hover:bg-accent hover:text-accent-foreground'
                                                            }`}
                                                        >
                                                            {day.label}
                                                        </button>
                                                    ))}
                                                </div>
                                                {editingConfig.weekdays.length > 0 ? (
                                                    <p className="text-center text-xs text-muted-foreground">
                                                        {editingConfig.weekdays
                                                            .sort((a, b) => a - b)
                                                            .map((d) => WEEKDAYS.find((w) => w.value === d)?.fullName)
                                                            .join(', ')}
                                                    </p>
                                                ) : (
                                                    <p className="text-center text-xs text-destructive">Selecciona al menos un día</p>
                                                )}
                                            </div>
                                        )}
                                    </div>
                                </div>
                            )}
                        </>
                    )}
                </div>

                <DialogFooter className="gap-2 sm:gap-0">
                    <Button variant="outline" size="sm" onClick={onClose}>
                        Cancelar
                    </Button>
                    <Button size="sm" onClick={handleSave} disabled={isSaving || hasErrors}>
                        {isSaving ? 'Guardando...' : 'Guardar'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

/**
 * Sección de categoría sortable (header + items)
 */
function SortableCategorySection({
    group,
    isExpanded,
    onToggle,
    onItemDragEnd,
    onSaveItems,
    onBadgeClick,
    onToggleItem,
    onToggleCategory,
    hasItemChanges,
    isSavingItems,
    sensors,
}: {
    group: MenuGroup;
    isExpanded: boolean;
    onToggle: () => void;
    onItemDragEnd: (event: DragEndEvent) => void;
    onSaveItems: () => void;
    onBadgeClick: (item: MenuItem) => void;
    onToggleItem?: (item: MenuItem) => void;
    onToggleCategory?: () => void;
    hasItemChanges: boolean;
    isSavingItems: boolean;
    sensors: ReturnType<typeof useSensors>;
}) {
    const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({ id: `cat-${group.category.id}` });

    const style = {
        transform: CSS.Transform.toString(transform),
        transition,
        opacity: isDragging ? 0.5 : 1,
    };

    return (
        <div ref={setNodeRef} style={style} className={`border-b last:border-b-0 ${isDragging ? 'shadow-lg' : ''}`}>
            {/* Category Header */}
            <div className={`flex items-center gap-3 px-4 py-3 ${isDragging ? 'bg-primary/10' : 'bg-muted/50'}`}>
                <button
                    className="cursor-grab text-muted-foreground transition-colors hover:text-foreground active:cursor-grabbing"
                    {...attributes}
                    {...listeners}
                >
                    <GripVertical className="h-5 w-5" />
                </button>
                <button onClick={onToggle} className="flex items-center gap-2 text-muted-foreground hover:text-foreground">
                    {isExpanded ? <ChevronDown className="h-4 w-4" /> : <ChevronRight className="h-4 w-4" />}
                </button>
                <div className="flex flex-1 items-center gap-2">
                    {group.category.is_combo_category ? <Box className="h-4 w-4 text-primary" /> : <Package className="h-4 w-4 text-primary" />}
                    <span className="font-semibold">{group.category.name}</span>
                    <span className="text-xs text-muted-foreground">({group.items.length})</span>
                </div>
                {onToggleCategory ? (
                    <Switch checked={group.category.is_active} onCheckedChange={onToggleCategory} />
                ) : (
                    <StatusBadge status={group.category.is_active ? 'active' : 'inactive'} configs={ACTIVE_STATUS_CONFIGS} showIcon={false} />
                )}
            </div>

            {/* Items */}
            {isExpanded && (
                <div className="bg-background">
                    <DndContext sensors={sensors} collisionDetection={closestCenter} onDragEnd={onItemDragEnd}>
                        <SortableContext items={group.items.map((i) => `item-${i.id}`)} strategy={verticalListSortingStrategy}>
                            {group.items.map((item) => (
                                <SortableMenuItem key={item.id} item={item} onBadgeClick={() => onBadgeClick(item)} onToggle={onToggleItem ? () => onToggleItem(item) : undefined} />
                            ))}
                        </SortableContext>
                    </DndContext>

                    {group.items.length === 0 && <div className="px-6 py-4 text-center text-sm text-muted-foreground">Sin items en esta categoría</div>}

                    {/* Save Button for items */}
                    {hasItemChanges && (
                        <div className="flex justify-end border-t bg-muted/30 px-4 py-2">
                            <Button onClick={onSaveItems} disabled={isSavingItems} size="sm">
                                <Save className="mr-2 h-4 w-4" />
                                {isSavingItems ? 'Guardando...' : 'Guardar Orden Items'}
                            </Button>
                        </div>
                    )}
                </div>
            )}
        </div>
    );
}

/**
 * Componente principal de menú jerárquico ordenable
 *
 * Dos niveles de ordenamiento:
 * 1. Categorías (headers arrastrables)
 * 2. Items dentro de cada categoría (productos o combos)
 */
export function HierarchicalSortableMenu({
    menuStructure,
    badgeTypes,
    stats,
    onReorderCategories,
    onReorderItems,
    onUpdateBadges,
    onToggleItem,
    onToggleCategory,
    isSavingCategories = false,
    isSavingItems = false,
    isSavingBadges = false,
}: HierarchicalSortableMenuProps) {
    const [localMenu, setLocalMenu] = useState<MenuGroup[]>(menuStructure);
    const [expandedCategories, setExpandedCategories] = useState<Set<number>>(new Set());
    const [categoriesChanged, setCategoriesChanged] = useState(false);
    const [changedItemCategories, setChangedItemCategories] = useState<Set<number>>(new Set());

    // Badge modal state
    const [badgeModalItem, setBadgeModalItem] = useState<{ item: MenuItem; itemType: 'product' | 'combo' } | null>(null);

    const sensors = useSensors(
        useSensor(PointerSensor),
        useSensor(KeyboardSensor, {
            coordinateGetter: sortableKeyboardCoordinates,
        }),
    );

    // Sincronizar cuando cambian los datos externos
    useEffect(() => {
        setLocalMenu(menuStructure);
        setCategoriesChanged(false);
        setChangedItemCategories(new Set());
    }, [menuStructure]);

    /**
     * Toggle expand/collapse categoría
     */
    const toggleCategory = (categoryId: number) => {
        setExpandedCategories((prev) => {
            const newSet = new Set(prev);
            if (newSet.has(categoryId)) {
                newSet.delete(categoryId);
            } else {
                newSet.add(categoryId);
            }
            return newSet;
        });
    };

    /**
     * Maneja el drag end de categorías
     */
    const handleCategoryDragEnd = (event: DragEndEvent) => {
        const { active, over } = event;

        if (over && active.id !== over.id) {
            setLocalMenu((groups) => {
                const oldIndex = groups.findIndex((g) => `cat-${g.category.id}` === active.id);
                const newIndex = groups.findIndex((g) => `cat-${g.category.id}` === over.id);
                return arrayMove(groups, oldIndex, newIndex);
            });
            setCategoriesChanged(true);
        }
    };

    /**
     * Maneja el drag end de items dentro de una categoría
     */
    const handleItemDragEnd = (event: DragEndEvent, categoryId: number) => {
        const { active, over } = event;

        if (over && active.id !== over.id) {
            setLocalMenu((groups) => {
                return groups.map((group) => {
                    if (group.category.id !== categoryId) return group;

                    const oldIndex = group.items.findIndex((i) => `item-${i.id}` === active.id);
                    const newIndex = group.items.findIndex((i) => `item-${i.id}` === over.id);

                    return {
                        ...group,
                        items: arrayMove(group.items, oldIndex, newIndex),
                    };
                });
            });
            setChangedItemCategories((prev) => new Set(prev).add(categoryId));
        }
    };

    /**
     * Guarda el orden de categorías
     */
    const handleSaveCategories = () => {
        const categories = localMenu.map((g, index) => ({
            ...g.category,
            sort_order: index + 1,
        }));
        onReorderCategories(categories);
        setCategoriesChanged(false);
    };

    /**
     * Guarda el orden de items de una categoría
     */
    const handleSaveItems = (categoryId: number) => {
        const group = localMenu.find((g) => g.category.id === categoryId);
        if (!group) return;

        const items = group.items.map((item, index) => ({
            ...item,
            sort_order: index + 1,
        }));

        onReorderItems(items, categoryId, group.item_type);
        setChangedItemCategories((prev) => {
            const newSet = new Set(prev);
            newSet.delete(categoryId);
            return newSet;
        });
    };

    /**
     * Abre el modal de badges para un item
     */
    const handleOpenBadgeModal = (item: MenuItem, itemType: 'product' | 'combo') => {
        setBadgeModalItem({ item, itemType });
    };

    /**
     * Guarda los badges del item
     */
    const handleSaveBadges = (badges: Omit<ItemBadge, 'badge_type'>[]) => {
        if (!badgeModalItem) return;
        onUpdateBadges(badgeModalItem.item.id, badgeModalItem.itemType, badges);
        setBadgeModalItem(null);
    };

    const menuStats = [
        { title: 'categorías', value: stats.total_categories, icon: <Layers className="h-4 w-4 text-primary" /> },
        { title: 'productos', value: stats.total_products, icon: <Package className="h-4 w-4 text-blue-600" /> },
        { title: 'combos', value: stats.total_combos, icon: <Box className="h-4 w-4 text-green-600" /> },
    ];

    return (
        <ErrorBoundary>
            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                {/* Page Header */}
                <div className="flex items-center justify-between">
                    <div className="space-y-1">
                        <h1 className="text-3xl font-bold tracking-tight">Menú App</h1>
                        <p className="text-muted-foreground">Ordena categorías, productos y asigna badges</p>
                    </div>
                    {categoriesChanged && (
                        <Button onClick={handleSaveCategories} disabled={isSavingCategories}>
                            <Save className="mr-2 h-4 w-4" />
                            {isSavingCategories ? 'Guardando...' : 'Guardar Orden Categorías'}
                        </Button>
                    )}
                </div>

                {/* Stats */}
                <Card>
                    <CardHeader className="pb-4">
                        <div className="flex flex-wrap items-center gap-6 text-sm text-muted-foreground">
                            {menuStats.map((stat, index) => (
                                <div key={index} className="flex items-center gap-2">
                                    {stat.icon}
                                    <span className="lowercase">{stat.title}</span>
                                    <span className="font-medium text-foreground">{stat.value}</span>
                                </div>
                            ))}
                        </div>
                    </CardHeader>

                    <CardContent className="p-0">
                        {/* Categories with items */}
                        <DndContext sensors={sensors} collisionDetection={closestCenter} onDragEnd={handleCategoryDragEnd}>
                            <SortableContext items={localMenu.map((g) => `cat-${g.category.id}`)} strategy={verticalListSortingStrategy}>
                                {localMenu.map((group) => (
                                    <SortableCategorySection
                                        key={group.category.id}
                                        group={group}
                                        isExpanded={expandedCategories.has(group.category.id)}
                                        onToggle={() => toggleCategory(group.category.id)}
                                        onItemDragEnd={(e) => handleItemDragEnd(e, group.category.id)}
                                        onSaveItems={() => handleSaveItems(group.category.id)}
                                        onBadgeClick={(item) => handleOpenBadgeModal(item, group.item_type)}
                                        onToggleItem={onToggleItem ? (item) => onToggleItem(item.id, group.item_type) : undefined}
                                        onToggleCategory={onToggleCategory ? () => onToggleCategory(group.category.id) : undefined}
                                        hasItemChanges={changedItemCategories.has(group.category.id)}
                                        isSavingItems={isSavingItems}
                                        sensors={sensors}
                                    />
                                ))}
                            </SortableContext>
                        </DndContext>

                        {localMenu.length === 0 && (
                            <div className="p-12 text-center text-muted-foreground">
                                <p>No hay categorías configuradas</p>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>

            {/* Badge Manager Modal */}
            <BadgeManagerDialog
                isOpen={badgeModalItem !== null}
                onClose={() => setBadgeModalItem(null)}
                item={badgeModalItem?.item || null}
                badgeTypes={badgeTypes}
                onSave={handleSaveBadges}
                isSaving={isSavingBadges}
            />
        </ErrorBoundary>
    );
}
