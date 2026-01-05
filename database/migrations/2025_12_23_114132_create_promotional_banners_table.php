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
        Schema::create('promotional_banners', function (Blueprint $table) {
            $table->id();

            // Basic info
            $table->string('title', 100);
            $table->string('description', 255)->nullable();
            $table->string('image')->comment('Path to banner image');

            // Display settings
            $table->enum('orientation', ['horizontal', 'vertical'])->default('horizontal');
            $table->unsignedSmallInteger('display_seconds')->default(5)->comment('Seconds to display in carousel');
            $table->unsignedInteger('sort_order')->default(0);

            // Link/Action (optional)
            $table->string('link_type', 20)->nullable()->comment('product, combo, category, promotion, url, none');
            $table->unsignedBigInteger('link_id')->nullable()->comment('ID of linked entity');
            $table->string('link_url')->nullable()->comment('External URL if link_type is url');

            // Validity (same pattern as badges)
            $table->enum('validity_type', ['permanent', 'date_range', 'weekdays'])->default('permanent');
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->json('weekdays')->nullable()->comment('Array of weekday numbers 1-7 (1=Monday)');

            // Status
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['is_active', 'orientation', 'sort_order'], 'idx_active_orientation_order');
            $table->index(['is_active', 'validity_type', 'valid_from', 'valid_until'], 'idx_validity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotional_banners');
    }
};
