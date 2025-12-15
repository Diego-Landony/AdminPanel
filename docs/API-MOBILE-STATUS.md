# Estado de Implementación - API Mobile Subway Guatemala

**Última actualización:** 2025-12-12

Este documento detalla el estado actual de implementación de la API móvil comparado con el plan original en `API-MOBILE-IMPLEMENTATION-PLAN.md`.

---

## Resumen Ejecutivo

| Fase | Descripción | Estado | Progreso |
|------|-------------|--------|----------|
| 1 | API de Menú y Restaurantes | ✅ Completo | 100% |
| 2 | Sistema de Carrito | ✅ Completo | 100% |
| 3 | Sistema de Órdenes | ✅ Completo | 100% |
| 4 | Features Adicionales | ⚠️ Parcial | 80% |
| - | Autenticación y Perfil | ✅ Completo | 100% |
| - | Geocercas y Delivery | ✅ Completo | 100% |
| - | NITs para Facturación | ✅ Completo | 100% |
| - | Sistema de Puntos | ✅ Completo | 100% |
| - | Sistema de Favoritos | ✅ Completo | 100% |
| - | Sistema de Reviews | ❌ Pendiente | 0% |
| - | Notificaciones Push | ⚠️ Parcial | 50% |

---

## FASE 1: API de Menú y Restaurantes

### Estado: ✅ COMPLETAMENTE IMPLEMENTADO

### Endpoints Implementados

| Endpoint | Método | Descripción | Estado |
|----------|--------|-------------|--------|
| `/api/v1/menu` | GET | Menú completo agrupado | ✅ |
| `/api/v1/menu/categories` | GET | Lista de categorías | ✅ |
| `/api/v1/menu/categories/{id}` | GET | Categoría con productos | ✅ |
| `/api/v1/menu/products` | GET | Lista de productos (con filtros) | ✅ |
| `/api/v1/menu/products/{id}` | GET | Detalle de producto | ✅ |
| `/api/v1/menu/combos` | GET | Lista de combos | ✅ |
| `/api/v1/menu/combos/{id}` | GET | Detalle de combo | ✅ |
| `/api/v1/menu/promotions` | GET | Promociones activas | ✅ |
| `/api/v1/menu/promotions/daily` | GET | Sub del Día | ✅ |
| `/api/v1/menu/promotions/combinados` | GET | Bundle specials | ✅ |
| `/api/v1/restaurants` | GET | Lista de restaurantes | ✅ |
| `/api/v1/restaurants/{id}` | GET | Detalle de restaurante | ✅ |
| `/api/v1/restaurants/nearby` | GET | Restaurantes cercanos (geolocalización) | ✅ |

### Resources Implementados

```
app/Http/Resources/Api/V1/Menu/
├── CategoryResource.php           ✅
├── ProductResource.php            ✅ (incluye 4 precios, badges, variantes, secciones)
├── ProductVariantResource.php     ✅ (incluye precios daily special)
├── ComboResource.php              ✅ (incluye items, badges)
├── ComboItemResource.php          ✅
├── ComboItemOptionResource.php    ✅
├── PromotionResource.php          ✅
├── PromotionItemResource.php      ✅
├── BundlePromotionItemResource.php ✅
├── SectionResource.php            ✅
├── SectionOptionResource.php      ✅
├── BadgeResource.php              ✅
└── RestaurantResource.php         ✅
```

### Controllers Implementados

```
app/Http/Controllers/Api/V1/Menu/
├── MenuController.php             ✅
├── CategoryController.php         ✅
├── ProductController.php          ✅
├── ComboController.php            ✅
├── PromotionController.php        ✅
└── RestaurantController.php       ✅
```

---

## FASE 2: Sistema de Carrito

### Estado: ✅ COMPLETAMENTE IMPLEMENTADO

### Endpoints Implementados

| Endpoint | Método | Descripción | Estado |
|----------|--------|-------------|--------|
| `/api/v1/cart` | GET | Ver carrito completo | ✅ |
| `/api/v1/cart` | DELETE | Vaciar carrito | ✅ |
| `/api/v1/cart/items` | POST | Agregar item | ✅ |
| `/api/v1/cart/items/{id}` | PUT | Actualizar item | ✅ |
| `/api/v1/cart/items/{id}` | DELETE | Eliminar item | ✅ |
| `/api/v1/cart/restaurant` | PUT | Cambiar restaurante | ✅ |
| `/api/v1/cart/service-type` | PUT | Cambiar pickup/delivery | ✅ |
| `/api/v1/cart/delivery-address` | PUT | Asignar dirección (con validación geocerca) | ✅ |
| `/api/v1/cart/validate` | POST | Validar disponibilidad | ✅ |
| `/api/v1/cart/apply-promotion` | POST | Aplicar código promocional | ✅ |

### Modelos y Servicios

| Componente | Ubicación | Estado |
|------------|-----------|--------|
| Cart Model | `app/Models/Cart.php` | ✅ |
| CartItem Model | `app/Models/CartItem.php` | ✅ |
| CartService | `app/Services/CartService.php` | ✅ |
| PromotionApplicationService | `app/Services/PromotionApplicationService.php` | ✅ |
| CartResource | `app/Http/Resources/Api/V1/Cart/CartResource.php` | ✅ |
| CartItemResource | `app/Http/Resources/Api/V1/Cart/CartItemResource.php` | ✅ |

### Tablas de Base de Datos

- ✅ `carts` - Con todos los campos del plan + `delivery_address_id`
- ✅ `cart_items` - Con `selected_options` y `combo_selections` JSON

---

## FASE 3: Sistema de Órdenes

### Estado: ✅ COMPLETAMENTE IMPLEMENTADO

### Endpoints Implementados

| Endpoint | Método | Descripción | Estado |
|----------|--------|-------------|--------|
| `/api/v1/orders` | POST | Crear orden desde carrito | ✅ |
| `/api/v1/orders` | GET | Historial de órdenes | ✅ |
| `/api/v1/orders/active` | GET | Órdenes activas | ✅ |
| `/api/v1/orders/{id}` | GET | Detalle de orden | ✅ |
| `/api/v1/orders/{id}/track` | GET | Rastrear orden + historial | ✅ |
| `/api/v1/orders/{id}/cancel` | POST | Cancelar orden | ✅ |
| `/api/v1/orders/{id}/reorder` | POST | Reordenar | ✅ |

### Modelos y Servicios

| Componente | Ubicación | Estado |
|------------|-----------|--------|
| Order Model | `app/Models/Order.php` | ✅ |
| OrderItem Model | `app/Models/OrderItem.php` | ✅ |
| OrderPromotion Model | `app/Models/OrderPromotion.php` | ✅ |
| OrderStatusHistory Model | `app/Models/OrderStatusHistory.php` | ✅ |
| OrderService | `app/Services/OrderService.php` | ✅ |
| OrderNumberGenerator | `app/Services/OrderNumberGenerator.php` | ✅ |
| PointsService | `app/Services/PointsService.php` | ✅ |

### Tablas de Base de Datos

- ✅ `orders` - Incluye `delivery_address_snapshot`, `nit_snapshot`, puntos
- ✅ `order_items` - Incluye `product_snapshot` para auditoría
- ✅ `order_promotions` - Promociones aplicadas a cada orden
- ✅ `order_status_history` - Historial completo de cambios de estado

### Estados de Orden Implementados

```
PICKUP:    pending → confirmed → preparing → ready → completed
DELIVERY:  pending → confirmed → preparing → ready → out_for_delivery → delivered → completed
CANCEL:    * → cancelled (antes de ready)
REFUND:    completed → refunded (solo admin)
```

---

## Autenticación y Perfil

### Estado: ✅ COMPLETAMENTE IMPLEMENTADO

### Endpoints de Autenticación

| Endpoint | Método | Descripción | Estado |
|----------|--------|-------------|--------|
| `/api/v1/auth/register` | POST | Registro de cliente | ✅ |
| `/api/v1/auth/login` | POST | Inicio de sesión | ✅ |
| `/api/v1/auth/logout` | POST | Cerrar sesión | ✅ |
| `/api/v1/auth/logout-all` | POST | Cerrar todas las sesiones | ✅ |
| `/api/v1/auth/refresh` | POST | Renovar token | ✅ |
| `/api/v1/auth/forgot-password` | POST | Solicitar reset | ✅ |
| `/api/v1/auth/reset-password` | POST | Resetear contraseña | ✅ |
| `/api/v1/auth/email/verify/{id}/{hash}` | POST | Verificar email | ✅ |
| `/api/v1/auth/email/resend` | POST | Reenviar verificación | ✅ |
| `/api/v1/auth/oauth/google/*` | GET | OAuth Google | ✅ |
| `/api/v1/auth/oauth/apple/*` | GET | OAuth Apple | ✅ |

### Endpoints de Perfil

| Endpoint | Método | Descripción | Estado |
|----------|--------|-------------|--------|
| `/api/v1/profile` | GET | Ver perfil completo | ✅ |
| `/api/v1/profile` | PUT | Editar perfil | ✅ |
| `/api/v1/profile` | DELETE | Eliminar cuenta | ✅ |
| `/api/v1/profile/avatar` | POST | Actualizar avatar | ✅ |
| `/api/v1/profile/avatar` | DELETE | Eliminar avatar | ✅ |
| `/api/v1/profile/password` | PUT | Cambiar contraseña | ✅ |

### Endpoints de Direcciones

| Endpoint | Método | Descripción | Estado |
|----------|--------|-------------|--------|
| `/api/v1/addresses` | GET | Lista direcciones | ✅ |
| `/api/v1/addresses` | POST | Crear dirección | ✅ |
| `/api/v1/addresses/{id}` | GET | Ver dirección | ✅ |
| `/api/v1/addresses/{id}` | PUT | Actualizar dirección | ✅ |
| `/api/v1/addresses/{id}` | DELETE | Eliminar dirección | ✅ |
| `/api/v1/addresses/{id}/set-default` | POST | Marcar como predeterminada | ✅ |
| `/api/v1/addresses/validate` | POST | Validar contra geocercas | ✅ |

### Endpoints de Dispositivos

| Endpoint | Método | Descripción | Estado |
|----------|--------|-------------|--------|
| `/api/v1/devices` | GET | Lista dispositivos | ✅ |
| `/api/v1/devices/register` | POST | Registrar dispositivo | ✅ |
| `/api/v1/devices/{id}` | DELETE | Eliminar dispositivo | ✅ |

---

## Sistema de Geocercas

### Estado: ✅ COMPLETAMENTE IMPLEMENTADO

### Servicios Implementados

| Servicio | Ubicación | Descripción |
|----------|-----------|-------------|
| PointInPolygonService | `app/Services/Geofence/PointInPolygonService.php` | Algoritmo ray-casting |
| KmlParserService | `app/Services/Geofence/KmlParserService.php` | Parser de KML a coordenadas |
| GeofenceService | `app/Services/Geofence/GeofenceService.php` | Orquestador de geocercas |
| DeliveryValidationService | `app/Services/DeliveryValidationService.php` | Validación de entregas |

### Funcionalidades

- ✅ Validar si coordenadas están dentro de geocerca
- ✅ Asignar restaurante automáticamente según geocerca
- ✅ Determinar zona de precios (capital/interior)
- ✅ Sugerir restaurantes cercanos para pickup si no hay delivery
- ✅ Validación obligatoria antes de crear orden de delivery

---

## FASE 4: Features Adicionales

### Estado: ⚠️ PARCIALMENTE IMPLEMENTADO

---

### 1. NITs para Facturación

**Estado: ✅ COMPLETAMENTE IMPLEMENTADO**

| Componente | Estado | Ubicación |
|------------|--------|-----------|
| Tabla `customer_nits` | ✅ | Migración existente |
| Modelo `CustomerNit` | ✅ | `app/Models/CustomerNit.php` |
| Relación en Order | ✅ | `nit_id` + `nit_snapshot` |
| Controller API | ✅ | `app/Http/Controllers/Api/V1/CustomerNitController.php` |
| Resource | ✅ | `app/Http/Resources/Api/V1/CustomerNitResource.php` |
| Form Requests | ✅ | `app/Http/Requests/Api/V1/CustomerNit/` |
| Tests | ✅ | `tests/Feature/Api/V1/CustomerNitControllerTest.php` (26 tests) |

**Endpoints Implementados:**
| Endpoint | Método | Descripción | Estado |
|----------|--------|-------------|--------|
| `/api/v1/nits` | GET | Lista NITs del cliente | ✅ |
| `/api/v1/nits` | POST | Crear NIT | ✅ |
| `/api/v1/nits/{id}` | GET | Ver NIT | ✅ |
| `/api/v1/nits/{id}` | PUT | Actualizar NIT | ✅ |
| `/api/v1/nits/{id}` | DELETE | Eliminar NIT | ✅ |
| `/api/v1/nits/{id}/set-default` | POST | Marcar como predeterminado | ✅ |

---

### 2. Sistema de Puntos

**Estado: ✅ COMPLETAMENTE IMPLEMENTADO**

| Componente | Estado | Ubicación |
|------------|--------|-----------|
| PointsService | ✅ | `app/Services/PointsService.php` |
| Campo `points_balance` en Customer | ✅ | Modelo Customer |
| Tabla `customer_types` | ✅ | Con multiplicadores |
| Tabla `customer_points_transactions` | ✅ | Historial completo |
| Acreditación al completar orden | ✅ | En OrderService |
| Canje de puntos | ✅ | En PointsService |
| Controller API | ✅ | `app/Http/Controllers/Api/V1/PointsController.php` |
| Resources | ✅ | `app/Http/Resources/Api/V1/Points/` |
| Tests | ✅ | `tests/Feature/Api/V1/PointsControllerTest.php` (15 tests) |

**Endpoints Implementados:**

| Endpoint | Método | Descripción | Estado |
|----------|--------|-------------|--------|
| `/api/v1/points/balance` | GET | Puntos actuales + valor en Q | ✅ |
| `/api/v1/points/history` | GET | Historial paginado de movimientos | ✅ |
| `/api/v1/points/redeem` | POST | Canjear puntos en una orden | ✅ |
| `/api/v1/points/rewards` | GET | Productos/combos redimibles | ✅ |

**Campos de Redención en Productos:**

Se agregaron campos `is_redeemable` y `points_cost` a:
- `products` - Productos pueden ser canjeados por puntos
- `product_variants` - Variantes pueden tener costo en puntos diferente
- `combos` - Combos pueden ser recompensas

**Conversión de Puntos:**
- Acumulación: 1 punto por cada Q10 gastados
- Redención: 1 punto = Q0.10

---

### 3. Sistema de Favoritos

**Estado: ✅ COMPLETAMENTE IMPLEMENTADO**

| Componente | Estado | Ubicación |
|------------|--------|-----------|
| Tabla `favorites` | ✅ | Migración polimórfica |
| Modelo `Favorite` | ✅ | `app/Models/Favorite.php` |
| Relación en Customer | ✅ | `favorites()` HasMany |
| Controller API | ✅ | `app/Http/Controllers/Api/V1/FavoriteController.php` |
| Resource | ✅ | `app/Http/Resources/Api/V1/FavoriteResource.php` |
| Form Request | ✅ | `app/Http/Requests/Api/V1/Favorite/StoreFavoriteRequest.php` |
| Factory | ✅ | `database/factories/FavoriteFactory.php` |
| Tests | ✅ | `tests/Feature/Api/V1/FavoriteControllerTest.php` (18 tests) |

**Endpoints Implementados:**

| Endpoint | Método | Descripción | Estado |
|----------|--------|-------------|--------|
| `/api/v1/favorites` | GET | Listar favoritos del cliente | ✅ |
| `/api/v1/favorites` | POST | Agregar producto/combo a favoritos | ✅ |
| `/api/v1/favorites/{type}/{id}` | DELETE | Quitar de favoritos | ✅ |

**Características:**

- Relación polimórfica (soporta Product y Combo)
- Prevención de duplicados a nivel de BD y aplicación
- Eager loading de datos del producto/combo favorito

---

### 4. Sistema de Reviews

**Estado: ❌ NO IMPLEMENTADO**

**Tabla Faltante:**
```sql
CREATE TABLE order_reviews (
    id BIGINT PRIMARY KEY,
    order_id BIGINT NOT NULL UNIQUE,
    customer_id BIGINT NOT NULL,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT NULL,
    food_rating INT NULL,
    service_rating INT NULL,
    delivery_rating INT NULL,
    is_public BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

**Endpoints Faltantes:**
```
POST /api/v1/orders/{id}/review       → Crear review de orden
GET  /api/v1/restaurants/{id}/reviews → Ver reviews de restaurante
```

**Archivos a Crear:**
- `database/migrations/YYYY_MM_DD_create_order_reviews_table.php`
- `app/Models/OrderReview.php`
- `app/Http/Controllers/Api/V1/ReviewController.php`
- `app/Http/Resources/Api/V1/ReviewResource.php`

---

### 5. Notificaciones Push de Órdenes

**Estado: ⚠️ Parcial - Falta integración**

| Componente | Estado | Ubicación |
|------------|--------|-----------|
| FCMService | ✅ | `app/Services/FCMService.php` |
| CustomerDevice Model | ✅ | `app/Models/CustomerDevice.php` |
| Tabla `customer_devices` | ✅ | Con `fcm_token` |
| Eventos de Orden | ❌ | No existen |
| Listeners de notificación | ❌ | No existen |
| Integración en OrderService | ❌ | No dispara eventos |

**Archivos a Crear:**
```
app/Events/OrderCreated.php
app/Events/OrderStatusChanged.php
app/Listeners/SendOrderCreatedNotification.php
app/Listeners/SendOrderStatusNotification.php
app/Notifications/OrderCreatedNotification.php
app/Notifications/OrderStatusChangedNotification.php
```

**Integración en OrderService:**
```php
// En createFromCart():
event(new OrderCreated($order));

// En updateStatus():
event(new OrderStatusChanged($order, $previousStatus, $newStatus));
```

---

## Prioridades de Implementación Restante

| Prioridad | Componente | Esfuerzo | Razón |
|-----------|------------|----------|-------|
| **1** | Notificaciones Push | Medio | Ya existe FCMService, falta integración con eventos |
| **2** | Sistema de Reviews | Medio | Nice to have para UX |

---

## Archivos de Referencia

### Servicios Principales
- `app/Services/CartService.php`
- `app/Services/OrderService.php`
- `app/Services/PointsService.php`
- `app/Services/DeliveryValidationService.php`
- `app/Services/PromotionApplicationService.php`
- `app/Services/FCMService.php`
- `app/Services/Geofence/GeofenceService.php`

### Controllers API
- `app/Http/Controllers/Api/V1/Auth/AuthController.php`
- `app/Http/Controllers/Api/V1/ProfileController.php`
- `app/Http/Controllers/Api/V1/CartController.php`
- `app/Http/Controllers/Api/V1/OrderController.php`
- `app/Http/Controllers/Api/V1/CustomerAddressController.php`
- `app/Http/Controllers/Api/V1/CustomerNitController.php`
- `app/Http/Controllers/Api/V1/PointsController.php`
- `app/Http/Controllers/Api/V1/FavoriteController.php`
- `app/Http/Controllers/Api/V1/DeviceController.php`
- `app/Http/Controllers/Api/V1/Menu/*Controller.php`

### Rutas
- `routes/api.php` - Todas las rutas API v1

### Documentación Relacionada
- `docs/API-MOBILE-IMPLEMENTATION-PLAN.md` - Plan original
- `docs/GEOFENCE-IMPLEMENTATION-PLAN.md` - Plan de geocercas

---

## Notas Técnicas

### Rate Limiting Configurado
- Endpoints públicos (menú): `throttle:60,1`
- Endpoints autenticados: `throttle:api`
- Endpoints sensibles (órdenes): `throttle:10,1`

### Middleware de Autenticación
- Rutas públicas: Sin middleware
- Rutas protegidas: `auth:sanctum`

### Formato de Respuestas
```json
// Éxito
{ "data": { ... }, "meta": { ... } }

// Error
{ "message": "...", "errors": { "field": ["..."] } }
```

### Precios
El sistema maneja 4 tipos de precios:
- `precio_pickup_capital`
- `precio_domicilio_capital`
- `precio_pickup_interior`
- `precio_domicilio_interior`

La zona se determina por `restaurant.price_location` (capital/interior).
