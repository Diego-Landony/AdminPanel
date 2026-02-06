<?php

namespace App\Models;

use App\Contracts\ActivityLoggable;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupportTicket extends Model implements ActivityLoggable
{
    use HasFactory, LogsActivity, SoftDeletes;

    public function getActivityLabelField(): string
    {
        return 'subject';
    }

    public static function getActivityModelName(): string
    {
        return 'Ticket de soporte';
    }

    protected $fillable = [
        'customer_id',
        'support_reason_id',
        'subject',
        'status',
        'contact_preference',
        'assigned_to',
        'resolved_at',
    ];

    protected $appends = [
        'ticket_number',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }

    /**
     * Obtener el número de ticket formateado (YYMMDD-NNNNN)
     */
    public function getTicketNumberAttribute(): string
    {
        $date = $this->created_at->format('ymd');
        $number = str_pad($this->id, 5, '0', STR_PAD_LEFT);

        return "{$date}-{$number}";
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function reason(): BelongsTo
    {
        return $this->belongsTo(SupportReason::class, 'support_reason_id');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportMessage::class)->orderBy('created_at', 'asc');
    }

    public function latestMessage()
    {
        return $this->hasOne(SupportMessage::class)->latestOfMany();
    }

    public function unreadMessagesCount(): int
    {
        return $this->messages()
            ->where('is_read', false)
            ->where('sender_type', Customer::class)
            ->count();
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeAssignedTo($query, int $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeUnassigned($query)
    {
        return $query->whereNull('assigned_to');
    }

    public function close(): void
    {
        $this->update([
            'status' => 'closed',
            'resolved_at' => now(),
        ]);
    }

    public function take(User $user): void
    {
        $this->update([
            'assigned_to' => $user->id,
        ]);
    }

    /**
     * Verifica si hay al menos un mensaje de admin en el ticket
     */
    public function hasAdminMessage(): bool
    {
        return $this->messages()
            ->where('sender_type', User::class)
            ->exists();
    }

    /**
     * Verifica si el cliente puede enviar mensajes
     * - Si contact_preference es 'no_contact': nunca puede
     * - Si contact_preference es 'contact': solo si ya hay mensaje de admin
     */
    public function customerCanSendMessages(): bool
    {
        if ($this->contact_preference === 'no_contact') {
            return false;
        }

        return $this->hasAdminMessage();
    }

    public function scopeWaitingContact($query)
    {
        return $query->where('contact_preference', 'contact')
            ->whereDoesntHave('messages', function ($q) {
                $q->where('sender_type', User::class);
            });
    }

    public function scopeFeedbackOnly($query)
    {
        return $query->where('contact_preference', 'no_contact');
    }
}
