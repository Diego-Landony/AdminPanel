# Guia de API para Flutter - Subway Guatemala Loyalty App

**Base URL:** `https://admin.subwaycardgt.com/api/v1`

**Autenticacion:** Bearer Token (Sanctum)
```dart
headers: {
  'Authorization': 'Bearer $token',
  'Content-Type': 'application/json',
  'Accept': 'application/json',
}
```

---

## Indice

1. [Autenticacion](#1-autenticacion)
2. [Perfil de Usuario](#2-perfil-de-usuario)
3. [Direcciones](#3-direcciones)
4. [NITs (Facturacion)](#4-nits-facturacion)
5. [Dispositivos (FCM)](#5-dispositivos-fcm)
6. [Menu](#6-menu)
7. [Restaurantes](#7-restaurantes)
8. [Carrito](#8-carrito)
9. [Ordenes](#9-ordenes)
10. [Puntos y Recompensas](#10-puntos-y-recompensas)
11. [Favoritos](#11-favoritos)

---

## 1. Autenticacion

### 1.1 Registro

**POST** `/auth/register`

```dart
// Request
{
  "first_name": "Juan",
  "last_name": "Perez",
  "email": "juan@example.com",
  "password": "Pass123",
  "password_confirmation": "Pass123",
  "phone": "+50212345678",
  "birth_date": "1990-05-15",
  "gender": "male",
  "device_identifier": "550e8400-e29b-41d4-a716-446655440000"
}

// Response 201
{
  "message": "Registro exitoso. Por favor verifica tu email.",
  "data": {
    "access_token": "1|abc123xyz...",
    "token_type": "Bearer",
    "customer": { ... }
  }
}
```

```dart
Future<AuthResponse> register(RegisterData data) async {
  final response = await http.post(
    Uri.parse('$baseUrl/auth/register'),
    headers: {'Content-Type': 'application/json'},
    body: jsonEncode(data.toJson()),
  );

  if (response.statusCode == 201) {
    return AuthResponse.fromJson(jsonDecode(response.body));
  }
  throw ApiException.fromResponse(response);
}
```

---

### 1.2 Login

**POST** `/auth/login`

```dart
// Request
{
  "email": "juan@example.com",
  "password": "SecurePass123!",
  "device_identifier": "550e8400-e29b-41d4-a716-446655440000"
}

// Response 200 - Exito
{
  "message": "Inicio de sesion exitoso.",
  "data": {
    "access_token": "2|xyz456abc...",
    "customer": { ... }
  }
}

// Response 409 - Cuenta OAuth (redirigir a Google/Apple)
{
  "code": "oauth_account_required",
  "data": {
    "oauth_provider": "google",
    "email": "juan@example.com"
  }
}

// Response 409 - Cuenta eliminada (ofrecer reactivacion)
{
  "code": "account_deleted_recoverable",
  "data": {
    "days_until_permanent_deletion": 15,
    "points": 150,
    "can_reactivate": true
  }
}
```

```dart
Future<AuthResponse> login(String email, String password, String deviceId) async {
  final response = await http.post(
    Uri.parse('$baseUrl/auth/login'),
    headers: {'Content-Type': 'application/json'},
    body: jsonEncode({
      'email': email,
      'password': password,
      'device_identifier': deviceId,
    }),
  );

  final data = jsonDecode(response.body);

  if (response.statusCode == 200) {
    return AuthResponse.fromJson(data);
  }

  if (response.statusCode == 409) {
    if (data['code'] == 'oauth_account_required') {
      throw OAuthRequiredException(data['data']['oauth_provider']);
    }
    if (data['code'] == 'account_deleted_recoverable') {
      throw AccountDeletedException(data['data']);
    }
  }

  throw ApiException.fromResponse(response);
}
```

---

### 1.3 OAuth (Google/Apple)

**Flujo Mobile con Deep Link:**

```dart
// Paso 1: Abrir navegador
final url = '$baseUrl/auth/oauth/google/redirect?action=login&platform=mobile&device_id=$deviceId';
await launchUrl(Uri.parse(url));

// Paso 2: Escuchar deep link
// subwayapp://oauth/callback?token=5|oauth123...&customer_id=42&is_new_customer=1
```

**Configuracion Android (AndroidManifest.xml):**
```xml
<intent-filter>
  <action android:name="android.intent.action.VIEW" />
  <category android:name="android.intent.category.DEFAULT" />
  <category android:name="android.intent.category.BROWSABLE" />
  <data android:scheme="subwayapp" android:host="oauth" />
</intent-filter>
```

---

### 1.4 Logout

**POST** `/auth/logout` - Cerrar sesion actual

**POST** `/auth/logout-all` - Cerrar todas las sesiones

---

### 1.5 Renovar Token

**POST** `/auth/refresh`

```dart
// Response 200
{
  "data": {
    "access_token": "3|newtoken123...",
    "customer": { ... }
  }
}
```

---

### 1.6 Recuperar Contrasena

**POST** `/auth/forgot-password`
```dart
{ "email": "juan@example.com" }
```

**POST** `/auth/reset-password`
```dart
{
  "email": "juan@example.com",
  "password": "NuevaPass123!",
  "password_confirmation": "NuevaPass123!",
  "token": "abc123resettoken456"
}
```

---

### 1.7 Reactivar Cuenta Eliminada

**POST** `/auth/reactivate`

```dart
// Request (cuenta local)
{
  "email": "juan@example.com",
  "password": "SecurePass123!",
  "device_identifier": "..."
}

// Request (cuenta OAuth - sin password)
{
  "email": "juan@example.com",
  "device_identifier": "..."
}
```

---

## 2. Perfil de Usuario

### 2.1 Obtener Perfil

**GET** `/profile`

```dart
// Response 200
{
  "data": {
    "customer": {
      "id": 1,
      "first_name": "Juan",
      "last_name": "Perez",
      "email": "juan@example.com",
      "phone": "+50212345678",
      "points": 500,
      "oauth_provider": "local",
      "customer_type": { "name": "Bronce" },
      "addresses": [...],
      "nits": [...]
    }
  }
}
```

---

### 2.2 Actualizar Perfil

**PUT** `/profile`

```dart
{
  "first_name": "Juan",
  "last_name": "Perez",
  "phone": "+50212345678",
  "email_offers_enabled": true
}
```

---

### 2.3 Eliminar Cuenta

**DELETE** `/profile`

```dart
// Cuenta local - requiere password
{ "password": "SecurePass123!" }

// Cuenta OAuth - body vacio
{}

// Response 200
{
  "message": "Cuenta eliminada exitosamente.",
  "data": {
    "can_reactivate_until": "2025-12-27T10:30:00Z",
    "days_to_reactivate": 30
  }
}
```

---

### 2.4 Avatar

**POST** `/profile/avatar`
```dart
{ "avatar": "https://example.com/avatar.jpg" }
```

**DELETE** `/profile/avatar`

---

### 2.5 Cambiar/Crear Contrasena

**PUT** `/profile/password`

```dart
// Cuenta local (cambiar)
{
  "current_password": "OldPass123!",
  "password": "NuevaPass123!",
  "password_confirmation": "NuevaPass123!"
}

// Cuenta OAuth (crear primera contrasena)
{
  "password": "NuevaPass123!",
  "password_confirmation": "NuevaPass123!"
}
```

---

## 3. Direcciones

### 3.1 Listar

**GET** `/addresses`

```dart
// Response 200
{
  "data": [
    {
      "id": 1,
      "label": "Casa",
      "address_line": "10 Calle 5-20 Zona 10",
      "latitude": 14.6017,
      "longitude": -90.5250,
      "zone": "capital",
      "is_default": true
    }
  ]
}
```

---

### 3.2 Crear

**POST** `/addresses`

```dart
{
  "label": "Casa",
  "address_line": "10 Calle 5-20 Zona 10",
  "latitude": 14.6017,
  "longitude": -90.5250,
  "delivery_notes": "Casa amarilla",
  "is_default": false
}
```

---

### 3.3 Actualizar

**PUT** `/addresses/{id}`

---

### 3.4 Eliminar

**DELETE** `/addresses/{id}`

---

### 3.5 Set Default

**POST** `/addresses/{id}/set-default`

---

### 3.6 Validar Ubicacion (Geofence)

**POST** `/addresses/validate`

```dart
// Request
{
  "latitude": 14.6017,
  "longitude": -90.5250
}

// Response 200 - Valida
{
  "data": {
    "is_valid": true,
    "delivery_available": true,
    "restaurant": {
      "id": 5,
      "name": "Subway Pradera Zona 10"
    },
    "zone": "capital"
  }
}

// Response 200 - Fuera de zona
{
  "data": {
    "is_valid": false,
    "delivery_available": false,
    "nearest_pickup_locations": [
      { "id": 3, "name": "Subway Oakland", "distance_km": 2.5 }
    ]
  }
}
```

---

## 4. NITs (Facturacion)

### 4.1 CRUD

**GET** `/nits` - Listar

**POST** `/nits` - Crear
```dart
{
  "nit": "123456789",
  "nit_type": "personal",
  "business_name": "Empresa XYZ S.A.",
  "is_default": false
}
```

**PUT** `/nits/{id}` - Actualizar

**DELETE** `/nits/{id}` - Eliminar

**POST** `/nits/{id}/set-default` - Set default

---

## 5. Dispositivos (FCM)

### 5.1 Registrar Dispositivo para Push

**POST** `/devices/register`

```dart
{
  "fcm_token": "fKw8h4Xj...",
  "device_identifier": "550e8400-e29b-41d4-a716-446655440000",
  "device_name": "iPhone 14 Pro de Juan"
}
```

---

### 5.2 Listar/Desactivar

**GET** `/devices`

**DELETE** `/devices/{id}`

---

## 6. Menu

> **⚠️ IMPORTANTE - Disclaimer de Precios**
>
> El campo `precio` muestra el precio de referencia (pickup_capital).
> **Los precios en el interior de la republica pueden variar.**
>
> **Recomendacion UI:**
> ```dart
> // Mostrar disclaimer en el menu
> Text('Q${product.precio}', style: priceStyle);
> Text('*Precios de referencia. Pueden variar segun ubicacion.',
>      style: TextStyle(fontSize: 10, color: Colors.grey));
> ```

### 6.1 Menu Completo

**GET** `/menu`

```dart
// Response 200 (~112KB)
{
  "data": {
    "categories": [
      {
        "id": 1,
        "name": "Subs 15cm",
        "products": [
          {
            "id": 1,
            "name": "Italian BMT",
            "precio": 45.00,  // <-- Precio referencia (pickup_capital)
            "prices": {
              "pickup_capital": 45.00,
              "domicilio_capital": 50.00,
              "pickup_interior": 48.00,
              "domicilio_interior": 53.00
            },
            "badges": [
              {
                "id": 1,
                "badge_type": {
                  "name": "nuevo",
                  "color": "#f97316"
                },
                "validity_type": "permanent",
                "is_valid_now": true
              }
            ],
            "variants": [...],
            "sections": [...]
          }
        ]
      }
    ],
    "combos": [...]
  }
}
```

### 6.1.1 Sistema de Badges

Los productos y combos pueden tener **badges** (etiquetas visuales):

| Badge | Color | Uso |
|-------|-------|-----|
| nuevo | #f97316 (naranja) | Producto recien agregado |

```dart
// Widget para mostrar badges
Widget buildBadges(List<Badge> badges) {
  return Wrap(
    spacing: 4,
    children: badges.map((badge) => Container(
      padding: EdgeInsets.symmetric(horizontal: 6, vertical: 2),
      decoration: BoxDecoration(
        color: Color(int.parse(badge.badgeType.color.replaceFirst('#', '0xFF'))),
        borderRadius: BorderRadius.circular(4),
      ),
      child: Text(
        badge.badgeType.name.toUpperCase(),
        style: TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.bold),
      ),
    )).toList(),
  );
}

// Uso en ProductCard
Column(
  crossAxisAlignment: CrossAxisAlignment.start,
  children: [
    if (product.badges.isNotEmpty) buildBadges(product.badges),
    Text(product.name),
    Text('Q${product.precio}'),
  ],
)
```

**Tipos de validez:**
- `permanent` - Siempre visible
- `date_range` - Visible entre fechas especificas
- `weekdays` - Visible solo ciertos dias de la semana

> Solo se retornan badges **activos y validos** en el momento de la consulta.

---

### 6.2 Menu Lite (RECOMENDADO para carga inicial)

**GET** `/menu?lite=1`

```dart
// Response 200 (~2KB - 50x mas rapido)
{
  "data": {
    "categories": [
      {
        "id": 1,
        "name": "Sandwiches",
        "image_url": "...",
        "products_count": 15
      }
    ],
    "combos_summary": {
      "count": 8,
      "price_range": { "min": 45.00, "max": 95.00 }
    }
  }
}
```

**Flujo Recomendado:**
```
1. GET /menu?lite=1           → Carga rapida de navegacion
2. Usuario toca categoria
3. GET /menu/categories/{id}  → Productos de esa categoria
4. POST /cart/items           → Agregar al carrito
```

---

### 6.3 Categoria con Productos

**GET** `/menu/categories/{id}`

---

### 6.4 Producto Detalle

**GET** `/menu/products/{id}`

```dart
{
  "data": {
    "product": {
      "id": 1,
      "name": "Italian BMT",
      "precio": 45.00,
      "prices": {
        "pickup_capital": 45.00,
        "domicilio_capital": 50.00,
        "pickup_interior": 48.00,
        "domicilio_interior": 53.00
      },
      "badges": [
        {
          "id": 1,
          "badge_type": { "name": "nuevo", "color": "#f97316" },
          "validity_type": "permanent",
          "is_valid_now": true
        }
      ],
      "variants": [
        { "id": 1, "name": "15cm", "precio": 35.00 },
        { "id": 2, "name": "30cm", "precio": 55.00 }
      ],
      "sections": [
        {
          "id": 1,
          "name": "Pan",
          "min_selections": 1,
          "max_selections": 1,
          "options": [
            { "id": 1, "name": "Italiano" },
            { "id": 2, "name": "Honey Oat" }
          ]
        }
      ]
    }
  }
}
```

---

### 6.5 Combos

**GET** `/menu/combos`

**GET** `/menu/combos/{id}`

---

### 6.6 Promociones

**GET** `/menu/promotions` - Todas las promociones activas

**GET** `/menu/promotions/daily` - Sub del Dia

**GET** `/menu/promotions/daily?today=1` - Solo subs de hoy

**GET** `/menu/promotions/combinados` - Bundle Specials

---

## 7. Restaurantes

### 7.1 Listar

**GET** `/restaurants`

Query params opcionales:
- `delivery_active=true`
- `pickup_active=true`

---

### 7.2 Detalle

**GET** `/restaurants/{id}`

```dart
{
  "data": {
    "restaurant": {
      "id": 1,
      "name": "Subway Pradera",
      "address": "Pradera Concepcion, Zona 14",
      "latitude": 14.6349,
      "longitude": -90.5069,
      "is_open_now": true,
      "today_schedule": { ... }
    }
  }
}
```

---

### 7.3 Restaurantes Cercanos

**GET** `/restaurants/nearby?lat=14.6349&lng=-90.5069`

Query params:
- `lat` (requerido)
- `lng` (requerido)
- `radius_km` (default: 10, max: 50)

```dart
{
  "data": {
    "restaurants": [
      { "id": 1, "name": "Subway Pradera", "distance_km": 2.45 }
    ],
    "total_found": 3
  }
}
```

---

### 7.4 Resenas de Restaurante

**GET** `/restaurants/{id}/reviews`

---

## 8. Carrito

### 8.1 Obtener Carrito

**GET** `/cart`

```dart
{
  "data": {
    "id": 1,
    "restaurant": { "id": 1, "name": "Subway Pradera" },
    "service_type": "pickup",
    "zone": "capital",
    "items": [
      {
        "id": 1,
        "product": { "id": 1, "name": "Italian BMT" },
        "variant": { "id": 1, "name": "15cm" },
        "quantity": 2,
        "unit_price": "45.00",  // <-- Precio real calculado
        "total_price": "90.00"
      }
    ],
    "summary": {
      "subtotal": "90.00",
      "promotions_applied": [
        {
          "promotion_id": 1,
          "promotion_name": "2x1 en Subs",
          "discount_amount": 45.00
        }
      ],
      "total_discount": "45.00",
      "total": "45.00"
    },
    "can_checkout": true,
    "validation_messages": []
  }
}
```

---

### 8.2 Agregar Item

**POST** `/cart/items`

```dart
// Producto
{
  "product_id": 1,
  "variant_id": 5,
  "quantity": 2,
  "selected_options": [
    { "section_id": 1, "option_id": 3 }
  ],
  "notes": "Sin cebolla"
}

// Combo
{
  "combo_id": 1,
  "quantity": 1,
  "combo_selections": [
    { "combo_item_id": 1, "product_id": 5, "variant_id": 2 }
  ]
}
```

---

### 8.3 Actualizar Item

**PUT** `/cart/items/{id}`

```dart
{
  "quantity": 3,
  "notes": "Extra queso"
}
```

---

### 8.4 Eliminar Item

**DELETE** `/cart/items/{id}`

---

### 8.5 Vaciar Carrito

**DELETE** `/cart`

---

### 8.6 Cambiar Restaurante

**PUT** `/cart/restaurant`

```dart
{ "restaurant_id": 2 }
```

---

### 8.7 Cambiar Tipo de Servicio

**PUT** `/cart/service-type`

```dart
{
  "service_type": "delivery",
  "zone": "capital"
}
```

> Los precios se recalculan automaticamente

---

### 8.8 Asignar Direccion de Entrega

**PUT** `/cart/delivery-address`

```dart
// Request
{ "delivery_address_id": 1 }

// Response 200 - Exito
{
  "data": {
    "delivery_address": { ... },
    "assigned_restaurant": { "id": 1, "name": "Subway Pradera" },
    "zone": "capital",
    "prices_updated": true
  }
}

// Response 422 - Fuera de zona
{
  "error_code": "ADDRESS_OUTSIDE_DELIVERY_ZONE",
  "data": {
    "nearest_pickup_locations": [
      { "id": 2, "name": "Subway Miraflores", "distance_km": 2.5 }
    ]
  }
}
```

---

### 8.9 Validar Carrito

**POST** `/cart/validate`

```dart
{
  "data": {
    "is_valid": true,
    "errors": []
  }
}
```

---

## 9. Ordenes

### 9.1 Crear Orden

**POST** `/orders`

```dart
// Pickup
{
  "restaurant_id": 1,
  "service_type": "pickup",
  "scheduled_pickup_time": "2025-12-15T15:30:00Z",
  "payment_method": "cash",
  "nit_id": 1,
  "notes": "Sin cebolla"
}

// Delivery
{
  "service_type": "delivery",
  "delivery_address_id": 1,
  "payment_method": "card"
}

// Response 201
{
  "data": {
    "id": 1,
    "order_number": "ORD-20251215-0001",
    "status": "pending",
    "summary": {
      "subtotal": 125.00,
      "total": 140.00
    }
  }
}
```

**payment_method:** `cash`, `card`, `online`

> **NOTA:** El pago se procesa en POS, no en la app.

---

### 9.2 Historial de Ordenes

**GET** `/orders`

Query params:
- `per_page` (default: 15)
- `status` (filtro opcional)

---

### 9.3 Ordenes Activas

**GET** `/orders/active`

---

### 9.4 Detalle de Orden

**GET** `/orders/{id}`

---

### 9.5 Tracking de Orden

**GET** `/orders/{id}/track`

```dart
{
  "data": {
    "order_number": "ORD-20251215-0001",
    "current_status": "preparing",
    "estimated_ready_at": "2025-12-15T15:30:00Z",
    "status_history": [
      {
        "status": "preparing",
        "previous_status": "confirmed",
        "timestamp": "2025-12-15T15:10:00Z"
      }
    ]
  }
}
```

**Flujo de Estados:**
- **Pickup:** `pending` → `confirmed` → `preparing` → `ready` → `completed`
- **Delivery:** `pending` → `confirmed` → `preparing` → `ready` → `out_for_delivery` → `delivered` → `completed`

---

### 9.6 Cancelar Orden

**POST** `/orders/{id}/cancel`

```dart
{ "reason": "Cambie de opinion" }
```

> Solo desde estados `pending` o `confirmed`

---

### 9.7 Reordenar

**POST** `/orders/{id}/reorder`

```dart
// Response 200
{
  "data": {
    "cart_id": 5,
    "items_count": 3
  }
}
```

---

### 9.8 Calificar Orden

**POST** `/orders/{id}/review`

```dart
{
  "overall_rating": 5,
  "quality_rating": 4,
  "speed_rating": 5,
  "service_rating": 4,
  "comment": "Muy buena comida!"
}
```

> Solo ordenes `completed` o `delivered`

---

### 9.9 Ordenes Recientes

**GET** `/me/recent-orders`

Retorna ultimas 5 ordenes completadas con `can_reorder`.

---

## 10. Puntos y Recompensas

### Sistema de Puntos
- **Acumulacion:** 1 punto por cada Q10 gastados
- **Redencion:** 1 punto = Q0.10 de descuento
- **Expiracion:** 6 meses de inactividad

---

### 10.1 Balance de Puntos

**GET** `/points/balance`

```dart
{
  "data": {
    "points_balance": 250,
    "points_value_in_currency": 25.00,
    "conversion_rate": {
      "points_per_quetzal_spent": 10,
      "points_value": 0.10
    }
  }
}
```

---

### 10.2 Historial de Puntos

**GET** `/points/history`

```dart
{
  "data": [
    {
      "id": 123,
      "points": 50,
      "type": "earned",
      "description": "Puntos ganados en orden #ORD-2025-000123"
    }
  ]
}
```

**Tipos:** `earned`, `redeemed`, `expired`, `bonus`, `adjustment`

---

### 10.3 Catalogo de Recompensas

**GET** `/rewards`

```dart
{
  "data": {
    "products": [...],
    "variants": [
      {
        "id": 1,
        "product_name": "Sub del Dia",
        "variant_name": "15cm",
        "points_cost": 450
      }
    ],
    "combos": [...],
    "total_count": 8
  }
}
```

---

## 11. Favoritos

### 11.1 Listar

**GET** `/favorites`

```dart
{
  "data": [
    {
      "id": 1,
      "favorable_type": "product",
      "favorable_id": 42,
      "favorable": {
        "id": 42,
        "name": "Italian BMT",
        "precio": 45.00
      }
    }
  ]
}
```

---

### 11.2 Agregar

**POST** `/favorites`

```dart
{
  "favorable_type": "product",  // o "combo"
  "favorable_id": 42
}
```

---

### 11.3 Eliminar

**DELETE** `/favorites/{type}/{id}`

Ejemplo: `DELETE /favorites/product/42`

---

## Manejo de Errores

### Codigos HTTP

| Codigo | Significado | Accion |
|--------|-------------|--------|
| 401 | No autenticado | Redirigir a login |
| 403 | Sin permisos | Mostrar error |
| 409 | Conflicto (OAuth/cuenta eliminada) | Ver campo `code` |
| 422 | Error de validacion | Mostrar errores |
| 429 | Rate limit | Esperar y reintentar |

### Clase de Errores Flutter

```dart
class ApiException implements Exception {
  final int statusCode;
  final String message;
  final Map<String, dynamic>? errors;

  ApiException({required this.statusCode, required this.message, this.errors});

  factory ApiException.fromResponse(http.Response response) {
    final data = jsonDecode(response.body);
    return ApiException(
      statusCode: response.statusCode,
      message: data['message'] ?? 'Error desconocido',
      errors: data['errors'],
    );
  }
}
```

---

## Flujos Principales

### Flujo de Registro/Login

```
1. POST /auth/register  o  POST /auth/login
2. Guardar token en secure storage
3. POST /devices/register (FCM token)
4. GET /profile
```

### Flujo de Pedido (Pickup)

```
1. GET /menu?lite=1                    → Navegacion
2. GET /menu/categories/{id}           → Productos
3. POST /cart/items                    → Agregar al carrito
4. PUT /cart/restaurant                → Seleccionar restaurante
5. POST /cart/validate                 → Validar
6. POST /orders                        → Crear orden
7. GET /orders/{id}/track              → Seguimiento (polling)
```

### Flujo de Pedido (Delivery)

```
1. GET /menu?lite=1
2. POST /cart/items
3. PUT /cart/delivery-address          → Valida geofence, asigna restaurante
4. (Si error) Mostrar pickup locations cercanos
5. POST /cart/validate
6. POST /orders
7. GET /orders/{id}/track
```

---

## Historial de Cambios

| Fecha | Cambio |
|-------|--------|
| 2025-12-22 | Documentacion completa para Flutter |
| 2025-12-22 | Agregado endpoint `/menu?lite=1` |
| 2025-12-22 | Integradas promociones automaticas al carrito |
| 2025-12-22 | Campo `precio` en productos/variantes/combos |
| 2025-12-22 | Eliminado delivery_fee (siempre 0) |
| 2025-12-22 | Eliminado metodo applyPromoCode (no implementado) |
