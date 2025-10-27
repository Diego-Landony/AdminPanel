import { Check, ChevronsUpDown } from 'lucide-react';
import * as React from 'react';

import { Button } from '@/components/ui/button';
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
import { Dialog, DialogContent, DialogDescription, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { VisuallyHidden } from '@/components/ui/visually-hidden';

interface Category {
    id: number;
    name: string;
}

interface CategoryComboboxProps {
    value: number | null;
    onChange: (value: number | null) => void;
    categories: Category[];
    label?: string;
    placeholder?: string;
    error?: string;
    required?: boolean;
}

export function CategoryCombobox({
    value,
    onChange,
    categories,
    label = 'Categoría',
    placeholder = 'Seleccionar categoría...',
    error,
    required = false,
}: CategoryComboboxProps) {
    const [open, setOpen] = React.useState(false);
    const [search, setSearch] = React.useState('');

    const selectedCategory = categories.find((c) => c.id === value);

    const filteredCategories = React.useMemo(() => {
        if (!search) return categories;

        const searchLower = search.toLowerCase();
        return categories.filter((c) => c.name.toLowerCase().includes(searchLower));
    }, [search, categories]);

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
                    {selectedCategory ? (
                        <span className="truncate">{selectedCategory.name}</span>
                    ) : (
                        <span className="text-muted-foreground">{placeholder}</span>
                    )}
                    <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                </Button>

                <DialogContent className="p-0" showCloseButton={false}>
                    <VisuallyHidden>
                        <DialogTitle>Seleccionar categoría</DialogTitle>
                        <DialogDescription>Busca y selecciona una categoría de la lista</DialogDescription>
                    </VisuallyHidden>
                    <Command shouldFilter={false}>
                        <CommandInput placeholder={placeholder} value={search} onValueChange={setSearch} />
                        <CommandList>
                            <CommandEmpty>No se encontraron categorías</CommandEmpty>
                            <CommandGroup>
                                {filteredCategories.map((category) => (
                                    <CommandItem
                                        key={category.id}
                                        value={category.id.toString()}
                                        onSelect={() => {
                                            onChange(category.id === value ? null : category.id);
                                            setOpen(false);
                                            setSearch('');
                                        }}
                                    >
                                        <Check className={`mr-2 h-4 w-4 ${category.id === value ? 'opacity-100' : 'opacity-0'}`} />
                                        <span>{category.name}</span>
                                    </CommandItem>
                                ))}
                            </CommandGroup>
                        </CommandList>
                    </Command>
                </DialogContent>
            </Dialog>

            {error && <p className="text-sm font-medium text-destructive">{error}</p>}
        </div>
    );
}
