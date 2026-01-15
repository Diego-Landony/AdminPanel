import { showNotification } from '@/hooks/useNotifications';
import { useForm } from '@inertiajs/react';
import { Building2, Check, ChevronsUpDown, Eye, EyeOff, Lock, Mail, Phone, User } from 'lucide-react';
import React, { useState } from 'react';

import { CreatePageLayout } from '@/components/create-page-layout';
import { FormSection } from '@/components/form-section';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
import { FormField } from '@/components/ui/form-field';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Switch } from '@/components/ui/switch';
import { cn } from '@/lib/utils';
import { AUTOCOMPLETE, FIELD_DESCRIPTIONS, NOTIFICATIONS, PLACEHOLDERS } from '@/constants/ui-constants';
import { Restaurant } from '@/types';

interface CreateDriverProps {
    restaurants: Restaurant[];
}

/**
 * Pagina para crear un nuevo motorista
 */
export default function CreateDriver({ restaurants }: CreateDriverProps) {
    const [showPassword, setShowPassword] = useState(false);
    const [restaurantOpen, setRestaurantOpen] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        restaurant_id: '',
        name: '',
        email: '',
        phone: '',
        password: '',
        password_confirmation: '',
        is_active: true,
    });

    /**
     * Maneja el envio del formulario
     */
    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        post(route('drivers.store'), {
            onSuccess: () => {
                reset();
            },
            onError: (errors) => {
                if (Object.keys(errors).length === 0) {
                    showNotification.error(NOTIFICATIONS.error.server);
                }
            },
        });
    };

    return (
        <CreatePageLayout
            title="Nuevo Motorista"
            backHref={route('drivers.index')}
            onSubmit={handleSubmit}
            processing={processing}
            pageTitle="Crear Motorista"
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

                                <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
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

                                    <FormField label="Telefono" error={errors.phone}>
                                        <div className="relative">
                                            <Phone className="absolute top-3 left-3 h-4 w-4 text-muted-foreground" />
                                            <Input
                                                id="phone"
                                                type="tel"
                                                placeholder={PLACEHOLDERS.phone}
                                                value={data.phone}
                                                onChange={(e) => setData('phone', e.target.value)}
                                                className="pl-10"
                                                autoComplete={AUTOCOMPLETE.phone}
                                            />
                                        </div>
                                    </FormField>
                                </div>

                                <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                                    <FormField label="Contrasena" error={errors.password} description={FIELD_DESCRIPTIONS.password} required>
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

                                    <FormField label="Confirmar Contrasena" error={errors.password_confirmation} required>
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
            </div>
        </CreatePageLayout>
    );
}
