/**
 * Hook para gestionar badges de items del menú
 */

import { useCallback, useEffect, useState } from 'react';

import type { BadgeConfig, ItemBadge, MenuItem, BadgeType } from '@/types/menu';

export interface UseBadgeManagerOptions {
    item: MenuItem | null;
    onSave: (itemId: number, itemType: 'product' | 'combo', badges: Omit<ItemBadge, 'badge_type'>[]) => void;
}

export interface UseBadgeManagerReturn {
    selectedBadges: Map<number, BadgeConfig>;
    editingBadgeId: number | null;
    validationErrors: Map<number, string>;
    hasErrors: boolean;

    toggleBadge: (badgeTypeId: number) => void;
    updateBadgeConfig: (badgeTypeId: number, field: keyof BadgeConfig, value: string | number[]) => void;
    toggleWeekday: (badgeTypeId: number, day: number) => void;
    setEditingBadgeId: (id: number | null) => void;
    handleSave: (itemType: 'product' | 'combo') => void;
    reset: () => void;
}

export function useBadgeManager({ item, onSave }: UseBadgeManagerOptions): UseBadgeManagerReturn {
    const [selectedBadges, setSelectedBadges] = useState<Map<number, BadgeConfig>>(new Map());
    const [editingBadgeId, setEditingBadgeId] = useState<number | null>(null);

    // Inicializar desde badges existentes del item
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

    // Toggle de badge seleccionado
    const toggleBadge = useCallback((badgeTypeId: number) => {
        setSelectedBadges((prev) => {
            const newMap = new Map(prev);
            if (newMap.has(badgeTypeId)) {
                newMap.delete(badgeTypeId);
                setEditingBadgeId((current) => (current === badgeTypeId ? null : current));
            } else {
                newMap.set(badgeTypeId, {
                    validity_type: 'permanent',
                    valid_from: '',
                    valid_until: '',
                    weekdays: [],
                });
                setEditingBadgeId(badgeTypeId);
            }
            return newMap;
        });
    }, []);

    // Actualizar configuración de un badge
    const updateBadgeConfig = useCallback(
        (badgeTypeId: number, field: keyof BadgeConfig, value: string | number[]) => {
            setSelectedBadges((prev) => {
                const newMap = new Map(prev);
                const current = newMap.get(badgeTypeId);
                if (current) {
                    newMap.set(badgeTypeId, { ...current, [field]: value });
                }
                return newMap;
            });
        },
        []
    );

    // Toggle de día de la semana
    const toggleWeekday = useCallback(
        (badgeTypeId: number, day: number) => {
            const current = selectedBadges.get(badgeTypeId);
            if (!current) return;

            const newWeekdays = current.weekdays.includes(day)
                ? current.weekdays.filter((d) => d !== day)
                : [...current.weekdays, day].sort((a, b) => a - b);

            updateBadgeConfig(badgeTypeId, 'weekdays', newWeekdays);
        },
        [selectedBadges, updateBadgeConfig]
    );

    // Validación de errores
    const getValidationErrors = useCallback((): Map<number, string> => {
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
    }, [selectedBadges]);

    const validationErrors = getValidationErrors();
    const hasErrors = validationErrors.size > 0;

    // Guardar badges
    const handleSave = useCallback(
        (itemType: 'product' | 'combo') => {
            if (hasErrors || !item) return;

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
            onSave(item.id, itemType, badges);
        },
        [selectedBadges, hasErrors, item, onSave]
    );

    // Reset
    const reset = useCallback(() => {
        setSelectedBadges(new Map());
        setEditingBadgeId(null);
    }, []);

    return {
        selectedBadges,
        editingBadgeId,
        validationErrors,
        hasErrors,

        toggleBadge,
        updateBadgeConfig,
        toggleWeekday,
        setEditingBadgeId,
        handleSave,
        reset,
    };
}
