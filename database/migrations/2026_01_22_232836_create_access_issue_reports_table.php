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
        Schema::create('access_issue_reports', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('issue_type'); // cant_login, forgot_password, account_locked, other
            $table->text('description');
            $table->string('status')->default('pending'); // pending, contacted, resolved
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('admin_notes')->nullable();
            $table->timestamp('contacted_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index('email');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('access_issue_reports');
    }
};
