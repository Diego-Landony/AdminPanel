# Plan: Sistema de Validación de Geocercas y Asignación de Restaurante

## Resumen

Sistema para validar que la **dirección de entrega** del cliente esté dentro de una geocerca de restaurante, asignando automáticamente el restaurante correcto y la zona de precios.

## Reglas de Negocio Confirmadas

| Regla | Comportamiento |
|-------|----------------|
| Múltiples restaurantes posibles | Seleccionar el **más cercano** a la dirección |
| Restaurante sin geocerca | **Solo pickup** - excluir de delivery |
| Validación de geocerca | **Siempre obligatoria** para delivery |
| Zona de precios | Según `restaurant.price_location` (capital/interior) |

---

# FASE 1: Servicios Core de Geocerca

## Objetivo

Crear los servicios base para parsear KML y detectar si un punto está dentro de un polígono.

## Archivos a Crear

### 1.1 PointInPolygonService

**Archivo:** `app/Services/Geofence/PointInPolygonService.php`

```php
<?php

namespace App\Services\Geofence;

class PointInPolygonService
{
    /**
     * Verifica si un punto está dentro de un polígono usando ray-casting
     *
     * @param float $lat Latitud del punto
     * @param float $lng Longitud del punto
     * @param array $polygon Array de ['lat' => float, 'lng' => float]
     */
    public function isPointInPolygon(float $lat, float $lng, array $polygon): bool
    {
        $inside = false;
        $n = count($polygon);

        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            $xi = $polygon[$i]['lat'];
            $yi = $polygon[$i]['lng'];
            $xj = $polygon[$j]['lat'];
            $yj = $polygon[$j]['lng'];

            if ((($yi > $lng) !== ($yj > $lng)) &&
                ($lat < ($xj - $xi) * ($lng - $yi) / ($yj - $yi) + $xi)) {
                $inside = !$inside;
            }
        }

        return $inside;
    }
}
```

### 1.2 KmlParserService

**Archivo:** `app/Services/Geofence/KmlParserService.php`

```php
<?php

namespace App\Services\Geofence;

use App\Exceptions\Delivery\InvalidKmlException;

class KmlParserService
{
    /**
     * Parsea contenido KML y extrae coordenadas del polígono
     *
     * @return array Array de ['lat' => float, 'lng' => float]
     * @throws InvalidKmlException
     */
    public function parseToCoordinates(string $kmlContent): array
    {
        // Basado en RestaurantGeofencesController::extractCoordinatesFromKML
        $coordinates = [];

        try {
            $dom = new \DOMDocument;
            $dom->loadXML($kmlContent);
            $coordElements = $dom->getElementsByTagName('coordinates');

            foreach ($coordElements as $coordElement) {
                $coordText = trim($coordElement->textContent);
                $points = explode(' ', $coordText);

                foreach ($points as $point) {
                    $point = trim($point);
                    if (!empty($point)) {
                        $coords = explode(',', $point);
                        if (count($coords) >= 2) {
                            $coordinates[] = [
                                'lat' => (float) $coords[1],
                                'lng' => (float) $coords[0],
                            ];
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            throw new InvalidKmlException(0, 'Error parsing KML: ' . $e->getMessage());
        }

        return $coordinates;
    }
}
```

### 1.3 GeofenceService

**Archivo:** `app/Services/Geofence/GeofenceService.php`

```php
<?php

namespace App\Services\Geofence;

use App\Models\Restaurant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class GeofenceService
{
    public function __construct(
        private KmlParserService $kmlParser,
        private PointInPolygonService $pointInPolygon
    ) {}

    /**
     * Encuentra restaurantes que pueden entregar a las coordenadas dadas
     */
    public function findRestaurantsForCoordinates(float $lat, float $lng): Collection
    {
        $restaurants = Restaurant::query()
            ->active()
            ->deliveryActive()
            ->withGeofence()
            ->get();

        return $restaurants->filter(function ($restaurant) use ($lat, $lng) {
            return $this->canRestaurantDeliverTo($restaurant, $lat, $lng);
        });
    }

    /**
     * Verifica si un restaurante puede entregar a las coordenadas
     */
    public function canRestaurantDeliverTo(Restaurant $restaurant, float $lat, float $lng): bool
    {
        if (!$restaurant->hasGeofence()) {
            return false;
        }

        $coordinates = $this->getGeofenceCoordinates($restaurant);

        if (empty($coordinates)) {
            return false;
        }

        return $this->pointInPolygon->isPointInPolygon($lat, $lng, $coordinates);
    }

    /**
     * Obtiene el mejor restaurante para delivery (el más cercano)
     */
    public function getBestRestaurantForDelivery(float $lat, float $lng): ?Restaurant
    {
        $restaurants = $this->findRestaurantsForCoordinates($lat, $lng);

        if ($restaurants->isEmpty()) {
            return null;
        }

        // Retornar el más cercano
        return $restaurants->sortBy(function ($restaurant) use ($lat, $lng) {
            return $this->calculateDistance($lat, $lng, $restaurant->latitude, $restaurant->longitude);
        })->first();
    }

    /**
     * Obtiene coordenadas de geocerca con cache
     */
    private function getGeofenceCoordinates(Restaurant $restaurant): array
    {
        $cacheKey = "restaurant:{$restaurant->id}:geofence:" . md5($restaurant->geofence_kml);

        return Cache::remember($cacheKey, now()->addHours(24), function () use ($restaurant) {
            return $this->kmlParser->parseToCoordinates($restaurant->geofence_kml);
        });
    }

    /**
     * Calcula distancia entre dos puntos (Haversine)
     */
    private function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371; // km

        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($lngDelta / 2) * sin($lngDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
```

### 1.4 Excepciones

**Archivo:** `app/Exceptions/Delivery/InvalidKmlException.php`

```php
<?php

namespace App\Exceptions\Delivery;

class InvalidKmlException extends \Exception
{
    public function __construct(
        public readonly int $restaurantId,
        string $message = 'El KML de la geocerca es inválido'
    ) {
        parent::__construct($message);
    }
}
```

**Archivo:** `app/Exceptions/Delivery/AddressOutsideDeliveryZoneException.php`

```php
<?php

namespace App\Exceptions\Delivery;

class AddressOutsideDeliveryZoneException extends \Exception
{
    public function __construct(
        public readonly float $lat,
        public readonly float $lng,
        string $message = 'La dirección está fuera de todas las zonas de entrega'
    ) {
        parent::__construct($message);
    }
}
```

## Tests a Crear

**Archivo:** `tests/Unit/Services/Geofence/PointInPolygonServiceTest.php`
**Archivo:** `tests/Unit/Services/Geofence/KmlParserServiceTest.php`
**Archivo:** `tests/Unit/Services/Geofence/GeofenceServiceTest.php`

---

# FASE 2: DeliveryValidationService

## Objetivo

Crear el servicio orquestador que valida direcciones de entrega y asigna restaurantes.

## Archivos a Crear

### 2.1 DeliveryValidationService

**Archivo:** `app/Services/DeliveryValidationService.php`

```php
<?php

namespace App\Services;

use App\Exceptions\Delivery\AddressOutsideDeliveryZoneException;
use App\Models\CustomerAddress;
use App\Models\Restaurant;
use App\Services\Geofence\GeofenceService;

class DeliveryValidationService
{
    public function __construct(
        private GeofenceService $geofenceService
    ) {}

    /**
     * Valida dirección de entrega y retorna el restaurante asignado
     *
     * @throws AddressOutsideDeliveryZoneException
     */
    public function validateDeliveryAddress(CustomerAddress $address): DeliveryValidationResult
    {
        return $this->validateCoordinates($address->latitude, $address->longitude);
    }

    /**
     * Valida coordenadas y retorna resultado
     *
     * @throws AddressOutsideDeliveryZoneException
     */
    public function validateCoordinates(float $lat, float $lng): DeliveryValidationResult
    {
        $restaurant = $this->geofenceService->getBestRestaurantForDelivery($lat, $lng);

        if (!$restaurant) {
            // Obtener restaurantes cercanos para pickup
            $nearbyPickup = $this->getNearbyPickupRestaurants($lat, $lng);

            return new DeliveryValidationResult(
                isValid: false,
                restaurant: null,
                zone: null,
                nearbyPickupRestaurants: $nearbyPickup,
                errorMessage: 'No tenemos cobertura de delivery en esta ubicación'
            );
        }

        return new DeliveryValidationResult(
            isValid: true,
            restaurant: $restaurant,
            zone: $restaurant->price_location,
            nearbyPickupRestaurants: [],
            errorMessage: null
        );
    }

    /**
     * Obtiene restaurantes cercanos para pickup
     */
    private function getNearbyPickupRestaurants(float $lat, float $lng, int $limit = 3): array
    {
        return Restaurant::query()
            ->active()
            ->pickupActive()
            ->withCoordinates()
            ->get()
            ->map(function ($restaurant) use ($lat, $lng) {
                $distance = $this->calculateDistance($lat, $lng, $restaurant->latitude, $restaurant->longitude);
                return [
                    'id' => $restaurant->id,
                    'name' => $restaurant->name,
                    'address' => $restaurant->address,
                    'distance_km' => round($distance, 2),
                ];
            })
            ->sortBy('distance_km')
            ->take($limit)
            ->values()
            ->toArray();
    }

    private function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371;
        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);
        $a = sin($latDelta / 2) * sin($latDelta / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($lngDelta / 2) * sin($lngDelta / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }
}
```

### 2.2 DeliveryValidationResult (Value Object)

**Archivo:** `app/Services/DeliveryValidationResult.php`

```php
<?php

namespace App\Services;

use App\Models\Restaurant;

readonly class DeliveryValidationResult
{
    public function __construct(
        public bool $isValid,
        public ?Restaurant $restaurant,
        public ?string $zone,
        public array $nearbyPickupRestaurants = [],
        public ?string $errorMessage = null
    ) {}
}
```

## Tests a Crear

**Archivo:** `tests/Unit/Services/DeliveryValidationServiceTest.php`

---

# FASE 3: API de Direcciones del Cliente

## Objetivo

Crear endpoints CRUD para que la app móvil gestione direcciones del cliente.

## Archivos a Crear

### 3.1 CustomerAddressController

**Archivo:** `app/Http/Controllers/Api/V1/CustomerAddressController.php`

Endpoints:
- `GET /api/v1/addresses` - Listar direcciones
- `POST /api/v1/addresses` - Crear dirección
- `GET /api/v1/addresses/{id}` - Ver dirección
- `PUT /api/v1/addresses/{id}` - Actualizar dirección
- `DELETE /api/v1/addresses/{id}` - Eliminar dirección
- `POST /api/v1/addresses/{id}/set-default` - Marcar como predeterminada
- `POST /api/v1/addresses/validate` - Validar coordenadas contra geocercas

### 3.2 CustomerAddressResource

**Archivo:** `app/Http/Resources/Api/V1/CustomerAddressResource.php`

```php
<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerAddressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'address_line' => $this->address_line,
            'latitude' => (float) $this->latitude,
            'longitude' => (float) $this->longitude,
            'delivery_notes' => $this->delivery_notes,
            'is_default' => $this->is_default,
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
```

### 3.3 Form Requests

**Archivos:**
- `app/Http/Requests/Api/V1/CustomerAddress/StoreCustomerAddressRequest.php`
- `app/Http/Requests/Api/V1/CustomerAddress/UpdateCustomerAddressRequest.php`
- `app/Http/Requests/Api/V1/CustomerAddress/ValidateLocationRequest.php`

### 3.4 Rutas

**Agregar a:** `routes/api.php`

```php
// Customer Addresses (autenticado)
Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    Route::prefix('addresses')->name('api.v1.addresses.')->group(function () {
        Route::get('/', [CustomerAddressController::class, 'index'])->name('index');
        Route::post('/', [CustomerAddressController::class, 'store'])->name('store');
        Route::get('/{address}', [CustomerAddressController::class, 'show'])->name('show');
        Route::put('/{address}', [CustomerAddressController::class, 'update'])->name('update');
        Route::delete('/{address}', [CustomerAddressController::class, 'destroy'])->name('destroy');
        Route::post('/{address}/set-default', [CustomerAddressController::class, 'setDefault'])->name('set-default');
        Route::post('/validate', [CustomerAddressController::class, 'validateLocation'])->name('validate');
    });
});
```

## Tests a Crear

**Archivo:** `tests/Feature/Api/V1/CustomerAddressControllerTest.php`

---

# FASE 4: Integración con Carrito

## Objetivo

Agregar endpoint para asignar dirección de entrega al carrito con validación de geocerca.

## Archivos a Modificar

### 4.1 Migración

**Crear:** `database/migrations/YYYY_MM_DD_add_delivery_address_id_to_carts_table.php`

```php
Schema::table('carts', function (Blueprint $table) {
    $table->foreignId('delivery_address_id')
        ->nullable()
        ->after('restaurant_id')
        ->constrained('customer_addresses')
        ->nullOnDelete();
});
```

### 4.2 Cart Model

**Modificar:** `app/Models/Cart.php`

Agregar:
- Relación `deliveryAddress()`
- Campo `delivery_address_id` a fillable

### 4.3 CartController

**Modificar:** `app/Http/Controllers/Api/V1/CartController.php`

Agregar método:

```php
/**
 * PUT /api/v1/cart/delivery-address
 * Asigna dirección de entrega y auto-asigna restaurante
 */
public function setDeliveryAddress(SetDeliveryAddressRequest $request): JsonResponse
{
    $customer = auth()->user();
    $cart = $this->cartService->getOrCreateCart($customer);

    $address = CustomerAddress::where('id', $request->delivery_address_id)
        ->where('customer_id', $customer->id)
        ->firstOrFail();

    $result = $this->deliveryValidation->validateDeliveryAddress($address);

    if (!$result->isValid) {
        return response()->json([
            'message' => $result->errorMessage,
            'error_code' => 'ADDRESS_OUTSIDE_DELIVERY_ZONE',
            'data' => [
                'nearest_pickup_locations' => $result->nearbyPickupRestaurants,
            ],
        ], 422);
    }

    $this->cartService->updateDeliveryAddress($cart, $address, $result->restaurant, $result->zone);

    return response()->json([
        'data' => [
            'delivery_address' => new CustomerAddressResource($address),
            'assigned_restaurant' => [
                'id' => $result->restaurant->id,
                'name' => $result->restaurant->name,
            ],
            'zone' => $result->zone,
            'prices_updated' => true,
        ],
    ]);
}
```

### 4.4 CartService

**Modificar:** `app/Services/CartService.php`

Agregar método:

```php
public function updateDeliveryAddress(
    Cart $cart,
    CustomerAddress $address,
    Restaurant $restaurant,
    string $zone
): Cart {
    $oldZone = $cart->zone;

    $cart->update([
        'delivery_address_id' => $address->id,
        'restaurant_id' => $restaurant->id,
        'zone' => $zone,
    ]);

    // Si cambió la zona, recalcular precios
    if ($oldZone !== $zone) {
        $this->recalculatePricesForZone($cart, $zone);
    }

    return $cart->fresh();
}
```

### 4.5 Rutas

**Agregar a:** `routes/api.php`

```php
Route::put('/cart/delivery-address', [CartController::class, 'setDeliveryAddress'])
    ->name('cart.delivery-address.update');
```

## Tests a Crear

**Archivo:** `tests/Feature/Api/V1/CartDeliveryAddressTest.php`

---

# FASE 5: Integración con Órdenes

## Objetivo

Validar geocerca obligatoriamente antes de crear una orden de delivery.

## Archivos a Modificar

### 5.1 OrderService

**Modificar:** `app/Services/OrderService.php`

En método `createFromCart()`, agregar validación:

```php
public function createFromCart(Cart $cart, array $data): Order
{
    // Validar servicio de delivery
    $serviceType = $data['service_type'] ?? $cart->service_type;

    if ($serviceType === 'delivery') {
        // Validación obligatoria de geocerca
        if (!isset($data['delivery_address_id'])) {
            throw new \InvalidArgumentException('La dirección de entrega es requerida para delivery');
        }

        $address = CustomerAddress::where('id', $data['delivery_address_id'])
            ->where('customer_id', $cart->customer_id)
            ->firstOrFail();

        $result = $this->deliveryValidation->validateDeliveryAddress($address);

        if (!$result->isValid) {
            throw new AddressOutsideDeliveryZoneException(
                $address->latitude,
                $address->longitude,
                $result->errorMessage
            );
        }

        // Override con restaurante validado
        $data['restaurant_id'] = $result->restaurant->id;
        $data['zone'] = $result->zone;
    }

    // Continuar con lógica existente...
}
```

### 5.2 CreateOrderRequest

**Modificar:** `app/Http/Requests/Api/V1/Order/CreateOrderRequest.php`

Agregar validación condicional:

```php
public function rules(): array
{
    return [
        // ... reglas existentes ...
        'delivery_address_id' => [
            Rule::requiredIf($this->service_type === 'delivery'),
            'integer',
            Rule::exists('customer_addresses', 'id')->where('customer_id', auth()->id()),
        ],
    ];
}
```

## Tests a Crear

**Archivo:** `tests/Feature/Api/V1/OrderGeofenceValidationTest.php`

---

# Resumen de Archivos por Fase

| Fase | Archivos Nuevos | Archivos Modificar |
|------|-----------------|-------------------|
| **1** | 5 (servicios + excepciones) | 0 |
| **2** | 2 (servicio + value object) | 0 |
| **3** | 5 (controller + resource + requests) | 1 (routes/api.php) |
| **4** | 2 (migración + request) | 3 (Cart, CartController, CartService) |
| **5** | 1 (test) | 2 (OrderService, CreateOrderRequest) |
| **Total** | ~15 | ~6 |

---

# Dependencias entre Fases

```
FASE 1 ──────────────────────────────────────►
   │
   └──► FASE 2 ──────────────────────────────►
            │
            ├──► FASE 3 (puede ser paralela)
            │
            └──► FASE 4 ──────────────────────►
                     │
                     └──► FASE 5
```

**Nota:** Fases 1-2 son prerequisitos. Fase 3 puede ejecutarse en paralelo con Fase 4-5 una vez que Fase 2 esté lista.
