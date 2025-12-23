# Guia de API para Flutter - Subway Guatemala Loyalty App

**Base URL:** `https://admin.subwaycardgt.com/api/v1`
**Documentacion completa en:** https://admin.subwaycardgt.com/docs

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

## Flujo General de la App

```
┌─────────────────────────────────────────────────────────────────────┐
│                    EXPLORAR MENU (Sin ubicacion)                    │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  ✅ Usuario puede ver menu SIN iniciar sesion                       │
│  ✅ Usuario puede ver menu SIN seleccionar ubicacion                │
│  ✅ Precios mostrados: PICKUP CAPITAL (precio de referencia)        │
│                                                                     │
│  ⚠️ OBLIGATORIO mostrar disclaimer del API:                         │
│     "*El precio puede variar segun area y tipo de servicio."        │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘
                                  ↓
                    Usuario decide agregar al carrito
                                  ↓
┌─────────────────────────────────────────────────────────────────────┐
│                    AGREGAR AL CARRITO                               │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  ✅ Requiere iniciar sesion (login/registro)                        │
│  ✅ Se agrega con precio temporal (pickup capital)                  │
│  ❌ NO requiere seleccionar ubicacion todavia                       │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘
                                  ↓
                    Usuario va al checkout
                                  ↓
┌─────────────────────────────────────────────────────────────────────┐
│                         CHECKOUT                                    │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  Usuario elige tipo de servicio:                                    │
│                                                                     │
│  ┌─────────────────────┐    ┌─────────────────────┐                 │
│  │      PICKUP         │    │      DELIVERY       │                 │
│  ├─────────────────────┤    ├─────────────────────┤                 │
│  │                     │    │                     │                 │
│  │ Seleccionar         │    │ Seleccionar         │                 │
│  │ restaurante         │    │ direccion guardada  │                 │
│  │ de la lista         │    │ (o crear nueva)     │                 │
│  │                     │    │                     │                 │
│  │ PUT /cart/restaurant│    │ PUT /cart/          │                 │
│  │                     │    │   delivery-address  │                 │
│  │        ↓            │    │        ↓            │                 │
│  │ zone = restaurant.  │    │ zone = address.zone │                 │
│  │   price_location    │    │                     │                 │
│  │                     │    │        ↓            │                 │
│  │        ↓            │    │ Valida geofence     │                 │
│  │ Precios             │    │ Asigna restaurante  │                 │
│  │ recalculados        │    │ Precios recalculados│                 │
│  └─────────────────────┘    └─────────────────────┘                 │
│                                                                     │
│  ✅ Precios en carrito ahora son EXACTOS (sin disclaimer)           │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘
                                  ↓
┌─────────────────────────────────────────────────────────────────────┐
│                    CREAR ORDEN                                      │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  ⚠️ REQUIERE EMAIL VERIFICADO                                       │
│                                                                     │
│  Si email NO verificado → Error 403 EMAIL_NOT_VERIFIED              │
│  Flutter debe mostrar pantalla para verificar email                 │
│                                                                     │
│  POST /orders → Crear orden                                         │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘
```

### Widget de Disclaimer (OBLIGATORIO en pantallas de menu)

El API devuelve `price_disclaimer` en la respuesta. Usarlo asi:

```dart
// El disclaimer viene del API en data.price_disclaimer
// Ejemplo: "El precio puede variar segun area y tipo de servicio."

Widget buildPriceDisclaimer(String disclaimer) {
  return Container(
    padding: EdgeInsets.symmetric(horizontal: 12, vertical: 8),
    margin: EdgeInsets.only(bottom: 16),
    decoration: BoxDecoration(
      color: Colors.amber[50],
      borderRadius: BorderRadius.circular(8),
      border: Border.all(color: Colors.amber[200]!),
    ),
    child: Row(
      children: [
        Icon(Icons.info_outline, size: 18, color: Colors.amber[800]),
        SizedBox(width: 8),
        Expanded(
          child: Text(
            disclaimer,  // <-- Usar el valor del API
            style: TextStyle(fontSize: 12, color: Colors.amber[900]),
          ),
        ),
      ],
    ),
  );
}

// Uso:
final menuResponse = await getMenu();
final disclaimer = menuResponse['data']['price_disclaimer'];
buildPriceDisclaimer(disclaimer);
```

---

## 1. Autenticacion

### 1.1 Registro

**POST** `/auth/register`

> **⚠️ TERMINOS Y CONDICIONES (OBLIGATORIO)**
>
> El campo `terms_accepted` es **requerido**.
> Flutter debe mostrar un checkbox que el usuario debe marcar antes de registrarse.
>
> ```dart
> // Widget de checkbox (ejemplo)
> CheckboxListTile(
>   title: RichText(
>     text: TextSpan(
>       text: 'Acepto los ',
>       style: TextStyle(color: Colors.black),
>       children: [
>         TextSpan(
>           text: 'Terminos y Condiciones',
>           style: TextStyle(color: Colors.blue, decoration: TextDecoration.underline),
>           recognizer: TapGestureRecognizer()..onTap = () => launchUrl(termsUrl),
>         ),
>       ],
>     ),
>   ),
>   value: termsAccepted,
>   onChanged: (value) => setState(() => termsAccepted = value ?? false),
> )
> ```
>
> Si `terms_accepted` es `false` o no se envia, el API retorna error 422:
> ```json
> { "errors": { "terms_accepted": ["Debes aceptar los terminos y condiciones."] } }
> ```

```dart
// Request
{
  "first_name": "Juan",
  "last_name": "Perez",
  "email": "juan@example.com",
  "password": "Pass123",
  "password_confirmation": "Pass123",
  "phone": "12345678",
  "birth_date": "1990-05-15",
  "gender": "male",
  "device_identifier": "550e8400-e29b-41d4-a716-446655440000",
  "terms_accepted": true  // REQUERIDO - Aceptacion de terminos y condiciones
}

// Response 201
{
  "message": "Registro exitoso. Por favor verifica tu email.",
  "data": {
    "token": "1|abc123xyz...",
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
    "token": "2|xyz456abc...",
    "customer": { ... }
  }
}

// Response 409 - Cuenta OAuth (redirigir a Google/Apple)
{
  "error_code": "oauth_account_required",
  "data": {
    "oauth_provider": "google",
    "email": "juan@example.com"
  }
}

// Response 409 - Cuenta eliminada (ofrecer reactivacion)
{
  "error_code": "account_deleted_recoverable",
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
    if (data['error_code'] == 'oauth_account_required') {
      throw OAuthRequiredException(data['data']['oauth_provider']);
    }
    if (data['error_code'] == 'account_deleted_recoverable') {
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
    "token": "3|newtoken123...",
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
// No requiere body - solo llamar al endpoint autenticado
// Response 200
{
  "message": "Cuenta eliminada exitosamente.",
  "data": {
    "can_reactivate_until": "2025-12-27T10:30:00Z",
    "days_to_reactivate": 30
  }
}
```

> **Nota:** La cuenta se puede recuperar dentro de 30 dias usando `POST /auth/reactivate`

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
> El API devuelve `price_disclaimer` en la respuesta del menu.
> **Flutter DEBE mostrar este disclaimer** junto a los precios.
>
> El campo `price` muestra el **precio de PICKUP en CAPITAL** (precio base de referencia).
> Este NO es el precio de delivery ni el precio en interior.
>
> **El precio final se calcula automaticamente cuando el usuario:**
> - Selecciona un restaurante para pickup → `PUT /cart/restaurant`
> - Selecciona una direccion para delivery → `PUT /cart/delivery-address`
>
> **Uso del disclaimer del API:**
> ```dart
> // El API devuelve: data.price_disclaimer
> // Valor: "El precio puede variar segun area y tipo de servicio."
>
> Widget buildMenuWithDisclaimer(MenuResponse menu) {
>   return Column(
>     children: [
>       // Banner de disclaimer (usar valor del API)
>       Container(
>         padding: EdgeInsets.all(8),
>         color: Colors.amber[50],
>         child: Row(
>           children: [
>             Icon(Icons.info_outline, size: 16, color: Colors.amber[800]),
>             SizedBox(width: 8),
>             Expanded(
>               child: Text(
>                 menu.priceDisclaimer,  // <-- Usar valor del API
>                 style: TextStyle(fontSize: 11, color: Colors.amber[900]),
>               ),
>             ),
>           ],
>         ),
>       ),
>       // Lista de productos...
>     ],
>   );
> }
> ```

### 6.1 Menu Completo

**GET** `/menu`

```dart
// Response 200 (~112KB)
{
  "data": {
    "price_disclaimer": "El precio puede variar segun area y tipo de servicio.",
    "categories": [
      {
        "id": 1,
        "name": "Subs 15cm",
        "products": [
          {
            "id": 1,
            "name": "Italian BMT",
            "price": 45.00,  // <-- Precio referencia (pickup_capital)
            "prices": {
              "pickup_capital": 45.00,
              "delivery_capital": 50.00,
              "pickup_interior": 48.00,
              "delivery_interior": 53.00
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
    Text('Q${product.price}'),
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
      "price": 45.00,
      "prices": {
        "pickup_capital": 45.00,
        "delivery_capital": 50.00,
        "pickup_interior": 48.00,
        "delivery_interior": 53.00
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
        { "id": 1, "name": "15cm", "price": 35.00 },
        { "id": 2, "name": "30cm", "price": 55.00 }
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
        "unit_price": 70.00,
        "subtotal": 140.00,
        // Campos para mostrar precio tachado
        "discount_amount": 70.00,       // Descuento aplicado a este item
        "final_price": 70.00,           // Precio despues del descuento
        "is_daily_special": false,      // Si aplica Sub del Dia
        "applied_promotion": {          // Promocion aplicada (null si ninguna)
          "id": 1,
          "name": "2x1 en Subs",
          "type": "two_for_one",
          "value": "2x1"
        }
      },
      {
        "id": 2,
        "product": { "id": 1, "name": "Italian BMT" },
        "variant": { "id": 1, "name": "15cm" },
        "quantity": 1,
        "unit_price": 70.00,
        "subtotal": 70.00,
        "discount_amount": 0.00,        // Sin descuento
        "final_price": 70.00,
        "is_daily_special": false,
        "applied_promotion": null
      }
    ],
    "summary": {
      "subtotal": "210.00",
      "promotions_applied": [
        {
          "promotion_id": 1,
          "promotion_name": "2x1 en Subs",
          "promotion_type": "two_for_one",
          "discount_amount": 70.00
        }
      ],
      "total_discount": "70.00",
      "total": "140.00"
    },
    "can_checkout": true,
    "validation_messages": []
  }
}
```

**Uso en Flutter para mostrar precio tachado:**

```dart
Widget buildCartItem(CartItem item) {
  final hasDiscount = item.discountAmount > 0;

  return Row(
    children: [
      if (hasDiscount) ...[
        // Precio original tachado
        Text(
          'Q${item.subtotal.toStringAsFixed(2)}',
          style: TextStyle(
            decoration: TextDecoration.lineThrough,
            color: Colors.grey,
          ),
        ),
        SizedBox(width: 8),
      ],
      // Precio final
      Text(
        'Q${item.finalPrice.toStringAsFixed(2)}',
        style: TextStyle(
          color: hasDiscount ? Colors.green : Colors.black,
          fontWeight: FontWeight.bold,
        ),
      ),
      if (item.appliedPromotion != null)
        Badge(label: item.appliedPromotion.value),
      if (item.isDailySpecial)
        Badge(label: 'SUB DEL DIA', color: Colors.orange),
    ],
  );
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

> **⚠️ REQUIERE EMAIL VERIFICADO**
>
> Este endpoint requiere que el usuario tenga su email verificado.
> Si el email no esta verificado, retorna error 403.
>
> ```dart
> // Error 403 - Email no verificado
> {
>   "message": "Debes verificar tu correo electronico para realizar esta accion.",
>   "error_code": "EMAIL_NOT_VERIFIED",
>   "data": {
>     "email": "usuario@email.com",
>     "resend_verification_url": "/api/v1/auth/email/resend"
>   }
> }
> ```
>
> **Flutter debe:**
> 1. Detectar `error_code == 'EMAIL_NOT_VERIFIED'`
> 2. Mostrar modal/pantalla pidiendo verificar email
> 3. Ofrecer boton para reenviar email de verificacion (`POST /auth/email/resend`)

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
        "price": 45.00
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
| 409 | Conflicto (OAuth/cuenta eliminada) | Ver campo `error_code` |
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

---

## Sistema de Precios y Zonas

### Como Funciona

El sistema maneja 4 tipos de precios:

| Tipo de Servicio | Zona | Campo |
|------------------|------|-------|
| Pickup | Capital | `precio_pickup_capital` |
| Pickup | Interior | `precio_pickup_interior` |
| Delivery | Capital | `precio_domicilio_capital` |
| Delivery | Interior | `precio_domicilio_interior` |

### Determinacion Automatica de Zona

**Para Pickup:**
- La zona se determina por `restaurant.price_location`
- Al llamar `PUT /cart/restaurant`, el sistema automaticamente:
  1. Establece `service_type = 'pickup'`
  2. Establece `zone = restaurant.price_location`
  3. Recalcula precios de todos los items

**Para Delivery:**
- La zona se determina por la direccion del usuario (`address.zone`)
- La direccion ya tiene zona guardada (se calcula al crearla via geofencing)
- Al llamar `PUT /cart/delivery-address`, el sistema automaticamente:
  1. Valida cobertura via geofence
  2. Asigna restaurante cercano
  3. Establece `service_type = 'delivery'`
  4. Establece `zone = address.zone`
  5. Recalcula precios de todos los items

---

### Flujo de Pedido (Pickup)

```
1. GET /menu?lite=1                    → Carga rapida de categorias
2. GET /menu/categories/{id}           → Productos de una categoria
3. POST /cart/items                    → Agregar al carrito (precio temporal)
4. GET /restaurants?pickup_active=true → Lista de restaurantes
5. PUT /cart/restaurant                → Seleccionar restaurante
   ↳ Automaticamente:
     - service_type = 'pickup'
     - zone = restaurant.price_location
     - Precios recalculados
6. GET /cart                           → Ver carrito con precios correctos
7. POST /cart/validate                 → Validar disponibilidad
8. POST /orders                        → Crear orden
9. GET /orders/{id}/track              → Seguimiento (polling)
```

```dart
// Ejemplo Flutter: Seleccionar restaurante
Future<void> selectRestaurantForPickup(int restaurantId) async {
  final response = await http.put(
    Uri.parse('$baseUrl/cart/restaurant'),
    headers: authHeaders,
    body: jsonEncode({'restaurant_id': restaurantId}),
  );

  final data = jsonDecode(response.body)['data'];
  // data.zone = 'capital' o 'interior' (automatico)
  // data.service_type = 'pickup' (automatico)
  // data.prices_updated = true

  // Refrescar carrito para ver precios actualizados
  await refreshCart();
}
```

---

### Flujo de Pedido (Delivery)

```
1. GET /menu?lite=1
2. POST /cart/items                    → Agregar al carrito (precio temporal)
3. GET /addresses                      → Direcciones guardadas del usuario
   ↳ Cada direccion ya tiene zone = 'capital' o 'interior'
4. PUT /cart/delivery-address          → Seleccionar direccion
   ↳ Automaticamente:
     - Valida geofence
     - Asigna restaurante cercano
     - service_type = 'delivery'
     - zone = address.zone
     - Precios recalculados
5. (Si error 422) Mostrar pickup locations cercanos
6. GET /cart                           → Ver carrito con precios correctos
7. POST /cart/validate
8. POST /orders
9. GET /orders/{id}/track
```

```dart
// Ejemplo Flutter: Seleccionar direccion para delivery
Future<void> selectAddressForDelivery(int addressId) async {
  final response = await http.put(
    Uri.parse('$baseUrl/cart/delivery-address'),
    headers: authHeaders,
    body: jsonEncode({'delivery_address_id': addressId}),
  );

  if (response.statusCode == 422) {
    // Fuera de zona de cobertura
    final data = jsonDecode(response.body);
    if (data['error_code'] == 'ADDRESS_OUTSIDE_DELIVERY_ZONE') {
      // Mostrar restaurantes cercanos para pickup
      final nearbyPickups = data['data']['nearest_pickup_locations'];
      showPickupSuggestions(nearbyPickups);
      return;
    }
  }

  final data = jsonDecode(response.body)['data'];
  // data.zone = 'capital' o 'interior' (de la direccion)
  // data.assigned_restaurant = restaurante asignado
  // data.prices_updated = true

  await refreshCart();
}
```

---

### Mostrar Precios en Menu (Antes de Seleccionar Ubicacion)

Antes de que el usuario seleccione restaurante/direccion, mostrar precio de referencia con disclaimer del API:

```dart
// Widget para precio en catalogo/menu
// Usar el disclaimer que viene del API: data.price_disclaimer
Widget buildMenuPrice(Product product, String disclaimer) {
  return Column(
    crossAxisAlignment: CrossAxisAlignment.start,
    children: [
      Text('Q${product.price.toStringAsFixed(2)}',
        style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
      Text(
        '*$disclaimer',  // <-- Usar valor del API
        style: TextStyle(fontSize: 9, color: Colors.grey[600], fontStyle: FontStyle.italic),
      ),
    ],
  );
}
```

**Despues de seleccionar restaurante/direccion**, los precios en el carrito son exactos y ya NO necesitan disclaimer.

---

## Historial de Cambios

| Fecha | Cambio |
|-------|--------|
| 2025-12-23 | POST /auth/register ahora requiere `terms_accepted: true` (checkbox de T&C) |
| 2025-12-23 | POST /orders ahora requiere email verificado (error 403 EMAIL_NOT_VERIFIED) |
| 2025-12-23 | GET /menu ahora devuelve `price_disclaimer` para mostrar en UI |
| 2025-12-23 | Agregado seccion "Flujo General de la App" con diagrama completo |
| 2025-12-22 | PUT /cart/restaurant ahora auto-determina zone segun restaurant.price_location |
| 2025-12-22 | Agregado seccion "Sistema de Precios y Zonas" con flujos detallados |
| 2025-12-22 | Agregado campos de descuento por item: discount_amount, final_price, is_daily_special, applied_promotion |
| 2025-12-22 | Estandarizado campos: price, delivery_capital, delivery_interior, error_code |
| 2025-12-22 | Estandarizado `token` en lugar de `access_token` |
| 2025-12-22 | Documentacion completa para Flutter |
| 2025-12-22 | Agregado endpoint `/menu?lite=1` |
| 2025-12-22 | Integradas promociones automaticas al carrito |
| 2025-12-22 | Campo `price` en productos/variantes/combos |
| 2025-12-22 | Eliminado delivery_fee (siempre 0) |
| 2025-12-22 | Eliminado metodo applyPromoCode (no implementado) |
