import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { FormField } from '@/components/ui/form-field';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { PLACEHOLDERS } from '@/constants/ui-constants';
import { showNotification } from '@/hooks/useNotifications';
import { router } from '@inertiajs/react';
import { MapPin } from 'lucide-react';
import React, { useEffect, useState } from 'react';

interface CustomerAddress {
    id: number;
    label: string | null;
    address_line: string;
    latitude: number | null;
    longitude: number | null;
    delivery_notes: string | null;
    is_default: boolean;
}

interface AddressFormModalProps {
    isOpen: boolean;
    onClose: () => void;
    customerId: number;
    address?: CustomerAddress | null;
    onSuccess?: () => void;
}

export const AddressFormModal: React.FC<AddressFormModalProps> = ({ isOpen, onClose, customerId, address, onSuccess }) => {
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [formData, setFormData] = useState({
        label: '',
        address_line: '',
        latitude: '',
        longitude: '',
        delivery_notes: '',
        is_default: false,
    });

    useEffect(() => {
        if (address) {
            setFormData({
                label: address.label || '',
                address_line: address.address_line,
                latitude: address.latitude?.toString() || '',
                longitude: address.longitude?.toString() || '',
                delivery_notes: address.delivery_notes || '',
                is_default: address.is_default,
            });
        } else {
            setFormData({
                label: '',
                address_line: '',
                latitude: '',
                longitude: '',
                delivery_notes: '',
                is_default: false,
            });
        }
        setErrors({});
    }, [address, isOpen]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);
        setErrors({});

        const submitData = {
            ...formData,
            latitude: formData.latitude ? parseFloat(formData.latitude) : null,
            longitude: formData.longitude ? parseFloat(formData.longitude) : null,
        };

        if (address) {
            router.put(
                route('customers.addresses.update', { customer: customerId, address: address.id }),
                submitData,
                {
                    onSuccess: () => {
                        showNotification.success('Dirección actualizada exitosamente');
                        onClose();
                        if (onSuccess) onSuccess();
                    },
                    onError: (errors) => {
                        setErrors(errors as Record<string, string>);
                        const firstError = Object.values(errors)[0];
                        if (typeof firstError === 'string') {
                            showNotification.error(firstError);
                        }
                    },
                    onFinish: () => setIsSubmitting(false),
                },
            );
        } else {
            router.post(
                route('customers.addresses.store', { customer: customerId }),
                submitData,
                {
                    onSuccess: () => {
                        showNotification.success('Dirección agregada exitosamente');
                        onClose();
                        if (onSuccess) onSuccess();
                    },
                    onError: (errors) => {
                        setErrors(errors as Record<string, string>);
                        const firstError = Object.values(errors)[0];
                        if (typeof firstError === 'string') {
                            showNotification.error(firstError);
                        }
                    },
                    onFinish: () => setIsSubmitting(false),
                },
            );
        }
    };

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="max-w-2xl">
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <MapPin className="h-5 w-5" />
                            {address ? 'Editar Dirección' : 'Nueva Dirección'}
                        </DialogTitle>
                    </DialogHeader>

                    <div className="space-y-4 py-4">
                        <FormField label="Etiqueta" error={errors.label} description="Ej: Casa, Oficina, etc.">
                            <Input
                                type="text"
                                value={formData.label}
                                onChange={(e) => setFormData({ ...formData, label: e.target.value })}
                                placeholder={PLACEHOLDERS.addressLabel || 'Casa, Oficina, etc.'}
                            />
                        </FormField>

                        <FormField label="Dirección" error={errors.address_line} required>
                            <Textarea
                                value={formData.address_line}
                                onChange={(e) => setFormData({ ...formData, address_line: e.target.value })}
                                placeholder="Dirección completa..."
                                rows={3}
                            />
                        </FormField>

                        <div className="grid grid-cols-2 gap-4">
                            <FormField label="Latitud" error={errors.latitude} description="Coordenada GPS">
                                <Input
                                    type="number"
                                    step="any"
                                    value={formData.latitude}
                                    onChange={(e) => setFormData({ ...formData, latitude: e.target.value })}
                                    placeholder="14.634915"
                                />
                            </FormField>

                            <FormField label="Longitud" error={errors.longitude} description="Coordenada GPS">
                                <Input
                                    type="number"
                                    step="any"
                                    value={formData.longitude}
                                    onChange={(e) => setFormData({ ...formData, longitude: e.target.value })}
                                    placeholder="-90.506882"
                                />
                            </FormField>
                        </div>

                        <FormField label="Notas de Entrega" error={errors.delivery_notes} description="Instrucciones adicionales">
                            <Textarea
                                value={formData.delivery_notes}
                                onChange={(e) => setFormData({ ...formData, delivery_notes: e.target.value })}
                                placeholder="Ej: Casa con portón azul, tocar el timbre..."
                                rows={2}
                            />
                        </FormField>

                        <FormField label="" error={errors.is_default}>
                            <label className="flex items-center gap-2">
                                <input
                                    type="checkbox"
                                    checked={formData.is_default}
                                    onChange={(e) => setFormData({ ...formData, is_default: e.target.checked })}
                                    className="h-4 w-4 rounded border-gray-300"
                                />
                                <span className="text-sm">Marcar como dirección predeterminada</span>
                            </label>
                        </FormField>
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={onClose} disabled={isSubmitting}>
                            Cancelar
                        </Button>
                        <Button type="submit" disabled={isSubmitting}>
                            {isSubmitting ? (
                                <>
                                    <div className="mr-2 h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent" />
                                    {address ? 'Actualizando...' : 'Guardando...'}
                                </>
                            ) : (
                                <>{address ? 'Actualizar' : 'Guardar'}</>
                            )}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
};
