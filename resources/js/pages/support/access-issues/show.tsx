import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, ArrowRight, CheckCircle, Clock, Mail, Phone } from 'lucide-react';
import { useState } from 'react';

interface Handler {
    id: number;
    name: string;
}

interface AccessIssueReport {
    id: number;
    email: string;
    phone: string | null;
    dpi: string | null;
    issue_type: string;
    issue_type_label: string;
    description: string;
    status: string;
    status_label: string;
    handler: Handler | null;
    admin_notes: string | null;
    contacted_at: string | null;
    resolved_at: string | null;
    created_at: string;
    updated_at: string;
}

interface ShowPageProps {
    report: AccessIssueReport;
}

const STATUS_CONFIG: Record<string, { label: string; color: string; icon: typeof Clock }> = {
    pending: { label: 'Pendiente', color: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300', icon: Clock },
    contacted: { label: 'Contactado', color: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300', icon: Phone },
    resolved: { label: 'Resuelto', color: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300', icon: CheckCircle },
};

const ISSUE_TYPE_CONFIG: Record<string, { label: string; color: string }> = {
    cant_find_account: { label: 'No encuentra cuenta', color: 'bg-orange-100 text-orange-700 dark:bg-orange-900 dark:text-orange-300' },
    cant_login: { label: 'No puede iniciar sesión', color: 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300' },
    account_locked: { label: 'Cuenta bloqueada', color: 'bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300' },
    no_reset_email: { label: 'No recibe correo', color: 'bg-pink-100 text-pink-700 dark:bg-pink-900 dark:text-pink-300' },
    other: { label: 'Otro', color: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300' },
};

export default function AccessIssueShow({ report }: ShowPageProps) {
    const [adminNotes, setAdminNotes] = useState(report.admin_notes || '');
    const [isSavingNotes, setIsSavingNotes] = useState(false);

    const handleStatusChange = (status: string) => {
        router.patch(`/support/access-issues/${report.id}/status`, { status }, { preserveScroll: true });
    };

    const handleSaveNotes = () => {
        setIsSavingNotes(true);
        router.patch(
            `/support/access-issues/${report.id}/notes`,
            { admin_notes: adminNotes },
            {
                preserveScroll: true,
                onFinish: () => setIsSavingNotes(false),
            }
        );
    };

    const formatDate = (dateString: string | null) => {
        if (!dateString) return '-';
        return new Date(dateString).toLocaleString('es-GT', {
            dateStyle: 'medium',
            timeStyle: 'short',
        });
    };

    return (
        <AppLayout>
            <Head title={`Reporte #${report.id}`} />

            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Button asChild variant="ghost" size="icon">
                            <Link href="/support/access-issues">
                                <ArrowLeft className="h-4 w-4" />
                            </Link>
                        </Button>
                        <div className="space-y-1">
                            <h1 className="text-2xl font-bold tracking-tight">Reporte #{report.id}</h1>
                            <p className="text-muted-foreground">Problema de acceso reportado</p>
                        </div>
                    </div>
                </div>

                <div className="grid gap-6 lg:grid-cols-3">
                    {/* Main Content */}
                    <div className="space-y-6 lg:col-span-2">
                        {/* Contact Info */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Información de Contacto</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="grid gap-4 sm:grid-cols-3">
                                    <div>
                                        <p className="text-xs text-muted-foreground">Correo electrónico</p>
                                        <a href={`mailto:${report.email}`} className="text-sm font-medium text-primary hover:underline">
                                            {report.email}
                                        </a>
                                    </div>
                                    {report.phone && (
                                        <div>
                                            <p className="text-xs text-muted-foreground">Teléfono</p>
                                            <a href={`tel:${report.phone}`} className="text-sm font-medium text-primary hover:underline">
                                                {report.phone}
                                            </a>
                                        </div>
                                    )}
                                    {report.dpi && (
                                        <div>
                                            <p className="text-xs text-muted-foreground">DPI</p>
                                            <span className="text-sm font-medium">{report.dpi}</span>
                                        </div>
                                    )}
                                </div>
                            </CardContent>
                        </Card>

                        {/* Problem Description */}
                        <Card>
                            <CardHeader>
                                <div className="flex items-center justify-between">
                                    <div>
                                        <CardTitle>Problema Reportado</CardTitle>
                                        <CardDescription>Descripción del problema de acceso</CardDescription>
                                    </div>
                                    <Badge className={ISSUE_TYPE_CONFIG[report.issue_type]?.color || 'bg-gray-100 text-gray-700'}>
                                        {report.issue_type_label}
                                    </Badge>
                                </div>
                            </CardHeader>
                            <CardContent>
                                <p className="whitespace-pre-wrap text-sm leading-relaxed">{report.description}</p>
                            </CardContent>
                        </Card>

                        {/* Admin Notes */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Notas del Administrador</CardTitle>
                                <CardDescription>Notas internas sobre este caso</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <Textarea
                                    value={adminNotes}
                                    onChange={(e) => setAdminNotes(e.target.value)}
                                    placeholder="Agregar notas sobre el caso..."
                                    rows={4}
                                />
                                <Button onClick={handleSaveNotes} disabled={isSavingNotes}>
                                    {isSavingNotes ? 'Guardando...' : 'Guardar Notas'}
                                </Button>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Sidebar */}
                    <div className="space-y-6">
                        {/* Estado y Gestión */}
                        <Card>
                            <CardHeader className="pb-3">
                                <CardTitle className="text-sm font-medium">Gestión del Reporte</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {/* Estado actual */}
                                <div>
                                    <Label className="text-xs text-muted-foreground">Estado actual</Label>
                                    <div className="mt-1">
                                        <Badge className={STATUS_CONFIG[report.status]?.color || 'bg-gray-100 text-gray-700'}>
                                            {STATUS_CONFIG[report.status]?.label || report.status}
                                        </Badge>
                                    </div>
                                </div>

                                {/* Acción para avanzar al siguiente estado */}
                                {report.status !== 'resolved' && (
                                    <div>
                                        <Label className="text-xs text-muted-foreground">Siguiente paso</Label>
                                        <div className="mt-1">
                                            {report.status === 'pending' && (
                                                <Button
                                                    onClick={() => handleStatusChange('contacted')}
                                                    variant="default"
                                                    size="sm"
                                                    className="w-full bg-blue-600 hover:bg-blue-700"
                                                >
                                                    <Phone className="mr-2 h-4 w-4" />
                                                    Marcar como Contactado
                                                    <ArrowRight className="ml-2 h-4 w-4" />
                                                </Button>
                                            )}
                                            {report.status === 'contacted' && (
                                                <Button
                                                    onClick={() => handleStatusChange('resolved')}
                                                    variant="default"
                                                    size="sm"
                                                    className="w-full bg-green-600 hover:bg-green-700"
                                                >
                                                    <CheckCircle className="mr-2 h-4 w-4" />
                                                    Marcar como Resuelto
                                                    <ArrowRight className="ml-2 h-4 w-4" />
                                                </Button>
                                            )}
                                        </div>
                                    </div>
                                )}

                                {/* Indicador de caso cerrado */}
                                {report.status === 'resolved' && (
                                    <div className="rounded-md bg-green-50 p-3 dark:bg-green-900/20">
                                        <div className="flex items-center gap-2 text-green-700 dark:text-green-400">
                                            <CheckCircle className="h-4 w-4" />
                                            <span className="text-sm font-medium">Caso resuelto</span>
                                        </div>
                                    </div>
                                )}

                                {/* Atendido por */}
                                <div>
                                    <Label className="text-xs text-muted-foreground">Atendido por</Label>
                                    <div className="mt-1 rounded-md border p-2">
                                        <span className="text-sm font-medium">{report.handler?.name || 'Sin asignar'}</span>
                                    </div>
                                </div>

                                {/* Fechas */}
                                <div className="space-y-2 border-t pt-4">
                                    <div className="flex justify-between text-sm">
                                        <span className="text-muted-foreground">Creado</span>
                                        <span>{formatDate(report.created_at)}</span>
                                    </div>
                                    {report.contacted_at && (
                                        <div className="flex justify-between text-sm">
                                            <span className="text-muted-foreground">Contactado</span>
                                            <span>{formatDate(report.contacted_at)}</span>
                                        </div>
                                    )}
                                    {report.resolved_at && (
                                        <div className="flex justify-between text-sm">
                                            <span className="text-muted-foreground">Resuelto</span>
                                            <span>{formatDate(report.resolved_at)}</span>
                                        </div>
                                    )}
                                </div>

                                {/* Acciones */}
                                <div className="space-y-2 border-t pt-4">
                                    <Button asChild variant="outline" size="sm" className="w-full">
                                        <a href={`mailto:${report.email}`}>
                                            <Mail className="mr-2 h-4 w-4" />
                                            Enviar correo
                                        </a>
                                    </Button>
                                    {report.phone && (
                                        <Button asChild variant="outline" size="sm" className="w-full">
                                            <a href={`tel:${report.phone}`}>
                                                <Phone className="mr-2 h-4 w-4" />
                                                Llamar
                                            </a>
                                        </Button>
                                    )}
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
