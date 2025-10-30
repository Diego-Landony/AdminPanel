import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

interface ProductVariant {
    id: number;
    name: string;
    size?: string;
}

interface VariantSelectorProps {
    variants: ProductVariant[];
    value: number | null;
    onChange: (variantId: number | null) => void;
    disabled?: boolean;
    error?: string;
    label?: string;
    required?: boolean;
}

export function VariantSelector({
    variants,
    value,
    onChange,
    disabled = false,
    error,
    label = 'Variante',
    required = false,
}: VariantSelectorProps) {
    if (variants.length === 0) {
        return null;
    }

    return (
        <div className="space-y-2">
            <Label>
                {label}
                {required && <span className="ml-1 text-destructive">*</span>}
            </Label>

            <Select value={value?.toString() || ''} onValueChange={(val) => onChange(val ? parseInt(val) : null)} disabled={disabled}>
                <SelectTrigger className="w-full">
                    <SelectValue placeholder="Seleccionar variante..." />
                </SelectTrigger>
                <SelectContent>
                    {variants.map((variant) => (
                        <SelectItem key={variant.id} value={variant.id.toString()}>
                            {variant.size ? `${variant.name} - ${variant.size}` : variant.name}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>

            {error && <p className="text-sm font-medium text-destructive">{error}</p>}
        </div>
    );
}
