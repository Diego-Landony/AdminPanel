<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('subway_card')->unique();
            $table->date('birth_date');
            $table->string('gender')->nullable();
            $table->string('client_type')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('location')->nullable();
            $table->string('nit')->nullable();
            $table->string('fcm_token')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->string('timezone')->default('America/Guatemala');
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index(['email']);
            $table->index(['subway_card']);
            $table->index(['client_type']);
            $table->index(['created_at']);
            $table->index(['last_activity_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
