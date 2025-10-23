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

        // ==================== TABLAS DE MENÚ Y PRODUCTOS ====================
        // IMPORTANTE: Solo crear tablas si no existen para preservar datos

        // Tabla de categorías
        if (! Schema::hasTable('categories')) {
            Schema::create('categories', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                // NOTA: image fue eliminado - no lo incluimos
                $table->boolean('is_active')->default(true);
                $table->boolean('uses_variants')->default(false);
                $table->json('variant_definitions')->nullable();
                $table->integer('sort_order')->default(0);
                $table->timestamps();

                $table->index(['is_active', 'sort_order'], 'idx_active_order');
            });
        }

        // Tabla de productos
        if (! Schema::hasTable('products')) {
            Schema::create('products', function (Blueprint $table) {
                $table->id();
                $table->foreignId('category_id')->nullable()->constrained('categories')->onDelete('set null');
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('image')->nullable();
                $table->boolean('has_variants')->default(false);
                $table->decimal('precio_pickup_capital', 8, 2)->nullable();
                $table->decimal('precio_domicilio_capital', 8, 2)->nullable();
                $table->decimal('precio_pickup_interior', 8, 2)->nullable();
                $table->decimal('precio_domicilio_interior', 8, 2)->nullable();
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(0);
                $table->timestamps();

                $table->index(['category_id']);
                $table->index(['is_active'], 'idx_active');
            });
        }

        // Tabla pivot category_product
        if (! Schema::hasTable('category_product')) {
            Schema::create('category_product', function (Blueprint $table) {
                $table->id();
                $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
                $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
                $table->integer('sort_order')->default(0);
                $table->timestamps();

                $table->unique(['category_id', 'product_id']);
                $table->index(['category_id', 'sort_order']);
            });
        }

        // Tabla de variantes de productos
        if (! Schema::hasTable('product_variants')) {
            Schema::create('product_variants', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
                $table->string('sku')->unique();
                $table->string('name');
                $table->string('size')->nullable();
                $table->decimal('precio_pickup_capital', 8, 2);
                $table->decimal('precio_domicilio_capital', 8, 2);
                $table->decimal('precio_pickup_interior', 8, 2);
                $table->decimal('precio_domicilio_interior', 8, 2);
                $table->boolean('is_daily_special')->default(false);
                $table->json('daily_special_days')->nullable();
                $table->decimal('daily_special_precio_pickup_capital', 8, 2)->nullable();
                $table->decimal('daily_special_precio_domicilio_capital', 8, 2)->nullable();
                $table->decimal('daily_special_precio_pickup_interior', 8, 2)->nullable();
                $table->decimal('daily_special_precio_domicilio_interior', 8, 2)->nullable();
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(0);
                $table->timestamps();

                $table->index(['product_id']);
                $table->index(['sku']);
                $table->index(['is_active']);
            });
        }

        // Tabla de secciones (para personalización de productos)
        if (! Schema::hasTable('sections')) {
            Schema::create('sections', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->text('description')->nullable();
                $table->boolean('is_required')->default(false);
                $table->boolean('allow_multiple')->default(false);
                $table->unsignedTinyInteger('min_selections')->default(0);
                $table->unsignedTinyInteger('max_selections')->nullable();
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(0);
                $table->timestamps();

                $table->index(['title'], 'idx_title');
            });
        }

        // Tabla pivot producto-sección
        if (! Schema::hasTable('product_sections')) {
            Schema::create('product_sections', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
                $table->foreignId('section_id')->constrained('sections')->onDelete('cascade');
                $table->integer('sort_order')->default(0);
                $table->timestamps();

                $table->unique(['product_id', 'section_id'], 'unique_product_section');
                $table->index(['product_id'], 'idx_product');
                $table->index(['section_id'], 'idx_section');
            });
        }

        // Tabla de opciones de sección
        if (! Schema::hasTable('section_options')) {
            Schema::create('section_options', function (Blueprint $table) {
                $table->id();
                $table->foreignId('section_id')->constrained('sections')->onDelete('cascade');
                $table->string('name');
                $table->boolean('is_extra')->default(false);
                $table->decimal('price_modifier', 8, 2)->default(0);
                $table->integer('sort_order')->default(0);
                $table->timestamps();

                $table->index(['section_id'], 'idx_section');
            });
        }

        // Tabla de promociones
        if (! Schema::hasTable('promotions')) {
            Schema::create('promotions', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->enum('type', ['two_for_one', 'percentage_discount', 'daily_special']);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();

                $table->index(['is_active']);
                $table->index(['type']);
            });
        }

        // Tabla de items de promoción
        if (! Schema::hasTable('promotion_items')) {
            Schema::create('promotion_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('promotion_id')->constrained('promotions')->onDelete('cascade');
                $table->foreignId('product_id')->nullable()->constrained('products')->onDelete('cascade');
                $table->foreignId('variant_id')->nullable()->constrained('product_variants')->onDelete('cascade');
                $table->foreignId('category_id')->nullable()->constrained('categories')->onDelete('cascade');
                $table->timestamps();

                $table->index(['promotion_id']);
                $table->index(['product_id']);
                $table->index(['variant_id']);
                $table->index(['category_id']);
                $table->unique(['promotion_id', 'product_id'], 'unique_promo_product');
                $table->unique(['promotion_id', 'variant_id'], 'unique_promo_variant');
                $table->unique(['promotion_id', 'category_id'], 'unique_promo_category');
            });

            // Agregar constraint check para promotion_items (solo uno de product/variant/category)
            // Usar try-catch para evitar errores si la tabla no existe
            try {
                DB::statement('ALTER TABLE promotion_items ADD CONSTRAINT check_product_variant_or_category CHECK (
                    (product_id IS NOT NULL AND variant_id IS NULL AND category_id IS NULL) OR
                    (product_id IS NULL AND variant_id IS NOT NULL AND category_id IS NULL) OR
                    (product_id IS NULL AND variant_id IS NULL AND category_id IS NOT NULL)
                )');
            } catch (\Exception $e) {
                // Constraint ya existe o tabla no existe, continuar
            }
        }

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
        // Eliminar tablas de menú y productos
        Schema::dropIfExists('promotion_items');
        Schema::dropIfExists('promotions');
        Schema::dropIfExists('section_options');
        Schema::dropIfExists('product_sections');
        Schema::dropIfExists('sections');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('category_product');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');

        // Eliminar tablas del sistema
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
        if (! $adminRoleId) {
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
            'settings.view', 'profile.view', 'profile.edit',
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
