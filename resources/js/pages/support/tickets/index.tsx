import { DataTable } from '@/components/DataTable';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, router } from '@inertiajs/react';
import { AlertCircle, CheckCircle, Clock, Headset, Inbox, MessageSquare, User } from 'lucide-react';

interface Customer {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
}

interface User {
    id: number;
    name: string;
}

interface LatestMessage {
    message: string;
    created_at: string;
    is_from_admin: boolean;
}

interface SupportTicket {
    id: number;
    subject: string;
    status: 'open' | 'in_progress' | 'resolved' | 'closed';
    priority: 'low' | 'medium' | 'high';
    customer: Customer;
    assigned_user: User | null;
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
        in_progress: number;
        resolved: number;
        unassigned: number;
    };
    admins: User[];
    filters: {
        status?: string;
        priority?: string;
        assigned_to?: string;
    };
}

const STATUS_CONFIG = {
    open: { label: 'Abierto', color: 'bg-yellow-100 text-yellow-800', icon: Inbox },
    in_progress: { label: 'En Progreso', color: 'bg-blue-100 text-blue-800', icon: Clock },
    resolved: { label: 'Resuelto', color: 'bg-green-100 text-green-800', icon: CheckCircle },
    closed: { label: 'Cerrado', color: 'bg-gray-100 text-gray-800', icon: CheckCircle },
};

const PRIORITY_CONFIG = {
    low: { label: 'Baja', color: 'bg-gray-100 text-gray-700' },
    medium: { label: 'Media', color: 'bg-yellow-100 text-yellow-700' },
    high: { label: 'Alta', color: 'bg-red-100 text-red-700' },
};

export default function TicketsIndex({ tickets, stats, admins, filters }: TicketsPageProps) {
    const handleFilterChange = (key: string, value: string) => {
        const newFilters = { ...filters, [key]: value === 'all' ? undefined : value };
        router.get('/support/tickets', newFilters, { preserveState: true });
    };

    const ticketStats = [
        { title: 'total', value: stats.total, icon: <MessageSquare className="h-4 w-4 text-primary" /> },
        { title: 'abiertos', value: stats.open, icon: <Inbox className="h-4 w-4 text-yellow-600" /> },
        { title: 'en progreso', value: stats.in_progress, icon: <Clock className="h-4 w-4 text-blue-600" /> },
        { title: 'sin asignar', value: stats.unassigned, icon: <AlertCircle className="h-4 w-4 text-red-600" /> },
    ];

    const columns = [
        {
            key: 'customer',
            title: 'Cliente',
            width: 'w-48',
            render: (ticket: SupportTicket) => (
                <div className="flex items-center gap-2">
                    <div className="flex h-8 w-8 items-center justify-center rounded-full bg-muted">
                        <User className="h-4 w-4" />
                    </div>
                    <div>
                        <div className="text-sm font-medium">
                            {ticket.customer.first_name} {ticket.customer.last_name}
                        </div>
                        <div className="text-xs text-muted-foreground">{ticket.customer.email}</div>
                    </div>
                </div>
            ),
        },
        {
            key: 'subject',
            title: 'Asunto',
            width: 'w-64',
            render: (ticket: SupportTicket) => (
                <div>
                    <div className="flex items-center gap-2">
                        <span className="text-sm font-medium">{ticket.subject}</span>
                        {ticket.unread_count > 0 && (
                            <Badge variant="destructive" className="h-5 min-w-[20px] px-1.5">
                                {ticket.unread_count}
                            </Badge>
                        )}
                    </div>
                    {ticket.latest_message && (
                        <div className="text-xs text-muted-foreground line-clamp-1">
                            {ticket.latest_message.is_from_admin ? 'Tú: ' : ''}
                            {ticket.latest_message.message}
                        </div>
                    )}
                </div>
            ),
        },
        {
            key: 'status',
            title: 'Estado',
            width: 'w-28',
            textAlign: 'center' as const,
            render: (ticket: SupportTicket) => {
                const config = STATUS_CONFIG[ticket.status];
                return <Badge className={config.color}>{config.label}</Badge>;
            },
        },
        {
            key: 'priority',
            title: 'Prioridad',
            width: 'w-24',
            textAlign: 'center' as const,
            render: (ticket: SupportTicket) => {
                const config = PRIORITY_CONFIG[ticket.priority];
                return <Badge className={config.color}>{config.label}</Badge>;
            },
        },
        {
            key: 'assigned',
            title: 'Asignado a',
            width: 'w-32',
            render: (ticket: SupportTicket) => (
                <span className="text-sm text-muted-foreground">{ticket.assigned_user?.name || 'Sin asignar'}</span>
            ),
        },
        {
            key: 'updated',
            title: 'Actualizado',
            width: 'w-32',
            render: (ticket: SupportTicket) => (
                <span className="text-sm text-muted-foreground">{new Date(ticket.updated_at).toLocaleDateString('es-GT')}</span>
            ),
        },
        {
            key: 'actions',
            title: '',
            width: 'w-24',
            textAlign: 'right' as const,
            render: (ticket: SupportTicket) => (
                <Button asChild size="sm" variant="outline">
                    <Link href={`/support/tickets/${ticket.id}`}>Ver chat</Link>
                </Button>
            ),
        },
    ];

    return (
        <AppLayout>
            <Head title="Chat de Soporte" />

            <div className="space-y-4">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10">
                            <Headset className="h-5 w-5 text-primary" />
                        </div>
                        <div>
                            <h1 className="text-xl font-semibold">Chat de Soporte</h1>
                            <p className="text-sm text-muted-foreground">Gestiona los tickets de soporte de los usuarios</p>
                        </div>
                    </div>
                </div>

                {/* Stats */}
                <div className="grid grid-cols-2 gap-3 md:grid-cols-4">
                    {ticketStats.map((stat) => (
                        <div key={stat.title} className="flex items-center gap-3 rounded-lg border bg-card p-3">
                            {stat.icon}
                            <div>
                                <div className="text-2xl font-bold">{stat.value}</div>
                                <div className="text-xs text-muted-foreground capitalize">{stat.title}</div>
                            </div>
                        </div>
                    ))}
                </div>

                {/* Filters */}
                <div className="flex flex-wrap gap-3">
                    <Select value={filters.status || 'all'} onValueChange={(value) => handleFilterChange('status', value)}>
                        <SelectTrigger className="w-40">
                            <SelectValue placeholder="Estado" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Todos los estados</SelectItem>
                            <SelectItem value="open">Abierto</SelectItem>
                            <SelectItem value="in_progress">En Progreso</SelectItem>
                            <SelectItem value="resolved">Resuelto</SelectItem>
                            <SelectItem value="closed">Cerrado</SelectItem>
                        </SelectContent>
                    </Select>

                    <Select value={filters.priority || 'all'} onValueChange={(value) => handleFilterChange('priority', value)}>
                        <SelectTrigger className="w-40">
                            <SelectValue placeholder="Prioridad" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Todas las prioridades</SelectItem>
                            <SelectItem value="high">Alta</SelectItem>
                            <SelectItem value="medium">Media</SelectItem>
                            <SelectItem value="low">Baja</SelectItem>
                        </SelectContent>
                    </Select>

                    <Select value={filters.assigned_to || 'all'} onValueChange={(value) => handleFilterChange('assigned_to', value)}>
                        <SelectTrigger className="w-48">
                            <SelectValue placeholder="Asignado a" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Todos</SelectItem>
                            <SelectItem value="unassigned">Sin asignar</SelectItem>
                            {admins.map((admin) => (
                                <SelectItem key={admin.id} value={admin.id.toString()}>
                                    {admin.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                {/* Table */}
                <DataTable
                    data={tickets.data}
                    columns={columns}
                    pagination={{
                        currentPage: tickets.current_page,
                        lastPage: tickets.last_page,
                        perPage: tickets.per_page,
                        total: tickets.total,
                    }}
                    emptyMessage="No hay tickets de soporte"
                />
            </div>
        </AppLayout>
    );
}
