import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Textarea } from '@/components/ui/textarea';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { showNotification } from '@/hooks/useNotifications';
import { useSupportTicketWebSocket } from '@/hooks/useSupportTicketWebSocket';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { ArrowLeft, CheckCircle, CreditCard, Hand, Inbox, Loader2, MapPin, Package, Paperclip, Send, Wifi, WifiOff, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

interface Customer {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
    avatar: string | null;
}

interface Admin {
    id: number;
    name: string;
}

interface Attachment {
    id: number;
    url: string;
    file_name: string;
    mime_type: string;
    file_size: number;
}

interface Sender {
    id: number;
    name?: string;
    first_name?: string;
    last_name?: string;
    full_name?: string;
}

interface Message {
    id: number;
    message: string | null;
    sender_type: string;
    sender_id: number;
    sender: Sender;
    is_read: boolean;
    attachments: Attachment[];
    created_at: string;
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
    customer: Customer;
    assigned_user: Admin | null;
    messages: Message[];
    resolved_at: string | null;
    created_at: string;
}

interface CustomerOrder {
    id: number;
    order_number: string;
    service_type: 'pickup' | 'delivery';
    status: string;
    total: string;
    created_at: string;
    restaurant: {
        id: number;
        name: string;
    } | null;
}

interface CustomerAddress {
    id: number;
    label: string;
    address_line: string;
    is_default: boolean;
}

interface CustomerNit {
    id: number;
    nit: string;
    nit_name: string;
    is_default: boolean;
}

interface CustomerType {
    id: number;
    name: string;
    color: string;
}

interface CustomerProfile {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
    phone: string | null;
    subway_card: string | null;
    birth_date: string | null;
    gender: string | null;
    points: number;
    email_verified_at: string | null;
    created_at: string;
    last_activity_at: string | null;
    last_purchase_at: string | null;
    orders_count: number;
    customer_type: CustomerType | null;
    addresses: CustomerAddress[];
    nits: CustomerNit[];
}

interface TicketShowProps {
    ticket: SupportTicket;
    customerOrders: CustomerOrder[];
    customerProfile: CustomerProfile;
}

const STATUS_CONFIG = {
    open: { label: 'Abierto', color: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300', icon: Inbox },
    closed: { label: 'Cerrado', color: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300', icon: CheckCircle },
};

const ORDER_STATUS_CONFIG: Record<string, { label: string; color: string }> = {
    pending: { label: 'Pendiente', color: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300' },
    confirmed: { label: 'Confirmado', color: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300' },
    preparing: { label: 'Preparando', color: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300' },
    ready: { label: 'Listo', color: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' },
    out_for_delivery: { label: 'En camino', color: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300' },
    delivered: { label: 'Entregado', color: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' },
    completed: { label: 'Completado', color: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300' },
    cancelled: { label: 'Cancelado', color: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' },
    refunded: { label: 'Reembolsado', color: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' },
};

export default function TicketShow({ ticket, customerOrders, customerProfile }: TicketShowProps) {
    const { auth } = usePage().props as { auth: { user: { id: number } } };
    const [message, setMessage] = useState('');
    const [attachments, setAttachments] = useState<File[]>([]);
    const [isSending, setIsSending] = useState(false);
    const [isProfileOpen, setIsProfileOpen] = useState(false);
    const messagesEndRef = useRef<HTMLDivElement>(null);
    const fileInputRef = useRef<HTMLInputElement>(null);

    // WebSocket para mensajes en tiempo real
    const { connectionState } = useSupportTicketWebSocket({
        ticketId: ticket.id,
        enabled: ticket.status === 'open',
        reloadProps: ['ticket'],
        playSound: false,
    });

    const scrollToBottom = () => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    };

    useEffect(() => {
        scrollToBottom();
    }, [ticket.messages]);

    const handleSendMessage = (e: React.FormEvent) => {
        e.preventDefault();
        if (!message.trim() && attachments.length === 0) return;

        setIsSending(true);

        const formData = new FormData();
        formData.append('message', message);
        attachments.forEach((file) => {
            formData.append('attachments[]', file);
        });

        router.post(`/support/tickets/${ticket.id}/messages`, formData, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                setMessage('');
                setAttachments([]);
            },
            onError: (errors) => {
                const firstError = Object.values(errors)[0];
                showNotification.error(typeof firstError === 'string' ? firstError : 'Error al enviar el mensaje');
            },
            onFinish: () => setIsSending(false),
        });
    };

    const handleFileSelect = (e: React.ChangeEvent<HTMLInputElement>) => {
        const files = Array.from(e.target.files || []);
        if (attachments.length + files.length > 4) {
            showNotification.error('Máximo 4 imágenes por mensaje');
            return;
        }
        setAttachments((prev) => [...prev, ...files].slice(0, 4));
        if (fileInputRef.current) {
            fileInputRef.current.value = '';
        }
    };

    const removeAttachment = (index: number) => {
        setAttachments((prev) => prev.filter((_, i) => i !== index));
    };

    const handleStatusChange = (status: string) => {
        router.patch(
            `/support/tickets/${ticket.id}/status`,
            { status },
            {
                preserveScroll: true,
                onError: () => showNotification.error('Error al cambiar el estado'),
            },
        );
    };

    const handleTakeTicket = () => {
        router.post(
            `/support/tickets/${ticket.id}/take`,
            {},
            {
                preserveScroll: true,
                onError: () => showNotification.error('Error al tomar el ticket'),
            },
        );
    };

    const isFromAdmin = (msg: Message) => msg.sender_type.includes('User');
    const isClosed = ticket.status === 'closed';
    const isMyTicket = ticket.assigned_user?.id === auth.user.id;

    return (
        <AppLayout>
            <Head title={`Ticket ${ticket.ticket_number}`} />

            <div className="flex h-[calc(100vh-8rem)] flex-col gap-4 lg:flex-row">
                {/* Chat Section */}
                <div className="flex flex-1 flex-col rounded-lg border bg-card">
                    {/* Chat Header */}
                    <div className="flex items-center justify-between border-b p-4">
                        <div className="flex items-center gap-3">
                            <Button asChild variant="ghost" size="icon">
                                <Link href="/support/tickets">
                                    <ArrowLeft className="h-4 w-4" />
                                </Link>
                            </Button>
                            <div>
                                <h2 className="font-semibold">{ticket.reason?.name || 'Sin motivo'}</h2>
                                <p className="text-sm text-muted-foreground">Ticket {ticket.ticket_number}</p>
                            </div>
                        </div>
                        <div className="flex items-center gap-2">
                            {/* Indicador de conexión WebSocket */}
                            {ticket.status === 'open' && (
                                <Tooltip>
                                    <TooltipTrigger asChild>
                                        <div className="flex items-center">
                                            {connectionState === 'connected' ? (
                                                <Wifi className="h-4 w-4 text-green-500" />
                                            ) : connectionState === 'connecting' ? (
                                                <Wifi className="h-4 w-4 text-yellow-500 animate-pulse" />
                                            ) : (
                                                <WifiOff className="h-4 w-4 text-red-500" />
                                            )}
                                        </div>
                                    </TooltipTrigger>
                                    <TooltipContent>
                                        {connectionState === 'connected' && 'Conectado en tiempo real'}
                                        {connectionState === 'connecting' && 'Conectando...'}
                                        {connectionState === 'disconnected' && 'Desconectado'}
                                        {connectionState === 'error' && 'Error de conexión'}
                                    </TooltipContent>
                                </Tooltip>
                            )}
                            <Badge className={STATUS_CONFIG[ticket.status].color}>{STATUS_CONFIG[ticket.status].label}</Badge>
                        </div>
                    </div>

                    {/* Messages */}
                    <div className="flex-1 overflow-y-auto p-4 space-y-3">
                        {ticket.messages.map((msg) => (
                            <div key={msg.id} className={cn('flex', isFromAdmin(msg) ? 'justify-end' : 'justify-start')}>
                                <div
                                    className={cn(
                                        'max-w-[75%] rounded-lg px-4 py-2',
                                        isFromAdmin(msg) ? 'bg-primary text-primary-foreground' : 'bg-muted',
                                    )}
                                >
                                    {msg.message && <p className="text-sm whitespace-pre-wrap">{msg.message}</p>}
                                    {msg.attachments.length > 0 && (
                                        <div className="mt-2 grid grid-cols-2 gap-2">
                                            {msg.attachments.map((att) => (
                                                <a
                                                    key={att.id}
                                                    href={att.url}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    className="block overflow-hidden rounded border"
                                                >
                                                    <img
                                                        src={att.url}
                                                        alt={att.file_name}
                                                        className="h-24 w-full object-cover hover:opacity-80"
                                                    />
                                                </a>
                                            ))}
                                        </div>
                                    )}
                                    <p className={cn('text-xs mt-1', isFromAdmin(msg) ? 'text-primary-foreground/70' : 'text-muted-foreground')}>
                                        {new Date(msg.created_at).toLocaleTimeString('es-GT', { hour: '2-digit', minute: '2-digit' })}
                                    </p>
                                </div>
                            </div>
                        ))}
                        <div ref={messagesEndRef} />
                    </div>

                    {/* Message Input */}
                    {!isClosed ? (
                        <form onSubmit={handleSendMessage} className="border-t p-4">
                            {attachments.length > 0 && (
                                <div className="mb-3 flex flex-wrap gap-2">
                                    {attachments.map((file, index) => (
                                        <div key={index} className="relative">
                                            <img
                                                src={URL.createObjectURL(file)}
                                                alt={file.name}
                                                className="h-16 w-16 rounded border object-cover"
                                            />
                                            <button
                                                type="button"
                                                onClick={() => removeAttachment(index)}
                                                className="absolute -right-2 -top-2 rounded-full bg-destructive p-1 text-destructive-foreground"
                                            >
                                                <X className="h-3 w-3" />
                                            </button>
                                        </div>
                                    ))}
                                </div>
                            )}
                            <div className="flex gap-2">
                                <input
                                    ref={fileInputRef}
                                    type="file"
                                    accept="image/jpeg,image/png,image/gif,image/webp"
                                    multiple
                                    onChange={handleFileSelect}
                                    className="hidden"
                                />
                                <Button type="button" variant="outline" size="icon" onClick={() => fileInputRef.current?.click()}>
                                    <Paperclip className="h-4 w-4" />
                                </Button>
                                <Textarea
                                    value={message}
                                    onChange={(e) => setMessage(e.target.value)}
                                    placeholder="Escribe un mensaje..."
                                    className="min-h-[44px] max-h-32 resize-none"
                                    rows={1}
                                    onKeyDown={(e) => {
                                        if (e.key === 'Enter' && !e.shiftKey) {
                                            e.preventDefault();
                                            handleSendMessage(e);
                                        }
                                    }}
                                />
                                <Button type="submit" disabled={isSending || (!message.trim() && attachments.length === 0)}>
                                    {isSending ? <Loader2 className="h-4 w-4 animate-spin" /> : <Send className="h-4 w-4" />}
                                </Button>
                            </div>
                        </form>
                    ) : (
                        <div className="border-t p-4 text-center text-sm text-muted-foreground">Este ticket está cerrado</div>
                    )}
                </div>

                {/* Sidebar Info */}
                <div className="w-full space-y-4 lg:w-96 shrink-0">
                    {/* Customer Info */}
                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-sm font-medium">Cliente</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <div className="min-w-0">
                                <div className="font-medium">
                                    {ticket.customer.first_name} {ticket.customer.last_name}
                                </div>
                                <div className="text-sm text-muted-foreground truncate">{ticket.customer.email}</div>
                            </div>
                            <Button variant="outline" size="sm" className="w-full" onClick={() => setIsProfileOpen(true)}>
                                Ver perfil completo
                            </Button>
                        </CardContent>
                    </Card>

                    {/* Ticket Info */}
                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-sm font-medium">Información del ticket</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {/* Tomado por */}
                            <div>
                                <label className="text-xs text-muted-foreground">Tomado por</label>
                                {ticket.assigned_user ? (
                                    <div className="mt-1 flex items-center justify-between rounded-md border p-2">
                                        <span className="text-sm font-medium">{ticket.assigned_user.name}</span>
                                        {isMyTicket && <Badge variant="outline" className="text-xs">Tú</Badge>}
                                    </div>
                                ) : (
                                    <Button onClick={handleTakeTicket} variant="outline" className="mt-1 w-full" disabled={isClosed}>
                                        <Hand className="mr-2 h-4 w-4" />
                                        Tomar Ticket
                                    </Button>
                                )}
                            </div>

                            {/* Estado */}
                            <div>
                                <label className="text-xs text-muted-foreground">Estado</label>
                                <div className="mt-1">
                                    {isClosed ? (
                                        <div className="flex items-center gap-2 rounded-md bg-green-50 p-2 dark:bg-green-900/20">
                                            <CheckCircle className="h-4 w-4 text-green-600 dark:text-green-400" />
                                            <span className="text-sm font-medium text-green-700 dark:text-green-400">Cerrado</span>
                                        </div>
                                    ) : ticket.assigned_user ? (
                                        <Button
                                            onClick={() => handleStatusChange('closed')}
                                            variant="default"
                                            size="sm"
                                            className="w-full bg-green-600 hover:bg-green-700"
                                        >
                                            <CheckCircle className="mr-2 h-4 w-4" />
                                            Cerrar Ticket
                                        </Button>
                                    ) : (
                                        <div className="rounded-md bg-muted p-2 text-center text-xs text-muted-foreground">
                                            Debe tomar el ticket para poder cerrarlo
                                        </div>
                                    )}
                                </div>
                            </div>

                            <div className="pt-2 border-t">
                                <div className="flex justify-between text-sm">
                                    <span className="text-muted-foreground">Creado</span>
                                    <span>{new Date(ticket.created_at).toLocaleDateString('es-GT')}</span>
                                </div>
                                {ticket.resolved_at && (
                                    <div className="flex justify-between text-sm mt-1">
                                        <span className="text-muted-foreground">Cerrado</span>
                                        <span>{new Date(ticket.resolved_at).toLocaleDateString('es-GT')}</span>
                                    </div>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Customer Orders */}
                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="flex items-center gap-2 text-sm font-medium">
                                <Package className="h-4 w-4" />
                                Pedidos del cliente
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {customerOrders.length === 0 ? (
                                <p className="text-sm text-muted-foreground">Sin pedidos</p>
                            ) : (
                                <div className="space-y-2 max-h-64 overflow-y-auto">
                                    {customerOrders.map((order) => (
                                        <Link
                                            key={order.id}
                                            href={`/orders/${order.id}`}
                                            className="block rounded-md border p-2 hover:bg-muted/50 transition-colors"
                                        >
                                            <div className="flex items-center justify-between">
                                                <span className="text-sm font-medium">#{order.order_number}</span>
                                                <Badge className={ORDER_STATUS_CONFIG[order.status]?.color || 'bg-gray-100'}>
                                                    {ORDER_STATUS_CONFIG[order.status]?.label || order.status}
                                                </Badge>
                                            </div>
                                            <div className="mt-1 flex items-center justify-between text-xs text-muted-foreground">
                                                <span>{order.restaurant?.name || 'Sin restaurante'}</span>
                                                <span>Q{parseFloat(order.total).toFixed(2)}</span>
                                            </div>
                                            <div className="mt-1 flex items-center justify-between text-xs text-muted-foreground">
                                                <span className="capitalize">{order.service_type}</span>
                                                <span>{new Date(order.created_at).toLocaleDateString('es-GT')}</span>
                                            </div>
                                        </Link>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>

            {/* Customer Profile Modal */}
            <Dialog open={isProfileOpen} onOpenChange={setIsProfileOpen}>
                <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>Perfil del Cliente</DialogTitle>
                    </DialogHeader>

                    <div className="space-y-6 pt-2">
                        {/* Información Personal */}
                        <div>
                            <h4 className="text-sm font-medium text-muted-foreground mb-3">Información Personal</h4>
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className="text-xs text-muted-foreground">Nombre</label>
                                    <p className="font-medium">{customerProfile?.first_name || 'N/A'}</p>
                                </div>
                                <div>
                                    <label className="text-xs text-muted-foreground">Apellido</label>
                                    <p className="font-medium">{customerProfile?.last_name || 'N/A'}</p>
                                </div>
                                <div>
                                    <label className="text-xs text-muted-foreground">Género</label>
                                    <p className="font-medium">
                                        {customerProfile?.gender === 'male' ? 'Masculino' :
                                         customerProfile?.gender === 'female' ? 'Femenino' :
                                         customerProfile?.gender === 'other' ? 'Otro' : 'No especificado'}
                                    </p>
                                </div>
                                <div>
                                    <label className="text-xs text-muted-foreground">Fecha de Nacimiento</label>
                                    <p className="font-medium">
                                        {customerProfile?.birth_date ? new Date(customerProfile.birth_date).toLocaleDateString('es-GT') : 'N/A'}
                                    </p>
                                </div>
                            </div>
                        </div>

                        {/* Contacto */}
                        <div className="border-t pt-4">
                            <h4 className="text-sm font-medium text-muted-foreground mb-3">Contacto</h4>
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className="text-xs text-muted-foreground">Email</label>
                                    <p className="font-medium">{customerProfile?.email}</p>
                                    <Badge variant={customerProfile?.email_verified_at ? 'default' : 'destructive'} className="text-xs mt-1">
                                        {customerProfile?.email_verified_at ? 'Verificado' : 'No verificado'}
                                    </Badge>
                                </div>
                                <div>
                                    <label className="text-xs text-muted-foreground">Teléfono</label>
                                    <p className="font-medium">{customerProfile?.phone || 'N/A'}</p>
                                </div>
                            </div>
                        </div>

                        {/* SubwayCard */}
                        <div className="border-t pt-4">
                            <h4 className="text-sm font-medium text-muted-foreground mb-3">SubwayCard</h4>
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className="text-xs text-muted-foreground">Número de Tarjeta</label>
                                    <p className="font-medium">{customerProfile?.subway_card || 'N/A'}</p>
                                </div>
                                <div>
                                    <label className="text-xs text-muted-foreground">Puntos Acumulados</label>
                                    <p className="font-medium text-primary">{customerProfile?.points || 0}</p>
                                </div>
                            </div>
                        </div>

                        {/* Tipo de Cliente */}
                        <div className="border-t pt-4">
                            <h4 className="text-sm font-medium text-muted-foreground mb-3">Tipo de Cliente</h4>
                            <div className="flex items-center gap-3">
                                {customerProfile?.customer_type ? (
                                    <>
                                        <Badge style={{ backgroundColor: customerProfile.customer_type.color }} className="text-white">
                                            {customerProfile.customer_type.name}
                                        </Badge>
                                    </>
                                ) : (
                                    <span className="text-muted-foreground">Sin tipo asignado</span>
                                )}
                            </div>
                        </div>

                        {/* Actividad */}
                        <div className="border-t pt-4">
                            <h4 className="text-sm font-medium text-muted-foreground mb-3">Actividad</h4>
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className="text-xs text-muted-foreground">Cliente desde</label>
                                    <p className="font-medium">
                                        {customerProfile?.created_at ? new Date(customerProfile.created_at).toLocaleDateString('es-GT') : 'N/A'}
                                    </p>
                                </div>
                                <div>
                                    <label className="text-xs text-muted-foreground">Último pedido</label>
                                    <p className="font-medium">
                                        {customerProfile?.last_purchase_at ? new Date(customerProfile.last_purchase_at).toLocaleDateString('es-GT') : 'Nunca'}
                                    </p>
                                </div>
                            </div>
                        </div>

                        {/* Direcciones */}
                        <div className="border-t pt-4">
                            <h4 className="text-sm font-medium text-muted-foreground mb-3">
                                Direcciones ({customerProfile?.addresses?.length || 0})
                            </h4>
                            {customerProfile?.addresses && customerProfile.addresses.length > 0 ? (
                                <div className="space-y-2">
                                    {customerProfile.addresses.map((address) => (
                                        <div key={address.id} className="rounded-md border p-3">
                                            <div className="flex items-center gap-2">
                                                <MapPin className="h-4 w-4 text-muted-foreground" />
                                                <span className="font-medium">{address.label}</span>
                                                {address.is_default && <Badge variant="outline" className="text-xs">Default</Badge>}
                                            </div>
                                            <p className="text-sm text-muted-foreground mt-1 ml-6">{address.address_line}</p>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <p className="text-sm text-muted-foreground">No hay direcciones guardadas</p>
                            )}
                        </div>

                        {/* NITs */}
                        <div className="border-t pt-4">
                            <h4 className="text-sm font-medium text-muted-foreground mb-3">
                                NITs ({customerProfile?.nits?.length || 0})
                            </h4>
                            {customerProfile?.nits && customerProfile.nits.length > 0 ? (
                                <div className="space-y-2">
                                    {customerProfile.nits.map((nit) => (
                                        <div key={nit.id} className="rounded-md border p-3">
                                            <div className="flex items-center gap-2">
                                                <CreditCard className="h-4 w-4 text-muted-foreground" />
                                                <span className="font-medium">{nit.nit}</span>
                                                {nit.is_default && <Badge variant="outline" className="text-xs">Default</Badge>}
                                            </div>
                                            <p className="text-sm text-muted-foreground mt-1 ml-6">{nit.nit_name}</p>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <p className="text-sm text-muted-foreground">No hay NITs guardados</p>
                            )}
                        </div>
                    </div>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
