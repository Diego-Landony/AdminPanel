import { Head, router } from '@inertiajs/react';
import { useState } from 'react';

import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import { Shield, ArrowLeft } from 'lucide-react';

/**
 * Interfaz para el formulario
 */
interface FormData {
    name: string;
    display_name: string;
    points_required: number;
    multiplier: number;
    color: string;
    is_active: boolean;
    sort_order: number;
}

/**
 * Página para crear un tipo de cliente
 */
export default function CustomerTypeCreate() {
    const [formData, setFormData] = useState<FormData>({
        name: '',
        display_name: '',
        points_required: 0,
        multiplier: 1.0,
        color: 'blue',
        is_active: true,
        sort_order: 0,
    });

    const [errors, setErrors] = useState<Record<string, string>>({});
    const [isSubmitting, setIsSubmitting] = useState(false);

    const handleInputChange = (field: keyof FormData, value: string | number | boolean) => {
        setFormData(prev => ({
            ...prev,
            [field]: value
        }));

        // Clear error when user starts typing
        if (errors[field]) {
            setErrors(prev => {
                const newErrors = { ...prev };
                delete newErrors[field];
                return newErrors;
            });
        }

        // Auto-generate name from display_name
        if (field === 'display_name') {
            const generatedName = (value as string)
                .toLowerCase()
                .replace(/[^a-z0-9\s]/g, '')
                .replace(/\s+/g, '_')
                .trim();
            
            setFormData(prev => ({
                ...prev,
                name: generatedName
            }));
        }
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);

        router.post('/customer-types', formData, {
            onSuccess: () => {
                // La redirección la maneja el controlador
            },
            onError: (errors) => {
                setErrors(errors as Record<string, string>);
                setIsSubmitting(false);
            }
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
        <AppLayout>
            <Head title="Crear Tipo de Cliente" />
            
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-6">
                {/* Encabezado */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Crear Tipo de Cliente</h1>
                        <p className="text-muted-foreground">
                            Crea un nuevo tipo de cliente con sus multiplicadores y requisitos
                        </p>
                    </div>
                    <Button
                        variant="outline"
                        onClick={() => router.get('/customer-types')}
                    >
                        <ArrowLeft className="mr-2 h-4 w-4" />
                        Volver
                    </Button>
                </div>

                {/* Formulario */}
                <Card className="border border-muted/50 shadow-sm max-w-2xl">
                    <CardHeader className="pb-6">
                        <div className="flex items-center gap-3">
                            <div className="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center">
                                <Shield className="w-5 h-5 text-primary" />
                            </div>
                            <div>
                                <h2 className="text-xl font-semibold">Información del Tipo</h2>
                                <p className="text-sm text-muted-foreground">
                                    Complete los datos del nuevo tipo de cliente
                                </p>
                            </div>
                        </div>
                    </CardHeader>

                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-6">
                            {/* Nombre para mostrar */}
                            <div className="space-y-2">
                                <Label htmlFor="display_name">Nombre para mostrar *</Label>
                                <Input
                                    id="display_name"
                                    type="text"
                                    value={formData.display_name}
                                    onChange={(e) => handleInputChange('display_name', e.target.value)}
                                    placeholder="ej: Bronce, Plata, Oro"
                                    className={errors.display_name ? 'border-destructive' : ''}
                                />
                                {errors.display_name && (
                                    <p className="text-sm text-destructive">{errors.display_name}</p>
                                )}
                            </div>

                            {/* Nombre interno */}
                            <div className="space-y-2">
                                <Label htmlFor="name">Nombre interno *</Label>
                                <Input
                                    id="name"
                                    type="text"
                                    value={formData.name}
                                    onChange={(e) => handleInputChange('name', e.target.value)}
                                    placeholder="ej: bronze, silver, gold"
                                    className={errors.name ? 'border-destructive' : ''}
                                />
                                {errors.name && (
                                    <p className="text-sm text-destructive">{errors.name}</p>
                                )}
                                <p className="text-xs text-muted-foreground">
                                    Se genera automáticamente pero puede editarse
                                </p>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                {/* Puntos requeridos */}
                                <div className="space-y-2">
                                    <Label htmlFor="points_required">Puntos requeridos *</Label>
                                    <Input
                                        id="points_required"
                                        type="number"
                                        min="0"
                                        value={formData.points_required}
                                        onChange={(e) => handleInputChange('points_required', parseInt(e.target.value) || 0)}
                                        className={errors.points_required ? 'border-destructive' : ''}
                                    />
                                    {errors.points_required && (
                                        <p className="text-sm text-destructive">{errors.points_required}</p>
                                    )}
                                </div>

                                {/* Multiplicador */}
                                <div className="space-y-2">
                                    <Label htmlFor="multiplier">Multiplicador *</Label>
                                    <Input
                                        id="multiplier"
                                        type="number"
                                        min="1"
                                        max="10"
                                        step="0.01"
                                        value={formData.multiplier}
                                        onChange={(e) => handleInputChange('multiplier', parseFloat(e.target.value) || 1.0)}
                                        className={errors.multiplier ? 'border-destructive' : ''}
                                    />
                                    {errors.multiplier && (
                                        <p className="text-sm text-destructive">{errors.multiplier}</p>
                                    )}
                                </div>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                {/* Color */}
                                <div className="space-y-2">
                                    <Label htmlFor="color">Color</Label>
                                    <Select value={formData.color} onValueChange={(value) => handleInputChange('color', value)}>
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {colorOptions.map((color) => (
                                                <SelectItem key={color.value} value={color.value}>
                                                    <div className="flex items-center gap-2">
                                                        <div className={`w-4 h-4 rounded ${color.class}`}></div>
                                                        {color.label}
                                                    </div>
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                {/* Orden */}
                                <div className="space-y-2">
                                    <Label htmlFor="sort_order">Orden de clasificación</Label>
                                    <Input
                                        id="sort_order"
                                        type="number"
                                        min="0"
                                        value={formData.sort_order}
                                        onChange={(e) => handleInputChange('sort_order', parseInt(e.target.value) || 0)}
                                    />
                                    <p className="text-xs text-muted-foreground">
                                        Número menor aparece primero
                                    </p>
                                </div>
                            </div>

                            {/* Estado activo */}
                            <div className="flex items-center space-x-2">
                                <Checkbox
                                    id="is_active"
                                    checked={formData.is_active}
                                    onCheckedChange={(checked) => handleInputChange('is_active', checked as boolean)}
                                />
                                <Label htmlFor="is_active" className="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                                    Tipo activo
                                </Label>
                            </div>

                            {/* Botones */}
                            <div className="flex gap-4 pt-4">
                                <Button type="submit" disabled={isSubmitting} className="flex-1">
                                    {isSubmitting ? 'Creando...' : 'Crear Tipo'}
                                </Button>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => router.get('/customer-types')}
                                    disabled={isSubmitting}
                                >
                                    Cancelar
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}