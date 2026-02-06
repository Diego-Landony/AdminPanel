<?php

namespace App\Http\Controllers\Support;

use App\Events\SupportMessageSent;
use App\Events\TicketStatusChanged;
use App\Http\Controllers\Controller;
use App\Http\Requests\Support\SendSupportMessageRequest;
use App\Models\AccessIssueReport;
use App\Models\SupportMessage;
use App\Models\SupportMessageAttachment;
use App\Models\SupportTicket;
use App\Models\User;
use App\Notifications\SupportTicketResponseNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class SupportTicketController extends Controller
{
    public function index(Request $request): Response
    {
        $query = SupportTicket::with(['reason:id,name,slug', 'customer:id,first_name,last_name,email', 'assignedUser:id,name', 'latestMessage'])
            ->withCount(['messages as unread_count' => function ($q) {
                $q->where('is_read', false)->where('sender_type', \App\Models\Customer::class);
            }])
            ->withExists(['messages as has_admin_message' => function ($q) {
                $q->where('sender_type', User::class);
            }]);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('assigned_to')) {
            if ($request->assigned_to === 'unassigned') {
                $query->whereNull('assigned_to');
            } else {
                $query->where('assigned_to', $request->assigned_to);
            }
        }

        if ($request->filled('contact_preference')) {
            if ($request->contact_preference === 'waiting_contact') {
                // Tickets con preferencia 'contact' que aún no tienen mensaje de admin
                $query->waitingContact();
            } elseif ($request->contact_preference === 'no_contact') {
                $query->feedbackOnly();
            }
        }

        $tickets = $query->latest()->paginate(20);

        $stats = [
            'total' => SupportTicket::count(),
            'open' => SupportTicket::open()->count(),
            'closed' => SupportTicket::closed()->count(),
            'unassigned' => SupportTicket::unassigned()->active()->count(),
            'waiting_contact' => SupportTicket::waitingContact()->active()->count(),
        ];

        return Inertia::render('support/tickets/index', [
            'tickets' => $tickets,
            'stats' => $stats,
            'filters' => $request->only(['status', 'assigned_to', 'contact_preference']),
        ]);
    }

    public function show(SupportTicket $ticket): Response
    {
        $ticket->load([
            'reason:id,name,slug',
            'customer:id,first_name,last_name,email',
            'assignedUser:id,name',
            'messages' => function ($q) {
                $q->with(['sender', 'attachments'])->orderBy('created_at', 'asc');
            },
        ]);

        // Agregar campo calculado para saber si tiene mensajes de admin
        $ticket->has_admin_message = $ticket->hasAdminMessage();
        $ticket->can_send_messages = $ticket->customerCanSendMessages();

        $ticket->messages()
            ->where('sender_type', \App\Models\Customer::class)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        // Cargar pedidos recientes del cliente
        $customerOrders = \App\Models\Order::where('customer_id', $ticket->customer_id)
            ->with('restaurant:id,name')
            ->select(['id', 'order_number', 'restaurant_id', 'service_type', 'status', 'total', 'created_at'])
            ->latest()
            ->limit(10)
            ->get();

        // Cargar datos completos del cliente para el modal
        $customerProfile = \App\Models\Customer::where('id', $ticket->customer_id)
            ->with(['customerType', 'addresses', 'nits'])
            ->first();

        // Agregar conteo de órdenes manualmente
        if ($customerProfile) {
            $customerProfile->orders_count = \App\Models\Order::where('customer_id', $ticket->customer_id)->count();
        }

        return Inertia::render('support/tickets/show', [
            'ticket' => $ticket,
            'customerOrders' => $customerOrders,
            'customerProfile' => $customerProfile,
        ]);
    }

    public function sendMessage(SendSupportMessageRequest $request, SupportTicket $ticket): RedirectResponse
    {
        $message = SupportMessage::create([
            'support_ticket_id' => $ticket->id,
            'sender_type' => User::class,
            'sender_id' => auth()->id(),
            'message' => $request->message,
            'is_read' => false,
        ]);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $filename = "ticket_{$ticket->id}_".Str::uuid().'.'.$file->getClientOriginalExtension();
                $path = $file->storeAs('support', $filename, 'public');

                SupportMessageAttachment::create([
                    'support_message_id' => $message->id,
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                ]);
            }
        }

        $message->load('attachments');

        // Auto-tomar ticket si no está asignado
        if (! $ticket->assigned_to) {
            $ticket->take(auth()->user());
        }

        broadcast(new SupportMessageSent($message))->toOthers();

        // Notificar al cliente por push
        if ($ticket->customer) {
            $ticket->customer->notify(new SupportTicketResponseNotification($ticket, $message));
        }

        return redirect()->back();
    }

    public function takeTicket(SupportTicket $ticket): RedirectResponse
    {
        $user = auth()->user();
        $ticket->take($user);

        broadcast(new TicketStatusChanged($ticket))->toOthers();

        return redirect()->back()
            ->with('success', 'Has tomado este ticket.');
    }

    public function updateStatus(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $request->validate([
            'status' => 'required|in:closed',
        ]);

        // Solo se puede cerrar, no reabrir
        if ($ticket->status === 'closed') {
            return redirect()->back()
                ->with('error', 'Este ticket ya está cerrado.');
        }

        // No se puede cerrar si no ha sido tomado
        if (! $ticket->assigned_to) {
            return redirect()->back()
                ->with('error', 'No se puede cerrar un ticket que no ha sido tomado.');
        }

        $ticket->close();

        broadcast(new TicketStatusChanged($ticket))->toOthers();

        return redirect()->back()
            ->with('success', 'Ticket cerrado exitosamente.');
    }

    public function destroy(SupportTicket $ticket): RedirectResponse
    {
        foreach ($ticket->messages as $message) {
            foreach ($message->attachments as $attachment) {
                $attachment->deleteFile();
            }
        }

        $ticket->delete();

        return redirect()->route('support.tickets.index')
            ->with('success', 'Ticket eliminado exitosamente.');
    }

    /**
     * Obtener estadísticas de soporte para notificaciones en tiempo real
     */
    public function stats(): JsonResponse
    {
        $userId = auth()->id();

        // Contar tickets ABIERTOS con mensajes no leídos de clientes
        // Solo tickets sin asignar O asignados al usuario actual
        $unreadTickets = SupportTicket::query()
            ->where('status', 'open')
            ->where(function ($query) use ($userId) {
                $query->whereNull('assigned_to')
                    ->orWhere('assigned_to', $userId);
            })
            ->whereHas('messages', function ($q) {
                $q->where('is_read', false)
                    ->where('sender_type', \App\Models\Customer::class);
            })
            ->count();

        // Contar reportes de acceso pendientes
        $pendingAccessIssues = AccessIssueReport::where('status', 'pending')->count();

        return response()->json([
            'unread_tickets' => $unreadTickets,
            'pending_access_issues' => $pendingAccessIssues,
        ]);
    }
}
