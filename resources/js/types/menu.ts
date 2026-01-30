/**
 * Tipos centralizados para el sistema de menú
 * AdminPanel Subway Guatemala
 */

// ============================================
// CATEGORY (Categoría)
// ============================================

export interface Category {
    id: number;
    name: string;
    description?: string | null;
    is_active: boolean;
    is_combo_category: boolean;
    uses_variants: boolean;
    variant_definitions: string[];
    sort_order: number;
    created_at?: string;
    updated_at?: string;
}

export interface CategoryFormData {
    name: string;
    description: string;
    is_active: boolean;
    is_combo_category: boolean;
    uses_variants: boolean;
    variant_definitions: string[];
}

// ============================================
// PRODUCT (Producto)
// ============================================

export interface Product {
    id: number;
    name: string;
    description: string | null;
    image: string | null;
    category_id: number | null;
    is_active: boolean;
    is_customizable?: boolean;
    has_variants: boolean;
    precio_pickup_capital: number | null;
    precio_domicilio_capital: number | null;
    precio_pickup_interior: number | null;
    precio_domicilio_interior: number | null;
    is_redeemable: boolean;
    points_cost: number | null;
    sort_order: number;
    created_at?: string;
    updated_at?: string;
    // Relations
    category?: Category;
    sections?: Section[];
    variants?: ProductVariant[];
}

export interface ProductFormData {
    category_id: string;
    name: string;
    description: string;
    is_active: boolean;
    has_variants: boolean;
    precio_pickup_capital: string;
    precio_domicilio_capital: string;
    precio_pickup_interior: string;
    precio_domicilio_interior: string;
    variants: VariantFormData[];
    sections: number[];
}

// ============================================
// PRODUCT VARIANT (Variante de Producto)
// ============================================

export interface ProductVariant {
    id: number;
    product_id: number;
    sku: string;
    name: string;
    size: string;
    precio_pickup_capital: number;
    precio_domicilio_capital: number;
    precio_pickup_interior: number;
    precio_domicilio_interior: number;
    is_active: boolean;
    sort_order: number;
    is_daily_special?: boolean;
    daily_special_days?: number[];
    daily_special_precio_pickup_capital?: number;
    daily_special_precio_domicilio_capital?: number;
    daily_special_precio_pickup_interior?: number;
    daily_special_precio_domicilio_interior?: number;
    created_at?: string;
    updated_at?: string;
    // Relations
    product?: Product;
}

export interface VariantFormData {
    id?: number | string;
    name: string;
    is_active: boolean;
    precio_pickup_capital: string;
    precio_domicilio_capital: string;
    precio_pickup_interior: string;
    precio_domicilio_interior: string;
    is_redeemable: boolean;
    points_cost: string;
}

// ============================================
// SECTION (Sección de Personalización)
// ============================================

export interface Section {
    id: number;
    title: string;
    description: string | null;
    is_required: boolean;
    allow_multiple: boolean;
    min_selections: number;
    max_selections: number;
    // Bundle pricing
    bundle_discount_enabled: boolean;
    bundle_size: number;
    bundle_discount_amount: number | null;
    is_active: boolean;
    sort_order: number;
    created_at?: string;
    updated_at?: string;
    // Relations
    options?: SectionOption[];
}

export interface SectionOption {
    id: number;
    section_id: number;
    name: string;
    is_extra: boolean;
    price_modifier: number;
    sort_order: number;
    created_at?: string;
    updated_at?: string;
}

// ============================================
// COMBO
// ============================================

export interface Combo {
    id: number;
    name: string;
    description: string | null;
    image: string | null;
    category_id: number | null;
    precio_pickup_capital: number;
    precio_domicilio_capital: number;
    precio_pickup_interior: number;
    precio_domicilio_interior: number;
    is_active: boolean;
    is_redeemable: boolean;
    points_cost: number | null;
    sort_order: number;
    items_count?: number;
    choice_groups_count?: number;
    created_at?: string;
    updated_at?: string;
    deleted_at?: string | null;
    // Relations
    category?: Category;
    items?: ComboItem[];
}

export interface ComboItem {
    id: number | string;
    combo_id?: number;
    is_choice_group: boolean;
    choice_label: string | null;
    product_id: number | null;
    variant_id: number | null;
    quantity: number;
    sort_order: number;
    created_at?: string;
    updated_at?: string;
    // Relations
    product?: Product | null;
    variant?: ProductVariant | null;
    options?: ComboItemOption[];
}

export interface ComboItemOption {
    id: number | string;
    combo_item_id?: number;
    product_id: number;
    variant_id: number | null;
    sort_order: number;
    // Relations
    product?: Product;
    variant?: ProductVariant | null;
}

export interface ComboFormData {
    category_id: string;
    name: string;
    description: string;
    is_active: boolean;
    is_redeemable: boolean;
    points_cost: string;
    precio_pickup_capital: string;
    precio_domicilio_capital: string;
    precio_pickup_interior: string;
    precio_domicilio_interior: string;
}

export interface LocalComboItem {
    id: string;
    is_choice_group: boolean;
    choice_label: string;
    product_id: number | null;
    variant_id: number | null;
    quantity: number;
    options: LocalChoiceOption[];
}

export interface LocalChoiceOption {
    id: string;
    product_id: number;
    variant_id: number | null;
}

// ============================================
// BADGE
// ============================================

export interface BadgeType {
    id: number;
    name: string;
    color: string;
    is_active: boolean;
    sort_order: number;
    product_badges_count?: number;
    created_at?: string;
    updated_at?: string;
}

export type ValidityType = 'permanent' | 'date_range' | 'weekdays';

export interface ProductBadge {
    id: number;
    badge_type_id: number;
    badgeable_type: string;
    badgeable_id: number;
    validity_type: ValidityType;
    valid_from: string | null;
    valid_until: string | null;
    weekdays: number[] | null;
    is_active: boolean;
    created_at?: string;
    updated_at?: string;
    // Relations
    badge_type?: BadgeType;
}

export interface ItemBadge {
    id?: number;
    badge_type_id: number;
    validity_type: ValidityType;
    valid_from: string | null;
    valid_until: string | null;
    weekdays: number[] | null;
    badge_type: BadgeType;
}

export interface BadgeConfig {
    validity_type: ValidityType;
    valid_from: string;
    valid_until: string;
    weekdays: number[];
}

// ============================================
// PROMOTION (Promoción)
// ============================================

export type PromotionType = 'percentage_discount' | 'two_for_one' | 'daily_special' | 'bundle_special';
export type PromotionValidityType = 'permanent' | 'date_range' | 'time_range' | 'date_time_range' | 'weekdays';

export interface Promotion {
    id: number;
    name: string;
    description: string | null;
    type: PromotionType;
    scope_type?: 'product' | 'combo' | 'bundle';
    applies_to?: 'product' | 'combo';
    validity_type: PromotionValidityType;
    is_permanent: boolean;
    valid_from: string | null;
    valid_until: string | null;
    has_time_restriction: boolean;
    time_from: string | null;
    time_until: string | null;
    active_days: number[] | null;
    weekdays: number[] | null;
    is_active: boolean;
    sort_order: number;
    items_count?: number;
    // Campos específicos según tipo (4 precios independientes)
    special_price_pickup_capital?: number | null;
    special_price_delivery_capital?: number | null;
    special_price_pickup_interior?: number | null;
    special_price_delivery_interior?: number | null;
    // Bundle special: 4 precios independientes
    special_bundle_price_pickup_capital?: number | null;
    special_bundle_price_delivery_capital?: number | null;
    special_bundle_price_pickup_interior?: number | null;
    special_bundle_price_delivery_interior?: number | null;
    discount_percentage?: number | null;
    created_at?: string;
    updated_at?: string;
    deleted_at?: string | null;
    // Relations
    items?: PromotionItem[];
    bundle_items?: BundlePromotionItem[];
}

export interface PromotionItem {
    id: number;
    promotion_id: number;
    product_id: number | null;
    variant_id: number | null;
    category_id: number;
    // 4 precios independientes
    special_price_pickup_capital?: number | null;
    special_price_delivery_capital?: number | null;
    special_price_pickup_interior?: number | null;
    special_price_delivery_interior?: number | null;
    discount_percentage?: number | null;
    validity_type: PromotionValidityType;
    valid_from: string | null;
    valid_until: string | null;
    time_from: string | null;
    time_until: string | null;
    weekdays: number[] | null;
    // Precios estandarizados (consistente con ProductResource)
    discounted_prices?: {
        pickup_capital: number | null;
        delivery_capital: number | null;
        pickup_interior: number | null;
        delivery_interior: number | null;
    } | null;
    created_at?: string;
    updated_at?: string;
    // Relations
    product?: Product | null;
    variant?: ProductVariant | null;
    category?: Category;
}

export interface BundlePromotionItem {
    id: number;
    promotion_id: number;
    product_id: number | null;
    variant_id: number | null;
    is_choice_group: boolean;
    choice_label: string | null;
    quantity: number;
    sort_order: number;
    created_at?: string;
    updated_at?: string;
    // Relations
    product?: Product | null;
    variant?: ProductVariant | null;
    options?: BundlePromotionItemOption[];
}

export interface BundlePromotionItemOption {
    id: number;
    bundle_item_id: number;
    product_id: number;
    variant_id: number | null;
    sort_order: number;
    // Relations
    product?: Product;
    variant?: ProductVariant | null;
}

export interface LocalPromotionItem {
    id: string;
    category_id: number | null;
    variant_id: number | null;
    selected_product_ids: number[];
    selected_combo_ids: number[];
    discount_percentage?: string;
}

// ============================================
// MENU ORDER (Ordenamiento del Menú)
// ============================================

export interface MenuCategory {
    id: number;
    name: string;
    is_active: boolean;
    sort_order: number;
}

export interface MenuItem {
    id: number;
    type: 'product' | 'combo';
    name: string;
    image: string | null;
    is_active: boolean;
    sort_order: number;
    badges: ItemBadge[];
}

export interface MenuGroup {
    category: MenuCategory;
    items: MenuItem[];
}

export interface MenuStats {
    total_categories: number;
    active_categories: number;
    total_products: number;
    total_combos: number;
}

// ============================================
// PRICE HELPERS
// ============================================

export interface PriceSet {
    precio_pickup_capital: number | string;
    precio_domicilio_capital: number | string;
    precio_pickup_interior: number | string;
    precio_domicilio_interior: number | string;
}

export interface PriceRange {
    min: number;
    max: number;
}

// ============================================
// FORM HELPERS
// ============================================

export type FormMode = 'create' | 'edit';

export interface FormErrors {
    [key: string]: string;
}

// ============================================
// TABLE HELPERS
// ============================================

export interface CategoryGroup<T> {
    category: Category;
    items: T[];
}

export interface ProductStats {
    total_products: number;
    active_products: number;
}

export interface CategoryStats {
    total_categories: number;
    active_categories: number;
}

export interface ComboStats {
    total_combos: number;
    active_combos: number;
    available_combos: number;
}

export interface SectionStats {
    total_sections: number;
    required_sections: number;
    total_options: number;
}
