import { FormField } from '@/components/ui/form-field';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

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
}

export function BadgeTypeSelector({ value, onChange, badgeTypes, error }: BadgeTypeSelectorProps) {
    const selectedBadge = badgeTypes.find((b) => b.id === value);
    const activeBadges = badgeTypes.filter((b) => b.is_active);

    return (
        <FormField label="Badge" error={error} description="Se mostrará en los productos con esta promoción">
            <div className="flex items-center gap-3">
                <Select
                    value={value?.toString() || 'none'}
                    onValueChange={(val) => onChange(val === 'none' ? null : parseInt(val, 10))}
                >
                    <SelectTrigger className="w-[200px]">
                        <SelectValue placeholder="Sin badge" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="none">
                            <span className="text-muted-foreground">Sin badge</span>
                        </SelectItem>
                        {activeBadges.map((badge) => (
                            <SelectItem key={badge.id} value={badge.id.toString()}>
                                <div className="flex items-center gap-2">
                                    <span
                                        className="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
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
                    <span
                        className="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                        style={{
                            backgroundColor: selectedBadge.color,
                            color: selectedBadge.text_color,
                        }}
                    >
                        {selectedBadge.name}
                    </span>
                )}
            </div>
        </FormField>
    );
}
