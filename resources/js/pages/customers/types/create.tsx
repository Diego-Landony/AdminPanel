import { showNotification } from '@/hooks/useNotifications';
import { useForm } from '@inertiajs/react';
import { Hash, Shield, Star } from 'lucide-react';
import React from 'react';

import { CreatePageLayout } from '@/components/create-page-layout';
import { FormSection } from '@/components/form-section';
import { Checkbox } from '@/components/ui/checkbox';
import { FormField } from '@/components/ui/form-field';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

/**
 * Página para crear un tipo de cliente
 */
export default function CustomerTypeCreate() {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        display_name: '',
        points_required: 0,
        multiplier: 1.0,
        color: 'blue',
        is_active: true,
        sort_order: 0,
    });

    const handleDisplayNameChange = (value: string) => {
        setData('display_name', value);

        // Auto-generate name from display_name
        const generatedName = value
            .toLowerCase()
            .replace(/[^a-z0-9\s]/g, '')
            .replace(/\s+/g, '_')
            .trim();

        setData('name', generatedName);
    };

    /**
     * Maneja el envío del formulario
     */
    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        post(route('customer-types.store'), {
            onSuccess: () => {
                reset();
            },
            onError: (errors) => {
                // Los errores de validación se muestran automáticamente
                // Los errores del servidor se manejan por el layout
                if (Object.keys(errors).length === 0) {
                    showNotification.error('Error del servidor al crear el tipo de cliente. Inténtalo de nuevo.');
                }
            },
        });
    };

    const colorOptions = [
        { value: 'gray', label: 'Gris', class: 'bg-gray-100 text-gray-800 border border-gray-200' },
        { value: 'blue', label: 'Azul', class: 'bg-blue-100 text-blue-800 border border-blue-200' },
        { value: 'green', label: 'Verde', class: 'bg-green-100 text-green-800 border border-green-200' },
        { value: 'yellow', label: 'Amarillo', class: 'bg-yellow-100 text-yellow-800 border border-yellow-200' },
        { value: 'orange', label: 'Naranja', class: 'bg-orange-100 text-orange-800 border border-orange-200' },
        { value: 'red', label: 'Rojo', class: 'bg-red-100 text-red-800 border border-red-200' },
        { value: 'purple', label: 'Púrpura', class: 'bg-purple-100 text-purple-800 border border-purple-200' },
        { value: 'slate', label: 'Pizarra', class: 'bg-slate-100 text-slate-800 border border-slate-200' },
    ];

    return (
        <CreatePageLayout
            title="Crear Tipo de Cliente"
            description="Crea un nuevo tipo de cliente con sus multiplicadores y requisitos"
            backHref={route('customer-types.index')}
            backLabel="Volver a Tipos de Cliente"
            onSubmit={handleSubmit}
            submitLabel="Crear Tipo"
            processing={processing}
            pageTitle="Crear Tipo de Cliente"
        >
            <FormSection icon={Shield} title="Información del Tipo" description="Complete los datos del nuevo tipo de cliente">
                {/* Nombre para mostrar */}
                <FormField label="Nombre para mostrar" error={errors.display_name} required>
                    <Input
                        id="display_name"
                        type="text"
                        value={data.display_name}
                        onChange={(e) => handleDisplayNameChange(e.target.value)}
                        placeholder="ej: Bronce, Plata, Oro"
                    />
                </FormField>

                {/* Nombre interno */}
                <FormField label="Nombre interno" error={errors.name} description="Se genera automáticamente pero puede editarse" required>
                    <div className="relative">
                        <Hash className="absolute top-3 left-3 h-4 w-4 text-muted-foreground" />
                        <Input
                            id="name"
                            type="text"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            placeholder="ej: bronze, silver, gold"
                            className="pl-10"
                        />
                    </div>
                </FormField>

                <div className="grid grid-cols-1 gap-6">
                    {/* Puntos requeridos */}
                    <FormField label="Puntos requeridos" error={errors.points_required} required>
                        <div className="relative">
                            <Star className="absolute top-3 left-3 h-4 w-4 text-muted-foreground" />
                            <Input
                                id="points_required"
                                type="number"
                                min="0"
                                value={data.points_required}
                                onChange={(e) => setData('points_required', parseInt(e.target.value) || 0)}
                                className="pl-10"
                            />
                        </div>
                    </FormField>

                    {/* Multiplicador */}
                    <FormField label="Multiplicador" error={errors.multiplier} required>
                        <Input
                            id="multiplier"
                            type="number"
                            min="1"
                            max="10"
                            step="0.01"
                            value={data.multiplier}
                            onChange={(e) => setData('multiplier', parseFloat(e.target.value) || 1.0)}
                        />
                    </FormField>
                </div>

                <div className="grid grid-cols-1 gap-6">
                    {/* Color */}
                    <FormField label="Color" error={errors.color}>
                        <Select value={data.color} onValueChange={(value) => setData('color', value)}>
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {colorOptions.map((color) => (
                                    <SelectItem key={color.value} value={color.value}>
                                        <div className="flex items-center gap-2">
                                            <div className={`h-4 w-4 rounded ${color.class}`}></div>
                                            {color.label}
                                        </div>
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </FormField>

                    {/* Orden */}
                    <FormField label="Orden de clasificación" error={errors.sort_order} description="Número menor aparece primero">
                        <Input
                            id="sort_order"
                            type="number"
                            min="0"
                            value={data.sort_order}
                            onChange={(e) => setData('sort_order', parseInt(e.target.value) || 0)}
                        />
                    </FormField>
                </div>

                {/* Estado activo */}
                <div className="flex items-center space-x-2">
                    <Checkbox id="is_active" checked={data.is_active} onCheckedChange={(checked) => setData('is_active', checked as boolean)} />
                    <Label htmlFor="is_active" className="text-sm leading-none font-medium peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                        Tipo activo
                    </Label>
                </div>
            </FormSection>
        </CreatePageLayout>
    );
}
