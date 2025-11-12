# SubwayApp - Developer Guide

**Target Audience**: Backend & Mobile App Developers
**Updated**: November 2025

---

## Table of Contents

1. [Getting Started](#getting-started)
2. [Development Environment](#development-environment)
3. [Project Structure](#project-structure)
4. [API Development](#api-development)
5. [Database Guide](#database-guide)
6. [Testing Guide](#testing-guide)
7. [Mobile App Integration](#mobile-app-integration)
8. [Troubleshooting](#troubleshooting)
9. [Best Practices](#best-practices)
10. [Common Tasks](#common-tasks)

---

## Getting Started

### Prerequisites

- **PHP**: 8.4+
- **Node.js**: 18+
- **Composer**: 2.x
- **NPM**: 9+
- **MariaDB**: 10.6+
- **Git**

### Quick Setup

```bash
# 1. Clone repository
git clone <repository-url>
cd AdminPanel

# 2. Install dependencies
composer install
npm install

# 3. Environment configuration
cp .env.example .env
php artisan key:generate

# 4. Configure database in .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=subwayapp
DB_USERNAME=your_user
DB_PASSWORD=your_password

# 5. Run migrations and seeders
php artisan migrate:fresh --seed

# 6. Start development servers
composer run dev
# This starts: Laravel server, queue worker, logs, and Vite
```

### First API Test

```bash
# Login to get token
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "customer.bronze@subway.gt",
    "password": "password"
  }'

# Use token for authenticated request
curl -X GET http://localhost:8000/api/v1/profile \
  -H "Authorization: Bearer <your_token>"
```

---

## Development Environment

### Required Services

```bash
# Start services
php artisan serve           # Laravel: http://localhost:8000
npm run dev                 # Vite: http://localhost:5173
php artisan queue:listen    # Queue worker
php artisan pail            # Real-time logs
```

### Recommended VSCode Extensions

```json
{
  "recommendations": [
    "bmewburn.vscode-intelephense-client",
    "bradlc.vscode-tailwindcss",
    "dbaeumer.vscode-eslint",
    "esbenp.prettier-vscode",
    "mikestead.dotenv",
    "editorconfig.editorconfig"
  ]
}
```

### Environment Variables

**Core**:
```env
APP_NAME="Subway Admin Panel"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
```

**Database**:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=subwayapp
```

**API**:
```env
SANCTUM_STATEFUL_DOMAINS=localhost:5173
```

**OAuth** (Optional for testing):
```env
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
```

**Firebase** (Optional for push notifications):
```env
FIREBASE_CREDENTIALS=storage/app/firebase/credentials.json
```

---

## Project Structure

### Directory Overview

```
AdminPanel/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/V1/           # API Controllers
│   │   │   │   ├── Auth/
│   │   │   │   │   ├── AuthController.php
│   │   │   │   │   └── OAuthController.php
│   │   │   │   ├── ProfileController.php
│   │   │   │   └── DeviceController.php
│   │   │   └── [Web Controllers]  # Admin panel controllers
│   │   ├── Requests/Api/V1/      # Form Requests (validation)
│   │   ├── Resources/            # API Resources (serialization)
│   │   └── Middleware/
│   ├── Models/
│   │   ├── Customer.php
│   │   ├── CustomerDevice.php
│   │   ├── Menu/                 # Menu domain models
│   │   └── [Other models]
│   └── Services/
│       ├── FCMService.php        # Push notifications
│       └── SocialAuthService.php # OAuth logic
├── database/
│   ├── migrations/
│   ├── seeders/
│   └── factories/
├── routes/
│   ├── api.php                   # API routes (/api/v1/*)
│   └── web.php                   # Web routes (admin panel)
├── resources/
│   ├── js/                       # React components
│   └── views/
├── tests/
│   ├── Feature/Api/              # API tests
│   └── Unit/
├── storage/
│   ├── app/firebase/             # Firebase credentials
│   └── api-docs/                 # Swagger JSON
└── docs/                         # Documentation
```

### Key Architectural Patterns

**Controllers**: Thin controllers, delegate to services
**Services**: Business logic, external API calls
**Form Requests**: Validation logic
**Resources**: Response serialization
**Models**: Domain logic, relationships

---

## API Development

### Creating a New Endpoint

#### 1. Define Route

```php
// routes/api.php
Route::prefix('v1')->group(function () {
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/orders', [OrderController::class, 'index'])
            ->name('api.v1.orders.index');
    });
});
```

#### 2. Create Controller

```php
// app/Http/Controllers/Api/V1/OrderController.php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/orders",
     *     tags={"Orders"},
     *     summary="List customer orders",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Success")
     * )
     */
    public function index(Request $request)
    {
        $orders = $request->user()
            ->orders()
            ->with(['items', 'restaurant'])
            ->latest()
            ->paginate(20);

        return OrderResource::collection($orders);
    }
}
```

#### 3. Create Form Request

```php
// app/Http/Requests/Api/V1/CreateOrderRequest.php
namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class CreateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authenticated via middleware
    }

    public function rules(): array
    {
        return [
            'restaurant_id' => 'required|exists:restaurants,id',
            'service_type' => 'required|in:pickup,delivery',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'restaurant_id.required' => 'Debe seleccionar un restaurante',
            'items.required' => 'Debe agregar al menos un producto',
        ];
    }
}
```

#### 4. Create API Resource

```php
// app/Http/Resources/OrderResource.php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'status' => $this->status,
            'service_type' => $this->service_type,
            'total_amount' => $this->total_amount,
            'points_earned' => $this->points_earned,
            'created_at' => $this->created_at,

            // Relationships (only when loaded)
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'restaurant' => RestaurantResource::make($this->whenLoaded('restaurant')),
        ];
    }
}
```

### Adding Swagger Documentation

```php
/**
 * @OA\Post(
 *     path="/api/v1/orders",
 *     tags={"Orders"},
 *     summary="Create new order",
 *     security={{"sanctum":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"restaurant_id","service_type","items"},
 *             @OA\Property(property="restaurant_id", type="integer", example=1),
 *             @OA\Property(property="service_type", type="string", enum={"pickup","delivery"}),
 *             @OA\Property(
 *                 property="items",
 *                 type="array",
 *                 @OA\Items(
 *                     @OA\Property(property="product_id", type="integer"),
 *                     @OA\Property(property="quantity", type="integer")
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(response=201, description="Order created"),
 *     @OA\Response(response=422, description="Validation error")
 * )
 */
```

### Regenerate Swagger Docs

```bash
php artisan l5-swagger:generate
```

---

## Database Guide

### Creating Migrations

```bash
# Create migration
php artisan make:migration create_orders_table

# Create migration for existing table
php artisan make:migration add_status_to_orders_table
```

### Migration Best Practices

```php
// database/migrations/2025_11_07_create_orders_table.php
public function up(): void
{
    Schema::create('orders', function (Blueprint $table) {
        $table->id();
        $table->foreignId('customer_id')
            ->constrained('customers')
            ->onDelete('restrict'); // Prevent deletion

        $table->foreignId('restaurant_id')
            ->constrained('restaurants')
            ->onDelete('restrict');

        $table->string('order_number', 20)->unique();
        $table->enum('status', ['pending', 'confirmed', 'preparing', 'ready', 'delivered', 'cancelled']);
        $table->enum('service_type', ['pickup', 'delivery']);

        $table->decimal('subtotal', 10, 2);
        $table->decimal('delivery_fee', 10, 2)->nullable();
        $table->decimal('total_amount', 10, 2);

        $table->integer('points_earned')->default(0);

        $table->timestamps();
        $table->softDeletes();

        // Indexes for performance
        $table->index(['customer_id', 'created_at']);
        $table->index('status');
        $table->index('order_number');
    });
}

public function down(): void
{
    Schema::dropIfExists('orders');
}
```

### Model Relationships

```php
// app/Models/Customer.php
class Customer extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    // Relationships
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(CustomerDevice::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function customerType(): BelongsTo
    {
        return $this->belongsTo(CustomerType::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }

    public function scopeWithPendingOrders($query)
    {
        return $query->whereHas('orders', function ($q) {
            $q->where('status', 'pending');
        });
    }
}
```

### Seeders for Development

```php
// database/seeders/OrderSeeder.php
class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $customers = Customer::active()->take(10)->get();

        foreach ($customers as $customer) {
            Order::factory()
                ->count(rand(1, 5))
                ->for($customer)
                ->create();
        }
    }
}
```

---

## Testing Guide

### Running Tests

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/Api/V1/Auth/LoginTest.php

# Run with filter
php artisan test --filter=login

# Run with coverage
php artisan test --coverage
```

### Writing Feature Tests

```php
// tests/Feature/Api/V1/OrderTest.php
use function Pest\Laravel\{actingAs, postJson, getJson};

it('can create order with valid data', function () {
    $customer = Customer::factory()->create();
    $restaurant = Restaurant::factory()->create();
    $product = Product::factory()->create();

    $response = actingAs($customer, 'sanctum')
        ->postJson('/api/v1/orders', [
            'restaurant_id' => $restaurant->id,
            'service_type' => 'pickup',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2]
            ]
        ]);

    $response->assertCreated()
        ->assertJsonStructure([
            'id',
            'order_number',
            'status',
            'total_amount'
        ]);

    expect(Order::count())->toBe(1);
});

it('requires authentication', function () {
    $response = postJson('/api/v1/orders', []);

    $response->assertUnauthorized();
});

it('validates required fields', function () {
    $customer = Customer::factory()->create();

    $response = actingAs($customer, 'sanctum')
        ->postJson('/api/v1/orders', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['restaurant_id', 'service_type', 'items']);
});
```

### Mocking External Services

```php
// tests/Feature/Api/V1/Auth/OAuthTest.php
use App\Services\SocialAuthService;
use Mockery;

it('can login with google oauth', function () {
    $mockService = Mockery::mock(SocialAuthService::class);
    $mockService->shouldReceive('verifyGoogleToken')
        ->once()
        ->with('mock_id_token')
        ->andReturn([
            'sub' => '123456789',
            'email' => 'test@gmail.com',
            'name' => 'Test User',
            'picture' => 'https://example.com/avatar.jpg'
        ]);

    $this->app->instance(SocialAuthService::class, $mockService);

    $response = postJson('/api/v1/auth/oauth/google', [
        'id_token' => 'mock_id_token',
        'device_name' => 'Test Device'
    ]);

    $response->assertOk()
        ->assertJsonStructure(['access_token', 'customer']);
});
```

---

## Mobile App Integration

### Authentication Flow

#### 1. Traditional Login

```typescript
// mobile/src/services/auth.ts
import AsyncStorage from '@react-native-async-storage/async-storage';
import DeviceInfo from 'react-native-device-info';

export async function login(email: string, password: string) {
  const deviceIdentifier = await DeviceInfo.getUniqueId();

  const response = await fetch('https://api.subway.gt/api/v1/auth/login', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      email,
      password,
      device_identifier: deviceIdentifier // REQUIRED: Unique device UUID
    })
  });

  if (!response.ok) {
    const error = await response.json();
    throw new Error(error.message);
  }

  const { access_token, customer } = await response.json();

  await AsyncStorage.setItem('token', access_token);
  await AsyncStorage.setItem('customer', JSON.stringify(customer));

  return { token: access_token, customer };
}
```

#### 2. Google OAuth

```typescript
// mobile/src/services/oauth.ts
import { GoogleSignin } from '@react-native-google-signin/google-signin';
import DeviceInfo from 'react-native-device-info';

GoogleSignin.configure({
  webClientId: 'YOUR_WEB_CLIENT_ID.apps.googleusercontent.com',
  offlineAccess: false
});

export async function loginWithGoogle() {
  // 1. Get Google ID token
  await GoogleSignin.hasPlayServices();
  const { idToken } = await GoogleSignin.signIn();

  // 2. Get device identifier
  const deviceIdentifier = await DeviceInfo.getUniqueId();

  // 3. Send to backend
  const response = await fetch('https://api.subway.gt/api/v1/auth/oauth/google', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      id_token: idToken,
      device_identifier: deviceIdentifier // REQUIRED: Unique device UUID
    })
  });

  const { access_token, customer } = await response.json();

  await AsyncStorage.setItem('token', access_token);

  return { token: access_token, customer };
}
```

#### 3. FCM Device Registration

```typescript
// mobile/src/services/notifications.ts
import messaging from '@react-native-firebase/messaging';
import DeviceInfo from 'react-native-device-info';

export async function registerDevice(token: string, userName: string) {
  const fcmToken = await messaging().getToken();
  const deviceIdentifier = await DeviceInfo.getUniqueId(); // UUID for device tracking
  const deviceModel = await DeviceInfo.getDeviceName(); // e.g., "iPhone 14 Pro"

  // IMPORTANT: Personalize device name with user's name
  // This helps users identify their devices in the device list
  const deviceName = `${deviceModel} de ${userName}`; // e.g., "iPhone 14 Pro de Juan"

  await fetch('https://api.subway.gt/api/v1/devices/register', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      fcm_token: fcmToken,                  // REQUIRED: Firebase Cloud Messaging token
      device_identifier: deviceIdentifier,  // REQUIRED: Unique device UUID
      device_name: deviceName               // REQUIRED: Personalized name (e.g., "iPhone de Juan")
    })
  });
}
```

### API Client Setup

```typescript
// mobile/src/api/client.ts
import AsyncStorage from '@react-native-async-storage/async-storage';

const API_BASE_URL = 'https://api.subway.gt/api/v1';

export async function apiRequest(
  endpoint: string,
  options: RequestInit = {}
) {
  const token = await AsyncStorage.getItem('token');

  const response = await fetch(`${API_BASE_URL}${endpoint}`, {
    ...options,
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      ...(token && { 'Authorization': `Bearer ${token}` }),
      ...options.headers
    }
  });

  if (response.status === 401) {
    // Token expired or invalid
    await AsyncStorage.removeItem('token');
    // Navigate to login screen
    throw new Error('Session expired. Please login again.');
  }

  if (!response.ok) {
    const error = await response.json();
    throw new Error(error.message || 'Request failed');
  }

  return response.json();
}

// Usage examples
export const auth = {
  login: (email: string, password: string) =>
    apiRequest('/auth/login', {
      method: 'POST',
      body: JSON.stringify({ email, password })
    }),

  logout: () =>
    apiRequest('/auth/logout', { method: 'POST' }),

  getProfile: () =>
    apiRequest('/profile')
};
```

---

## Troubleshooting

### Common Issues

#### "Unauthenticated" Error

**Problem**: API returns 401 even with valid token

**Solutions**:
1. Check token format: `Bearer <token>`
2. Verify Sanctum configuration:
   ```php
   // config/sanctum.php
   'expiration' => 525600, // 365 days
   ```
3. Check database `personal_access_tokens` table
4. Ensure middleware is applied:
   ```php
   Route::middleware('auth:sanctum')->group(...)
   ```

#### Firebase Push Not Sending

**Problem**: FCM notifications not reaching devices

**Solutions**:
1. Verify Firebase credentials:
   ```bash
   ls -la storage/app/firebase/credentials.json
   ```
2. Check device token is active:
   ```sql
   SELECT * FROM customer_devices WHERE fcm_token = 'xxx' AND is_active = 1;
   ```
3. Test with Firebase Console
4. Check Laravel logs:
   ```bash
   php artisan pail
   ```

#### OAuth "Invalid Token" Error

**Problem**: Google OAuth returns 401

**Solutions**:
1. Verify OAuth credentials in `.env`
2. Check token hasn't expired (mobile SDK handles this)
3. Validate audience matches your client ID
4. Check Socialite configuration:
   ```php
   // config/services.php
   'google' => [
       'client_id' => env('GOOGLE_CLIENT_ID'),
       'client_secret' => env('GOOGLE_CLIENT_SECRET'),
   ]
   ```

#### Rate Limiting Triggered

**Problem**: 429 Too Many Requests

**Solutions**:
1. Check rate limit configuration:
   ```php
   // bootstrap/app.php
   ->withMiddleware(function (Middleware $middleware) {
       $middleware->throttleApi();
   })
   ```
2. Wait for retry_after seconds
3. Implement exponential backoff in mobile app
4. Use batch operations where possible

---

## Best Practices

### API Design

1. **Use RESTful conventions**:
   - GET: Retrieve resources
   - POST: Create resources
   - PUT/PATCH: Update resources
   - DELETE: Remove resources

2. **Version your API**: `/api/v1/`, `/api/v2/`

3. **Use proper HTTP status codes**:
   - 200: Success
   - 201: Created
   - 204: No content
   - 400: Bad request
   - 401: Unauthorized
   - 403: Forbidden
   - 404: Not found
   - 422: Validation error
   - 500: Server error

4. **Consistent error responses**:
   ```json
   {
     "message": "The given data was invalid",
     "errors": {
       "email": ["The email has already been taken."]
     }
   }
   ```

### Security

1. **Never expose sensitive data**:
   - Passwords (even hashed)
   - OAuth provider IDs
   - FCM tokens
   - Internal IDs when unnecessary

2. **Validate all input**:
   - Use Form Requests
   - Sanitize user input
   - Validate file uploads

3. **Use HTTPS only** in production

4. **Implement rate limiting** to prevent abuse

5. **Log security events**:
   - Failed login attempts
   - Password changes
   - Token revocations

### Database

1. **Use migrations** for all schema changes

2. **Always add indexes** for:
   - Foreign keys
   - Frequently queried columns
   - Unique constraints

3. **Use Eloquent relationships** instead of manual joins

4. **Prevent N+1 queries** with eager loading:
   ```php
   Customer::with(['orders', 'addresses'])->get();
   ```

5. **Use transactions** for multi-step operations:
   ```php
   DB::transaction(function () {
       $order = Order::create([...]);
       $order->items()->createMany([...]);
       $customer->increment('points', $order->points_earned);
   });
   ```

### Testing

1. **Write tests before fixing bugs** (TDD)

2. **Test happy paths and edge cases**

3. **Mock external services** (OAuth, FCM)

4. **Use factories** for test data:
   ```php
   Customer::factory()->create();
   ```

5. **Clean up after tests**:
   - Pest/PHPUnit handles this automatically with database transactions
   - Use `RefreshDatabase` trait

---

## Common Tasks

### Add New Validation Rule

```php
// app/Http/Requests/Api/V1/UpdateProfileRequest.php
protected function rules(): array
{
    return [
        'name' => 'required|string|max:255',
        'email' => [
            'required',
            'email',
            'unique:customers,email,' . $this->user()->id
        ],
        'phone' => [
            'required',
            'regex:/^\+502 [0-9]{4}-[0-9]{4}$/'
        ],
    ];
}

protected function messages(): array
{
    return [
        'phone.regex' => 'El formato del teléfono debe ser: +502 5555-1234'
    ];
}
```

### Send Push Notification

```php
// app/Services/FCMService.php
use App\Services\FCMService;

$fcmService = app(FCMService::class);

// Send to specific customer
$fcmService->sendToCustomer($customerId, [
    'title' => 'Pedido Confirmado',
    'body' => 'Tu pedido #12345 ha sido confirmado',
    'data' => [
        'type' => 'order',
        'order_id' => 12345,
        'action' => 'view'
    ]
]);

// Send to multiple customers
$fcmService->sendToMultipleCustomers([1, 2, 3], [
    'title' => 'Nueva Promoción',
    'body' => '2x1 en todos los subs esta semana'
]);
```

### Generate API Documentation

```bash
# Generate Swagger JSON
php artisan l5-swagger:generate

# View in browser
open http://localhost:8000/api/documentation
```

### Create New Seeder

```bash
# Create seeder
php artisan make:seeder ProductSeeder

# Run specific seeder
php artisan db:seed --class=ProductSeeder

# Refresh database with all seeders
php artisan migrate:fresh --seed
```

### Debug API Request

```bash
# Watch logs in real-time
php artisan pail

# Check recent errors
tail -f storage/logs/laravel.log

# Query database
php artisan tinker
>>> Customer::with('orders')->find(1)
```

---

## Additional Resources

**Documentation**:
- [API Documentation](./API_DOCUMENTATION.md)
- [Architecture Guide](./ARCHITECTURE.md)
- [README](../README.md)

**Laravel**:
- [Laravel 12 Docs](https://laravel.com/docs/12.x)
- [Sanctum Docs](https://laravel.com/docs/12.x/sanctum)
- [Socialite Docs](https://laravel.com/docs/12.x/socialite)

**Testing**:
- [Pest PHP](https://pestphp.com)
- [PHPUnit](https://phpunit.de)

**Tools**:
- [Postman](https://www.postman.com)
- [Insomnia](https://insomnia.rest)
- [Swagger UI](http://localhost:8000/api/documentation)

---

**Happy Coding! 🚀**
