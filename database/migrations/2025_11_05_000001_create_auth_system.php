<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * MÓDULO: Sistema de Autenticación y Seguridad
     * - Usuarios administrativos
     * - Roles y permisos (RBAC)
     * - Sesiones y recuperación de contraseñas
     */
    public function up(): void
    {
        // ==================== USERS ====================
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

        // ==================== ROLES ====================
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        // ==================== PERMISSIONS ====================
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('display_name');
            $table->text('description')->nullable();
            $table->string('group')->default('general');
            $table->timestamps();
        });

        // ==================== ROLE_USER (Pivot) ====================
        Schema::create('role_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('role_id')->constrained('roles')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['user_id', 'role_id']);
        });

        // ==================== PERMISSION_ROLE (Pivot) ====================
        Schema::create('permission_role', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->onDelete('cascade');
            $table->foreignId('permission_id')->constrained('permissions')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['role_id', 'permission_id']);
        });

        // ==================== SESSIONS ====================
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->text('user_agent')->nullable();
            $table->text('payload');
            $table->integer('last_activity')->index();
            $table->string('ip_address', 45)->nullable();
        });

        // ==================== PASSWORD_RESET_TOKENS ====================
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // ==================== SEED INITIAL DATA ====================
        $this->seedInitialUsers();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('users');
    }

    /**
     * Seed initial users and permissions
     */
    private function seedInitialUsers(): void
    {
        // Crear el usuario de test
        $testUserId = DB::table('users')->insertGetId([
            'name' => 'Test Admin',
            'email' => 'admin@test.com',
            'email_verified_at' => now(),
            'password' => Hash::make('admintest'),
            'timezone' => 'America/Guatemala',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Crear el usuario administrador principal
        $mainAdminId = DB::table('users')->insertGetId([
            'name' => 'Admin',
            'email' => 'admin@admin.com',
            'email_verified_at' => now(),
            'password' => Hash::make('admin'),
            'timezone' => 'America/Guatemala',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Crear rol admin
        $adminRoleId = DB::table('roles')->insertGetId([
            'name' => 'admin',
            'description' => 'Administrador del sistema con acceso completo',
            'is_system' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Asignar el rol admin a ambos usuarios
        DB::table('role_user')->insert([
            [
                'user_id' => $testUserId,
                'role_id' => $adminRoleId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $mainAdminId,
                'role_id' => $adminRoleId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
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
};
