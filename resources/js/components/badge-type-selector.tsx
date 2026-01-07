import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Award } from 'lucide-react';

export interface BadgeType {
    id: number;
    name: string;
    color: string;
    text_color: string;
    is_active: boolean;
}

interface BadgeTypeSelectorProps {
    value: number | null | undefined;
    onChange: (value: number | null) => void;
    badgeTypes: BadgeType[];
    error?: string;
    label?: string;
    description?: string;
    showLabels?: boolean;
}

export function BadgeTypeSelector({
    value,
    onChange,
    badgeTypes,
    error,
    label = 'Insignia de promoción',
    description = 'Selecciona una insignia para mostrar en los productos de esta promoción',
    showLabels = true,
}: BadgeTypeSelectorProps) {
    const selectedBadge = badgeTypes.find((b) => b.id === value);

    return (
        <div className="space-y-2">
            {showLabels && <Label>{label}</Label>}
            {showLabels && description && (
                <p className="text-muted-foreground text-sm">{description}</p>
            )}

            <Select
                value={value?.toString() || 'none'}
                onValueChange={(val) => onChange(val === 'none' ? null : parseInt(val, 10))}
            >
                <SelectTrigger className="w-full">
                    <SelectValue placeholder="Sin insignia (opcional)" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="none">Sin insignia</SelectItem>
                    {badgeTypes
                        .filter((b) => b.is_active)
                        .map((badge) => (
                            <SelectItem key={badge.id} value={badge.id.toString()}>
                                <div className="flex items-center gap-2">
                                    <span
                                        className="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                        style={{
                                            backgroundColor: badge.color,
                                            color: badge.text_color,
                                        }}
                                    >
                                        {badge.name}
                                    </span>
                                </div>
                            </SelectItem>
                        ))}
                </SelectContent>
            </Select>

            {selectedBadge && (
                <div className="bg-muted/30 mt-3 rounded-lg border p-3">
                    <div className="flex items-center gap-2">
                        <Award className="text-muted-foreground h-4 w-4" />
                        <span className="text-muted-foreground text-sm">Vista previa:</span>
                        <span
                            className="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                            style={{
                                backgroundColor: selectedBadge.color,
                                color: selectedBadge.text_color,
                            }}
                        >
                            {selectedBadge.name}
                        </span>
                    </div>
                </div>
            )}

            {error && <p className="text-destructive text-sm">{error}</p>}
        </div>
    );
}
