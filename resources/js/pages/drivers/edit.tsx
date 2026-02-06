import { showNotification } from '@/hooks/useNotifications';
import { useForm } from '@inertiajs/react';
import { Building2, Check, ChevronsUpDown, Eye, EyeOff, Lock, Mail, User } from 'lucide-react';
import React, { useState } from 'react';

import { EditPageLayout } from '@/components/edit-page-layout';
import { FormSection } from '@/components/form-section';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
import { FormField } from '@/components/ui/form-field';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Switch } from '@/components/ui/switch';
import { cn } from '@/lib/utils';
import { AUTOCOMPLETE, FIELD_DESCRIPTIONS, NOTIFICATIONS, PLACEHOLDERS } from '@/constants/ui-constants';
import { Driver, Restaurant } from '@/types';

interface EditDriverProps {
    driver: Driver;
    restaurants: Restaurant[];
}

/**
 * Pagina para editar un motorista existente
 */
export default function EditDriver({ driver, restaurants }: EditDriverProps) {
    const [showPassword, setShowPassword] = useState(false);
    const [changePassword, setChangePassword] = useState(false);
    const [restaurantOpen, setRestaurantOpen] = useState(false);

    const { data, setData, patch, processing, errors } = useForm({
        restaurant_id: driver.restaurant_id?.toString() || '',
        name: driver.name || '',
        email: driver.email || '',
        password: '',
        password_confirmation: '',
        is_active: driver.is_active,
    });

    /**
     * Maneja el envio del formulario
     */
    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        const submitData: Record<string, unknown> = { ...data };
        if (!changePassword) {
            delete submitData.password;
            delete submitData.password_confirmation;
        }

        patch(route('drivers.update', driver.id), {
            onSuccess: () => {
                if (changePassword) {
                    setChangePassword(false);
                    setData('password', '');
                    setData('password_confirmation', '');
                }
            },
            onError: (errors) => {
                if (Object.keys(errors).length === 0) {
                    showNotification.error(NOTIFICATIONS.error.server);
                }
            },
        });
    };

    /**
     * Formatea una fecha para mostrar
     */
    const formatDate = (dateString: string | null) => {
        if (!dateString) return 'Nunca';
        try {
            return new Date(dateString).toLocaleDateString('es-GT', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
            });
        } catch {
            return 'Fecha invalida';
        }
    };

    return (
        <EditPageLayout
            title="Editar Motorista"
            description={`Modifica la informacion de ${driver.name}`}
            backHref={route('drivers.index')}
            onSubmit={handleSubmit}
            processing={processing}
            pageTitle={`Editar Motorista - ${driver.name}`}
            loading={processing}
        >
            <div className="space-y-8">
                <Card>
                    <CardContent className="pt-6">
                        <FormSection icon={User} title="Informacion del Motorista" description="Datos basicos del motorista">
                            <div className="space-y-6">
                                <FormField label="Restaurante" error={errors.restaurant_id} required>
                                    <Popover open={restaurantOpen} onOpenChange={setRestaurantOpen}>
                                        <PopoverTrigger asChild>
                                            <Button
                                                variant="outline"
                                                role="combobox"
                                                aria-expanded={restaurantOpen}
                                                className="w-full justify-between font-normal"
                                            >
                                                <span className="flex items-center gap-2 truncate">
                                                    <Building2 className="h-4 w-4 shrink-0 text-muted-foreground" />
                                                    {data.restaurant_id
                                                        ? restaurants.find((r) => r.id.toString() === data.restaurant_id)?.name
                                                        : 'Buscar restaurante...'}
                                                </span>
                                                <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                                            </Button>
                                        </PopoverTrigger>
                                        <PopoverContent className="w-[--radix-popover-trigger-width] p-0" align="start">
                                            <Command>
                                                <CommandInput placeholder="Buscar restaurante..." />
                                                <CommandList>
                                                    <CommandEmpty>No se encontraron restaurantes.</CommandEmpty>
                                                    <CommandGroup>
                                                        {restaurants.map((restaurant) => (
                                                            <CommandItem
                                                                key={restaurant.id}
                                                                value={restaurant.name}
                                                                onSelect={() => {
                                                                    setData('restaurant_id', restaurant.id.toString());
                                                                    setRestaurantOpen(false);
                                                                }}
                                                            >
                                                                <Check
                                                                    className={cn(
                                                                        'mr-2 h-4 w-4',
                                                                        data.restaurant_id === restaurant.id.toString()
                                                                            ? 'opacity-100'
                                                                            : 'opacity-0',
                                                                    )}
                                                                />
                                                                {restaurant.name}
                                                            </CommandItem>
                                                        ))}
                                                    </CommandGroup>
                                                </CommandList>
                                            </Command>
                                        </PopoverContent>
                                    </Popover>
                                </FormField>

                                <FormField label="Nombre Completo" error={errors.name} required>
                                    <Input
                                        id="name"
                                        type="text"
                                        value={data.name}
                                        onChange={(e) => setData('name', e.target.value)}
                                        autoComplete={AUTOCOMPLETE.name}
                                    />
                                </FormField>

                                <FormField label="Correo Electronico" error={errors.email} required>
                                    <div className="relative">
                                        <Mail className="absolute top-3 left-3 h-4 w-4 text-muted-foreground" />
                                        <Input
                                            id="email"
                                            type="email"
                                            placeholder={PLACEHOLDERS.email}
                                            value={data.email}
                                            onChange={(e) => setData('email', e.target.value)}
                                            className="pl-10"
                                            autoComplete={AUTOCOMPLETE.email}
                                        />
                                    </div>
                                </FormField>

                                <div className="flex items-center justify-between">
                                    <Label htmlFor="is_active" className="cursor-pointer">
                                        Motorista Activo
                                    </Label>
                                    <Switch id="is_active" checked={data.is_active} onCheckedChange={(checked) => setData('is_active', checked)} />
                                </div>
                            </div>
                        </FormSection>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="pt-6">
                        <FormSection icon={Lock} title="Cambiar Contrasena" description="Opcional: Cambiar la contrasena del motorista">
                            <div className="space-y-4">
                                <div className="flex items-center space-x-2 py-2">
                                    <Checkbox
                                        id="change-password"
                                        checked={changePassword}
                                        onCheckedChange={(checked) => setChangePassword(checked as boolean)}
                                    />
                                    <Label htmlFor="change-password">Cambiar contrasena</Label>
                                </div>

                                {changePassword && (
                                    <div className="space-y-4">
                                        <FormField label="Nueva Contrasena" error={errors.password} description={FIELD_DESCRIPTIONS.password}>
                                            <div className="relative">
                                                <Lock className="absolute top-3 left-3 h-4 w-4 text-muted-foreground" />
                                                <Input
                                                    id="password"
                                                    type={showPassword ? 'text' : 'password'}
                                                    placeholder={PLACEHOLDERS.password}
                                                    value={data.password}
                                                    onChange={(e) => setData('password', e.target.value)}
                                                    className="pr-10 pl-10"
                                                    autoComplete={AUTOCOMPLETE.newPassword}
                                                />
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="sm"
                                                    className="absolute top-1 right-1 h-11 w-11 p-0 md:h-8 md:w-8"
                                                    onClick={() => setShowPassword(!showPassword)}
                                                >
                                                    {showPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                                                </Button>
                                            </div>
                                        </FormField>

                                        <FormField label="Confirmar Nueva Contrasena" error={errors.password_confirmation}>
                                            <div className="relative">
                                                <Lock className="absolute top-3 left-3 h-4 w-4 text-muted-foreground" />
                                                <Input
                                                    id="password_confirmation"
                                                    type={showPassword ? 'text' : 'password'}
                                                    placeholder={PLACEHOLDERS.password}
                                                    value={data.password_confirmation}
                                                    onChange={(e) => setData('password_confirmation', e.target.value)}
                                                    className="pl-10"
                                                    autoComplete={AUTOCOMPLETE.newPassword}
                                                />
                                            </div>
                                        </FormField>
                                    </div>
                                )}
                            </div>
                        </FormSection>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="pt-6">
                        <FormSection icon={User} title="Informacion del Sistema" description="Datos del registro del motorista">
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                <div>
                                    <Label className="text-xs text-muted-foreground">ID</Label>
                                    <p className="font-mono text-sm">#{driver.id}</p>
                                </div>
                                <div>
                                    <Label className="text-xs text-muted-foreground">Estado</Label>
                                    <p className="text-sm">
                                        {driver.is_active ? (
                                            <Badge variant="default" className="text-xs">
                                                Activo
                                            </Badge>
                                        ) : (
                                            <Badge variant="destructive" className="text-xs">
                                                Inactivo
                                            </Badge>
                                        )}
                                    </p>
                                </div>
                                <div>
                                    <Label className="text-xs text-muted-foreground">Disponibilidad</Label>
                                    <p className="text-sm">
                                        {driver.is_available ? (
                                            <Badge variant="default" className="text-xs bg-green-600">
                                                Disponible
                                            </Badge>
                                        ) : (
                                            <Badge variant="secondary" className="text-xs">
                                                No Disponible
                                            </Badge>
                                        )}
                                    </p>
                                </div>
                                <div>
                                    <Label className="text-xs text-muted-foreground">Creado</Label>
                                    <p className="text-sm">{formatDate(driver.created_at)}</p>
                                </div>
                                <div>
                                    <Label className="text-xs text-muted-foreground">Actualizado</Label>
                                    <p className="text-sm">{formatDate(driver.updated_at)}</p>
                                </div>
                                <div>
                                    <Label className="text-xs text-muted-foreground">Ultimo Login</Label>
                                    <p className="text-sm">{formatDate(driver.last_login_at)}</p>
                                </div>
                            </div>
                        </FormSection>
                    </CardContent>
                </Card>
            </div>
        </EditPageLayout>
    );
}
