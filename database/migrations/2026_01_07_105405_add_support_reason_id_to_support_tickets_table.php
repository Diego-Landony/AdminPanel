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
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->foreignId('support_reason_id')->nullable()->after('customer_id')->constrained()->nullOnDelete();
            $table->string('subject')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropForeign(['support_reason_id']);
            $table->dropColumn('support_reason_id');
            $table->string('subject')->nullable(false)->change();
        });
    }
};
