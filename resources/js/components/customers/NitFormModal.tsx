import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { FormField } from '@/components/ui/form-field';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { showNotification } from '@/hooks/useNotifications';
import { router } from '@inertiajs/react';
import { FileText } from 'lucide-react';
import React, { useEffect, useState } from 'react';

interface CustomerNit {
    id: number;
    nit: string;
    nit_type: 'personal' | 'company' | 'other';
    business_name: string | null;
    is_default: boolean;
}

interface NitFormModalProps {
    isOpen: boolean;
    onClose: () => void;
    customerId: number;
    nit?: CustomerNit | null;
    onSuccess?: () => void;
}

export const NitFormModal: React.FC<NitFormModalProps> = ({ isOpen, onClose, customerId, nit, onSuccess }) => {
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [formData, setFormData] = useState({
        nit: '',
        nit_type: 'personal' as 'personal' | 'company' | 'other',
        business_name: '',
        is_default: false,
    });

    useEffect(() => {
        if (nit) {
            setFormData({
                nit: nit.nit,
                nit_type: nit.nit_type,
                business_name: nit.business_name || '',
                is_default: nit.is_default,
            });
        } else {
            setFormData({
                nit: '',
                nit_type: 'personal',
                business_name: '',
                is_default: false,
            });
        }
        setErrors({});
    }, [nit, isOpen]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);
        setErrors({});

        const submitData = {
            ...formData,
            business_name: formData.business_name || null,
        };

        if (nit) {
            router.put(
                route('customers.nits.update', { customer: customerId, nit: nit.id }),
                submitData,
                {
                    onSuccess: () => {
                        showNotification.success('NIT actualizado exitosamente');
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
                route('customers.nits.store', { customer: customerId }),
                submitData,
                {
                    onSuccess: () => {
                        showNotification.success('NIT agregado exitosamente');
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
            <DialogContent className="max-w-lg">
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <FileText className="h-5 w-5" />
                            {nit ? 'Editar NIT' : 'Nuevo NIT'}
                        </DialogTitle>
                    </DialogHeader>

                    <div className="space-y-4 py-4">
                        <FormField label="Número de NIT" error={errors.nit} required>
                            <Input
                                type="text"
                                value={formData.nit}
                                onChange={(e) => setFormData({ ...formData, nit: e.target.value })}
                                placeholder="12345678-9"
                                className="font-mono"
                            />
                        </FormField>

                        <FormField label="Tipo de NIT" error={errors.nit_type} required>
                            <Select value={formData.nit_type} onValueChange={(value: 'personal' | 'company' | 'other') => setFormData({ ...formData, nit_type: value })}>
                                <SelectTrigger>
                                    <SelectValue placeholder="Selecciona el tipo de NIT" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="personal">Personal</SelectItem>
                                    <SelectItem value="company">Empresa</SelectItem>
                                    <SelectItem value="other">Otro</SelectItem>
                                </SelectContent>
                            </Select>
                        </FormField>

                        {formData.nit_type === 'company' && (
                            <FormField label="Nombre de Empresa" error={errors.business_name} description="Razón social de la empresa">
                                <Input
                                    type="text"
                                    value={formData.business_name}
                                    onChange={(e) => setFormData({ ...formData, business_name: e.target.value })}
                                    placeholder="Ej: Subway Guatemala S.A."
                                />
                            </FormField>
                        )}

                        <FormField label="" error={errors.is_default}>
                            <label className="flex items-center gap-2">
                                <input
                                    type="checkbox"
                                    checked={formData.is_default}
                                    onChange={(e) => setFormData({ ...formData, is_default: e.target.checked })}
                                    className="h-4 w-4 rounded border-gray-300"
                                />
                                <span className="text-sm">Marcar como NIT predeterminado</span>
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
                                    {nit ? 'Actualizando...' : 'Guardando...'}
                                </>
                            ) : (
                                <>{nit ? 'Actualizar' : 'Guardar'}</>
                            )}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
};
