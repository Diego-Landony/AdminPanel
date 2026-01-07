import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Award, Building2, Calendar, Check, CreditCard, FileText, Mail, MapPin, Phone, Star, User, X } from 'lucide-react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';

interface CustomerType {
    id: number;
    name: string;
    color: string | null;
    points_required: number;
    multiplier: number;
}

interface CustomerAddress {
    id: number;
    label: string | null;
    address_line: string;
    latitude: number | null;
    longitude: number | null;
    delivery_notes: string | null;
    is_default: boolean;
}

interface CustomerNit {
    id: number;
    nit: string;
    nit_type: 'personal' | 'company' | 'other';
    nit_name: string | null;
    is_default: boolean;
}

interface Customer {
    id: number;
    first_name: string;
    last_name: string;
    full_name: string;
    email: string;
    subway_card: string;
    birth_date: string | null;
    gender: string | null;
    customer_type_id: number | null;
    customer_type: CustomerType | null;
    phone: string | null;
    points: number;
    addresses: CustomerAddress[];
    nits: CustomerNit[];
    email_verified_at: string | null;
    created_at: string | null;
    updated_at: string | null;
    last_activity_at: string | null;
    last_purchase: string | null;
}

interface ShowCustomerProps {
    customer: Customer;
}

const formatDate = (dateString: string | null): string => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('es-GT', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        timeZone: 'America/Guatemala',
    });
};

const formatDateShort = (dateString: string | null): string => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('es-GT', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const calculateAge = (birthDate: string | null): number | null => {
    if (!birthDate) return null;
    const today = new Date();
    const birth = new Date(birthDate);
    let age = today.getFullYear() - birth.getFullYear();
    const monthDiff = today.getMonth() - birth.getMonth();
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
        age--;
    }
    return age;
};

const getGenderLabel = (gender: string | null): string => {
    switch (gender) {
        case 'male':
            return 'Masculino';
        case 'female':
            return 'Femenino';
        case 'other':
            return 'Otro';
        default:
            return 'No especificado';
    }
};

export default function ShowCustomer({ customer }: ShowCustomerProps) {
    const age = calculateAge(customer.birth_date);

    return (
        <AppLayout>
            <Head title={`Cliente - ${customer.full_name}`} />

            <div className="mx-auto flex h-full w-full flex-1 flex-col gap-6 p-6">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10">
                            <User className="h-5 w-5 text-primary" />
                        </div>
                        <div>
                            <h1 className="text-3xl font-bold tracking-tight">{customer.full_name}</h1>
                            <p className="text-muted-foreground">{customer.email}</p>
                        </div>
                    </div>
                    <Link href="/customers">
                        <Button variant="outline">
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Volver
                        </Button>
                    </Link>
                </div>

                <div className="grid gap-6 md:grid-cols-2">
                    {/* Información Personal */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <User className="h-5 w-5" />
                                Información Personal
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <span className="text-xs text-muted-foreground">Nombre</span>
                                    <p className="font-medium">{customer.first_name}</p>
                                </div>
                                <div>
                                    <span className="text-xs text-muted-foreground">Apellido</span>
                                    <p className="font-medium">{customer.last_name}</p>
                                </div>
                                <div>
                                    <span className="text-xs text-muted-foreground">Género</span>
                                    <p className="font-medium">{getGenderLabel(customer.gender)}</p>
                                </div>
                                <div>
                                    <span className="text-xs text-muted-foreground">Fecha de Nacimiento</span>
                                    <p className="font-medium">
                                        {customer.birth_date ? formatDateShort(customer.birth_date) : 'N/A'}
                                        {age !== null && <span className="ml-2 text-muted-foreground">({age} años)</span>}
                                    </p>
                                </div>
                            </div>
                            <div>
                                <span className="text-xs text-muted-foreground">Email</span>
                                <div className="flex items-center gap-2">
                                    <Mail className="h-4 w-4 text-muted-foreground" />
                                    <p className="font-medium">{customer.email}</p>
                                    {customer.email_verified_at ? (
                                        <Badge variant="outline" className="border-green-200 bg-green-50 text-green-700">
                                            <Check className="mr-1 h-3 w-3" />
                                            Verificado
                                        </Badge>
                                    ) : (
                                        <Badge variant="outline" className="border-red-200 bg-red-50 text-red-700">
                                            <X className="mr-1 h-3 w-3" />
                                            No verificado
                                        </Badge>
                                    )}
                                </div>
                            </div>
                            {customer.phone && (
                                <div>
                                    <span className="text-xs text-muted-foreground">Teléfono</span>
                                    <div className="flex items-center gap-2">
                                        <Phone className="h-4 w-4 text-muted-foreground" />
                                        <p className="font-medium">{customer.phone}</p>
                                    </div>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Subway Card y Puntos */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <CreditCard className="h-5 w-5" />
                                SubwayCard
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div>
                                <span className="text-xs text-muted-foreground">Número de Tarjeta</span>
                                <p className="font-mono text-lg font-medium">{customer.subway_card}</p>
                            </div>
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <span className="text-xs text-muted-foreground">Tipo de Cliente</span>
                                    <div className="flex items-center gap-2 mt-1">
                                        <Award className="h-4 w-4 text-primary" />
                                        <Badge variant="secondary">{customer.customer_type?.name || 'Sin tipo'}</Badge>
                                        {customer.customer_type && (
                                            <span className="text-xs text-muted-foreground">{customer.customer_type.multiplier}x</span>
                                        )}
                                    </div>
                                </div>
                                <div>
                                    <span className="text-xs text-muted-foreground">Puntos Acumulados</span>
                                    <div className="flex items-center gap-2 mt-1">
                                        <Star className="h-4 w-4 text-yellow-500" />
                                        <p className="text-lg font-bold text-primary">{customer.points.toLocaleString()}</p>
                                    </div>
                                </div>
                            </div>
                            {customer.last_purchase && (
                                <div>
                                    <span className="text-xs text-muted-foreground">Última Compra</span>
                                    <p className="font-medium">{formatDate(customer.last_purchase)}</p>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Direcciones */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <MapPin className="h-5 w-5" />
                                Direcciones ({customer.addresses.length})
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {customer.addresses.length > 0 ? (
                                <div className="space-y-3">
                                    {customer.addresses.map((address) => (
                                        <div key={address.id} className="rounded-lg border bg-muted/50 p-3">
                                            <div className="flex items-start justify-between">
                                                <div className="flex-1">
                                                    {address.label && <p className="font-medium">{address.label}</p>}
                                                    <p className="text-sm text-muted-foreground">{address.address_line}</p>
                                                    {address.delivery_notes && (
                                                        <p className="mt-1 text-xs text-muted-foreground">Notas: {address.delivery_notes}</p>
                                                    )}
                                                </div>
                                                {address.is_default && (
                                                    <Badge variant="default" className="text-xs">
                                                        Predeterminada
                                                    </Badge>
                                                )}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <p className="text-center text-sm text-muted-foreground py-4">No hay direcciones guardadas</p>
                            )}
                        </CardContent>
                    </Card>

                    {/* NITs */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <FileText className="h-5 w-5" />
                                NITs ({customer.nits.length})
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {customer.nits.length > 0 ? (
                                <div className="space-y-3">
                                    {customer.nits.map((nit) => (
                                        <div key={nit.id} className="rounded-lg border bg-muted/50 p-3">
                                            <div className="flex items-start justify-between">
                                                <div className="flex-1">
                                                    <div className="flex items-center gap-2 mb-1">
                                                        {nit.nit_type === 'personal' && <User className="h-3 w-3 text-blue-600" />}
                                                        {nit.nit_type === 'company' && <Building2 className="h-3 w-3 text-purple-600" />}
                                                        {nit.nit_type === 'other' && <FileText className="h-3 w-3 text-gray-600" />}
                                                        <Badge
                                                            variant="outline"
                                                            className={
                                                                nit.nit_type === 'personal'
                                                                    ? 'border-blue-200 bg-blue-50 text-blue-700'
                                                                    : nit.nit_type === 'company'
                                                                      ? 'border-purple-200 bg-purple-50 text-purple-700'
                                                                      : 'border-gray-200 bg-gray-50 text-gray-700'
                                                            }
                                                        >
                                                            {nit.nit_type === 'personal' && 'Personal'}
                                                            {nit.nit_type === 'company' && 'Empresa'}
                                                            {nit.nit_type === 'other' && 'Otro'}
                                                        </Badge>
                                                    </div>
                                                    <p className="font-mono font-medium">{nit.nit}</p>
                                                    {nit.nit_name && (
                                                        <p className="mt-1 text-xs text-muted-foreground">
                                                            <Building2 className="mr-1 inline h-3 w-3" />
                                                            {nit.nit_name}
                                                        </p>
                                                    )}
                                                </div>
                                                {nit.is_default && (
                                                    <Badge variant="default" className="text-xs">
                                                        Predeterminado
                                                    </Badge>
                                                )}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <p className="text-center text-sm text-muted-foreground py-4">No hay NITs guardados</p>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* Información del Sistema */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Calendar className="h-5 w-5" />
                            Información del Sistema
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-2 gap-4 md:grid-cols-4">
                            <div>
                                <span className="text-xs text-muted-foreground">ID</span>
                                <p className="font-mono font-medium">#{customer.id}</p>
                            </div>
                            <div>
                                <span className="text-xs text-muted-foreground">Fecha de Registro</span>
                                <p className="font-medium">{formatDate(customer.created_at)}</p>
                            </div>
                            <div>
                                <span className="text-xs text-muted-foreground">Última Actualización</span>
                                <p className="font-medium">{formatDate(customer.updated_at)}</p>
                            </div>
                            <div>
                                <span className="text-xs text-muted-foreground">Última Actividad</span>
                                <p className="font-medium">{formatDate(customer.last_activity_at)}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
