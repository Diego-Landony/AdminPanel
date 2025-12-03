import { showNotification } from '@/hooks/useNotifications';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';

import { HierarchicalSortableMenu } from '@/components/HierarchicalSortableMenu';
import AppLayout from '@/layouts/app-layout';

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

interface MenuOrderPageProps {
    menuStructure: MenuGroup[];
    badgeTypes: BadgeTypeData[];
    stats: {
        total_categories: number;
        active_categories: number;
        total_products: number;
        total_combos: number;
    };
}

export default function MenuOrderIndex({ menuStructure, badgeTypes, stats }: MenuOrderPageProps) {
    const [isSavingCategories, setIsSavingCategories] = useState(false);
    const [isSavingItems, setIsSavingItems] = useState(false);
    const [isSavingBadges, setIsSavingBadges] = useState(false);

    const handleReorderCategories = (categories: MenuCategory[]) => {
        setIsSavingCategories(true);

        const orderData = categories.map((c) => ({
            id: c.id,
            sort_order: c.sort_order,
        }));

        router.post(
            route('menu.categories.reorder'),
            { categories: orderData },
            {
                preserveState: true,
                onSuccess: () => {
                    showNotification.success('Orden de categorías guardado');
                },
                onError: (error) => {
                    if (error.message) {
                        showNotification.error(error.message);
                    }
                },
                onFinish: () => {
                    setIsSavingCategories(false);
                },
            },
        );
    };

    const handleReorderItems = (items: MenuItem[], categoryId: number, itemType: 'product' | 'combo') => {
        setIsSavingItems(true);

        const orderData = items.map((item) => ({
            id: item.id,
            sort_order: item.sort_order,
        }));

        const endpoint = itemType === 'combo' ? route('menu.combos.reorder') : route('menu.products.reorder');

        const payload = itemType === 'combo' ? { combos: orderData } : { products: orderData };

        router.post(endpoint, payload, {
            preserveState: true,
            onSuccess: () => {
                showNotification.success(`Orden de ${itemType === 'combo' ? 'combos' : 'productos'} guardado`);
            },
            onError: (error) => {
                if (error.message) {
                    showNotification.error(error.message);
                }
            },
            onFinish: () => {
                setIsSavingItems(false);
            },
        });
    };

    const handleUpdateBadges = (itemId: number, itemType: 'product' | 'combo', badges: Omit<ItemBadge, 'badge_type'>[]) => {
        setIsSavingBadges(true);

        router.post(
            route('menu.order.badges'),
            {
                item_type: itemType,
                item_id: itemId,
                badges: badges,
            },
            {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    showNotification.success('Badges actualizados');
                },
                onError: (error) => {
                    if (error.message) {
                        showNotification.error(error.message);
                    }
                },
                onFinish: () => {
                    setIsSavingBadges(false);
                },
            },
        );
    };

    const handleToggleItem = (itemId: number, itemType: 'product' | 'combo') => {
        router.post(
            route('menu.order.toggle-item'),
            {
                item_type: itemType,
                item_id: itemId,
            },
            {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    showNotification.success(`${itemType === 'combo' ? 'Combo' : 'Producto'} actualizado`);
                },
                onError: (error) => {
                    if (error.message) {
                        showNotification.error(error.message);
                    }
                },
            },
        );
    };

    const handleToggleCategory = (categoryId: number) => {
        router.post(
            route('menu.order.toggle-category'),
            {
                category_id: categoryId,
            },
            {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    showNotification.success('Categoría actualizada');
                },
                onError: (error) => {
                    if (error.message) {
                        showNotification.error(error.message);
                    }
                },
            },
        );
    };

    return (
        <AppLayout>
            <Head title="Menú App" />

            <HierarchicalSortableMenu
                menuStructure={menuStructure}
                badgeTypes={badgeTypes}
                stats={stats}
                onReorderCategories={handleReorderCategories}
                onReorderItems={handleReorderItems}
                onUpdateBadges={handleUpdateBadges}
                onToggleItem={handleToggleItem}
                onToggleCategory={handleToggleCategory}
                isSavingCategories={isSavingCategories}
                isSavingItems={isSavingItems}
                isSavingBadges={isSavingBadges}
            />
        </AppLayout>
    );
}
