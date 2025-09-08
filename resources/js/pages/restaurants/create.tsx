import { Head, Link, useForm } from "@inertiajs/react";
import { ArrowLeft } from "lucide-react";

import AppLayout from "@/layouts/app-layout";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Checkbox } from "@/components/ui/checkbox";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";

interface RestaurantFormData {
    name: string;
    description: string;
    address: string;
    is_active: boolean;
    delivery_active: boolean;
    pickup_active: boolean;
    phone: string;
    schedule: Record<string, { is_open: boolean; open: string; close: string }>;
    minimum_order_amount: string;
    delivery_fee: string;
    estimated_delivery_time: string;
    email: string;
    manager_name: string;
    sort_order: string;
}

export default function RestaurantCreate() {
    const { data, setData, post, processing, errors } = useForm<RestaurantFormData>({
        name: "",
        description: "",
        address: "",
        is_active: true,
        delivery_active: true,
        pickup_active: true,
        phone: "",
        schedule: {
            monday: { is_open: true, open: "08:00", close: "22:00" },
            tuesday: { is_open: true, open: "08:00", close: "22:00" },
            wednesday: { is_open: true, open: "08:00", close: "22:00" },
            thursday: { is_open: true, open: "08:00", close: "22:00" },
            friday: { is_open: true, open: "08:00", close: "22:00" },
            saturday: { is_open: true, open: "08:00", close: "22:00" },
            sunday: { is_open: true, open: "08:00", close: "22:00" },
        },
        minimum_order_amount: "50.00",
        delivery_fee: "25.00",
        estimated_delivery_time: "30",
        email: "",
        manager_name: "",
        sort_order: "100",
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route("restaurants.store"));
    };

    const handleScheduleChange = (
        day: string,
        field: string,
        value: boolean | string
    ) => {
        setData("schedule", {
            ...data.schedule,
            [day]: {
                ...data.schedule[day],
                [field]: value,
            },
        });
    };

    const dayLabels = {
        monday: "Lunes",
        tuesday: "Martes",
        wednesday: "Miércoles",
        thursday: "Jueves",
        friday: "Viernes",
        saturday: "Sábado",
        sunday: "Domingo",
    };

    return (
        <AppLayout>
            <Head title="Crear Restaurante" />

            <div className="space-y-6">
                <div className="flex items-center space-x-4">
                    <Link href={route("restaurants.index")}>
                        <Button variant="outline" size="icon">
                            <ArrowLeft className="h-4 w-4" />
                        </Button>
                    </Link>
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">
                            Crear Nuevo Restaurante
                        </h1>
                        <p className="text-muted-foreground">
                            Completa la información del restaurante
                        </p>
                    </div>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    <div className="grid gap-6 md:grid-cols-2">
                        {/* Información Básica */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Información Básica</CardTitle>
                                <CardDescription>
                                    Datos principales del restaurante
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="space-y-2">
                                    <Label htmlFor="name">Nombre *</Label>
                                    <Input
                                        id="name"
                                        value={data.name}
                                        onChange={(e) => setData("name", e.target.value)}
                                        placeholder="Nombre del restaurante"
                                        className={errors.name ? "border-destructive" : ""}
                                    />
                                    {errors.name && (
                                        <p className="text-sm text-destructive">{errors.name}</p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="description">Descripción</Label>
                                    <Textarea
                                        id="description"
                                        value={data.description}
                                        onChange={(e) => setData("description", e.target.value)}
                                        placeholder="Descripción del restaurante"
                                        className={errors.description ? "border-destructive" : ""}
                                        rows={3}
                                    />
                                    {errors.description && (
                                        <p className="text-sm text-destructive">{errors.description}</p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="address">Dirección *</Label>
                                    <Input
                                        id="address"
                                        value={data.address}
                                        onChange={(e) => setData("address", e.target.value)}
                                        placeholder="Dirección del restaurante"
                                        className={errors.address ? "border-destructive" : ""}
                                    />
                                    {errors.address && (
                                        <p className="text-sm text-destructive">{errors.address}</p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="phone">Teléfono</Label>
                                    <Input
                                        id="phone"
                                        value={data.phone}
                                        onChange={(e) => setData("phone", e.target.value)}
                                        placeholder="+502 1234 5678"
                                        className={errors.phone ? "border-destructive" : ""}
                                    />
                                    {errors.phone && (
                                        <p className="text-sm text-destructive">{errors.phone}</p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="email">Email</Label>
                                    <Input
                                        id="email"
                                        type="email"
                                        value={data.email}
                                        onChange={(e) => setData("email", e.target.value)}
                                        placeholder="email@restaurante.com"
                                        className={errors.email ? "border-destructive" : ""}
                                    />
                                    {errors.email && (
                                        <p className="text-sm text-destructive">{errors.email}</p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="manager_name">Nombre del Encargado</Label>
                                    <Input
                                        id="manager_name"
                                        value={data.manager_name}
                                        onChange={(e) => setData("manager_name", e.target.value)}
                                        placeholder="Nombre del encargado"
                                        className={errors.manager_name ? "border-destructive" : ""}
                                    />
                                    {errors.manager_name && (
                                        <p className="text-sm text-destructive">{errors.manager_name}</p>
                                    )}
                                </div>
                            </CardContent>
                        </Card>

                        {/* Configuración de Servicios */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Configuración de Servicios</CardTitle>
                                <CardDescription>
                                    Servicios y configuración operativa
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="space-y-3">
                                    <div className="flex items-center space-x-2">
                                        <Checkbox
                                            id="is_active"
                                            checked={data.is_active}
                                            onCheckedChange={(checked) =>
                                                setData("is_active", checked as boolean)
                                            }
                                        />
                                        <Label htmlFor="is_active">Restaurante Activo</Label>
                                    </div>

                                    <div className="flex items-center space-x-2">
                                        <Checkbox
                                            id="delivery_active"
                                            checked={data.delivery_active}
                                            onCheckedChange={(checked) =>
                                                setData("delivery_active", checked as boolean)
                                            }
                                        />
                                        <Label htmlFor="delivery_active">Servicio de Delivery</Label>
                                    </div>

                                    <div className="flex items-center space-x-2">
                                        <Checkbox
                                            id="pickup_active"
                                            checked={data.pickup_active}
                                            onCheckedChange={(checked) =>
                                                setData("pickup_active", checked as boolean)
                                            }
                                        />
                                        <Label htmlFor="pickup_active">Servicio de Pickup</Label>
                                    </div>
                                </div>

                                <div className="grid gap-4 md:grid-cols-2">
                                    <div className="space-y-2">
                                        <Label htmlFor="minimum_order_amount">
                                            Monto Mínimo de Pedido (Q)
                                        </Label>
                                        <Input
                                            id="minimum_order_amount"
                                            type="number"
                                            step="0.01"
                                            value={data.minimum_order_amount}
                                            onChange={(e) => setData("minimum_order_amount", e.target.value)}
                                            placeholder="50.00"
                                            className={errors.minimum_order_amount ? "border-destructive" : ""}
                                        />
                                        {errors.minimum_order_amount && (
                                            <p className="text-sm text-destructive">{errors.minimum_order_amount}</p>
                                        )}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="delivery_fee">
                                            Tarifa de Delivery (Q)
                                        </Label>
                                        <Input
                                            id="delivery_fee"
                                            type="number"
                                            step="0.01"
                                            value={data.delivery_fee}
                                            onChange={(e) => setData("delivery_fee", e.target.value)}
                                            placeholder="25.00"
                                            className={errors.delivery_fee ? "border-destructive" : ""}
                                        />
                                        {errors.delivery_fee && (
                                            <p className="text-sm text-destructive">{errors.delivery_fee}</p>
                                        )}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="estimated_delivery_time">
                                            Tiempo Estimado de Entrega (min)
                                        </Label>
                                        <Input
                                            id="estimated_delivery_time"
                                            type="number"
                                            value={data.estimated_delivery_time}
                                            onChange={(e) => setData("estimated_delivery_time", e.target.value)}
                                            placeholder="30"
                                            className={errors.estimated_delivery_time ? "border-destructive" : ""}
                                        />
                                        {errors.estimated_delivery_time && (
                                            <p className="text-sm text-destructive">{errors.estimated_delivery_time}</p>
                                        )}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="sort_order">Orden de Visualización</Label>
                                        <Input
                                            id="sort_order"
                                            type="number"
                                            value={data.sort_order}
                                            onChange={(e) => setData("sort_order", e.target.value)}
                                            placeholder="100"
                                            className={errors.sort_order ? "border-destructive" : ""}
                                        />
                                        {errors.sort_order && (
                                            <p className="text-sm text-destructive">{errors.sort_order}</p>
                                        )}
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Horarios */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Horarios de Atención</CardTitle>
                            <CardDescription>
                                Define los horarios de atención para cada día de la semana
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-4">
                                {Object.entries(dayLabels).map(([day, label]) => (
                                    <div key={day} className="flex items-center space-x-4">
                                        <div className="w-24">
                                            <Label>{label}</Label>
                                        </div>
                                        <div className="flex items-center space-x-2">
                                            <Checkbox
                                                checked={data.schedule[day].is_open}
                                                onCheckedChange={(checked) =>
                                                    handleScheduleChange(day, "is_open", checked as boolean)
                                                }
                                            />
                                            <Label className="text-sm">Abierto</Label>
                                        </div>
                                        {data.schedule[day].is_open && (
                                            <>
                                                <div className="flex items-center space-x-2">
                                                    <Label className="text-sm">De:</Label>
                                                    <Input
                                                        type="time"
                                                        value={data.schedule[day].open}
                                                        onChange={(e) =>
                                                            handleScheduleChange(day, "open", e.target.value)
                                                        }
                                                        className="w-32"
                                                    />
                                                </div>
                                                <div className="flex items-center space-x-2">
                                                    <Label className="text-sm">A:</Label>
                                                    <Input
                                                        type="time"
                                                        value={data.schedule[day].close}
                                                        onChange={(e) =>
                                                            handleScheduleChange(day, "close", e.target.value)
                                                        }
                                                        className="w-32"
                                                    />
                                                </div>
                                            </>
                                        )}
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>

                    <div className="flex items-center space-x-4">
                        <Button type="submit" disabled={processing}>
                            {processing ? "Creando..." : "Crear Restaurante"}
                        </Button>
                        <Link href={route("restaurants.index")}>
                            <Button type="button" variant="outline">
                                Cancelar
                            </Button>
                        </Link>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}