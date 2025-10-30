import { useState, useMemo, useEffect } from 'react';
import { Search, PackageX } from 'lucide-react';

import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface Product {
    id: number;
    name: string;
    is_active: boolean;
}

interface ProductCheckboxListProps {
    products: Product[];
    selectedIds: number[];
    onChange: (selectedIds: number[]) => void;
    disabled?: boolean;
    label?: string;
}

export function ProductCheckboxList({ products, selectedIds, onChange, disabled = false, label = 'Productos' }: ProductCheckboxListProps) {
    const [search, setSearch] = useState('');
    const [debouncedSearch, setDebouncedSearch] = useState('');

    useEffect(() => {
        const timer = setTimeout(() => {
            setDebouncedSearch(search);
        }, 300);

        return () => clearTimeout(timer);
    }, [search]);

    const filteredProducts = useMemo(() => {
        if (!debouncedSearch) return products;

        const searchLower = debouncedSearch.toLowerCase();
        return products.filter((p) => p.name.toLowerCase().includes(searchLower));
    }, [debouncedSearch, products]);

    const handleToggle = (productId: number) => {
        if (selectedIds.includes(productId)) {
            onChange(selectedIds.filter((id) => id !== productId));
        } else {
            onChange([...selectedIds, productId]);
        }
    };

    const selectedCount = selectedIds.length;
    const totalCount = products.length;
    const inactiveSelectedCount = products.filter((p) => selectedIds.includes(p.id) && !p.is_active).length;

    if (products.length === 0) {
        return (
            <div className="space-y-2">
                <Label>{label}</Label>
                <div className="flex flex-col items-center justify-center rounded-lg border border-dashed p-8 text-center">
                    <PackageX className="mb-2 h-10 w-10 text-muted-foreground" />
                    <p className="text-sm font-medium text-muted-foreground">No hay productos disponibles</p>
                    <p className="text-xs text-muted-foreground">Selecciona una categoría diferente o verifica que la categoría tenga productos</p>
                </div>
            </div>
        );
    }

    return (
        <div className="space-y-3">
            <div className="flex items-center justify-between">
                <Label>{label}</Label>
                <span className="text-xs text-muted-foreground">
                    {selectedCount} de {totalCount} seleccionados
                </span>
            </div>

            <div className="relative">
                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                <Input
                    type="text"
                    placeholder="Buscar productos..."
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    className="pl-9"
                    disabled={disabled}
                />
            </div>

            {inactiveSelectedCount > 0 && (
                <div className="rounded-md bg-amber-50 p-3 dark:bg-amber-950/20">
                    <p className="text-sm text-amber-800 dark:text-amber-200">
                        ⚠ {inactiveSelectedCount} {inactiveSelectedCount === 1 ? 'producto inactivo seleccionado' : 'productos inactivos seleccionados'}. La
                        promoción no se aplicará hasta que se activen.
                    </p>
                </div>
            )}

            <div className="max-h-80 space-y-2 overflow-y-auto rounded-lg border p-3">
                {filteredProducts.length === 0 ? (
                    <div className="py-8 text-center">
                        <p className="text-sm text-muted-foreground">No se encontraron productos</p>
                    </div>
                ) : (
                    filteredProducts.map((product) => (
                        <label
                            key={product.id}
                            className="flex items-center gap-3 rounded-md p-2 transition-colors hover:bg-accent cursor-pointer"
                        >
                            <Checkbox
                                checked={selectedIds.includes(product.id)}
                                onCheckedChange={() => !disabled && handleToggle(product.id)}
                                disabled={disabled}
                            />
                            <div className="flex flex-1 items-center justify-between gap-2">
                                <span className="text-sm">{product.name}</span>
                                <Badge variant={product.is_active ? 'default' : 'secondary'}>{product.is_active ? 'Activo' : 'Inactivo'}</Badge>
                            </div>
                        </label>
                    ))
                )}
            </div>
        </div>
    );
}
