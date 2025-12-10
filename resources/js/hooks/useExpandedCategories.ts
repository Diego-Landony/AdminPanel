/**
 * Hook simple para gestionar qué categorías están expandidas
 */

import { useCallback, useState } from 'react';

export interface UseExpandedCategoriesReturn {
    expandedCategories: Set<number>;
    isExpanded: (categoryId: number) => boolean;
    toggleCategory: (categoryId: number) => void;
    expandAll: (categoryIds: number[]) => void;
    collapseAll: () => void;
}

export function useExpandedCategories(
    initialExpanded: number[] = []
): UseExpandedCategoriesReturn {
    const [expandedCategories, setExpandedCategories] = useState<Set<number>>(
        new Set(initialExpanded)
    );

    const isExpanded = useCallback(
        (categoryId: number) => expandedCategories.has(categoryId),
        [expandedCategories]
    );

    const toggleCategory = useCallback((categoryId: number) => {
        setExpandedCategories((prev) => {
            const newSet = new Set(prev);
            if (newSet.has(categoryId)) {
                newSet.delete(categoryId);
            } else {
                newSet.add(categoryId);
            }
            return newSet;
        });
    }, []);

    const expandAll = useCallback((categoryIds: number[]) => {
        setExpandedCategories(new Set(categoryIds));
    }, []);

    const collapseAll = useCallback(() => {
        setExpandedCategories(new Set());
    }, []);

    return {
        expandedCategories,
        isExpanded,
        toggleCategory,
        expandAll,
        collapseAll,
    };
}
