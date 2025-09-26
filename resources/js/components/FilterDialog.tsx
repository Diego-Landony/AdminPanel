import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ScrollArea } from '@/components/ui/scroll-area';
import { LucideIcon } from 'lucide-react';
import React from 'react';

export interface FilterOption {
    id: string | number;
    label: string;
    value?: unknown;
    subtitle?: string;
}

export interface FilterDialogProps {
    /** Button text when no items selected */
    placeholder: string;
    /** Icon for the trigger button */
    icon: LucideIcon;
    /** Dialog title */
    title: string;
    /** Dialog description */
    description: string;
    /** Available options to select from */
    options: FilterOption[];
    /** Currently selected option IDs */
    selectedIds: (string | number)[];
    /** Callback when selection changes */
    onSelectionChange: (selectedIds: (string | number)[]) => void;
    /** Dialog open state */
    isOpen: boolean;
    /** Open state change handler */
    onOpenChange: (open: boolean) => void;
    /** Enable search functionality */
    searchEnabled?: boolean;
    /** Search placeholder text */
    searchPlaceholder?: string;
    /** Search term for external control */
    searchTerm?: string;
    /** Search term change handler */
    onSearchChange?: (term: string) => void;
    /** Custom button variant */
    buttonVariant?: 'outline' | 'default' | 'secondary';
    /** Maximum dialog width */
    maxWidth?: 'sm' | 'md' | 'lg' | 'xl';
    /** Maximum height for scroll area */
    maxHeight?: string;
}

/**
 * Professional unified filter dialog component
 * Supports checkbox-based multi-select filtering with optional search
 *
 * Features:
 * - Multi-select with checkboxes
 * - Optional search functionality
 * - Smart button text based on selection
 * - Flexible option structure with subtitles
 * - Professional styling with scroll area
 * - Configurable dialog size and appearance
 */
export const FilterDialog: React.FC<FilterDialogProps> = ({
    placeholder,
    icon: IconComponent,
    title,
    description,
    options,
    selectedIds,
    onSelectionChange,
    isOpen,
    onOpenChange,
    searchEnabled = false,
    searchPlaceholder = 'Buscar...',
    searchTerm = '',
    onSearchChange,
    buttonVariant = 'outline',
    maxWidth = 'md',
    maxHeight = 'h-64',
}) => {
    const getButtonText = () => {
        if (selectedIds.length === 0) return placeholder;

        if (selectedIds.length === 1) {
            const option = options.find((opt) => opt.id === selectedIds[0]);
            return option ? option.label : placeholder;
        }

        if (selectedIds.length <= 3) {
            return selectedIds
                .map((id) => options.find((opt) => opt.id === id)?.label)
                .filter(Boolean)
                .join(', ');
        }

        return `${selectedIds.length} elementos seleccionados`;
    };

    const handleSelectionToggle = (optionId: string | number, checked: boolean) => {
        if (checked) {
            onSelectionChange([...selectedIds, optionId]);
        } else {
            onSelectionChange(selectedIds.filter((id) => id !== optionId));
        }
    };

    const filteredOptions =
        searchEnabled && searchTerm
            ? options.filter(
                  (option) =>
                      option.label.toLowerCase().includes(searchTerm.toLowerCase()) ||
                      (option.subtitle && option.subtitle.toLowerCase().includes(searchTerm.toLowerCase())),
              )
            : options;

    const maxWidthClasses = {
        sm: 'max-w-sm',
        md: 'max-w-md',
        lg: 'max-w-lg',
        xl: 'max-w-xl',
    };

    return (
        <Dialog open={isOpen} onOpenChange={onOpenChange}>
            <DialogTrigger asChild>
                <Button variant={buttonVariant} className="justify-between">
                    <span className="truncate">{getButtonText()}</span>
                    <IconComponent className="ml-2 h-4 w-4 flex-shrink-0" />
                </Button>
            </DialogTrigger>
            <DialogContent className={maxWidthClasses[maxWidth]}>
                <DialogHeader>
                    <DialogTitle>{title}</DialogTitle>
                    <DialogDescription>{description}</DialogDescription>
                </DialogHeader>

                {searchEnabled && (
                    <div className="mb-4">
                        <Input placeholder={searchPlaceholder} value={searchTerm} onChange={(e) => onSearchChange?.(e.target.value)} />
                    </div>
                )}

                <ScrollArea className={maxHeight}>
                    <div className="space-y-3 p-2">
                        {filteredOptions.map((option) => (
                            <div key={option.id} className="flex items-center space-x-3">
                                <Checkbox
                                    id={`filter-${option.id}`}
                                    checked={selectedIds.includes(option.id)}
                                    onCheckedChange={(checked) => handleSelectionToggle(option.id, checked as boolean)}
                                />
                                <Label htmlFor={`filter-${option.id}`} className="cursor-pointer text-sm">
                                    {option.subtitle ? (
                                        <div>
                                            <div className="font-medium">{option.label}</div>
                                            <div className="text-xs text-muted-foreground">{option.subtitle}</div>
                                        </div>
                                    ) : (
                                        option.label
                                    )}
                                </Label>
                            </div>
                        ))}

                        {filteredOptions.length === 0 && <div className="py-8 text-center text-muted-foreground">No se encontraron resultados</div>}
                    </div>
                </ScrollArea>
            </DialogContent>
        </Dialog>
    );
};

/**
 * Date Range Filter Dialog - Specialized component for date filtering
 */
export interface DateRange {
    from?: Date;
    to?: Date;
}

export interface DateRangeFilterDialogProps {
    /** Dialog open state */
    isOpen: boolean;
    /** Open state change handler */
    onOpenChange: (open: boolean) => void;
    /** Current date range */
    dateRange?: DateRange;
    /** Date range change handler */
    onDateRangeChange: (range: DateRange) => void;
    /** Button text format function */
    formatButtonText?: (range?: DateRange) => string;
    /** Icon for trigger button */
    icon?: LucideIcon;
    /** Dialog title */
    title?: string;
    /** Dialog description */
    description?: string;
}

export const DateRangeFilterDialog: React.FC<DateRangeFilterDialogProps> = ({
    isOpen,
    onOpenChange,
    dateRange,
    onDateRangeChange,
    formatButtonText,
    icon: IconComponent,
    title = 'Seleccionar Rango de Fechas',
    description = 'Selecciona el período de fechas para filtrar',
}) => {
    const defaultFormatButtonText = (range?: DateRange) => {
        if (range?.from && range?.to) {
            return `${range.from.toLocaleDateString()} - ${range.to.toLocaleDateString()}`;
        }
        return 'Seleccionar fechas...';
    };

    const buttonText = formatButtonText ? formatButtonText(dateRange) : defaultFormatButtonText(dateRange);

    const handleFromDateChange = (dateString: string) => {
        const date = dateString ? new Date(dateString) : undefined;
        onDateRangeChange({
            from: date,
            to: dateRange?.to && date && date > dateRange.to ? undefined : dateRange?.to,
        });
    };

    const handleToDateChange = (dateString: string) => {
        const date = dateString ? new Date(dateString) : undefined;
        onDateRangeChange({
            from: dateRange?.from,
            to: date,
        });
    };

    const formatDateForInput = (date?: Date) => {
        return date ? date.toISOString().split('T')[0] : '';
    };

    return (
        <Dialog open={isOpen} onOpenChange={onOpenChange}>
            <DialogTrigger asChild>
                <Button variant="outline" className="justify-between">
                    <span className="truncate">{buttonText}</span>
                    {IconComponent && <IconComponent className="ml-2 h-4 w-4" />}
                </Button>
            </DialogTrigger>
            <DialogContent className="max-w-md">
                <DialogHeader>
                    <DialogTitle>{title}</DialogTitle>
                    <DialogDescription>{description}</DialogDescription>
                </DialogHeader>
                <div className="space-y-4 p-4">
                    <div className="space-y-2">
                        <Label>Fecha de inicio</Label>
                        <Input
                            type="date"
                            value={formatDateForInput(dateRange?.from)}
                            onChange={(e) => handleFromDateChange(e.target.value)}
                            max={dateRange?.to ? formatDateForInput(dateRange.to) : formatDateForInput(new Date())}
                        />
                    </div>

                    <div className="space-y-2">
                        <Label>Fecha de fin</Label>
                        <Input
                            type="date"
                            value={formatDateForInput(dateRange?.to)}
                            onChange={(e) => handleToDateChange(e.target.value)}
                            min={dateRange?.from ? formatDateForInput(dateRange.from) : undefined}
                            max={formatDateForInput(new Date())}
                        />
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    );
};
