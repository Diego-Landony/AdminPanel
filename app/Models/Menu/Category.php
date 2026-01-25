<?php

namespace App\Models\Menu;

use App\Contracts\ActivityLoggable;
use App\Models\Concerns\LogsActivity;
use App\Traits\InvalidatesMenuVersion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Category extends Model implements ActivityLoggable
{
    use HasFactory, InvalidatesMenuVersion, LogsActivity;

    public static function getActivityModelName(): string
    {
        return 'Categoría';
    }

    protected $fillable = [
        'name',
        'image',
        'description',
        'is_active',
        'is_combo_category',
        'sort_order',
        'uses_variants',
        'variant_definitions',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_combo_category' => 'boolean',
        'sort_order' => 'integer',
        'uses_variants' => 'boolean',
        'variant_definitions' => 'array',
    ];

    /**
     * Relación 1:N: Una categoría tiene múltiples productos
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class)->orderBy('sort_order');
    }

    /**
     * Scope para filtrar solo categorías activas
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para ordenar categorías por sort_order y luego por nombre
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Scope para filtrar solo categorías de combos
     */
    public function scopeComboCategories($query)
    {
        return $query->where('is_combo_category', true);
    }

    /**
     * Relación 1:N: Una categoría puede tener múltiples combos
     */
    public function combos(): HasMany
    {
        return $this->hasMany(Combo::class);
    }

    /**
     * Obtiene la URL completa de la imagen de la categoría
     */
    public function getImageUrl(): ?string
    {
        if (! $this->image) {
            return null;
        }

        if (str_starts_with($this->image, 'http://') || str_starts_with($this->image, 'https://')) {
            return $this->image;
        }

        if (str_starts_with($this->image, '/storage/')) {
            return $this->image;
        }

        if (str_starts_with($this->image, 'storage/')) {
            return '/'.$this->image;
        }

        return Storage::url($this->image);
    }
}
