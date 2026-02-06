import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, router, usePoll } from '@inertiajs/react';
import { AlertCircle, CheckCircle, Eye, Inbox, MessageCircle, MessageSquare, Phone, Settings2 } from 'lucide-react';

interface Customer {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
}

interface Admin {
    id: number;
    name: string;
}

interface LatestMessage {
    message: string;
    created_at: string;
    is_from_admin: boolean;
}

interface SupportReason {
    id: number;
    name: string;
    slug: string;
}

interface SupportTicket {
    id: number;
    ticket_number: string;
    reason: SupportReason | null;
    status: 'open' | 'closed';
    contact_preference: 'no_contact' | 'contact';
    has_admin_message: boolean;
    customer: Customer;
    assigned_user: Admin | null;
    latest_message: LatestMessage | null;
    unread_count: number;
    created_at: string;
    updated_at: string;
}

interface TicketsPageProps {
    tickets: {
        data: SupportTicket[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    stats: {
        total: number;
        open: number;
        closed: number;
        unassigned: number;
        waiting_contact: number;
    };
    filters: {
        status?: string;
        assigned_to?: string;
        contact_preference?: string;
    };
}

const STATUS_CONFIG = {
    open: { label: 'Abierto', color: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300', icon: Inbox },
    closed: { label: 'Cerrado', color: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300', icon: CheckCircle },
};

const CONTACT_PREFERENCE_CONFIG = {
    no_contact: {
        label: 'Solo feedback',
        color: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
        icon: MessageCircle,
    },
    contact: {
        label: 'Espera contacto',
        color: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
        icon: Phone,
    },
};

export default function TicketsIndex({ tickets, stats, filters }: TicketsPageProps) {
    usePoll(10000);

    const handleFilterChange = (key: string, value: string) => {
        const newFilters = { ...filters, [key]: value === 'all' ? undefined : value };
        router.get('/support/tickets', newFilters, { preserveState: true });
    };

    const ticketStats = [
        { title: 'total', value: stats.total, icon: <MessageSquare className="h-4 w-4 text-primary" /> },
        { title: 'abiertos', value: stats.open, icon: <Inbox className="h-4 w-4 text-yellow-600" /> },
        { title: 'cerrados', value: stats.closed, icon: <CheckCircle className="h-4 w-4 text-gray-600" /> },
        { title: 'sin tomar', value: stats.unassigned, icon: <AlertCircle className="h-4 w-4 text-red-600" /> },
        { title: 'esperan contacto', value: stats.waiting_contact, icon: <Phone className="h-4 w-4 text-blue-600" /> },
    ];

    return (
        <AppLayout>
            <Head title="Chat de Soporte" />

            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="space-y-1">
                        <h1 className="text-3xl font-bold tracking-tight">Chat de Soporte</h1>
                        <p className="text-muted-foreground">Gestiona los tickets de soporte de los clientes</p>
                    </div>
                    <Button asChild variant="outline">
                        <Link href="/support/reasons">
                            <Settings2 className="mr-2 h-4 w-4" />
                            Motivos
                        </Link>
                    </Button>
                </div>

                <Card>
                    <CardContent className="pt-6">
                        {/* Stats & Filters */}
                        <div className="mb-6 flex flex-wrap items-start justify-between gap-4">
                            {/* Stats */}
                            <div className="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-muted-foreground">
                                {ticketStats.map((stat) => (
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
                                    <SelectTrigger className="h-9 w-32">
                                        <SelectValue placeholder="Estado" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Todos</SelectItem>
                                        <SelectItem value="open">Abiertos</SelectItem>
                                        <SelectItem value="closed">Cerrados</SelectItem>
                                    </SelectContent>
                                </Select>

                                <Select value={filters.assigned_to || 'all'} onValueChange={(value) => handleFilterChange('assigned_to', value)}>
                                    <SelectTrigger className="h-9 w-32">
                                        <SelectValue placeholder="Tomado" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Todos</SelectItem>
                                        <SelectItem value="unassigned">Sin tomar</SelectItem>
                                    </SelectContent>
                                </Select>

                                <Select value={filters.contact_preference || 'all'} onValueChange={(value) => handleFilterChange('contact_preference', value)}>
                                    <SelectTrigger className="h-9 w-40">
                                        <SelectValue placeholder="Preferencia" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Todas</SelectItem>
                                        <SelectItem value="waiting_contact">Esperan contacto</SelectItem>
                                        <SelectItem value="no_contact">Solo feedback</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        {/* Table */}
                        <div className="overflow-x-auto rounded-md border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="w-40">Ticket</TableHead>
                                        <TableHead className="w-44">Cliente</TableHead>
                                        <TableHead className="max-w-xs">Motivo</TableHead>
                                        <TableHead className="w-24 text-center">Estado</TableHead>
                                        <TableHead className="w-32">Tomado por</TableHead>
                                        <TableHead className="w-28">Actualizado</TableHead>
                                        <TableHead className="w-20"></TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {tickets.data.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={7} className="h-32 text-center text-muted-foreground">
                                                No hay tickets de soporte
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        tickets.data.map((ticket) => (
                                            <TableRow key={ticket.id}>
                                                <TableCell>
                                                    <div className="flex items-center gap-2">
                                                        <span className="font-mono text-xs">{ticket.ticket_number}</span>
                                                        {ticket.contact_preference === 'contact' && !ticket.has_admin_message && (
                                                            <Badge className="bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300 text-[10px] px-1.5">
                                                                <Phone className="h-3 w-3 mr-1" />
                                                                Contactar
                                                            </Badge>
                                                        )}
                                                        {ticket.contact_preference === 'no_contact' && (
                                                            <Badge className="bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300 text-[10px] px-1.5">
                                                                Feedback
                                                            </Badge>
                                                        )}
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <div className="min-w-0">
                                                        <div className="truncate text-sm font-medium">
                                                            {ticket.customer.first_name} {ticket.customer.last_name}
                                                        </div>
                                                        <div className="truncate text-xs text-muted-foreground">{ticket.customer.email}</div>
                                                    </div>
                                                </TableCell>
                                                <TableCell className="max-w-xs">
                                                    <div className="min-w-0">
                                                        <div className="flex items-center gap-2">
                                                            <span className="truncate text-sm font-medium">{ticket.reason?.name || 'Sin motivo'}</span>
                                                            {ticket.unread_count > 0 && (
                                                                <Badge variant="destructive" className="h-5 shrink-0 px-1.5">
                                                                    {ticket.unread_count}
                                                                </Badge>
                                                            )}
                                                        </div>
                                                        {ticket.latest_message && (
                                                            <div className="truncate text-xs text-muted-foreground">
                                                                {ticket.latest_message.is_from_admin ? 'Tú: ' : ''}
                                                                {ticket.latest_message.message}
                                                            </div>
                                                        )}
                                                    </div>
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    <Badge className={STATUS_CONFIG[ticket.status].color}>
                                                        {STATUS_CONFIG[ticket.status].label}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell>
                                                    <span className="text-sm text-muted-foreground">
                                                        {ticket.assigned_user?.name || 'Sin tomar'}
                                                    </span>
                                                </TableCell>
                                                <TableCell>
                                                    <span className="text-sm text-muted-foreground">
                                                        {new Date(ticket.updated_at).toLocaleDateString('es-GT')}
                                                    </span>
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <Button asChild size="sm" variant="ghost">
                                                        <Link href={`/support/tickets/${ticket.id}`}>
                                                            <Eye className="h-4 w-4" />
                                                            Ver
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
                        {tickets.total > 0 && (
                            <div className="mt-4 text-sm text-muted-foreground">
                                Mostrando {tickets.data.length} de {tickets.total} tickets
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
