/**
 * Hook para gestionar el ordenamiento del menú
 * Maneja drag & drop de categorías e items
 */

import { DragEndEvent, KeyboardSensor, PointerSensor, useSensor, useSensors } from '@dnd-kit/core';
import { arrayMove, sortableKeyboardCoordinates } from '@dnd-kit/sortable';
import { useCallback, useEffect, useState } from 'react';

import type { MenuGroup, MenuCategory, MenuItem } from '@/types/menu';

export interface UseMenuSortOptions {
    initialMenu: MenuGroup[];
    onReorderCategories: (categories: MenuCategory[]) => void;
    onReorderItems: (items: MenuItem[], categoryId: number, itemType: 'product' | 'combo') => void;
}

export interface UseMenuSortReturn {
    localMenu: MenuGroup[];
    categoriesChanged: boolean;
    changedItemCategories: Set<number>;
    sensors: ReturnType<typeof useSensors>;

    handleCategoryDragEnd: (event: DragEndEvent) => void;
    handleItemDragEnd: (event: DragEndEvent, categoryId: number) => void;
    handleSaveCategories: () => void;
    handleSaveItems: (categoryId: number) => void;
}

export function useMenuSort({
    initialMenu,
    onReorderCategories,
    onReorderItems,
}: UseMenuSortOptions): UseMenuSortReturn {
    const [localMenu, setLocalMenu] = useState<MenuGroup[]>(initialMenu);
    const [categoriesChanged, setCategoriesChanged] = useState(false);
    const [changedItemCategories, setChangedItemCategories] = useState<Set<number>>(new Set());

    // DnD Sensors
    const sensors = useSensors(
        useSensor(PointerSensor),
        useSensor(KeyboardSensor, {
            coordinateGetter: sortableKeyboardCoordinates,
        })
    );

    // Sincronizar cuando cambian los datos externos
    useEffect(() => {
        setLocalMenu(initialMenu);
        setCategoriesChanged(false);
        setChangedItemCategories(new Set());
    }, [initialMenu]);

    // Maneja el drag end de categorías
    const handleCategoryDragEnd = useCallback((event: DragEndEvent) => {
        const { active, over } = event;

        if (over && active.id !== over.id) {
            setLocalMenu((groups) => {
                const oldIndex = groups.findIndex((g) => `cat-${g.category.id}` === active.id);
                const newIndex = groups.findIndex((g) => `cat-${g.category.id}` === over.id);
                return arrayMove(groups, oldIndex, newIndex);
            });
            setCategoriesChanged(true);
        }
    }, []);

    // Maneja el drag end de items dentro de una categoría
    const handleItemDragEnd = useCallback((event: DragEndEvent, categoryId: number) => {
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
    }, []);

    // Guarda el orden de categorías
    const handleSaveCategories = useCallback(() => {
        const categories = localMenu.map((g, index) => ({
            ...g.category,
            sort_order: index + 1,
        }));
        onReorderCategories(categories);
        setCategoriesChanged(false);
    }, [localMenu, onReorderCategories]);

    // Guarda el orden de items de una categoría
    const handleSaveItems = useCallback(
        (categoryId: number) => {
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
        },
        [localMenu, onReorderItems]
    );

    return {
        localMenu,
        categoriesChanged,
        changedItemCategories,
        sensors,

        handleCategoryDragEnd,
        handleItemDragEnd,
        handleSaveCategories,
        handleSaveItems,
    };
}
