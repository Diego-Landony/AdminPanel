import { Check, ChevronsUpDown } from 'lucide-react';
import * as React from 'react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
import { Dialog, DialogContent, DialogDescription, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { VisuallyHidden } from '@/components/ui/visually-hidden';

interface BaseItem {
    id: number;
    name: string;
    is_active: boolean;
    category?: {
        id: number;
        name: string;
    };
}

interface Variant {
    id: number;
    name: string;
}

interface Product extends BaseItem {
    has_variants: boolean;
    variants?: Variant[];
}

interface Combo extends BaseItem {
    precio_pickup_capital: number;
    precio_domicilio_capital: number;
}

interface ProductOrComboSelectorProps {
    value: number | null;
    onChange: (value: number | null, type: 'product' | 'combo') => void;
    products: Product[];
    combos: Combo[];
    label?: string;
    placeholder?: string;
    error?: string;
    required?: boolean;
    type: 'product' | 'combo' | null;
}

export function ProductOrComboSelector({
    value,
    onChange,
    products,
    combos,
    label = 'Producto o Combo',
    placeholder = 'Seleccionar...',
    error,
    required = false,
    type,
}: ProductOrComboSelectorProps) {
    const [open, setOpen] = React.useState(false);
    const [search, setSearch] = React.useState('');

    const selectedItem = type === 'product'
        ? products.find((p) => p.id === value)
        : type === 'combo'
        ? combos.find((c) => c.id === value)
        : null;

    const allItems = [
        ...products.map((p) => ({ ...p, type: 'product' as const })),
        ...combos.map((c) => ({ ...c, type: 'combo' as const })),
    ];

    // Agrupar items por categoría y tipo
    const itemsByCategory = allItems.reduce<Record<string, typeof allItems>>((acc, item) => {
        const categoryName = item.category?.name || 'Sin categoría';
        const groupKey = `${item.type === 'combo' ? 'Combos' : 'Productos'} - ${categoryName}`;
        if (!acc[groupKey]) {
            acc[groupKey] = [];
        }
        // Normalizar category_id a número para comparaciones consistentes
        const normalizedItem = {
            ...item,
            category_id: item.category?.id ? Number(item.category.id) : undefined,
        };
        acc[groupKey].push(normalizedItem as typeof item);
        return acc;
    }, {});

    // Filtrar según búsqueda
    const filteredItems = React.useMemo(() => {
        if (!search) return itemsByCategory;

        const searchLower = search.toLowerCase();
        const filtered: Record<string, typeof allItems> = {};

        Object.entries(itemsByCategory).forEach(([category, items]) => {
            const matchingItems = items.filter(
                (item) => item.name.toLowerCase().includes(searchLower) || item.category?.name.toLowerCase().includes(searchLower),
            );

            if (matchingItems.length > 0) {
                filtered[category] = matchingItems;
            }
        });

        return filtered;
    }, [search, itemsByCategory]);

    return (
        <div className="space-y-2">
            {label && (
                <Label>
                    {label}
                    {required && <span className="ml-1 text-destructive">*</span>}
                </Label>
            )}

            <Dialog open={open} onOpenChange={setOpen}>
                <Button
                    type="button"
                    variant="outline"
                    role="combobox"
                    aria-expanded={open}
                    className="w-full justify-between"
                    onClick={() => setOpen(!open)}
                >
                    {selectedItem ? (
                        <span className="flex items-center gap-2 truncate">
                            <span className="truncate">{selectedItem.name}</span>
                            <Badge variant={selectedItem.is_active ? 'default' : 'secondary'} className="shrink-0">
                                {selectedItem.is_active ? 'Activo' : 'Inactivo'}
                            </Badge>
                            <Badge variant="outline" className="shrink-0">
                                {type === 'combo' ? 'Combo' : 'Producto'}
                            </Badge>
                            {selectedItem.category && <span className="ml-2 text-muted-foreground text-xs">({selectedItem.category.name})</span>}
                        </span>
                    ) : (
                        <span className="text-muted-foreground">{placeholder}</span>
                    )}
                    <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                </Button>

                <DialogContent className="p-0" showCloseButton={false}>
                    <VisuallyHidden>
                        <DialogTitle>Seleccionar producto o combo</DialogTitle>
                        <DialogDescription>Busca y selecciona un producto o combo de la lista</DialogDescription>
                    </VisuallyHidden>
                    <Command shouldFilter={false}>
                        <CommandInput placeholder={placeholder} value={search} onValueChange={setSearch} />
                        <CommandList>
                            <CommandEmpty>No se encontraron resultados</CommandEmpty>
                            {Object.entries(filteredItems).map(([groupName, items]) => (
                                <CommandGroup key={groupName} heading={groupName}>
                                    {items.map((item) => (
                                        <CommandItem
                                            key={`${item.type}-${item.id}`}
                                            value={`${item.type}-${item.id}`}
                                            onSelect={() => {
                                                onChange(item.id === value && type === item.type ? null : item.id, item.type);
                                                setOpen(false);
                                                setSearch('');
                                            }}
                                        >
                                            <Check className={`mr-2 h-4 w-4 ${item.id === value && type === item.type ? 'opacity-100' : 'opacity-0'}`} />
                                            <div className="flex flex-1 items-center justify-between gap-2">
                                                <div className="flex flex-col">
                                                    <span>{item.name}</span>
                                                    {item.category && <span className="text-xs text-muted-foreground">{item.category.name}</span>}
                                                </div>
                                                <div className="flex gap-1">
                                                    <Badge variant="outline" className="shrink-0">
                                                        {item.type === 'combo' ? 'Combo' : 'Producto'}
                                                    </Badge>
                                                    <Badge variant={item.is_active ? 'default' : 'secondary'} className="shrink-0">
                                                        {item.is_active ? 'Activo' : 'Inactivo'}
                                                    </Badge>
                                                </div>
                                            </div>
                                        </CommandItem>
                                    ))}
                                </CommandGroup>
                            ))}
                        </CommandList>
                    </Command>
                </DialogContent>
            </Dialog>

            {error && <p className="text-sm font-medium text-destructive">{error}</p>}
        </div>
    );
}
