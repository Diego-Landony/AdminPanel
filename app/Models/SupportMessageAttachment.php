<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class SupportMessageAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'support_message_id',
        'file_path',
        'file_name',
        'mime_type',
        'file_size',
    ];

    protected $appends = ['url'];

    public function message(): BelongsTo
    {
        return $this->belongsTo(SupportMessage::class, 'support_message_id');
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->file_path);
    }

    public function deleteFile(): bool
    {
        return Storage::disk('public')->delete($this->file_path);
    }
}
