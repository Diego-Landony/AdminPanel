import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, router } from '@inertiajs/react';
import { CheckCircle, Clock, CreditCard, Eye, Mail, Phone, ShieldAlert } from 'lucide-react';

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

interface AccessIssuesPageProps {
    reports: {
        data: AccessIssueReport[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    stats: {
        total: number;
        pending: number;
        contacted: number;
        resolved: number;
    };
    filters: {
        status?: string;
        issue_type?: string;
    };
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

export default function AccessIssuesIndex({ reports, stats, filters }: AccessIssuesPageProps) {
    const handleFilterChange = (key: string, value: string) => {
        const newFilters = { ...filters, [key]: value === 'all' ? undefined : value };
        router.get('/support/access-issues', newFilters, { preserveState: true });
    };

    const reportStats = [
        { title: 'total', value: stats.total, icon: <ShieldAlert className="h-4 w-4 text-primary" /> },
        { title: 'pendientes', value: stats.pending, icon: <Clock className="h-4 w-4 text-yellow-600" /> },
        { title: 'contactados', value: stats.contacted, icon: <Phone className="h-4 w-4 text-blue-600" /> },
        { title: 'resueltos', value: stats.resolved, icon: <CheckCircle className="h-4 w-4 text-green-600" /> },
    ];

    return (
        <AppLayout>
            <Head title="Problemas de Acceso" />

            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="space-y-1">
                        <h1 className="text-3xl font-bold tracking-tight">Problemas de Acceso</h1>
                        <p className="text-muted-foreground">Reportes de usuarios que no pueden acceder a su cuenta</p>
                    </div>
                </div>

                <Card>
                    <CardContent className="pt-6">
                        {/* Stats & Filters */}
                        <div className="mb-6 flex flex-wrap items-start justify-between gap-4">
                            {/* Stats */}
                            <div className="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-muted-foreground">
                                {reportStats.map((stat) => (
                                    <div key={stat.title} className="flex items-center gap-2">
                                        {stat.icon}
                                        <span className="lowercase">{stat.title}</span>
                                        <span className="font-medium text-foreground tabular-nums">{stat.value}</span>
                                    </div>
                                ))}
                            </div>

                            {/* Filters */}
                            <div className="flex flex-wrap gap-2">
                                <Select value={filters.status || 'all'} onValueChange={(value) => handleFilterChange('status', value)}>
                                    <SelectTrigger className="h-9 w-36">
                                        <SelectValue placeholder="Estado" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Todos</SelectItem>
                                        <SelectItem value="pending">Pendientes</SelectItem>
                                        <SelectItem value="contacted">Contactados</SelectItem>
                                        <SelectItem value="resolved">Resueltos</SelectItem>
                                    </SelectContent>
                                </Select>

                                <Select value={filters.issue_type || 'all'} onValueChange={(value) => handleFilterChange('issue_type', value)}>
                                    <SelectTrigger className="h-9 w-44">
                                        <SelectValue placeholder="Tipo de problema" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Todos los tipos</SelectItem>
                                        <SelectItem value="cant_find_account">No encuentra cuenta</SelectItem>
                                        <SelectItem value="cant_login">No puede iniciar sesión</SelectItem>
                                        <SelectItem value="account_locked">Cuenta bloqueada</SelectItem>
                                        <SelectItem value="no_reset_email">No recibe correo</SelectItem>
                                        <SelectItem value="other">Otro</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        {/* Table */}
                        <div className="overflow-x-auto rounded-md border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="w-48">Contacto</TableHead>
                                        <TableHead className="w-40">Tipo de Problema</TableHead>
                                        <TableHead className="max-w-xs">Descripción</TableHead>
                                        <TableHead className="w-28 text-center">Estado</TableHead>
                                        <TableHead className="w-32">Atendido por</TableHead>
                                        <TableHead className="w-28">Fecha</TableHead>
                                        <TableHead className="w-24"></TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {reports.data.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={7} className="h-32 text-center text-muted-foreground">
                                                No hay reportes de problemas de acceso
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        reports.data.map((report) => (
                                            <TableRow key={report.id}>
                                                <TableCell>
                                                    <div className="min-w-0">
                                                        <div className="flex items-center gap-1 truncate text-sm font-medium">
                                                            <Mail className="h-3 w-3 text-muted-foreground" />
                                                            {report.email}
                                                        </div>
                                                        {report.phone && (
                                                            <div className="flex items-center gap-1 truncate text-xs text-muted-foreground">
                                                                <Phone className="h-3 w-3" />
                                                                {report.phone}
                                                            </div>
                                                        )}
                                                        {report.dpi && (
                                                            <div className="flex items-center gap-1 truncate text-xs text-muted-foreground">
                                                                <CreditCard className="h-3 w-3" />
                                                                DPI: {report.dpi}
                                                            </div>
                                                        )}
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <Badge className={ISSUE_TYPE_CONFIG[report.issue_type]?.color || 'bg-gray-100 text-gray-700'}>
                                                        {report.issue_type_label}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell className="max-w-xs">
                                                    <p className="truncate text-sm text-muted-foreground">{report.description}</p>
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    <Badge className={STATUS_CONFIG[report.status]?.color || 'bg-gray-100 text-gray-700'}>
                                                        {report.status_label}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell>
                                                    <span className="text-sm text-muted-foreground">{report.handler?.name || '-'}</span>
                                                </TableCell>
                                                <TableCell>
                                                    <span className="text-sm text-muted-foreground">
                                                        {new Date(report.created_at).toLocaleDateString('es-GT')}
                                                    </span>
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <Button asChild size="icon" variant="ghost" title="Ver detalle">
                                                        <Link href={`/support/access-issues/${report.id}`}>
                                                            <Eye className="h-4 w-4" />
                                                        </Link>
                                                    </Button>
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                        </div>

                        {/* Pagination info */}
                        {reports.total > 0 && (
                            <div className="mt-4 text-sm text-muted-foreground">
                                Mostrando {reports.data.length} de {reports.total} reportes
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
