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
        Schema::dropIfExists('product_views');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('product_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->morphs('viewable');
            $table->timestamp('viewed_at');

            $table->index(['customer_id', 'viewed_at']);
        });
    }
};
