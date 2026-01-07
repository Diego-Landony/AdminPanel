<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LegalDocument extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'type',
        'content_json',
        'content_html',
        'version',
        'created_by',
        'is_published',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'content_json' => 'array',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeTermsAndConditions($query)
    {
        return $query->where('type', 'terms_and_conditions');
    }

    public function scopePrivacyPolicy($query)
    {
        return $query->where('type', 'privacy_policy');
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public static function getPublishedTerms(): ?self
    {
        return static::termsAndConditions()->published()->latest('published_at')->first();
    }

    public static function getPublishedPrivacyPolicy(): ?self
    {
        return static::privacyPolicy()->published()->latest('published_at')->first();
    }

    public function publish(): void
    {
        static::where('type', $this->type)
            ->where('id', '!=', $this->id)
            ->update(['is_published' => false]);

        $this->update([
            'is_published' => true,
            'published_at' => now(),
        ]);
    }

    public static function generateNextVersion(string $type): string
    {
        $latest = static::where('type', $type)
            ->orderByDesc('id')
            ->first();

        if (! $latest) {
            return '1.0';
        }

        $parts = explode('.', $latest->version);
        $major = (int) ($parts[0] ?? 1);
        $minor = (int) ($parts[1] ?? 0);

        return $major.'.'.($minor + 1);
    }
}
