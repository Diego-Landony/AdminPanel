<?php

use App\Jobs\ExpireInactivePoints;
use App\Models\Customer;
use App\Models\CustomerPointsTransaction;
use App\Models\PointsSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('ExpireInactivePoints Job', function () {
    describe('Metodo TOTAL - Todos los puntos expiran de golpe', function () {
        beforeEach(function () {
            // Configurar metodo TOTAL
            PointsSetting::create([
                'quetzales_per_point' => 10,
                'expiration_method' => 'total',
                'expiration_months' => 6,
                'rounding_threshold' => 0.70,
            ]);
        });

        test('expira todos los puntos si el cliente tiene 6+ meses de inactividad', function () {
            // Cliente con 200 puntos y sin actividad por 7 meses
            $customer = Customer::factory()->withoutCustomerType()->create([
                'points' => 200,
                'points_last_activity_at' => now()->subMonths(7),
            ]);

            // Crear transacciones de puntos ganados
            CustomerPointsTransaction::factory()->earned(100)
                ->for($customer)
                ->create(['created_at' => now()->subMonths(8)]);
            CustomerPointsTransaction::factory()->earned(100)
                ->for($customer)
                ->create(['created_at' => now()->subMonths(7)]);

            // Ejecutar el job
            (new ExpireInactivePoints)->handle();

            // Verificar que todos los puntos expiraron
            $customer->refresh();
            expect($customer->points)->toBe(0);

            // Verificar que se creo una transaccion de expiracion
            $expiredTransaction = CustomerPointsTransaction::where('customer_id', $customer->id)
                ->where('type', 'expired')
                ->first();

            expect($expiredTransaction)->not->toBeNull();
            expect($expiredTransaction->points)->toBe(-200);
            expect($expiredTransaction->is_expired)->toBeTrue();
        });

        test('no expira puntos si el cliente tiene actividad reciente', function () {
            // Cliente con 200 puntos y actividad hace 3 meses
            $customer = Customer::factory()->withoutCustomerType()->create([
                'points' => 200,
                'points_last_activity_at' => now()->subMonths(3),
            ]);

            CustomerPointsTransaction::factory()->earned(200)
                ->for($customer)
                ->create();

            // Ejecutar el job
            (new ExpireInactivePoints)->handle();

            // Verificar que no perdio puntos
            $customer->refresh();
            expect($customer->points)->toBe(200);

            // No debe haber transaccion de expiracion
            $expiredCount = CustomerPointsTransaction::where('customer_id', $customer->id)
                ->where('type', 'expired')
                ->count();

            expect($expiredCount)->toBe(0);
        });

        test('no expira puntos si el cliente tiene 0 puntos', function () {
            $customer = Customer::factory()->withoutCustomerType()->create([
                'points' => 0,
                'points_last_activity_at' => now()->subMonths(12),
            ]);

            // Ejecutar el job
            (new ExpireInactivePoints)->handle();

            // Verificar que sigue en 0
            $customer->refresh();
            expect($customer->points)->toBe(0);
        });

        test('expira puntos de multiples clientes inactivos', function () {
            // Crear 3 clientes inactivos
            $customers = collect();
            for ($i = 1; $i <= 3; $i++) {
                $customer = Customer::factory()->withoutCustomerType()->create([
                    'points' => $i * 100,
                    'points_last_activity_at' => now()->subMonths(7),
                ]);
                CustomerPointsTransaction::factory()->earned($i * 100)
                    ->for($customer)
                    ->create();
                $customers->push($customer);
            }

            // Ejecutar el job
            (new ExpireInactivePoints)->handle();

            // Verificar que todos perdieron sus puntos
            foreach ($customers as $customer) {
                $customer->refresh();
                expect($customer->points)->toBe(0);
            }
        });

        test('marca todas las transacciones earned como expiradas', function () {
            $customer = Customer::factory()->withoutCustomerType()->create([
                'points' => 300,
                'points_last_activity_at' => now()->subMonths(7),
            ]);

            // Crear 3 transacciones earned
            for ($i = 0; $i < 3; $i++) {
                CustomerPointsTransaction::factory()->earned(100)
                    ->for($customer)
                    ->create(['created_at' => now()->subMonths(8 - $i)]);
            }

            // Ejecutar el job
            (new ExpireInactivePoints)->handle();

            // Verificar que todas las transacciones earned estan marcadas como expiradas
            $earnedTransactions = CustomerPointsTransaction::where('customer_id', $customer->id)
                ->where('type', 'earned')
                ->get();

            foreach ($earnedTransactions as $transaction) {
                expect($transaction->is_expired)->toBeTrue();
            }
        });
    });

    describe('Metodo FIFO - Solo expiran los puntos mas antiguos', function () {
        beforeEach(function () {
            // Configurar metodo FIFO
            PointsSetting::create([
                'quetzales_per_point' => 10,
                'expiration_method' => 'fifo',
                'expiration_months' => 6,
                'rounding_threshold' => 0.70,
            ]);
        });

        test('solo expira transacciones antiguas, mantiene las recientes', function () {
            // Cliente con 250 puntos: 200 de enero (vencidos) + 50 de febrero (no vencidos)
            $customer = Customer::factory()->withoutCustomerType()->create([
                'points' => 250,
                'points_last_activity_at' => now()->subMonths(7),
            ]);

            // Transaccion de enero (ya vencio - expires_at paso)
            CustomerPointsTransaction::factory()
                ->for($customer)
                ->create([
                    'points' => 200,
                    'type' => 'earned',
                    'expires_at' => now()->subDays(30), // Ya paso
                    'is_expired' => false,
                    'created_at' => now()->subMonths(8),
                ]);

            // Transaccion de febrero (aun no vence)
            CustomerPointsTransaction::factory()
                ->for($customer)
                ->create([
                    'points' => 50,
                    'type' => 'earned',
                    'expires_at' => now()->addDays(30), // Aun no pasa
                    'is_expired' => false,
                    'created_at' => now()->subMonths(7),
                ]);

            // Ejecutar el job
            (new ExpireInactivePoints)->handle();

            // Verificar que solo se expiraron 200 puntos (los de enero)
            $customer->refresh();
            expect($customer->points)->toBe(50);

            // Verificar la transaccion de expiracion
            $expiredTransaction = CustomerPointsTransaction::where('customer_id', $customer->id)
                ->where('type', 'expired')
                ->first();

            expect($expiredTransaction)->not->toBeNull();
            expect($expiredTransaction->points)->toBe(-200);
        });

        test('ejemplo del usuario: 200 enero + 50 febrero, consume 100, quedan 150, expiran 100 de enero', function () {
            // Escenario del usuario:
            // - 200 puntos en enero
            // - 50 puntos en febrero
            // - Consume 100 puntos (simulamos que ya se restaron)
            // - Quedan 150 puntos (100 de enero + 50 de febrero)
            // - Pasan 6 meses de inactividad
            // - Solo deben expirar los 100 puntos restantes de enero

            $customer = Customer::factory()->withoutCustomerType()->create([
                'points' => 150, // Ya se consumieron 100
                'points_last_activity_at' => now()->subMonths(7),
            ]);

            // Transaccion de enero: originalmente 200, pero como se consumieron 100 via FIFO,
            // quedarian 100 de esta transaccion (simulamos que solo quedan 100)
            CustomerPointsTransaction::factory()
                ->for($customer)
                ->create([
                    'points' => 100, // Solo quedan 100 de los 200 originales
                    'type' => 'earned',
                    'expires_at' => now()->subDays(1), // Ya vencio
                    'is_expired' => false,
                    'created_at' => now()->subMonths(8),
                ]);

            // Transaccion de febrero: 50 puntos intactos
            CustomerPointsTransaction::factory()
                ->for($customer)
                ->create([
                    'points' => 50,
                    'type' => 'earned',
                    'expires_at' => now()->addMonths(1), // Vence en 1 mes
                    'is_expired' => false,
                    'created_at' => now()->subMonths(7),
                ]);

            // Ejecutar el job
            (new ExpireInactivePoints)->handle();

            // Solo deben quedar los 50 de febrero
            $customer->refresh();
            expect($customer->points)->toBe(50);

            // Verificar transaccion de expiracion
            $expiredTransaction = CustomerPointsTransaction::where('customer_id', $customer->id)
                ->where('type', 'expired')
                ->first();

            expect($expiredTransaction)->not->toBeNull();
            expect($expiredTransaction->points)->toBe(-100);
        });

        test('no expira nada si todas las transacciones aun no vencen', function () {
            $customer = Customer::factory()->withoutCustomerType()->create([
                'points' => 300,
                'points_last_activity_at' => now()->subMonths(7),
            ]);

            // Todas las transacciones tienen expires_at en el futuro
            for ($i = 1; $i <= 3; $i++) {
                CustomerPointsTransaction::factory()
                    ->for($customer)
                    ->create([
                        'points' => 100,
                        'type' => 'earned',
                        'expires_at' => now()->addMonths($i), // Futuro
                        'is_expired' => false,
                    ]);
            }

            // Ejecutar el job
            (new ExpireInactivePoints)->handle();

            // No debe expirar nada
            $customer->refresh();
            expect($customer->points)->toBe(300);
        });

        test('expira multiples transacciones antiguas en orden FIFO', function () {
            $customer = Customer::factory()->withoutCustomerType()->create([
                'points' => 400,
                'points_last_activity_at' => now()->subMonths(10),
            ]);

            // Enero: 100 puntos (vencidos hace 4 meses)
            CustomerPointsTransaction::factory()
                ->for($customer)
                ->create([
                    'points' => 100,
                    'type' => 'earned',
                    'expires_at' => now()->subMonths(4),
                    'is_expired' => false,
                    'created_at' => now()->subMonths(10),
                ]);

            // Febrero: 100 puntos (vencidos hace 3 meses)
            CustomerPointsTransaction::factory()
                ->for($customer)
                ->create([
                    'points' => 100,
                    'type' => 'earned',
                    'expires_at' => now()->subMonths(3),
                    'is_expired' => false,
                    'created_at' => now()->subMonths(9),
                ]);

            // Marzo: 100 puntos (vencidos hace 2 meses)
            CustomerPointsTransaction::factory()
                ->for($customer)
                ->create([
                    'points' => 100,
                    'type' => 'earned',
                    'expires_at' => now()->subMonths(2),
                    'is_expired' => false,
                    'created_at' => now()->subMonths(8),
                ]);

            // Abril: 100 puntos (aun no vence)
            CustomerPointsTransaction::factory()
                ->for($customer)
                ->create([
                    'points' => 100,
                    'type' => 'earned',
                    'expires_at' => now()->addMonths(2),
                    'is_expired' => false,
                    'created_at' => now()->subMonths(7),
                ]);

            // Ejecutar el job
            (new ExpireInactivePoints)->handle();

            // Solo deben quedar los 100 de abril
            $customer->refresh();
            expect($customer->points)->toBe(100);

            // Verificar que se expiraron 300 puntos
            $expiredTransaction = CustomerPointsTransaction::where('customer_id', $customer->id)
                ->where('type', 'expired')
                ->first();

            expect($expiredTransaction->points)->toBe(-300);
        });

        test('no expira nada si el cliente tiene actividad reciente', function () {
            // Cliente con actividad hace 3 meses (menos de 6)
            $customer = Customer::factory()->withoutCustomerType()->create([
                'points' => 200,
                'points_last_activity_at' => now()->subMonths(3),
            ]);

            // Transaccion que tecnicamente ya "vencio" segun expires_at
            CustomerPointsTransaction::factory()
                ->for($customer)
                ->create([
                    'points' => 200,
                    'type' => 'earned',
                    'expires_at' => now()->subDays(1),
                    'is_expired' => false,
                ]);

            // Ejecutar el job
            (new ExpireInactivePoints)->handle();

            // No debe expirar porque el cliente tiene actividad reciente
            $customer->refresh();
            expect($customer->points)->toBe(200);
        });
    });

    describe('Cambio de configuracion dinamica', function () {
        test('respeta la configuracion de meses de expiracion personalizada', function () {
            // Configurar 3 meses en lugar de 6
            PointsSetting::create([
                'quetzales_per_point' => 10,
                'expiration_method' => 'total',
                'expiration_months' => 3,
                'rounding_threshold' => 0.70,
            ]);

            // Cliente con 4 meses de inactividad (deberia expirar con config de 3 meses)
            $customer = Customer::factory()->withoutCustomerType()->create([
                'points' => 100,
                'points_last_activity_at' => now()->subMonths(4),
            ]);

            CustomerPointsTransaction::factory()->earned(100)
                ->for($customer)
                ->create();

            // Ejecutar el job
            (new ExpireInactivePoints)->handle();

            // Deberia expirar porque 4 > 3 meses
            $customer->refresh();
            expect($customer->points)->toBe(0);
        });

        test('cliente con 5 meses de inactividad no expira si config es 6 meses', function () {
            PointsSetting::create([
                'quetzales_per_point' => 10,
                'expiration_method' => 'total',
                'expiration_months' => 6,
                'rounding_threshold' => 0.70,
            ]);

            $customer = Customer::factory()->withoutCustomerType()->create([
                'points' => 100,
                'points_last_activity_at' => now()->subMonths(5),
            ]);

            CustomerPointsTransaction::factory()->earned(100)
                ->for($customer)
                ->create();

            // Ejecutar el job
            (new ExpireInactivePoints)->handle();

            // No deberia expirar porque 5 < 6 meses
            $customer->refresh();
            expect($customer->points)->toBe(100);
        });
    });
});
