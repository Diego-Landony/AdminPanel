<?php

namespace App\Http\Controllers\Api\V1\Support;

use App\Events\SupportMessageSent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Support\CreateSupportTicketRequest;
use App\Http\Requests\Api\V1\Support\ReportAccessIssueRequest;
use App\Http\Requests\Api\V1\Support\SendMessageRequest;
use App\Http\Resources\Api\V1\Support\SupportMessageResource;
use App\Http\Resources\Api\V1\Support\SupportTicketResource;
use App\Models\AccessIssueReport;
use App\Models\Customer;
use App\Models\SupportMessage;
use App\Models\SupportMessageAttachment;
use App\Models\SupportTicket;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class SupportTicketController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/support/tickets",
     *     tags={"Support"},
     *     summary="List customer's support tickets",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Tickets retrieved successfully"
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        $customer = auth()->user();

        $tickets = SupportTicket::where('customer_id', $customer->id)
            ->with(['reason', 'latestMessage.attachments', 'assignedUser:id,name'])
            ->withCount(['messages as unread_count' => function ($q) {
                $q->where('is_read', false)->where('sender_type', \App\Models\User::class);
            }])
            ->latest()
            ->get();

        return response()->json([
            'data' => [
                'tickets' => SupportTicketResource::collection($tickets),
            ],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/support/tickets",
     *     tags={"Support"},
     *     summary="Create a new support ticket",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *
     *             @OA\Schema(
     *                 required={"reason_id", "message"},
     *
     *                 @OA\Property(property="reason_id", type="integer", description="ID del motivo de soporte", example=1),
     *                 @OA\Property(property="message", type="string", description="Mensaje inicial del ticket", example="Tengo un problema con mi pedido #123"),
     *                 @OA\Property(property="attachments[]", type="array", @OA\Items(type="string", format="binary"), description="Imágenes adjuntas (máx 4)")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Ticket created successfully"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function store(CreateSupportTicketRequest $request): JsonResponse
    {
        $customer = auth()->user();

        $ticket = SupportTicket::create([
            'customer_id' => $customer->id,
            'support_reason_id' => $request->reason_id,
            'status' => 'open',
        ]);

        $message = SupportMessage::create([
            'support_ticket_id' => $ticket->id,
            'sender_type' => Customer::class,
            'sender_id' => $customer->id,
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

        $ticket->load(['reason', 'messages.attachments', 'assignedUser:id,name']);

        broadcast(new SupportMessageSent($message))->toOthers();

        return response()->json([
            'message' => 'Ticket creado',
            'data' => [
                'ticket' => new SupportTicketResource($ticket),
            ],
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/support/tickets/{id}",
     *     tags={"Support"},
     *     summary="Get a specific ticket with messages",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Ticket retrieved successfully"
     *     )
     * )
     */
    public function show(SupportTicket $ticket): JsonResponse
    {
        $customer = auth()->user();

        if ($ticket->customer_id !== $customer->id) {
            return response()->json([
                'message' => 'Sin acceso al ticket',
            ], 403);
        }

        $ticket->load(['reason', 'messages.attachments', 'assignedUser:id,name']);

        $ticket->messages()
            ->where('sender_type', \App\Models\User::class)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json([
            'data' => [
                'ticket' => new SupportTicketResource($ticket),
            ],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/support/tickets/{id}/messages",
     *     tags={"Support"},
     *     summary="Send a message to a ticket",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Message sent successfully"
     *     )
     * )
     */
    public function sendMessage(SendMessageRequest $request, SupportTicket $ticket): JsonResponse
    {
        $customer = auth()->user();

        if ($ticket->customer_id !== $customer->id) {
            return response()->json([
                'message' => 'Sin acceso al ticket',
            ], 403);
        }

        if ($ticket->status === 'closed') {
            return response()->json([
                'message' => 'Ticket cerrado, no permite mensajes',
            ], 422);
        }

        $message = SupportMessage::create([
            'support_ticket_id' => $ticket->id,
            'sender_type' => Customer::class,
            'sender_id' => $customer->id,
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

        broadcast(new SupportMessageSent($message))->toOthers();

        return response()->json([
            'message' => 'Mensaje enviado',
            'data' => [
                'message' => new SupportMessageResource($message),
            ],
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/support/access-issues",
     *     tags={"Support"},
     *     summary="Report an access issue (public endpoint)",
     *     description="Allows users who cannot log in to report access issues. No authentication required.",
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"email", "issue_type", "description"},
     *
     *             @OA\Property(property="email", type="string", format="email", example="usuario@email.com"),
     *             @OA\Property(property="phone", type="string", example="12345678"),
     *             @OA\Property(property="dpi", type="string", example="1234567890123", description="DPI (solo números)"),
     *             @OA\Property(property="issue_type", type="string", enum={"cant_find_account", "cant_login", "account_locked", "no_reset_email", "other"}, example="cant_login"),
     *             @OA\Property(property="description", type="string", example="No puedo iniciar sesión, me dice que mi contraseña es incorrecta")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Report submitted successfully"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function reportAccessIssue(ReportAccessIssueRequest $request): JsonResponse
    {
        AccessIssueReport::create([
            'email' => $request->email,
            'phone' => $request->phone,
            'dpi' => $request->dpi,
            'issue_type' => $request->issue_type,
            'description' => $request->description,
        ]);

        return response()->json([
            'message' => 'Reporte recibido. Nuestro equipo te contactará pronto por correo o teléfono.',
        ], 201);
    }
}
