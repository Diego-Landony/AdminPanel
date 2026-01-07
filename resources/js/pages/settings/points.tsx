import { Transition } from '@headlessui/react';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

import HeadingSmall from '@/components/heading-small';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { FormField } from '@/components/ui/form-field';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { Clock, Coins } from 'lucide-react';

/**
 * Configuracion del sistema de puntos
 */
interface PointsSettings {
    quetzales_per_point: number;
    expiration_method: 'total' | 'fifo';
    expiration_months: number;
    rounding_threshold: number;
}

interface PageProps {
    settings: PointsSettings;
}

/**
 * Pagina de configuracion de puntos
 * Permite configurar como los clientes acumulan y pierden puntos de lealtad
 */
export default function PointsSettingsPage({ settings }: PageProps) {
    const { data, setData, patch, errors, processing, recentlySuccessful } = useForm<PointsSettings>({
        quetzales_per_point: Number(settings.quetzales_per_point),
        expiration_method: settings.expiration_method,
        expiration_months: Number(settings.expiration_months),
        rounding_threshold: Number(settings.rounding_threshold),
    });

    /**
     * Envia el formulario de configuracion de puntos
     */
    const handleSubmit: FormEventHandler = (e) => {
        e.preventDefault();

        patch(route('settings.points.update'), {
            preserveScroll: true,
        });
    };

    return (
        <AppLayout>
            <Head title="Configuracion de Puntos" />

            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall
                        title="Configuracion de Puntos"
                        description="Configura como los clientes acumulan y pierden puntos de lealtad."
                    />

                    <form onSubmit={handleSubmit} className="space-y-6">
                        {/* Seccion: Acumulacion */}
                        <Card>
                            <CardHeader>
                                <div className="flex items-center gap-2">
                                    <Coins className="h-5 w-5 text-primary" />
                                    <CardTitle>Acumulacion de Puntos</CardTitle>
                                </div>
                                <CardDescription>Define cuanto debe gastar un cliente para ganar puntos.</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <FormField
                                    label="Quetzales por punto"
                                    description="El cliente gana 1 punto por cada X quetzales gastados."
                                    error={errors.quetzales_per_point}
                                >
                                    <div className="flex items-center gap-2">
                                        <span className="text-muted-foreground">Q</span>
                                        <Input
                                            type="number"
                                            min={1}
                                            max={1000}
                                            value={data.quetzales_per_point}
                                            onChange={(e) => setData('quetzales_per_point', parseInt(e.target.value) || 1)}
                                            className="w-24"
                                        />
                                        <span className="text-muted-foreground">= 1 punto</span>
                                    </div>
                                </FormField>

                                <FormField
                                    label="Umbral de redondeo"
                                    description={`Ej: Q2${String(Math.round(data.rounding_threshold * 100)).charAt(0)} = 2.${String(Math.round(data.rounding_threshold * 100)).charAt(0)} → da ${data.rounding_threshold > 0 ? 3 : 2} puntos.`}
                                    error={errors.rounding_threshold}
                                >
                                    <div className="flex items-center gap-2">
                                        <span className="text-muted-foreground">0.</span>
                                        <Input
                                            type="text"
                                            inputMode="numeric"
                                            pattern="[0-9]*"
                                            value={Math.round(data.rounding_threshold * 100)}
                                            onChange={(e) => {
                                                const value = e.target.value.replace(/\D/g, '');
                                                const parsed = parseInt(value) || 0;
                                                const val = Math.min(99, parsed);
                                                setData('rounding_threshold', val / 100);
                                            }}
                                            className="w-20"
                                        />
                                    </div>
                                </FormField>
                            </CardContent>
                        </Card>

                        {/* Seccion: Expiracion */}
                        <Card>
                            <CardHeader>
                                <div className="flex items-center gap-2">
                                    <Clock className="h-5 w-5 text-primary" />
                                    <CardTitle>Expiracion de Puntos</CardTitle>
                                </div>
                                <CardDescription>Define como y cuando expiran los puntos de los clientes.</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-6">
                                <FormField
                                    label="Meses de inactividad"
                                    description="Los puntos expiran despues de este periodo sin actividad (compras)."
                                    error={errors.expiration_months}
                                >
                                    <div className="flex items-center gap-2">
                                        <Input
                                            type="number"
                                            min={1}
                                            max={24}
                                            value={data.expiration_months}
                                            onChange={(e) => setData('expiration_months', parseInt(e.target.value) || 1)}
                                            className="w-24"
                                        />
                                        <span className="text-muted-foreground">meses</span>
                                    </div>
                                </FormField>

                                <div className="space-y-3">
                                    <Label>Metodo de expiracion</Label>
                                    <RadioGroup
                                        value={data.expiration_method}
                                        onValueChange={(value: 'total' | 'fifo') => setData('expiration_method', value)}
                                        className="space-y-3"
                                    >
                                        <div className="flex items-start space-x-3 rounded-lg border p-4 transition-colors hover:bg-accent/50">
                                            <RadioGroupItem value="total" id="total" className="mt-1" />
                                            <div className="space-y-1">
                                                <Label htmlFor="total" className="cursor-pointer font-medium">
                                                    Total
                                                </Label>
                                                <p className="text-sm text-muted-foreground">
                                                    Todos los puntos expiran de golpe.
                                                </p>
                                            </div>
                                        </div>

                                        <div className="flex items-start space-x-3 rounded-lg border p-4 transition-colors hover:bg-accent/50">
                                            <RadioGroupItem value="fifo" id="fifo" className="mt-1" />
                                            <div className="space-y-1">
                                                <Label htmlFor="fifo" className="cursor-pointer font-medium">
                                                    FIFO
                                                </Label>
                                                <p className="text-sm text-muted-foreground">
                                                    Solo expiran los puntos mas antiguos primero.
                                                </p>
                                            </div>
                                        </div>
                                    </RadioGroup>

                                    <p className="text-sm text-muted-foreground pt-2">
                                        {data.expiration_method === 'total'
                                            ? `Si no compra en ${data.expiration_months} meses, pierde todos sus puntos.`
                                            : `Si no compra en ${data.expiration_months} meses, solo pierde los puntos que tengan mas de ${data.expiration_months} meses.`}
                                    </p>
                                </div>
                            </CardContent>
                        </Card>

                        <div className="flex items-center gap-4">
                            <Button disabled={processing}>{processing ? 'Guardando...' : 'Guardar cambios'}</Button>

                            <Transition
                                show={recentlySuccessful}
                                enter="transition ease-in-out"
                                enterFrom="opacity-0"
                                leave="transition ease-in-out"
                                leaveTo="opacity-0"
                            >
                                <p className="text-sm text-neutral-600">Guardado</p>
                            </Transition>
                        </div>
                    </form>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
