Plan: Corrección de API de Órdenes + Tiempo Real (FCM + Reverb)
Resumen Ejecutivo
Este plan aborda dos objetivos:

Corregir problemas críticos en la gestión de estados de órdenes
Implementar tiempo real con FCM (push) + Reverb (WebSocket) para Flutter
Parte 1: Corrección de Problemas en API de Órdenes
Problema 1: CRÍTICO - Validación pickup vs delivery inexistente
Ubicación: app/Services/OrderService.php:468-482

Problema actual:


Order::STATUS_READY => [Order::STATUS_OUT_FOR_DELIVERY, Order::STATUS_COMPLETED],
Permite que una orden PICKUP vaya a out_for_delivery (incorrecto).

Solución: Modificar validateStatusTransition() para recibir $serviceType:


private function validateStatusTransition(string $current, string $new, string $serviceType): bool
{
    $validTransitions = [
        Order::STATUS_PENDING => [Order::STATUS_PREPARING, Order::STATUS_CANCELLED],
        Order::STATUS_PREPARING => [Order::STATUS_READY, Order::STATUS_CANCELLED],
        Order::STATUS_COMPLETED => [],
        Order::STATUS_CANCELLED => [],
        Order::STATUS_REFUNDED => [],
    ];

    // Transiciones específicas según tipo de servicio
    if ($serviceType === 'pickup') {
        $validTransitions[Order::STATUS_READY] = [Order::STATUS_COMPLETED, Order::STATUS_CANCELLED];
    } else { // delivery
        $validTransitions[Order::STATUS_READY] = [Order::STATUS_OUT_FOR_DELIVERY, Order::STATUS_CANCELLED];
        $validTransitions[Order::STATUS_OUT_FOR_DELIVERY] = [Order::STATUS_DELIVERED, Order::STATUS_CANCELLED];
        $validTransitions[Order::STATUS_DELIVERED] = [Order::STATUS_COMPLETED];
    }

    return in_array($new, $validTransitions[$current] ?? []);
}
Archivos a modificar:

app/Services/OrderService.php - método validateStatusTransition (línea 468)
app/Services/OrderService.php - método updateStatus (línea 312) - pasar service_type
Problema 2: CRÍTICO - OrderManagementService sin validación
Ubicación: app/Services/OrderManagementService.php:82-109

Problema: Actualiza estado sin validar transiciones válidas.

Solución: Delegar a OrderService para reutilizar validación:


public function updateOrderStatus(Order $order, string $status, ?string $notes = null): Order
{
    return $this->orderService->updateStatus(
        $order,
        $status,
        $notes,
        'admin',
        auth()->id()
    );
}
Archivos a modificar:

app/Services/OrderManagementService.php - inyectar OrderService, delegar updateOrderStatus
Problema 3: ALTO - Duplicación de lógica de puntos (4 lugares)
Ubicaciones:

OrderService.php:323-326
OrderManagementService.php:100-103
Restaurant/OrderController.php:331
Restaurant/OrderController.php:369
Solución: Centralizar en OrderService y eliminar de los demás:

Mantener SOLO en OrderService::updateStatus()
Eliminar de OrderManagementService (ya delegará a OrderService)
Eliminar de Restaurant/OrderController - usar OrderService
Archivos a modificar:

app/Services/OrderManagementService.php - eliminar creditPoints
app/Http/Controllers/Restaurant/OrderController.php - usar OrderService
Problema 4: BAJO - canBeCancelled()
Ubicación: app/Models/Order.php:152-155

Decisión: Mantener solo PENDING como cancelable (comportamiento actual).

Sin cambios necesarios - El usuario prefiere que solo se puedan cancelar órdenes pendientes.

Problema 5: MEDIO - delivered_at se sobrescribe incorrectamente
Ubicación: app/Services/OrderManagementService.php:94

Problema: delivered_at se actualiza tanto en DELIVERED como en COMPLETED.

Solución: Solo actualizar en DELIVERED:


if ($status === Order::STATUS_DELIVERED) {
    $updateData['delivered_at'] = now();
}
// Eliminar el elseif de COMPLETED que también actualiza delivered_at
Nota: Este problema se resolverá automáticamente al delegar a OrderService.

Parte 2: Implementación de Tiempo Real
2.1 Crear Evento OrderStatusUpdated para Broadcasting
Nuevo archivo: app/Events/OrderStatusUpdated.php


<?php

namespace App\Events;

use App\Http\Resources\Api\V1\Order\OrderResource;
use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Order $order,
        public string $previousStatus
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('customer.' . $this->order->customer_id . '.orders'),
            new PrivateChannel('restaurant.' . $this->order->restaurant_id . '.orders'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'order.status.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'order' => new OrderResource($this->order),
            'previous_status' => $this->previousStatus,
            'new_status' => $this->order->status,
        ];
    }
}
2.2 Registrar Canales de Broadcasting
Archivo: routes/channels.php

Agregar:


// Canal privado para órdenes del cliente
Broadcast::channel('customer.{customerId}.orders', function ($user, $customerId) {
    // Para API: el customer autenticado
    if ($user instanceof \App\Models\Customer) {
        return $user->id === (int) $customerId;
    }
    return false;
});

// Canal privado para órdenes del restaurante
Broadcast::channel('restaurant.{restaurantId}.orders', function ($user, $restaurantId) {
    // Para panel de restaurante
    if ($user instanceof \App\Models\RestaurantUser) {
        return $user->restaurant_id === (int) $restaurantId;
    }
    return false;
});
2.3 Registrar Canal FCM en Laravel
Archivo: app/Providers/AppServiceProvider.php

Agregar en boot():


use Illuminate\Notifications\ChannelManager;
use App\Notifications\Channels\FcmChannel;

public function boot(): void
{
    // ... código existente ...

    // Registrar canal FCM
    $this->app->make(ChannelManager::class)->extend('fcm', function ($app) {
        return $app->make(FcmChannel::class);
    });
}
2.4 Disparar Evento y Notificación en OrderService
Archivo: app/Services/OrderService.php

La notificación OrderStatusChangedNotification ya existe y está lista. Solo necesitamos:

Disparar el nuevo evento de broadcast
Enviar la notificación push existente
En método updateStatus(), después del DB::transaction:


use App\Events\OrderStatusUpdated;
use App\Notifications\OrderStatusChangedNotification;

// Después del DB::transaction, antes del return
event(new OrderStatusUpdated($order, $previousStatus));

// Enviar push notification (la notificación ya existe)
if ($order->customer) {
    $order->customer->notify(new OrderStatusChangedNotification($order, $previousStatus));
}
Nota: OrderStatusChangedNotification ya existe con:

Canal FCM configurado
Mensajes por estado (preparing, ready, out_for_delivery, etc.)
Datos: order_id, order_number, status, previous_status
2.5 Agregar Reverb al comando dev
Archivo: composer.json

Modificar script dev:


"dev": [
    "Composer\\Config::disableProcessTimeout",
    "npx concurrently -c \"#93c5fd,#c4b5fd,#fb7185,#fdba74,#22d3ee\" \"php artisan serve\" \"php artisan queue:listen --tries=1\" \"php artisan pail --timeout=0\" \"npm run dev\" \"php artisan reverb:start\" --names=server,queue,logs,vite,reverb"
]
Parte 3: Configuración del Servidor Reverb (Producción)
3.1 Crear archivo de configuración Supervisor
Nuevo archivo: /etc/supervisor/conf.d/reverb.conf


[program:reverb]
process_name=%(program_name)s
command=php /var/www/html/AdminPanel/artisan reverb:start --host=0.0.0.0 --port=8080
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/html/AdminPanel/storage/logs/reverb.log
stopwaitsecs=3600
3.2 Comandos para activar

# Recargar supervisor
sudo supervisorctl reread
sudo supervisorctl update

# Iniciar Reverb
sudo supervisorctl start reverb

# Verificar estado
sudo supervisorctl status reverb
3.3 Configuración Nginx (WebSocket proxy)
Agregar al bloque server de la API:


location /app {
    proxy_pass http://127.0.0.1:8080;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "upgrade";
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
    proxy_read_timeout 60s;
    proxy_send_timeout 60s;
}
Parte 4: Tests
4.1 Test de Validación de Transiciones de Estado
Nuevo archivo: tests/Feature/Services/OrderServiceStatusTransitionTest.php


<?php

use App\Models\Order;
use App\Models\Customer;
use App\Models\Restaurant;
use App\Services\OrderService;

it('permite transición pickup: ready -> completed', function () {
    $order = Order::factory()->create([
        'status' => Order::STATUS_READY,
        'service_type' => 'pickup',
    ]);

    $service = app(OrderService::class);
    $updated = $service->updateStatus($order, Order::STATUS_COMPLETED, null, 'system', null);

    expect($updated->status)->toBe(Order::STATUS_COMPLETED);
});

it('rechaza transición pickup: ready -> out_for_delivery', function () {
    $order = Order::factory()->create([
        'status' => Order::STATUS_READY,
        'service_type' => 'pickup',
    ]);

    $service = app(OrderService::class);

    expect(fn() => $service->updateStatus($order, Order::STATUS_OUT_FOR_DELIVERY, null, 'system', null))
        ->toThrow(InvalidArgumentException::class);
});

it('permite transición delivery: ready -> out_for_delivery', function () {
    $order = Order::factory()->create([
        'status' => Order::STATUS_READY,
        'service_type' => 'delivery',
    ]);

    $service = app(OrderService::class);
    $updated = $service->updateStatus($order, Order::STATUS_OUT_FOR_DELIVERY, null, 'system', null);

    expect($updated->status)->toBe(Order::STATUS_OUT_FOR_DELIVERY);
});

it('acredita puntos solo una vez al completar orden', function () {
    $customer = Customer::factory()->create(['points' => 0]);
    $order = Order::factory()->create([
        'customer_id' => $customer->id,
        'status' => Order::STATUS_READY,
        'service_type' => 'pickup',
        'total' => 100,
    ]);

    $service = app(OrderService::class);
    $service->updateStatus($order, Order::STATUS_COMPLETED, null, 'system', null);

    $customer->refresh();
    $initialPoints = $customer->points;

    // Intentar completar de nuevo debería fallar
    expect(fn() => $service->updateStatus($order->fresh(), Order::STATUS_COMPLETED, null, 'system', null))
        ->toThrow(InvalidArgumentException::class);

    // Puntos no deberían cambiar
    expect($customer->fresh()->points)->toBe($initialPoints);
});
4.2 Test de Broadcasting
Nuevo archivo: tests/Feature/Events/OrderStatusUpdatedTest.php


<?php

use App\Events\OrderStatusUpdated;
use App\Models\Order;
use Illuminate\Support\Facades\Event;

it('dispara evento OrderStatusUpdated al cambiar estado', function () {
    Event::fake([OrderStatusUpdated::class]);

    $order = Order::factory()->create([
        'status' => Order::STATUS_PENDING,
        'service_type' => 'pickup',
    ]);

    $service = app(\App\Services\OrderService::class);
    $service->updateStatus($order, Order::STATUS_PREPARING, null, 'system', null);

    Event::assertDispatched(OrderStatusUpdated::class, function ($event) use ($order) {
        return $event->order->id === $order->id
            && $event->previousStatus === Order::STATUS_PENDING;
    });
});

it('broadcast en canales correctos', function () {
    $order = Order::factory()->create();
    $event = new OrderStatusUpdated($order, 'pending');

    $channels = $event->broadcastOn();

    expect($channels)->toHaveCount(2);
    expect($channels[0]->name)->toBe('private-customer.' . $order->customer_id . '.orders');
    expect($channels[1]->name)->toBe('private-restaurant.' . $order->restaurant_id . '.orders');
});
Parte 5: Configuración para Flutter
3.1 Endpoint de Autenticación para WebSocket
Nuevo archivo: app/Http/Controllers/Api/V1/BroadcastingController.php


<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;

class BroadcastingController extends Controller
{
    public function auth(Request $request)
    {
        return Broadcast::auth($request);
    }
}
Ruta: routes/api.php


Route::post('/broadcasting/auth', [BroadcastingController::class, 'auth'])
    ->middleware('auth:sanctum');
Archivos a Crear
Archivo	Propósito
app/Events/OrderStatusUpdated.php	Evento broadcast para cambios de estado
app/Http/Controllers/Api/V1/BroadcastingController.php	Auth para WebSocket desde Flutter
tests/Feature/Services/OrderServiceStatusTransitionTest.php	Tests de validación de estados
tests/Feature/Events/OrderStatusUpdatedTest.php	Tests de broadcasting
Archivos a Modificar
Archivo	Cambios
app/Services/OrderService.php	Mejorar validateStatusTransition con service_type, disparar evento y notificación
app/Services/OrderManagementService.php	Delegar a OrderService, eliminar lógica duplicada
app/Http/Controllers/Restaurant/OrderController.php	Usar OrderService en lugar de lógica manual
app/Providers/AppServiceProvider.php	Registrar canal FCM
routes/channels.php	Agregar canales customer.{id}.orders y restaurant.{id}.orders
routes/api.php	Agregar ruta broadcasting/auth
composer.json	Agregar reverb al comando dev
Configuración de Servidor (Manual)
Archivo	Propósito
/etc/supervisor/conf.d/reverb.conf	Configuración Supervisor para Reverb
Nginx config	Proxy WebSocket para /app
Verificación
Tests a ejecutar:

# Nuevos tests de transiciones de estado
php artisan test tests/Feature/Services/OrderServiceStatusTransitionTest.php

# Nuevos tests de broadcasting
php artisan test tests/Feature/Events/OrderStatusUpdatedTest.php

# Tests existentes de órdenes (verificar que no se rompieron)
php artisan test --filter=Order

# Tests de dispositivos/FCM
php artisan test tests/Feature/Api/V1/DeviceControllerTest.php

# Suite completa al final
php artisan test
Verificación manual:
Validación pickup vs delivery:


php artisan tinker

$order = Order::where('service_type', 'pickup')->where('status', 'ready')->first();
app(OrderService::class)->updateStatus($order, 'out_for_delivery', null, 'system', null);
// Debe lanzar InvalidArgumentException
Verificar Reverb funcionando:


php artisan reverb:start
# En otra terminal:
curl -I http://localhost:8080
Probar push notification:


$order = Order::with('customer')->first();
$order->customer->notify(new \App\Notifications\OrderStatusChangedNotification($order, 'pending'));
Verificar evento broadcast:


event(new \App\Events\OrderStatusUpdated(Order::first(), 'pending'));
# Revisar logs de Reverb
Verificación en producción:

# Estado de Reverb
sudo supervisorctl status reverb

# Logs de Reverb
tail -f /var/www/html/AdminPanel/storage/logs/reverb.log
Para Flutter (Referencia)
WebSocket con Reverb:

// Usar paquete laravel_echo_null o web_socket_channel
final echo = Echo(
  broadcaster: 'reverb',
  options: EchoOptions(
    host: 'wss://appmobile.subwaycardgt.com:8080',
    authEndpoint: 'https://api.../api/v1/broadcasting/auth',
    auth: {'headers': {'Authorization': 'Bearer $token'}},
  ),
);

echo.private('customer.$customerId.orders')
  .listen('order.status.updated', (data) {
    // Actualizar estado de orden en UI
  });
Push Notifications:

// firebase_messaging ya configurado
FirebaseMessaging.onMessage.listen((message) {
  if (message.data['type'] == 'order_status_changed') {
    // Actualizar UI
  }
});
