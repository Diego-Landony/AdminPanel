interface ComboItem {
    id: string;
    is_choice_group: boolean;
    choice_label?: string;
    product_id?: number | null;
    variant_id?: number | null;
    quantity: number;
    sort_order: number;
    options?: ChoiceOption[];
}

interface ChoiceOption {
    id: string;
    product_id: number;
    variant_id?: number | null;
    sort_order: number;
}

interface Product {
    id: number;
    name: string;
    has_variants: boolean;
    variants?: ProductVariant[];
}

interface ProductVariant {
    id: number;
    name: string;
    size?: string;
}

export interface ValidationResult {
    valid: boolean;
    errors: string[];
}

/**
 * Valida un combo item (fijo o grupo)
 */
export function validateComboItem(item: ComboItem): ValidationResult {
    const errors: string[] = [];

    if (item.is_choice_group) {
        // Validar grupo de elección
        if (!item.choice_label || item.choice_label.trim() === '') {
            errors.push('El grupo debe tener una etiqueta');
        }

        if (!item.options || item.options.length < 2) {
            errors.push('El grupo debe tener al menos 2 opciones');
        }

        // Validar duplicados
        const optionKeys = new Set();
        item.options?.forEach((opt, index) => {
            const key = `${opt.product_id}-${opt.variant_id || 'null'}`;
            if (optionKeys.has(key)) {
                errors.push(`Opción ${index + 1} está duplicada`);
            }
            optionKeys.add(key);
        });
    } else {
        // Validar item fijo
        if (!item.product_id) {
            errors.push('El item fijo debe tener un producto');
        }
    }

    if (!item.quantity || Number(item.quantity) < 1) {
        errors.push('La cantidad debe ser al menos 1');
    }

    return {
        valid: errors.length === 0,
        errors,
    };
}

/**
 * Detecta inconsistencias en variantes de un grupo
 */
export function detectVariantInconsistency(options: ChoiceOption[], products: Product[]): { consistent: boolean; sizes: string[] } {
    const sizes: string[] = [];

    options.forEach((opt) => {
        if (!opt.variant_id) return;

        const product = products.find((p) => p.id === opt.product_id);
        const variant = product?.variants?.find((v) => v.id === opt.variant_id);

        if (variant?.size) {
            sizes.push(variant.size);
        }
    });

    const uniqueSizes = [...new Set(sizes)];

    return {
        consistent: uniqueSizes.length <= 1,
        sizes: uniqueSizes,
    };
}

/**
 * Formatea el resumen de un item para mostrar
 */
export function formatComboItemSummary(item: ComboItem, products: Product[]): string {
    if (item.is_choice_group) {
        const optionsCount = item.options?.length || 0;
        return `Elige ${item.quantity} de ${optionsCount} opciones`;
    }

    const product = products.find((p) => p.id === Number(item.product_id));
    if (!product) return 'Producto no encontrado';

    if (item.variant_id) {
        const variant = product.variants?.find((v) => v.id === Number(item.variant_id));
        return `${product.name} - ${variant?.name || ''}`;
    }

    return product.name;
}

/**
 * Genera un ID único para items/opciones
 */
export function generateUniqueItemId(prefix: string = 'item'): string {
    return `${prefix}-${Date.now()}-${Math.random().toString(36).substring(2, 9)}`;
}

/**
 * Valida que un combo tenga al menos la estructura mínima
 */
export function validateMinimumComboStructure(items: ComboItem[]): ValidationResult {
    const errors: string[] = [];

    if (!items || items.length < 2) {
        errors.push('Un combo debe tener al menos 2 items');
    }

    return {
        valid: errors.length === 0,
        errors,
    };
}

/**
 * Cuenta items por tipo
 */
export function countItemsByType(items: ComboItem[]): {
    total: number;
    choiceGroups: number;
    fixedItems: number;
} {
    const choiceGroups = items.filter((i) => i.is_choice_group).length;
    const fixedItems = items.filter((i) => !i.is_choice_group).length;

    return {
        total: items.length,
        choiceGroups,
        fixedItems,
    };
}

/**
 * Prepara datos de combo para enviar al backend
 */
export function prepareComboDataForSubmit(items: ComboItem[]): any[] {
    return items.map((item, index) => {
        const baseItem = {
            is_choice_group: item.is_choice_group,
            quantity: item.quantity,
            sort_order: index + 1,
        };

        if (item.is_choice_group) {
            return {
                ...baseItem,
                choice_label: item.choice_label,
                product_id: null,
                variant_id: null,
                options: (item.options || []).map((opt, optIndex) => ({
                    product_id: opt.product_id,
                    variant_id: opt.variant_id || null,
                    sort_order: optIndex + 1,
                })),
            };
        }

        return {
            ...baseItem,
            product_id: item.product_id,
            variant_id: item.variant_id || null,
            choice_label: null,
            options: [],
        };
    });
}
