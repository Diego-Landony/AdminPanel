<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SupportMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'support_ticket_id',
        'sender_type',
        'sender_id',
        'message',
        'is_read',
    ];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id');
    }

    public function sender(): MorphTo
    {
        return $this->morphTo();
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(SupportMessageAttachment::class);
    }

    public function isFromCustomer(): bool
    {
        return $this->sender_type === Customer::class;
    }

    public function isFromAdmin(): bool
    {
        return $this->sender_type === User::class;
    }

    public function markAsRead(): void
    {
        if (! $this->is_read) {
            $this->update(['is_read' => true]);
        }
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeFromCustomer($query)
    {
        return $query->where('sender_type', Customer::class);
    }

    public function scopeFromAdmin($query)
    {
        return $query->where('sender_type', User::class);
    }
}
