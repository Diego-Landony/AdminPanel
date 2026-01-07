<?php

namespace App\Http\Controllers\Support;

use App\Events\SupportMessageSent;
use App\Events\TicketStatusChanged;
use App\Http\Controllers\Controller;
use App\Http\Requests\Support\SendSupportMessageRequest;
use App\Models\SupportMessage;
use App\Models\SupportMessageAttachment;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class SupportTicketController extends Controller
{
    public function index(Request $request): Response
    {
        $query = SupportTicket::with(['customer:id,first_name,last_name,email', 'assignedUser:id,name', 'latestMessage'])
            ->withCount(['messages as unread_count' => function ($q) {
                $q->where('is_read', false)->where('sender_type', \App\Models\Customer::class);
            }]);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('assigned_to')) {
            if ($request->assigned_to === 'unassigned') {
                $query->whereNull('assigned_to');
            } else {
                $query->where('assigned_to', $request->assigned_to);
            }
        }

        $tickets = $query->latest()->paginate(20);

        $stats = [
            'total' => SupportTicket::count(),
            'open' => SupportTicket::open()->count(),
            'in_progress' => SupportTicket::inProgress()->count(),
            'resolved' => SupportTicket::resolved()->count(),
            'unassigned' => SupportTicket::unassigned()->active()->count(),
        ];

        $admins = User::whereHas('roles', function ($q) {
            $q->whereHas('permissions', function ($q2) {
                $q2->where('name', 'support.tickets.manage');
            });
        })->orWhereHas('permissions', function ($q) {
            $q->where('name', 'support.tickets.manage');
        })->get(['id', 'name']);

        return Inertia::render('support/tickets/index', [
            'tickets' => $tickets,
            'stats' => $stats,
            'admins' => $admins,
            'filters' => $request->only(['status', 'priority', 'assigned_to']),
        ]);
    }

    public function show(SupportTicket $ticket): Response
    {
        $ticket->load([
            'customer:id,first_name,last_name,email,avatar',
            'assignedUser:id,name',
            'messages' => function ($q) {
                $q->with(['sender', 'attachments'])->orderBy('created_at', 'asc');
            },
        ]);

        $ticket->messages()
            ->where('sender_type', \App\Models\Customer::class)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        $admins = User::whereHas('roles', function ($q) {
            $q->whereHas('permissions', function ($q2) {
                $q2->where('name', 'support.tickets.manage');
            });
        })->orWhereHas('permissions', function ($q) {
            $q->where('name', 'support.tickets.manage');
        })->get(['id', 'name']);

        return Inertia::render('support/tickets/show', [
            'ticket' => $ticket,
            'admins' => $admins,
        ]);
    }

    public function sendMessage(SendSupportMessageRequest $request, SupportTicket $ticket): RedirectResponse
    {
        $message = SupportMessage::create([
            'support_ticket_id' => $ticket->id,
            'sender_type' => User::class,
            'sender_id' => auth()->id(),
            'message' => $request->message,
            'is_read' => true,
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

        if ($ticket->status === 'open') {
            $ticket->update(['status' => 'in_progress']);
        }

        broadcast(new SupportMessageSent($message))->toOthers();

        return redirect()->back()
            ->with('success', 'Mensaje enviado exitosamente.');
    }

    public function assign(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::findOrFail($request->user_id);
        $ticket->assign($user);

        broadcast(new TicketStatusChanged($ticket))->toOthers();

        return redirect()->back()
            ->with('success', "Ticket asignado a {$user->name}.");
    }

    public function updateStatus(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $request->validate([
            'status' => 'required|in:open,in_progress,resolved,closed',
        ]);

        $oldStatus = $ticket->status;
        $newStatus = $request->status;

        if ($newStatus === 'resolved') {
            $ticket->markAsResolved();
        } elseif ($newStatus === 'closed') {
            $ticket->markAsClosed();
        } else {
            $ticket->update(['status' => $newStatus]);
        }

        broadcast(new TicketStatusChanged($ticket))->toOthers();

        $statusLabels = [
            'open' => 'abierto',
            'in_progress' => 'en progreso',
            'resolved' => 'resuelto',
            'closed' => 'cerrado',
        ];

        return redirect()->back()
            ->with('success', "Estado cambiado a {$statusLabels[$newStatus]}.");
    }

    public function updatePriority(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $request->validate([
            'priority' => 'required|in:low,medium,high',
        ]);

        $ticket->update(['priority' => $request->priority]);

        $priorityLabels = [
            'low' => 'baja',
            'medium' => 'media',
            'high' => 'alta',
        ];

        return redirect()->back()
            ->with('success', "Prioridad cambiada a {$priorityLabels[$request->priority]}.");
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
}
