<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Concerns\LogsActivity;
use App\Models\Concerns\TracksUserStatus;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, LogsActivity, Notifiable, TracksUserStatus;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'last_login_at',
        'last_activity_at',
        'timezone',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
            'last_activity_at' => 'datetime',
        ];
    }

    /**
     * Attributes that should be appended to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = ['status', 'is_online'];

    /**
     * Relación con las actividades del usuario
     */
    public function activities(): HasMany
    {
        return $this->hasMany(UserActivity::class);
    }

    /**
     * Relación con los logs de actividad del usuario
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    /**
     * Relación con los roles del usuario
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * Registra una actividad del usuario
     *
     * @param  string  $type  Tipo de actividad
     * @param  string  $description  Descripción de la actividad
     * @param  array  $metadata  Metadatos adicionales
     */
    public function logActivity(string $type, string $description, array $metadata = []): UserActivity
    {
        return $this->activities()->create([
            'activity_type' => $type,
            'description' => $description,
            'user_agent' => request()->userAgent(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'metadata' => $metadata,
        ]);
    }

    /**
     * Verifica si el usuario tiene un rol específico
     */
    public function hasRole(string $role): bool
    {
        return $this->roles()->where('name', $role)->exists();
    }

    /**
     * Verifica si el usuario tiene un permiso específico
     * Admin siempre tiene todos los permisos (bypass automático)
     */
    public function hasPermission(string $permission): bool
    {
        // Super Admin: bypass automático para todos los permisos
        if ($this->isAdmin()) {
            return true;
        }

        // Verificar en el array de permisos cacheado
        return in_array($permission, $this->getAllPermissions());
    }

    /**
     * Verifica si el usuario es administrador
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Obtiene todos los permisos del usuario
     * Admin tiene wildcard (*) = todos los permisos
     * Usa cache persistente (Redis/File) con TTL de 1 hora
     */
    public function getAllPermissions(): array
    {
        // Super Admin: wildcard automático (todos los permisos)
        if ($this->isAdmin()) {
            return ['*'];
        }

        // Cache persistente de permisos (1 hora de TTL)
        return Cache::remember("user.{$this->id}.permissions", now()->addHour(), function () {
            // Obtener permisos únicos de todos los roles del usuario
            return $this->roles
                ->flatMap(fn ($role) => $role->permissions)
                ->pluck('name')
                ->unique()
                ->values()
                ->toArray();
        });
    }

    /**
     * Invalida el cache de permisos del usuario
     */
    public function flushPermissionsCache(): void
    {
        Cache::forget("user.{$this->id}.permissions");
    }

    /**
     * Obtiene la primera página a la que el usuario tiene acceso
     * Orden de prioridad: dashboard > users > activity > roles > settings
     * Si no tiene permisos, retorna dashboard como página por defecto
     */
    public function getFirstAccessiblePage(): ?string
    {
        // Si no tiene roles, solo puede acceder al dashboard
        if ($this->roles()->count() === 0) {
            return '/dashboard';
        }

        $priorityPages = [
            'dashboard.view' => '/dashboard',
            'users.view' => '/users',
            'activity.view' => '/activity',
            'roles.view' => '/roles',
            'settings.view' => '/settings',
        ];

        foreach ($priorityPages as $permission => $route) {
            if ($this->hasPermission($permission)) {
                return $route;
            }
        }

        // Si no tiene permisos específicos pero tiene roles, puede acceder al dashboard
        return '/dashboard';
    }

    /**
     * Verifica si el usuario tiene acceso a una página específica
     * Alias conveniente para hasPermission con .view
     */
    public function hasAccessToPage(string $page): bool
    {
        return $this->hasPermission("{$page}.view");
    }

    /**
     * Verifica si el usuario puede realizar una acción en una página
     * Ejemplo: canPerformAction('users', 'create')
     */
    public function canPerformAction(string $page, string $action): bool
    {
        return $this->hasPermission("{$page}.{$action}");
    }

    /**
     * Verifica si el usuario tiene acceso a alguna página del sistema
     * Los usuarios siempre tienen acceso al dashboard como mínimo
     */
    public function hasAnyPageAccess(): bool
    {
        return true; // Siempre tienen acceso al dashboard
    }

    /**
     * Envía la notificación de restablecimiento de contraseña
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}
