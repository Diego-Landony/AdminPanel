# Plan de Implementación - API Mobile Subway App

## Resumen Ejecutivo

Este documento describe el plan completo para implementar la API que consumirá la aplicación móvil de Subway Guatemala. El sistema permitirá a los clientes ver el menú, agregar productos al carrito, realizar órdenes y acumular puntos de fidelidad.

---

## Estado Actual del Sistema

### Lo que YA existe y funciona

| Componente | Estado | Ubicación |
|------------|--------|-----------|
| Sistema de Menú | ✅ Completo | `app/Models/Menu/*` |
| 4 Tipos de Precios | ✅ Implementado | Products, Variants, Combos |
| Autenticación API | ✅ Funcional | Sanctum + OAuth |
| Clientes | ✅ Completo | `app/Models/Customer.php` |
| Tarjeta Metro | ✅ Implementado | 12 dígitos auto-generados |
| Restaurantes | ✅ Parcial | `app/Models/Restaurant.php` |
| CustomerTypes | ✅ Completo | Regular→Bronce→Plata→Oro→Platino |
| PriceCalculatorService | ✅ Creado | `app/Services/PriceCalculatorService.php` |

### Lo que FALTA implementar

| Componente | Prioridad | Fase |
|------------|-----------|------|
| API Resources de Menú | Alta | 1 |
| Endpoints de Menú | Alta | 1 |
| API de Restaurantes | Alta | 1 |
| Sistema de Carrito | Alta | 2 |
| Sistema de Órdenes | Alta | 3 |
| Historial de Órdenes | Media | 3 |
| Sistema de Favoritos | Baja | 4 |
| Reviews/Calificaciones | Baja | 4 |

---

## Arquitectura de Precios

El sistema maneja **4 tipos de precios** basados en zona y tipo de servicio:

```
┌─────────────────────────────────────────────────────────┐
│                    ESTRUCTURA DE PRECIOS                │
├─────────────────────────────────────────────────────────┤
│                                                         │
│   ZONA CAPITAL                    ZONA INTERIOR         │
│   ┌───────────────────┐          ┌───────────────────┐  │
│   │ precio_pickup_    │          │ precio_pickup_    │  │
│   │ capital           │          │ interior          │  │
│   │                   │          │                   │  │
│   │ precio_domicilio_ │          │ precio_domicilio_ │  │
│   │ capital           │          │ interior          │  │
│   └───────────────────┘          └───────────────────┘  │
│                                                         │
│   Aplica a: Products, ProductVariants, Combos           │
│   Daily Special: 4 precios adicionales en variantes     │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

---

## FASE 1: API de Menú y Restaurantes (Solo Lectura)

### Objetivo
Exponer el catálogo de productos para que la app móvil pueda mostrar el menú completo.

### Endpoints a Crear

```
GET /api/v1/menu                         → Menú completo agrupado por categoría
GET /api/v1/menu/categories              → Lista de categorías activas
GET /api/v1/menu/categories/{id}         → Categoría con sus productos
GET /api/v1/menu/products                → Lista de productos con filtros
GET /api/v1/menu/products/{id}           → Detalle de producto + secciones + variantes
GET /api/v1/menu/combos                  → Lista de combos activos
GET /api/v1/menu/combos/{id}             → Detalle de combo + items + opciones
GET /api/v1/menu/promotions              → Promociones activas
GET /api/v1/menu/promotions/daily        → Sub del Día de hoy
GET /api/v1/restaurants                  → Lista de restaurantes
GET /api/v1/restaurants/{id}             → Detalle de restaurante
GET /api/v1/restaurants/nearby           → Restaurantes cercanos (lat/lng)
```

### Resources a Crear

```
app/Http/Resources/Api/V1/Menu/
├── CategoryResource.php
├── CategoryCollection.php
├── ProductResource.php
├── ProductCollection.php
├── ProductVariantResource.php
├── ComboResource.php
├── ComboCollection.php
├── ComboItemResource.php
├── ComboItemOptionResource.php
├── PromotionResource.php
├── PromotionCollection.php
├── SectionResource.php
├── SectionOptionResource.php
├── BadgeResource.php
└── RestaurantResource.php
```

### Controllers a Crear

```
app/Http/Controllers/Api/V1/Menu/
├── MenuController.php           → Menú completo
├── CategoryController.php       → CRUD categorías
├── ProductController.php        → CRUD productos
├── ComboController.php          → CRUD combos
├── PromotionController.php      → Promociones activas
└── RestaurantController.php     → Restaurantes
```

### Estructura de Respuesta del Menú

```json
{
  "data": {
    "categories": [
      {
        "id": 1,
        "name": "Subs Clásicos",
        "uses_variants": true,
        "variant_definitions": ["15cm", "30cm"],
        "products": [
          {
            "id": 1,
            "name": "Italian BMT",
            "description": "Pepperoni, salami y jamón",
            "image": "/storage/images/italian-bmt.jpg",
            "has_variants": true,
            "is_customizable": true,
            "badges": [
              {"name": "Popular", "color": "#FF5722"}
            ],
            "variants": [
              {
                "id": 1,
                "name": "15cm",
                "sku": "IBMT-15",
                "prices": {
                  "pickup_capital": 45.00,
                  "domicilio_capital": 50.00,
                  "pickup_interior": 42.00,
                  "domicilio_interior": 47.00
                },
                "is_daily_special": false
              },
              {
                "id": 2,
                "name": "30cm",
                "sku": "IBMT-30",
                "prices": {
                  "pickup_capital": 75.00,
                  "domicilio_capital": 82.00,
                  "pickup_interior": 70.00,
                  "domicilio_interior": 77.00
                },
                "is_daily_special": true,
                "daily_special_days": [1, 3, 5],
                "daily_special_prices": {
                  "pickup_capital": 55.00,
                  "domicilio_capital": 60.00,
                  "pickup_interior": 52.00,
                  "domicilio_interior": 57.00
                }
              }
            ],
            "sections": [
              {
                "id": 1,
                "title": "Extras",
                "is_required": false,
                "allow_multiple": true,
                "min_selections": 0,
                "max_selections": 5,
                "options": [
                  {"id": 1, "name": "Doble Queso", "price_modifier": 8.00, "is_extra": true},
                  {"id": 2, "name": "Tocino", "price_modifier": 12.00, "is_extra": true}
                ]
              }
            ]
          }
        ]
      }
    ],
    "combos": [
      {
        "id": 1,
        "name": "Combo Sub + Bebida + Galleta",
        "description": "Tu sub favorito con bebida y galleta",
        "image": "/storage/images/combo-1.jpg",
        "prices": {
          "pickup_capital": 65.00,
          "domicilio_capital": 72.00,
          "pickup_interior": 60.00,
          "domicilio_interior": 67.00
        },
        "items": [
          {
            "id": 1,
            "is_choice_group": true,
            "choice_label": "Elige tu Sub",
            "options": [
              {"product_id": 1, "variant_id": 1, "name": "Italian BMT 15cm"},
              {"product_id": 2, "variant_id": 3, "name": "Subway Club 15cm"}
            ]
          },
          {
            "id": 2,
            "is_choice_group": false,
            "product_id": 10,
            "product_name": "Galleta",
            "quantity": 1
          }
        ]
      }
    ],
    "promotions": {
      "daily_special": {
        "variant_id": 2,
        "product_name": "Italian BMT 30cm",
        "original_price": 75.00,
        "special_price": 55.00,
        "valid_today": true
      },
      "active": [
        {
          "id": 1,
          "name": "2x1 en Subs 15cm",
          "type": "two_for_one",
          "applies_to": "category",
          "category_id": 1
        }
      ]
    }
  },
  "meta": {
    "total_categories": 5,
    "total_products": 45,
    "total_combos": 8,
    "generated_at": "2025-12-10T15:30:00Z"
  }
}
```

---

## FASE 2: Sistema de Carrito

### Objetivo
Permitir a los clientes agregar productos al carrito con cálculo automático de precios y promociones.

### Migraciones a Crear

```sql
-- carts
CREATE TABLE carts (
    id BIGINT PRIMARY KEY,
    customer_id BIGINT NOT NULL REFERENCES customers(id),
    restaurant_id BIGINT NULL REFERENCES restaurants(id),
    service_type ENUM('pickup', 'delivery') DEFAULT 'pickup',
    zone ENUM('capital', 'interior') DEFAULT 'capital',
    status ENUM('active', 'abandoned', 'converted') DEFAULT 'active',
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    INDEX (customer_id, status),
    INDEX (expires_at)
);

-- cart_items
CREATE TABLE cart_items (
    id BIGINT PRIMARY KEY,
    cart_id BIGINT NOT NULL REFERENCES carts(id) ON DELETE CASCADE,

    -- Producto o Combo (uno de los dos)
    product_id BIGINT NULL REFERENCES products(id),
    variant_id BIGINT NULL REFERENCES product_variants(id),
    combo_id BIGINT NULL REFERENCES combos(id),

    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,

    -- Opciones seleccionadas (secciones, items de combo)
    selected_options JSON NULL,

    -- Para combos: selecciones de grupos de elección
    combo_selections JSON NULL,

    notes TEXT NULL,

    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    INDEX (cart_id),
    INDEX (product_id),
    INDEX (combo_id)
);
```

### Endpoints del Carrito

```
POST   /api/v1/cart/items              → Agregar item al carrito
PUT    /api/v1/cart/items/{id}         → Actualizar item (cantidad, opciones)
DELETE /api/v1/cart/items/{id}         → Eliminar item
GET    /api/v1/cart                    → Ver carrito completo con totales
DELETE /api/v1/cart                    → Vaciar carrito
PUT    /api/v1/cart/restaurant         → Cambiar restaurante
PUT    /api/v1/cart/service-type       → Cambiar pickup/delivery
POST   /api/v1/cart/validate           → Validar disponibilidad
POST   /api/v1/cart/apply-promotion    → Aplicar código promocional
```

### Modelos a Crear

```
app/Models/
├── Cart.php
└── CartItem.php
```

### Servicios a Crear

```
app/Services/
├── CartService.php                → Gestión del carrito
└── PromotionApplicationService.php → Aplicar promociones al carrito
```

### Estructura de Respuesta del Carrito

```json
{
  "data": {
    "id": 123,
    "restaurant": {
      "id": 1,
      "name": "Subway Pradera Zona 10"
    },
    "service_type": "delivery",
    "zone": "capital",
    "items": [
      {
        "id": 1,
        "type": "product",
        "product": {
          "id": 1,
          "name": "Italian BMT",
          "variant": {"id": 2, "name": "30cm"}
        },
        "quantity": 2,
        "unit_price": 75.00,
        "subtotal": 150.00,
        "selected_options": [
          {"section_id": 1, "option_id": 1, "name": "Doble Queso", "price": 8.00}
        ],
        "options_total": 16.00,
        "line_total": 166.00,
        "notes": "Sin cebolla"
      },
      {
        "id": 2,
        "type": "combo",
        "combo": {
          "id": 1,
          "name": "Combo Sub + Bebida + Galleta"
        },
        "quantity": 1,
        "unit_price": 72.00,
        "subtotal": 72.00,
        "combo_selections": {
          "choice_group_1": {"product_id": 1, "variant_id": 1}
        },
        "line_total": 72.00
      }
    ],
    "summary": {
      "subtotal": 238.00,
      "promotions_applied": [
        {
          "id": 1,
          "name": "2x1 en Subs",
          "discount": -75.00
        }
      ],
      "total_discount": -75.00,
      "delivery_fee": 15.00,
      "total": 178.00
    },
    "can_checkout": true,
    "validation_messages": []
  }
}
```

---

## FASE 3: Sistema de Órdenes

### Objetivo
Procesar compras, manejar estados de orden y mantener historial.

### Migraciones a Crear

```sql
-- orders
CREATE TABLE orders (
    id BIGINT PRIMARY KEY,
    order_number VARCHAR(20) UNIQUE NOT NULL, -- ORD-2025-000001
    customer_id BIGINT NOT NULL REFERENCES customers(id),
    restaurant_id BIGINT NOT NULL REFERENCES restaurants(id),

    -- Tipo de servicio
    service_type ENUM('pickup', 'delivery') NOT NULL,
    zone ENUM('capital', 'interior') NOT NULL,

    -- Dirección de entrega (si es delivery)
    delivery_address_id BIGINT NULL REFERENCES customer_addresses(id),
    delivery_address_snapshot JSON NULL, -- Copia de la dirección al momento

    -- Totales
    subtotal DECIMAL(10,2) NOT NULL,
    discount_total DECIMAL(10,2) DEFAULT 0,
    delivery_fee DECIMAL(10,2) DEFAULT 0,
    tax DECIMAL(10,2) DEFAULT 0,
    total DECIMAL(10,2) NOT NULL,

    -- Estado
    status ENUM('pending', 'confirmed', 'preparing', 'ready',
                'out_for_delivery', 'delivered', 'completed',
                'cancelled', 'refunded') DEFAULT 'pending',

    -- Pago
    payment_method ENUM('cash', 'card', 'online') DEFAULT 'cash',
    payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
    paid_at TIMESTAMP NULL,

    -- Tiempos
    estimated_ready_at TIMESTAMP NULL,
    ready_at TIMESTAMP NULL,
    delivered_at TIMESTAMP NULL,

    -- Puntos
    points_earned INT DEFAULT 0,
    points_redeemed INT DEFAULT 0,

    -- NIT para facturación
    nit_id BIGINT NULL REFERENCES customer_nits(id),
    nit_snapshot JSON NULL,

    notes TEXT NULL,
    cancellation_reason TEXT NULL,

    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    INDEX (customer_id),
    INDEX (restaurant_id),
    INDEX (status),
    INDEX (created_at),
    INDEX (order_number)
);

-- order_items
CREATE TABLE order_items (
    id BIGINT PRIMARY KEY,
    order_id BIGINT NOT NULL REFERENCES orders(id) ON DELETE CASCADE,

    -- Producto o Combo
    product_id BIGINT NULL REFERENCES products(id),
    variant_id BIGINT NULL REFERENCES product_variants(id),
    combo_id BIGINT NULL REFERENCES combos(id),

    -- Snapshot del producto al momento de la compra
    product_snapshot JSON NOT NULL,

    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    options_price DECIMAL(10,2) DEFAULT 0,
    subtotal DECIMAL(10,2) NOT NULL,

    -- Opciones seleccionadas
    selected_options JSON NULL,
    combo_selections JSON NULL,

    notes TEXT NULL,

    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    INDEX (order_id),
    INDEX (product_id),
    INDEX (combo_id)
);

-- order_promotions
CREATE TABLE order_promotions (
    id BIGINT PRIMARY KEY,
    order_id BIGINT NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
    promotion_id BIGINT NULL REFERENCES promotions(id),

    promotion_type VARCHAR(50) NOT NULL,
    promotion_name VARCHAR(255) NOT NULL,
    discount_amount DECIMAL(10,2) NOT NULL,
    description TEXT NULL,

    created_at TIMESTAMP,

    INDEX (order_id)
);

-- order_status_history
CREATE TABLE order_status_history (
    id BIGINT PRIMARY KEY,
    order_id BIGINT NOT NULL REFERENCES orders(id) ON DELETE CASCADE,

    previous_status VARCHAR(50) NULL,
    new_status VARCHAR(50) NOT NULL,

    changed_by_type ENUM('system', 'customer', 'admin', 'restaurant') DEFAULT 'system',
    changed_by_id BIGINT NULL,

    notes TEXT NULL,
    metadata JSON NULL,

    created_at TIMESTAMP,

    INDEX (order_id),
    INDEX (created_at)
);
```

### Endpoints de Órdenes

```
POST   /api/v1/orders                  → Crear orden desde carrito
GET    /api/v1/orders                  → Historial de órdenes del cliente
GET    /api/v1/orders/{id}             → Detalle de orden
GET    /api/v1/orders/{id}/track       → Estado actual + historial
POST   /api/v1/orders/{id}/cancel      → Cancelar orden (si permite)
POST   /api/v1/orders/{id}/reorder     → Reordenar (crear carrito con mismos items)
GET    /api/v1/orders/active           → Órdenes activas (no completadas)
```

### Modelos a Crear

```
app/Models/
├── Order.php
├── OrderItem.php
├── OrderPromotion.php
└── OrderStatusHistory.php
```

### Servicios a Crear

```
app/Services/
├── OrderService.php           → Crear orden, cambiar estado
├── OrderNumberGenerator.php   → Generar números únicos
└── PointsService.php          → Calcular y asignar puntos
```

### Estados de Orden

```
┌─────────────────────────────────────────────────────────────┐
│                    FLUJO DE ESTADOS                         │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│   PICKUP:                                                   │
│   pending → confirmed → preparing → ready → completed       │
│                                                             │
│   DELIVERY:                                                 │
│   pending → confirmed → preparing → ready →                 │
│            out_for_delivery → delivered → completed         │
│                                                             │
│   CANCELACIÓN (desde cualquier estado antes de ready):      │
│   * → cancelled                                             │
│                                                             │
│   REEMBOLSO (solo admin):                                   │
│   completed → refunded                                      │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

## FASE 4: Features Adicionales

### Sistema de Favoritos

```sql
CREATE TABLE customer_favorites (
    id BIGINT PRIMARY KEY,
    customer_id BIGINT NOT NULL REFERENCES customers(id),
    favoritable_type VARCHAR(50) NOT NULL, -- 'product', 'combo'
    favoritable_id BIGINT NOT NULL,
    created_at TIMESTAMP,

    UNIQUE (customer_id, favoritable_type, favoritable_id)
);
```

```
POST   /api/v1/favorites              → Agregar favorito
DELETE /api/v1/favorites/{type}/{id}  → Eliminar favorito
GET    /api/v1/favorites              → Listar favoritos
```

### Sistema de Reviews

```sql
CREATE TABLE order_reviews (
    id BIGINT PRIMARY KEY,
    order_id BIGINT NOT NULL REFERENCES orders(id),
    customer_id BIGINT NOT NULL REFERENCES customers(id),

    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT NULL,

    -- Ratings específicos
    food_rating INT NULL CHECK (food_rating BETWEEN 1 AND 5),
    service_rating INT NULL CHECK (service_rating BETWEEN 1 AND 5),
    delivery_rating INT NULL CHECK (delivery_rating BETWEEN 1 AND 5),

    is_public BOOLEAN DEFAULT TRUE,

    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    UNIQUE (order_id)
);
```

```
POST /api/v1/orders/{id}/review       → Crear review
GET  /api/v1/restaurants/{id}/reviews → Ver reviews de restaurante
```

### Sistema de Puntos Mejorado

```
POST /api/v1/points/redeem            → Canjear puntos
GET  /api/v1/points/history           → Historial de puntos
GET  /api/v1/points/rewards           → Recompensas disponibles
```

---

## Archivos a Crear por Fase

### FASE 1 - Lista de Archivos

```
# Resources
app/Http/Resources/Api/V1/Menu/CategoryResource.php
app/Http/Resources/Api/V1/Menu/CategoryCollection.php
app/Http/Resources/Api/V1/Menu/ProductResource.php
app/Http/Resources/Api/V1/Menu/ProductCollection.php
app/Http/Resources/Api/V1/Menu/ProductVariantResource.php
app/Http/Resources/Api/V1/Menu/ComboResource.php
app/Http/Resources/Api/V1/Menu/ComboCollection.php
app/Http/Resources/Api/V1/Menu/ComboItemResource.php
app/Http/Resources/Api/V1/Menu/ComboItemOptionResource.php
app/Http/Resources/Api/V1/Menu/PromotionResource.php
app/Http/Resources/Api/V1/Menu/PromotionCollection.php
app/Http/Resources/Api/V1/Menu/SectionResource.php
app/Http/Resources/Api/V1/Menu/SectionOptionResource.php
app/Http/Resources/Api/V1/Menu/BadgeResource.php
app/Http/Resources/Api/V1/Menu/RestaurantResource.php
app/Http/Resources/Api/V1/Menu/RestaurantCollection.php

# Controllers
app/Http/Controllers/Api/V1/Menu/MenuController.php
app/Http/Controllers/Api/V1/Menu/CategoryController.php
app/Http/Controllers/Api/V1/Menu/ProductController.php
app/Http/Controllers/Api/V1/Menu/ComboController.php
app/Http/Controllers/Api/V1/Menu/PromotionController.php
app/Http/Controllers/Api/V1/Menu/RestaurantController.php

# Form Requests
app/Http/Requests/Api/V1/Menu/GetMenuRequest.php
app/Http/Requests/Api/V1/Menu/GetProductsRequest.php
app/Http/Requests/Api/V1/Menu/GetRestaurantsRequest.php

# Routes
routes/api.php (agregar rutas de menu)

# Tests
tests/Feature/Api/V1/Menu/MenuControllerTest.php
tests/Feature/Api/V1/Menu/ProductControllerTest.php
tests/Feature/Api/V1/Menu/ComboControllerTest.php
tests/Feature/Api/V1/Menu/RestaurantControllerTest.php
```

### FASE 2 - Lista de Archivos

```
# Migrations
database/migrations/YYYY_MM_DD_create_carts_table.php
database/migrations/YYYY_MM_DD_create_cart_items_table.php

# Models
app/Models/Cart.php
app/Models/CartItem.php

# Resources
app/Http/Resources/Api/V1/Cart/CartResource.php
app/Http/Resources/Api/V1/Cart/CartItemResource.php

# Controllers
app/Http/Controllers/Api/V1/CartController.php

# Services
app/Services/CartService.php
app/Services/PromotionApplicationService.php

# Form Requests
app/Http/Requests/Api/V1/Cart/AddCartItemRequest.php
app/Http/Requests/Api/V1/Cart/UpdateCartItemRequest.php
app/Http/Requests/Api/V1/Cart/UpdateCartRequest.php

# Tests
tests/Feature/Api/V1/CartControllerTest.php
```

### FASE 3 - Lista de Archivos

```
# Migrations
database/migrations/YYYY_MM_DD_create_orders_table.php
database/migrations/YYYY_MM_DD_create_order_items_table.php
database/migrations/YYYY_MM_DD_create_order_promotions_table.php
database/migrations/YYYY_MM_DD_create_order_status_history_table.php

# Models
app/Models/Order.php
app/Models/OrderItem.php
app/Models/OrderPromotion.php
app/Models/OrderStatusHistory.php

# Resources
app/Http/Resources/Api/V1/Order/OrderResource.php
app/Http/Resources/Api/V1/Order/OrderCollection.php
app/Http/Resources/Api/V1/Order/OrderItemResource.php
app/Http/Resources/Api/V1/Order/OrderStatusResource.php

# Controllers
app/Http/Controllers/Api/V1/OrderController.php

# Services
app/Services/OrderService.php
app/Services/OrderNumberGenerator.php
app/Services/PointsService.php

# Form Requests
app/Http/Requests/Api/V1/Order/CreateOrderRequest.php
app/Http/Requests/Api/V1/Order/CancelOrderRequest.php

# Notifications
app/Notifications/OrderCreatedNotification.php
app/Notifications/OrderStatusChangedNotification.php

# Tests
tests/Feature/Api/V1/OrderControllerTest.php
```

---

## Convenciones a Seguir

### Estructura de Respuestas API

```json
// Éxito
{
  "data": { ... },
  "meta": { ... }
}

// Error
{
  "message": "Descripción del error",
  "errors": {
    "field": ["Error específico"]
  }
}
```

### Naming Conventions

- **Controllers**: `{Entity}Controller.php`
- **Resources**: `{Entity}Resource.php`
- **Collections**: `{Entity}Collection.php`
- **Requests**: `{Action}{Entity}Request.php`
- **Services**: `{Entity}Service.php`

### Rate Limiting

```php
// Endpoints públicos (menú)
'throttle:60,1'  // 60 requests por minuto

// Endpoints autenticados
'throttle:api'   // Configuración estándar

// Endpoints sensibles (crear orden)
'throttle:10,1'  // 10 requests por minuto
```

---

## Notas de Implementación

### Caché de Menú

El menú debe cachearse para mejor performance:

```php
// Caché por 1 hora, invalidar al actualizar productos
Cache::remember('menu:full', 3600, fn() => $this->buildMenu());
Cache::remember('menu:category:'.$id, 3600, fn() => $this->getCategory($id));
```

### Precios según Contexto

La app debe enviar `zone` y `service_type` para recibir precios correctos:

```
GET /api/v1/menu?zone=capital&service_type=delivery
```

### Validación de Disponibilidad

Antes de crear orden, validar:
1. Restaurante activo y abierto
2. Productos disponibles
3. Promociones aún vigentes
4. Stock (si aplica en futuro)

---

## Timeline Estimado

| Fase | Alcance | Archivos | Complejidad |
|------|---------|----------|-------------|
| 1 | API Menú + Restaurantes | ~25 archivos | Media |
| 2 | Sistema de Carrito | ~15 archivos | Alta |
| 3 | Sistema de Órdenes | ~20 archivos | Alta |
| 4 | Extras (Favoritos, Reviews) | ~10 archivos | Baja |

---

## Próximos Pasos

1. ✅ Crear este documento de planificación
2. 🔄 Implementar FASE 1: API de Menú
3. ⏳ Implementar FASE 2: Carrito
4. ⏳ Implementar FASE 3: Órdenes
5. ⏳ Implementar FASE 4: Extras
6. ⏳ Documentación OpenAPI/Swagger
7. ⏳ Testing completo
8. ⏳ Deploy y monitoreo
