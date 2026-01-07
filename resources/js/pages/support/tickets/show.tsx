import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { showNotification } from '@/hooks/useNotifications';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { ArrowLeft, CheckCircle, Clock, Image, Inbox, Loader2, Paperclip, Send, User, X } from 'lucide-react';
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

interface SupportTicket {
    id: number;
    subject: string;
    status: 'open' | 'in_progress' | 'resolved' | 'closed';
    priority: 'low' | 'medium' | 'high';
    customer: Customer;
    assigned_user: Admin | null;
    messages: Message[];
    resolved_at: string | null;
    created_at: string;
}

interface TicketShowProps {
    ticket: SupportTicket;
    admins: Admin[];
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

export default function TicketShow({ ticket, admins }: TicketShowProps) {
    const { auth } = usePage().props as { auth: { user: { id: number } } };
    const [message, setMessage] = useState('');
    const [attachments, setAttachments] = useState<File[]>([]);
    const [isSending, setIsSending] = useState(false);
    const messagesEndRef = useRef<HTMLDivElement>(null);
    const fileInputRef = useRef<HTMLInputElement>(null);

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

    const handlePriorityChange = (priority: string) => {
        router.patch(
            `/support/tickets/${ticket.id}/priority`,
            { priority },
            {
                preserveScroll: true,
                onError: () => showNotification.error('Error al cambiar la prioridad'),
            },
        );
    };

    const handleAssign = (userId: string) => {
        router.patch(
            `/support/tickets/${ticket.id}/assign`,
            { user_id: userId },
            {
                preserveScroll: true,
                onError: () => showNotification.error('Error al asignar'),
            },
        );
    };

    const isFromAdmin = (msg: Message) => msg.sender_type.includes('User');
    const isClosed = ticket.status === 'closed' || ticket.status === 'resolved';

    return (
        <AppLayout>
            <Head title={`Ticket #${ticket.id}`} />

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
                                <h2 className="font-semibold">{ticket.subject}</h2>
                                <p className="text-sm text-muted-foreground">Ticket #{ticket.id}</p>
                            </div>
                        </div>
                        <Badge className={STATUS_CONFIG[ticket.status].color}>{STATUS_CONFIG[ticket.status].label}</Badge>
                    </div>

                    {/* Messages */}
                    <div className="flex-1 overflow-y-auto p-4 space-y-4">
                        {ticket.messages.map((msg) => (
                            <div key={msg.id} className={cn('flex gap-3', isFromAdmin(msg) ? 'justify-end' : 'justify-start')}>
                                {!isFromAdmin(msg) && (
                                    <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-muted">
                                        <User className="h-4 w-4" />
                                    </div>
                                )}
                                <div
                                    className={cn(
                                        'max-w-[70%] rounded-lg px-4 py-2',
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
                                {isFromAdmin(msg) && (
                                    <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary">
                                        <User className="h-4 w-4 text-primary-foreground" />
                                    </div>
                                )}
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
                        <div className="border-t p-4 text-center text-sm text-muted-foreground">Este ticket está {ticket.status === 'resolved' ? 'resuelto' : 'cerrado'}</div>
                    )}
                </div>

                {/* Sidebar Info */}
                <div className="w-full space-y-4 lg:w-80">
                    {/* Customer Info */}
                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-sm font-medium">Cliente</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <div className="flex items-center gap-3">
                                <div className="flex h-10 w-10 items-center justify-center rounded-full bg-muted">
                                    <User className="h-5 w-5" />
                                </div>
                                <div>
                                    <div className="font-medium">
                                        {ticket.customer.first_name} {ticket.customer.last_name}
                                    </div>
                                    <div className="text-sm text-muted-foreground">{ticket.customer.email}</div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Ticket Info */}
                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-sm font-medium">Información del ticket</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div>
                                <label className="text-xs text-muted-foreground">Estado</label>
                                <Select value={ticket.status} onValueChange={handleStatusChange}>
                                    <SelectTrigger className="mt-1">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="open">Abierto</SelectItem>
                                        <SelectItem value="in_progress">En Progreso</SelectItem>
                                        <SelectItem value="resolved">Resuelto</SelectItem>
                                        <SelectItem value="closed">Cerrado</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div>
                                <label className="text-xs text-muted-foreground">Prioridad</label>
                                <Select value={ticket.priority} onValueChange={handlePriorityChange}>
                                    <SelectTrigger className="mt-1">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="low">Baja</SelectItem>
                                        <SelectItem value="medium">Media</SelectItem>
                                        <SelectItem value="high">Alta</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div>
                                <label className="text-xs text-muted-foreground">Asignado a</label>
                                <Select value={ticket.assigned_user?.id.toString() || ''} onValueChange={handleAssign}>
                                    <SelectTrigger className="mt-1">
                                        <SelectValue placeholder="Sin asignar" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {admins.map((admin) => (
                                            <SelectItem key={admin.id} value={admin.id.toString()}>
                                                {admin.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="pt-2 border-t">
                                <div className="flex justify-between text-sm">
                                    <span className="text-muted-foreground">Creado</span>
                                    <span>{new Date(ticket.created_at).toLocaleDateString('es-GT')}</span>
                                </div>
                                {ticket.resolved_at && (
                                    <div className="flex justify-between text-sm mt-1">
                                        <span className="text-muted-foreground">Resuelto</span>
                                        <span>{new Date(ticket.resolved_at).toLocaleDateString('es-GT')}</span>
                                    </div>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
