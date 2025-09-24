<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Limpia campos legacy de la tabla customers:
     * - Migra cualquier dato faltante de client_type a customer_type_id
     * - Elimina la columna client_type
     */
    public function up(): void
    {
        // PASO 1: Verificar integridad de datos antes de la limpieza
        $inconsistentRecords = DB::select("
            SELECT COUNT(*) as count
            FROM customers c
            LEFT JOIN customer_types ct ON c.customer_type_id = ct.id
            WHERE c.client_type IS NOT NULL 
            AND c.customer_type_id IS NOT NULL 
            AND c.client_type != ct.name
        ");

        if ($inconsistentRecords[0]->count > 0) {
            throw new Exception("Data integrity check failed: {$inconsistentRecords[0]->count} records have inconsistent client_type and customer_type_id values");
        }

        // PASO 2: Migrar cualquier registro que solo tenga client_type
        DB::statement("
            UPDATE customers 
            SET customer_type_id = (
                SELECT id FROM customer_types 
                WHERE name = customers.client_type
            ) 
            WHERE customer_type_id IS NULL 
            AND client_type IS NOT NULL
        ");

        // PASO 3: Log estadísticas antes de eliminar
        $stats = DB::select("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN client_type IS NOT NULL THEN 1 ELSE 0 END) as with_client_type,
                SUM(CASE WHEN customer_type_id IS NOT NULL THEN 1 ELSE 0 END) as with_customer_type_id
            FROM customers
        ");
        
        \Log::info('Legacy field cleanup stats', [
            'total_customers' => $stats[0]->total,
            'with_client_type' => $stats[0]->with_client_type,
            'with_customer_type_id' => $stats[0]->with_customer_type_id,
            'migration' => 'cleanup_customer_legacy_fields'
        ]);

        // PASO 4: Eliminar columna legacy
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('client_type');
        });

        \Log::info('Successfully removed legacy client_type column from customers table');
    }

    /**
     * Reverse the migrations.
     * 
     * ATENCIÓN: Esta reversión recrea el campo pero NO recupera los datos originales
     * Los datos del campo client_type se perderán permanentemente
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('client_type', 50)->nullable()->after('gender')
                ->comment('Legacy field - restored by rollback but data is lost');
        });

        // Opcional: Intentar repoblar basado en customer_type_id
        DB::statement("
            UPDATE customers c
            INNER JOIN customer_types ct ON c.customer_type_id = ct.id
            SET c.client_type = ct.name
            WHERE c.client_type IS NULL
        ");

        \Log::warning('Legacy field client_type restored but original data was lost', [
            'migration' => 'cleanup_customer_legacy_fields_rollback'
        ]);
    }
};
