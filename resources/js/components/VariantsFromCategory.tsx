import { PriceFields } from '@/components/PriceFields';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { AlertCircle, ChevronDown, ChevronRight } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

interface VariantData {
    id?: number | string;
    name: string;
    is_active: boolean;
    precio_pickup_capital: string;
    precio_domicilio_capital: string;
    precio_pickup_interior: string;
    precio_domicilio_interior: string;
}

interface ExistingVariant {
    id?: number | string;
    name: string;
    is_active?: boolean;
    precio_pickup_capital: string | number;
    precio_domicilio_capital: string | number;
    precio_pickup_interior: string | number;
    precio_domicilio_interior: string | number;
}

interface VariantsFromCategoryProps {
    categoryVariants: string[];
    existingVariants?: ExistingVariant[];
    onChange: (variants: VariantData[]) => void;
    errors?: Record<string, string>;
}

export function VariantsFromCategory({ categoryVariants, existingVariants = [], onChange, errors = {} }: VariantsFromCategoryProps) {
    const [variants, setVariants] = useState<VariantData[]>([]);
    const prevCategoryVariantsRef = useRef<string[]>([]);
    const onChangeRef = useRef(onChange);
    const isInitializedRef = useRef(false);

    // Keep onChange ref updated without triggering re-renders
    useEffect(() => {
        onChangeRef.current = onChange;
    }, [onChange]);

    useEffect(() => {
        // Only reinitialize when category variants actually change, not on every render
        const hasVariantsChanged = JSON.stringify(prevCategoryVariantsRef.current) !== JSON.stringify(categoryVariants);
        const isFirstMount = !isInitializedRef.current && categoryVariants.length > 0;

        if (!hasVariantsChanged && !isFirstMount) {
            return;
        }

        const initializedVariants = categoryVariants.map((variantName) => {
            const existing = existingVariants.find((v) => v.name === variantName);

            if (existing) {
                return {
                    id: existing.id,
                    name: variantName,
                    is_active: existing.is_active ?? true,
                    precio_pickup_capital: String(existing.precio_pickup_capital || ''),
                    precio_domicilio_capital: String(existing.precio_domicilio_capital || ''),
                    precio_pickup_interior: String(existing.precio_pickup_interior || ''),
                    precio_domicilio_interior: String(existing.precio_domicilio_interior || ''),
                };
            }

            return {
                name: variantName,
                is_active: false,
                precio_pickup_capital: '',
                precio_domicilio_capital: '',
                precio_pickup_interior: '',
                precio_domicilio_interior: '',
            };
        });

        setVariants(initializedVariants);
        onChangeRef.current(initializedVariants);
        prevCategoryVariantsRef.current = categoryVariants;
        isInitializedRef.current = true;
    }, [categoryVariants, existingVariants]);

    const updateVariant = (index: number, field: keyof VariantData, value: string | boolean) => {
        const updated = [...variants];
        updated[index] = { ...updated[index], [field]: value };
        setVariants(updated);
        onChange(updated);
    };

    const toggleVariant = (index: number, checked: boolean) => {
        updateVariant(index, 'is_active', checked);
    };

    const activeVariantsCount = variants.filter((v) => v.is_active).length;
    const hasIncompleteVariants = variants.some((v) => {
        if (!v.is_active) return false;
        return !v.precio_pickup_capital || !v.precio_domicilio_capital || !v.precio_pickup_interior || !v.precio_domicilio_interior;
    });

    if (categoryVariants.length === 0) {
        return (
            <Alert variant="default" className="border-yellow-200 bg-yellow-50 dark:bg-yellow-950">
                <AlertCircle className="h-4 w-4 text-yellow-600" />
                <AlertDescription className="text-yellow-700 dark:text-yellow-300">Esta categoría no tiene variantes definidas.</AlertDescription>
            </Alert>
        );
    }

    return (
        <div className="space-y-4">
            {activeVariantsCount > 0 && (
                <div className="flex justify-end">
                    <span className="text-xs text-muted-foreground">
                        {activeVariantsCount} de {variants.length} activos
                    </span>
                </div>
            )}

            {activeVariantsCount === 0 && (
                <Alert variant="default" className="border-yellow-200 bg-yellow-50 dark:bg-yellow-950">
                    <AlertCircle className="h-4 w-4 text-yellow-600" />
                    <AlertDescription className="text-yellow-700 dark:text-yellow-300">Activa al menos una variante.</AlertDescription>
                </Alert>
            )}

            {hasIncompleteVariants && (
                <Alert variant="destructive">
                    <AlertCircle className="h-4 w-4" />
                    <AlertDescription>Las variantes activas deben tener los 4 precios.</AlertDescription>
                </Alert>
            )}

            {variants.map((variant, index) => (
                <div key={variant.name} className="space-y-4 rounded-lg border border-border p-4">
                    <div className="flex items-center justify-between">
                        <Label htmlFor={`variant-${index}`} className="flex cursor-pointer items-center gap-2 text-base font-medium">
                            {variant.is_active ? (
                                <ChevronDown className="h-4 w-4 text-muted-foreground" />
                            ) : (
                                <ChevronRight className="h-4 w-4 text-muted-foreground" />
                            )}
                            {variant.name}
                        </Label>
                        <div className="flex items-center gap-2">
                            {variant.is_active && <span className="text-sm font-medium text-muted-foreground">Activo</span>}
                            <Switch
                                id={`variant-${index}`}
                                checked={variant.is_active}
                                onCheckedChange={(checked) => toggleVariant(index, checked as boolean)}
                            />
                        </div>
                    </div>

                    {variant.is_active && (
                        <div className="space-y-6 pt-2 pl-8">
                            <PriceFields
                                capitalPickup={variant.precio_pickup_capital}
                                capitalDomicilio={variant.precio_domicilio_capital}
                                interiorPickup={variant.precio_pickup_interior}
                                interiorDomicilio={variant.precio_domicilio_interior}
                                onChangeCapitalPickup={(value) => updateVariant(index, 'precio_pickup_capital', value)}
                                onChangeCapitalDomicilio={(value) => updateVariant(index, 'precio_domicilio_capital', value)}
                                onChangeInteriorPickup={(value) => updateVariant(index, 'precio_pickup_interior', value)}
                                onChangeInteriorDomicilio={(value) => updateVariant(index, 'precio_domicilio_interior', value)}
                                errors={{
                                    capitalPickup: errors[`variants.${index}.precio_pickup_capital`],
                                    capitalDomicilio: errors[`variants.${index}.precio_domicilio_capital`],
                                    interiorPickup: errors[`variants.${index}.precio_pickup_interior`],
                                    interiorDomicilio: errors[`variants.${index}.precio_domicilio_interior`],
                                }}
                            />
                        </div>
                    )}
                </div>
            ))}

            {errors.variants && (
                <Alert variant="destructive">
                    <AlertCircle className="h-4 w-4" />
                    <AlertDescription>{errors.variants}</AlertDescription>
                </Alert>
            )}
        </div>
    );
}
