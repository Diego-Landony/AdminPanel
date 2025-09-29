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
        // Clean up existing fake data first
        DB::table('restaurants')->truncate();

        // Check if columns exist before dropping them
        $tableColumns = Schema::getColumnListing('restaurants');

        Schema::table('restaurants', function (Blueprint $table) use ($tableColumns) {
            // Only drop columns that exist
            $columnsToRemove = [];

            if (in_array('description', $tableColumns)) {
                $columnsToRemove[] = 'description';
            }
            if (in_array('manager_name', $tableColumns)) {
                $columnsToRemove[] = 'manager_name';
            }
            if (in_array('delivery_fee', $tableColumns)) {
                $columnsToRemove[] = 'delivery_fee';
            }
            if (in_array('rating', $tableColumns)) {
                $columnsToRemove[] = 'rating';
            }
            if (in_array('total_reviews', $tableColumns)) {
                $columnsToRemove[] = 'total_reviews';
            }
            if (in_array('sort_order', $tableColumns)) {
                $columnsToRemove[] = 'sort_order';
            }
            if (in_array('image', $tableColumns)) {
                $columnsToRemove[] = 'image';
            }
            if (in_array('delivery_area', $tableColumns)) {
                $columnsToRemove[] = 'delivery_area';
            }

            if (! empty($columnsToRemove)) {
                $table->dropColumn($columnsToRemove);
            }
        });

        // Add the KML column if it doesn't exist
        if (! in_array('geofence_kml', Schema::getColumnListing('restaurants'))) {
            Schema::table('restaurants', function (Blueprint $table) {
                $table->longText('geofence_kml')->nullable()->after('longitude');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            // Restore simplified columns for rollback
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
    }
};
