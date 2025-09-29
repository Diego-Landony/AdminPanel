<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, clean up existing fake data
        DB::table('restaurants')->truncate();

        Schema::table('restaurants', function (Blueprint $table) {
            // Remove unnecessary fields for Subway system
            $table->dropColumn([
                'description',
                'manager_name',
                'delivery_fee',
                'rating',
                'total_reviews',
                'sort_order',
                'image',
                'delivery_area',
            ]);

            // Add KML geofence field
            $table->longText('geofence_kml')->nullable()->after('longitude');
        });

        // Update indexes - remove old ones and add new ones
        Schema::table('restaurants', function (Blueprint $table) {
            // Try to drop indexes that might exist
            try {
                $table->dropIndex(['is_active', 'delivery_active']);
            } catch (\Exception $e) {
                // Index doesn't exist, continue
            }

            try {
                $table->dropIndex(['pickup_active', 'is_active']);
            } catch (\Exception $e) {
                // Index doesn't exist, continue
            }

            // Drop sort_order index only if it exists
            $indexExists = DB::select("SHOW INDEX FROM restaurants WHERE Key_name = 'restaurants_sort_order_index'");
            if (! empty($indexExists)) {
                $table->dropIndex('restaurants_sort_order_index');
            }

            // Add simplified indexes
            $table->index(['is_active']);
            $table->index(['delivery_active', 'is_active']);
            $table->index(['pickup_active', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            // Restore removed columns
            $table->text('description')->nullable()->after('name');
            $table->string('manager_name')->nullable()->after('email');
            $table->decimal('delivery_fee', 8, 2)->default(0)->after('minimum_order_amount');
            $table->decimal('rating', 3, 2)->default(0)->after('estimated_delivery_time');
            $table->integer('total_reviews')->default(0)->after('rating');
            $table->integer('sort_order')->default(0)->after('total_reviews');
            $table->string('image')->nullable()->after('sort_order');
            $table->json('delivery_area')->nullable()->after('minimum_order_amount');

            // Remove KML field
            $table->dropColumn('geofence_kml');
        });

        // Restore original indexes
        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
            $table->dropIndex(['delivery_active', 'is_active']);
            $table->dropIndex(['pickup_active', 'is_active']);

            $table->index(['is_active', 'delivery_active']);
            $table->index(['pickup_active', 'is_active']);
            $table->index('sort_order');
        });
    }
};
