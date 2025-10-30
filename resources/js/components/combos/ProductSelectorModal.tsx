import { ProductCombobox } from '@/components/ProductCombobox';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { NOTIFICATIONS } from '@/constants/ui-constants';
import { showNotification } from '@/hooks/useNotifications';
import { useState } from 'react';

interface Product {
    id: number;
    name: string;
    has_variants: boolean;
    is_active: boolean;
    variants?: ProductVariant[];
    category?: {
        id: number;
        name: string;
    };
}

interface ProductVariant {
    id: number;
    name: string;
    size: string;
}

interface ProductSelectorModalProps {
    isOpen: boolean;
    onClose: () => void;
    onSelect: (product: Product, variant?: ProductVariant) => void;
    products: Product[];
    excludeIds?: Array<{ productId: number; variantId?: number | null }>;
}

export function ProductSelectorModal({ isOpen, onClose, onSelect, products, excludeIds = [] }: ProductSelectorModalProps) {
    const [selectedProductId, setSelectedProductId] = useState<number | null>(null);
    const [selectedVariantId, setSelectedVariantId] = useState<string>('');

    const selectedProduct = products.find((p) => p.id === selectedProductId);

    const handleConfirm = () => {
        if (!selectedProduct) return;

        const variant = selectedProduct.variants?.find((v) => v.id === Number(selectedVariantId));

        const isExcluded = excludeIds.some(
            (ex) => ex.productId === selectedProduct.id && (ex.variantId === null ? !variant : ex.variantId === variant?.id),
        );

        if (isExcluded) {
            showNotification.error(NOTIFICATIONS.error.duplicateOption);
            return;
        }

        onSelect(selectedProduct, variant);
        handleClose();
    };

    const handleClose = () => {
        setSelectedProductId(null);
        setSelectedVariantId('');
        onClose();
    };

    const availableProducts = products.filter((product) => {
        if (!product.has_variants) {
            return !excludeIds.some((ex) => ex.productId === product.id);
        }
        return true;
    });

    const canConfirm = selectedProduct && (!selectedProduct.has_variants || selectedVariantId);

    return (
        <Dialog open={isOpen} onOpenChange={handleClose}>
            <DialogContent className="max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Seleccionar Producto</DialogTitle>
                </DialogHeader>

                <div className="space-y-4">
                    <ProductCombobox
                        label="Producto"
                        value={selectedProductId}
                        onChange={setSelectedProductId}
                        products={availableProducts}
                        placeholder="Buscar producto..."
                    />

                    {selectedProduct?.has_variants && (
                        <div className="space-y-2">
                            <Label>Variante</Label>
                            <Select value={selectedVariantId} onValueChange={setSelectedVariantId}>
                                <SelectTrigger>
                                    <SelectValue placeholder="Selecciona una variante" />
                                </SelectTrigger>
                                <SelectContent>
                                    {selectedProduct.variants?.map((variant) => (
                                        <SelectItem key={variant.id} value={String(variant.id)}>
                                            {variant.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    )}

                    {selectedProduct && !selectedProduct.is_active && (
                        <div className="rounded-md bg-amber-50 p-3 dark:bg-amber-950/20">
                            <p className="text-sm text-amber-800 dark:text-amber-200">
                                ⚠ Producto inactivo seleccionado. El combo no estará disponible para los clientes hasta que se active este producto.
                            </p>
                        </div>
                    )}

                    <div className="flex justify-end gap-3">
                        <Button variant="outline" onClick={handleClose}>
                            Cancelar
                        </Button>
                        <Button onClick={handleConfirm} disabled={!canConfirm}>
                            Agregar
                        </Button>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    );
}
