<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccessIssueReport extends Model
{
    protected $fillable = [
        'email',
        'phone',
        'dpi',
        'issue_type',
        'description',
        'status',
        'handled_by',
        'admin_notes',
        'contacted_at',
        'resolved_at',
    ];

    protected $appends = [
        'issue_type_label',
        'status_label',
    ];

    protected function casts(): array
    {
        return [
            'contacted_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function getIssueTypeLabelAttribute(): string
    {
        return match ($this->issue_type) {
            'cant_find_account' => 'No encuentra su cuenta',
            'cant_login' => 'No puede iniciar sesión',
            'account_locked' => 'Cuenta bloqueada',
            'no_reset_email' => 'No recibe correo de recuperación',
            'other' => 'Otro problema de acceso',
            default => $this->issue_type,
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Pendiente',
            'contacted' => 'Contactado',
            'resolved' => 'Resuelto',
            default => $this->status,
        };
    }
}
