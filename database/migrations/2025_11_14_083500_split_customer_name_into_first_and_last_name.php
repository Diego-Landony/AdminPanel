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
        Schema::table('customers', function (Blueprint $table) {
            $table->string('first_name')->after('id')->nullable();
            $table->string('last_name')->after('first_name')->nullable();
        });

        // Migrate existing data from 'name' to 'first_name' and 'last_name'
        DB::table('customers')->orderBy('id')->chunk(100, function ($customers) {
            foreach ($customers as $customer) {
                $nameParts = explode(' ', trim($customer->name), 2);
                $firstName = $nameParts[0] ?? '';
                $lastName = $nameParts[1] ?? '';

                DB::table('customers')
                    ->where('id', $customer->id)
                    ->update([
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                    ]);
            }
        });

        // Make columns NOT NULL after data migration
        Schema::table('customers', function (Blueprint $table) {
            $table->string('first_name')->nullable(false)->change();
            $table->string('last_name')->nullable(false)->change();
        });

        // Drop old 'name' column
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-add 'name' column
        Schema::table('customers', function (Blueprint $table) {
            $table->string('name')->after('id')->nullable();
        });

        // Migrate data back from 'first_name' and 'last_name' to 'name'
        DB::table('customers')->orderBy('id')->chunk(100, function ($customers) {
            foreach ($customers as $customer) {
                $fullName = trim("{$customer->first_name} {$customer->last_name}");

                DB::table('customers')
                    ->where('id', $customer->id)
                    ->update([
                        'name' => $fullName,
                    ]);
            }
        });

        // Make 'name' NOT NULL
        Schema::table('customers', function (Blueprint $table) {
            $table->string('name')->nullable(false)->change();
        });

        // Drop first_name and last_name columns
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'last_name']);
        });
    }
};
