<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
    // Tabla de usuarios (compatible con MariaDB, sin ENGINE ni CHARSET explícitos)
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->timestamp('last_login_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->string('timezone')->default('America/Guatemala');
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        // Tabla de roles
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        // Tabla de permisos
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('display_name');
            $table->text('description')->nullable();
            $table->string('group')->default('general');
            $table->timestamps();
        });

        // Tabla pivot para usuarios y roles
        Schema::create('role_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('role_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['user_id', 'role_id']);
        });

        // Tabla pivot para roles y permisos
        Schema::create('permission_role', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained()->onDelete('cascade');
            $table->foreignId('permission_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['role_id', 'permission_id']);
        });

        // Tabla de actividades del usuario
        Schema::create('user_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('activity_type');
            $table->string('description');
            $table->string('user_agent')->nullable();
            $table->string('url')->nullable();
            $table->string('method')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['activity_type']);
        });

        // Tabla de logs de actividad
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('event_type', 100);
            $table->string('target_model', 255)->nullable();
            $table->unsignedBigInteger('target_id')->nullable();
            $table->text('description');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            // Índices para optimizar consultas
            $table->index(['user_id', 'created_at']);
            $table->index(['event_type', 'created_at']);
            $table->index(['target_model', 'target_id']);
            $table->index(['created_at']);
        });

        // Tabla de sesiones
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->text('user_agent')->nullable();
            $table->text('payload');
            $table->integer('last_activity')->index();
            $table->string('ip_address', 45)->nullable();
        });

        // Tabla de tokens de reset de contraseña
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // Tabla de cache
        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->integer('expiration');
        });

        // Tabla de cache de locks
        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('owner');
            $table->integer('expiration');
        });

        // Tabla de trabajos en cola
        Schema::create('jobs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });

        // Tabla de trabajos fallidos
        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });

        // Crear usuario de test admin@test.com con contraseña admintest
        $this->createTestAdminUser();

        // Crear usuario administrador principal admin@admin.com con contraseña admin
        $this->createMainAdminUser();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('failed_jobs');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('cache_locks');
        Schema::dropIfExists('cache');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('user_activities');
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('users');
    }

    /**
     * Crear usuario de test con permisos de administrador
     */
    private function createTestAdminUser(): void
    {
        // Crear el usuario de test
        $userId = DB::table('users')->insertGetId([
            'name' => 'Test Admin',
            'email' => 'admin@test.com',
            'email_verified_at' => now(),
            'password' => Hash::make('admintest'),
            'timezone' => 'America/Guatemala',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Crear rol admin si no existe
        $adminRoleId = DB::table('roles')->insertGetId([
            'name' => 'admin',
            'description' => 'Administrador del sistema con acceso completo',
            'is_system' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Asignar el rol admin al usuario de test
        DB::table('role_user')->insert([
            'user_id' => $userId,
            'role_id' => $adminRoleId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Crear permisos básicos
        $permissions = [
            ['name' => 'dashboard.view', 'display_name' => 'Dashboard', 'description' => 'Ver dashboard', 'group' => 'dashboard'],
            ['name' => 'home.view', 'display_name' => 'Inicio', 'description' => 'Ver página de inicio', 'group' => 'home'],
            ['name' => 'users.view', 'display_name' => 'Usuarios', 'description' => 'Ver usuarios', 'group' => 'users'],
            ['name' => 'users.create', 'display_name' => 'Crear Usuarios', 'description' => 'Crear nuevos usuarios', 'group' => 'users'],
            ['name' => 'users.edit', 'display_name' => 'Editar Usuarios', 'description' => 'Editar usuarios existentes', 'group' => 'users'],
            ['name' => 'users.delete', 'display_name' => 'Eliminar Usuarios', 'description' => 'Eliminar usuarios', 'group' => 'users'],
            ['name' => 'roles.view', 'display_name' => 'Roles', 'description' => 'Ver roles', 'group' => 'roles'],
            ['name' => 'roles.create', 'display_name' => 'Crear Roles', 'description' => 'Crear nuevos roles', 'group' => 'roles'],
            ['name' => 'roles.edit', 'display_name' => 'Editar Roles', 'description' => 'Editar roles existentes', 'group' => 'roles'],
            ['name' => 'roles.delete', 'display_name' => 'Eliminar Roles', 'description' => 'Eliminar roles', 'group' => 'roles'],
            ['name' => 'permissions.view', 'display_name' => 'Permisos', 'description' => 'Ver permisos', 'group' => 'permissions'],
            ['name' => 'settings.view', 'display_name' => 'Configuración', 'description' => 'Ver configuración', 'group' => 'settings'],
            ['name' => 'profile.view', 'display_name' => 'Perfil', 'description' => 'Ver perfil propio', 'group' => 'profile'],
            ['name' => 'profile.edit', 'display_name' => 'Editar Perfil', 'description' => 'Editar perfil propio', 'group' => 'profile'],
        ];

        foreach ($permissions as $permission) {
            $permissionId = DB::table('permissions')->insertGetId([
                'name' => $permission['name'],
                'display_name' => $permission['display_name'],
                'description' => $permission['description'],
                'group' => $permission['group'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Asignar todos los permisos al rol admin
            DB::table('permission_role')->insert([
                'role_id' => $adminRoleId,
                'permission_id' => $permissionId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Crear usuario administrador principal admin@admin.com con contraseña admin
     */
    private function createMainAdminUser(): void
    {
        // Verificar si el usuario ya existe
        $existingUser = DB::table('users')->where('email', 'admin@admin.com')->first();
        if ($existingUser) {
            return; // El usuario ya existe, no crear duplicados
        }

        // Crear el usuario de administrador principal
        $mainAdminId = DB::table('users')->insertGetId([
            'name' => 'Admin',
            'email' => 'admin@admin.com',
            'email_verified_at' => now(),
            'password' => Hash::make('admin'),
            'timezone' => 'America/Guatemala',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Buscar rol admin existente o crear uno nuevo
        $adminRoleId = DB::table('roles')->where('name', 'admin')->first();
        if (!$adminRoleId) {
            $adminRoleId = DB::table('roles')->insertGetId([
                'name' => 'admin',
                'description' => 'Administrador del sistema con acceso completo',
                'is_system' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $adminRoleId = $adminRoleId->id;
        }

        // Asignar el rol admin al usuario principal
        DB::table('role_user')->insert([
            'user_id' => $mainAdminId,
            'role_id' => $adminRoleId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Verificar si los permisos ya existen antes de crearlos
        $existingPermissions = DB::table('permissions')->whereIn('name', [
            'dashboard.view', 'home.view', 'users.view', 'users.create', 'users.edit', 'users.delete',
            'roles.view', 'roles.create', 'roles.edit', 'roles.delete', 'permissions.view',
            'settings.view', 'profile.view', 'profile.edit'
        ])->pluck('id')->toArray();

        if (empty($existingPermissions)) {
            // Crear permisos básicos solo si no existen
            $permissions = [
                ['name' => 'dashboard.view', 'display_name' => 'Dashboard', 'description' => 'Ver dashboard', 'group' => 'dashboard'],
                ['name' => 'home.view', 'display_name' => 'Inicio', 'description' => 'Ver página de inicio', 'group' => 'home'],
                ['name' => 'users.view', 'display_name' => 'Usuarios', 'description' => 'Ver usuarios', 'group' => 'users'],
                ['name' => 'users.create', 'display_name' => 'Crear Usuarios', 'description' => 'Crear nuevos usuarios', 'group' => 'users'],
                ['name' => 'users.edit', 'display_name' => 'Editar Usuarios', 'description' => 'Editar usuarios existentes', 'group' => 'users'],
                ['name' => 'users.delete', 'display_name' => 'Eliminar Usuarios', 'description' => 'Eliminar usuarios', 'group' => 'users'],
                ['name' => 'roles.view', 'display_name' => 'Roles', 'description' => 'Ver roles', 'group' => 'roles'],
                ['name' => 'roles.create', 'display_name' => 'Crear Roles', 'description' => 'Crear nuevos roles', 'group' => 'roles'],
                ['name' => 'roles.edit', 'display_name' => 'Editar Roles', 'description' => 'Editar roles existentes', 'group' => 'roles'],
                ['name' => 'roles.delete', 'display_name' => 'Eliminar Roles', 'description' => 'Eliminar roles', 'group' => 'roles'],
                ['name' => 'permissions.view', 'display_name' => 'Permisos', 'description' => 'Ver permisos', 'group' => 'permissions'],
                ['name' => 'settings.view', 'display_name' => 'Configuración', 'description' => 'Ver configuración', 'group' => 'settings'],
                ['name' => 'profile.view', 'display_name' => 'Perfil', 'description' => 'Ver perfil propio', 'group' => 'profile'],
                ['name' => 'profile.edit', 'display_name' => 'Editar Perfil', 'description' => 'Editar perfil propio', 'group' => 'profile'],
            ];

            foreach ($permissions as $permission) {
                $permissionId = DB::table('permissions')->insertGetId([
                    'name' => $permission['name'],
                    'display_name' => $permission['display_name'],
                    'description' => $permission['description'],
                    'group' => $permission['group'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Asignar todos los permisos al rol admin
                DB::table('permission_role')->insert([
                    'role_id' => $adminRoleId,
                    'permission_id' => $permissionId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        } else {
            // Asignar permisos existentes al rol admin
            foreach ($existingPermissions as $permissionId) {
                DB::table('permission_role')->insertOrIgnore([
                    'role_id' => $adminRoleId,
                    'permission_id' => $permissionId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
};
