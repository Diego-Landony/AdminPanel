import { Check, ChevronsUpDown } from 'lucide-react';
import * as React from 'react';

import { Button } from '@/components/ui/button';
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
import { Label } from '@/components/ui/label';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
} from '@/components/ui/dialog';
import { VisuallyHidden } from '@/components/ui/visually-hidden';

interface Product {
    id: number;
    name: string;
    category?: {
        id: number;
        name: string;
    };
}

interface ProductComboboxProps {
    value: number | null;
    onChange: (value: number | null) => void;
    products: Product[];
    label?: string;
    placeholder?: string;
    error?: string;
    required?: boolean;
}

export function ProductCombobox({
    value,
    onChange,
    products,
    label = 'Producto',
    placeholder = 'Seleccionar producto...',
    error,
    required = false,
}: ProductComboboxProps) {
    const [open, setOpen] = React.useState(false);
    const [search, setSearch] = React.useState('');

    const selectedProduct = products.find((p) => p.id === value);

    // Agrupar productos por categoría
    const productsByCategory = products.reduce<Record<string, Product[]>>((acc, product) => {
        const categoryName = product.category?.name || 'Sin categoría';
        if (!acc[categoryName]) {
            acc[categoryName] = [];
        }
        acc[categoryName].push(product);
        return acc;
    }, {});

    // Filtrar productos según búsqueda
    const filteredProducts = React.useMemo(() => {
        if (!search) return productsByCategory;

        const searchLower = search.toLowerCase();
        const filtered: Record<string, Product[]> = {};

        Object.entries(productsByCategory).forEach(([category, prods]) => {
            const matchingProds = prods.filter(
                (p) =>
                    p.name.toLowerCase().includes(searchLower) ||
                    p.category?.name.toLowerCase().includes(searchLower)
            );

            if (matchingProds.length > 0) {
                filtered[category] = matchingProds;
            }
        });

        return filtered;
    }, [search, productsByCategory]);

    return (
        <div className="space-y-2">
            {label && (
                <Label>
                    {label}
                    {required && <span className="text-destructive ml-1">*</span>}
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
                    {selectedProduct ? (
                        <span className="truncate">
                            {selectedProduct.name}
                            {selectedProduct.category && (
                                <span className="text-muted-foreground ml-2">
                                    ({selectedProduct.category.name})
                                </span>
                            )}
                        </span>
                    ) : (
                        <span className="text-muted-foreground">{placeholder}</span>
                    )}
                    <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                </Button>

                <DialogContent className="p-0" showCloseButton={false}>
                    <VisuallyHidden>
                        <DialogTitle>Seleccionar producto</DialogTitle>
                        <DialogDescription>Busca y selecciona un producto de la lista</DialogDescription>
                    </VisuallyHidden>
                    <Command shouldFilter={false}>
                        <CommandInput
                            placeholder={placeholder}
                            value={search}
                            onValueChange={setSearch}
                        />
                        <CommandList>
                            <CommandEmpty>No se encontraron productos</CommandEmpty>
                            {Object.entries(filteredProducts).map(([categoryName, prods]) => (
                                <CommandGroup key={categoryName} heading={categoryName}>
                                    {prods.map((product) => (
                                        <CommandItem
                                            key={product.id}
                                            value={product.id.toString()}
                                            onSelect={() => {
                                                onChange(product.id === value ? null : product.id);
                                                setOpen(false);
                                                setSearch('');
                                            }}
                                        >
                                            <Check
                                                className={`mr-2 h-4 w-4 ${
                                                    product.id === value ? 'opacity-100' : 'opacity-0'
                                                }`}
                                            />
                                            <div className="flex flex-col">
                                                <span>{product.name}</span>
                                                {product.category && (
                                                    <span className="text-xs text-muted-foreground">
                                                        {product.category.name}
                                                    </span>
                                                )}
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
