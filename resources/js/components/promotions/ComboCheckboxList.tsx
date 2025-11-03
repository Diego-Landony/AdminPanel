import { useState, useMemo, useEffect } from 'react';
import { Search, PackageX } from 'lucide-react';

import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface Combo {
    id: number;
    name: string;
    is_active: boolean;
}

interface ComboCheckboxListProps {
    combos: Combo[];
    selectedIds: number[];
    onChange: (selectedIds: number[]) => void;
    disabled?: boolean;
    label?: string;
}

export function ComboCheckboxList({ combos, selectedIds, onChange, disabled = false, label = 'Combos' }: ComboCheckboxListProps) {
    const [search, setSearch] = useState('');
    const [debouncedSearch, setDebouncedSearch] = useState('');

    useEffect(() => {
        const timer = setTimeout(() => {
            setDebouncedSearch(search);
        }, 300);

        return () => clearTimeout(timer);
    }, [search]);

    const filteredCombos = useMemo(() => {
        if (!debouncedSearch) return combos;

        const searchLower = debouncedSearch.toLowerCase();
        return combos.filter((c) => c.name.toLowerCase().includes(searchLower));
    }, [debouncedSearch, combos]);

    const handleToggle = (comboId: number) => {
        if (selectedIds.includes(comboId)) {
            onChange(selectedIds.filter((id) => id !== comboId));
        } else {
            onChange([...selectedIds, comboId]);
        }
    };

    const selectedCount = selectedIds.length;
    const totalCount = combos.length;
    const inactiveSelectedCount = combos.filter((c) => selectedIds.includes(c.id) && !c.is_active).length;

    if (combos.length === 0) {
        return (
            <div className="space-y-2">
                <Label>{label}</Label>
                <div className="flex flex-col items-center justify-center rounded-lg border border-dashed p-8 text-center">
                    <PackageX className="mb-2 h-10 w-10 text-muted-foreground" />
                    <p className="text-sm font-medium text-muted-foreground">No hay combos disponibles</p>
                    <p className="text-xs text-muted-foreground">Selecciona una categoría diferente o verifica que la categoría tenga combos</p>
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
                    placeholder="Buscar combos..."
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    className="pl-9"
                    disabled={disabled}
                />
            </div>

            {inactiveSelectedCount > 0 && (
                <div className="rounded-md bg-amber-50 p-3 dark:bg-amber-950/20">
                    <p className="text-sm text-amber-800 dark:text-amber-200">
                        ⚠ {inactiveSelectedCount} {inactiveSelectedCount === 1 ? 'combo inactivo seleccionado' : 'combos inactivos seleccionados'}. La
                        promoción no se aplicará hasta que se activen.
                    </p>
                </div>
            )}

            <div className="max-h-80 space-y-2 overflow-y-auto rounded-lg border p-3">
                {filteredCombos.length === 0 ? (
                    <div className="py-8 text-center">
                        <p className="text-sm text-muted-foreground">No se encontraron combos</p>
                    </div>
                ) : (
                    filteredCombos.map((combo) => (
                        <label
                            key={combo.id}
                            className="flex items-center gap-3 rounded-md p-2 transition-colors hover:bg-accent cursor-pointer"
                        >
                            <Checkbox
                                checked={selectedIds.includes(combo.id)}
                                onCheckedChange={() => !disabled && handleToggle(combo.id)}
                                disabled={disabled}
                            />
                            <div className="flex flex-1 items-center justify-between gap-2">
                                <span className="text-sm">{combo.name}</span>
                                <Badge variant={combo.is_active ? 'default' : 'secondary'}>{combo.is_active ? 'Activo' : 'Inactivo'}</Badge>
                            </div>
                        </label>
                    ))
                )}
            </div>
        </div>
    );
}
