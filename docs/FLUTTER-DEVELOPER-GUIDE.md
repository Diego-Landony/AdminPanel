# Guia para Desarrollador Flutter - SubwayApp

Documento basado en analisis real del codigo del backend y la app Flutter.

---

## Informacion del Proyecto

| Aspecto | Detalle |
|---------|---------|
| **Proyecto Flutter** | `/Users/diegolandony/programing/subwayapp_flutter` |
| **Nombre** | `subway_card` |
| **API Base URL** | `https://appmobile.subwaycardgt.com/api/v1` |

---

## Indice

1. [Resumen del Backend](#resumen-lo-que-hace-el-backend)
2. [Autenticacion](#1-autenticacion)
3. [Sistema de Precios](#2-sistema-de-precios)
4. [Menu y Promociones](#3-menu-y-promociones)
5. [Carrito](#4-endpoints-del-carrito)
6. [Crear Orden](#5-crear-orden)
7. [Gestion de Ordenes](#6-gestion-de-ordenes)
8. [Perfil del Cliente](#7-perfil-del-cliente)
9. [Sistema de Puntos](#8-sistema-de-puntos)
10. [Favoritos](#9-favoritos)
11. [Soporte y Legal](#10-soporte-y-documentos-legales)
12. [Metodos de Pago](#11-metodos-de-pago)
13. [Manejo de Errores](#12-manejo-de-errores)
14. [Checklist de Implementacion](#13-checklist-de-implementacion)

---

## Resumen: Lo que hace el Backend

El backend Laravel es robusto y maneja automaticamente:

| Funcionalidad | El Backend lo hace? | Donde |
|---------------|---------------------|-------|
| Recalcular precios al cambiar restaurante | SI | `CartService::updateRestaurant()` |
| Recalcular precios al cambiar tipo servicio | SI | `CartService::updateServiceType()` |
| Recalcular precios al cambiar direccion | SI | `CartService::updateDeliveryAddress()` |
| Validar geofence de delivery | SI | `DeliveryValidationService` |
| Aplicar promociones automaticamente | SI | `PromotionApplicationService` |
| Validar carrito antes de crear orden | SI | `CartService::validateCart()` |
| Validar items activos/disponibles | SI | En `validateCart()` y `addItem()` |

**El backend hace las validaciones. La app Flutter debe consumir correctamente los endpoints y manejar las respuestas/errores.**

---

# 1. Autenticacion

## Base URL: `/api/v1/auth`

### 1.1 Registro

**Endpoint:** `POST /api/v1/auth/register`

**Request:**
```json
{
  "first_name": "Juan",
  "last_name": "Perez",
  "email": "juan@example.com",
  "password": "Pass123!",
  "password_confirmation": "Pass123!",
  "phone": "+50212345678",
  "birth_date": "1990-05-15",
  "gender": "male",
  "device_identifier": "550e8400-e29b-41d4-a716-446655440000",
  "terms_accepted": true
}
```

| Campo | Tipo | Requerido | Validacion |
|-------|------|-----------|------------|
| `first_name` | string | Si | max: 255 |
| `last_name` | string | Si | max: 255 |
| `email` | string | Si | email valido, unico |
| `password` | string | Si | min 8 chars, 1 letra, 1 numero, 1 simbolo |
| `password_confirmation` | string | Si | debe coincidir |
| `phone` | string | Si | formato telefono |
| `birth_date` | date | Si | YYYY-MM-DD |
| `gender` | string | Si | `male`, `female`, `other` |
| `device_identifier` | UUID | Si | identificador unico dispositivo |
| `terms_accepted` | boolean | Si | debe ser true |

**Response 201:**
```json
{
  "message": "Registro exitoso. Por favor verifica tu email.",
  "data": {
    "customer": {
      "id": 1,
      "first_name": "Juan",
      "last_name": "Perez",
      "email": "juan@example.com",
      "email_verified_at": null
    },
    "token": "1|abc123xyz..."
  }
}
```

---

### 1.2 Login

**Endpoint:** `POST /api/v1/auth/login`

**Rate Limiting:** 5 intentos por minuto por email/IP

**Request:**
```json
{
  "email": "juan@example.com",
  "password": "SecurePass123!",
  "device_identifier": "550e8400-e29b-41d4-a716-446655440000"
}
```

**Response 200:**
```json
{
  "message": "Inicio de sesion exitoso.",
  "data": {
    "customer": { ... },
    "token": "1|abc123xyz..."
  }
}
```

**Response 409 - Cuenta OAuth:**
```json
{
  "message": "Esta cuenta usa autenticacion con google. Por favor inicia sesion con google.",
  "error_code": "oauth_account_required",
  "data": {
    "oauth_provider": "google",
    "email": "juan@example.com"
  }
}
```

**Response 409 - Cuenta eliminada recuperable:**
```json
{
  "message": "Encontramos una cuenta eliminada con este correo.",
  "error_code": "account_deleted_recoverable",
  "data": {
    "deleted_at": "2025-11-15T10:30:00Z",
    "days_until_permanent_deletion": 15,
    "points": 150,
    "can_reactivate": true
  }
}
```

---

### 1.3 Logout

**Endpoint:** `POST /api/v1/auth/logout`

**Headers:** `Authorization: Bearer {token}`

**Response 200:**
```json
{
  "message": "Sesion cerrada exitosamente."
}
```

---

### 1.4 Logout de Todos los Dispositivos

**Endpoint:** `POST /api/v1/auth/logout-all`

**Headers:** `Authorization: Bearer {token}`

Revoca todos los tokens. El cliente sera desconectado de todos los dispositivos.

---

### 1.5 Renovar Token

**Endpoint:** `POST /api/v1/auth/refresh`

**Headers:** `Authorization: Bearer {token}`

**Response 200:**
```json
{
  "message": "Token renovado exitosamente.",
  "data": {
    "token": "2|xyz456abc...",
    "customer": { ... }
  }
}
```

---

### 1.6 Recuperar Contrasena

**Solicitar reset:** `POST /api/v1/auth/forgot-password`
```json
{
  "email": "juan@example.com"
}
```

**Restablecer:** `POST /api/v1/auth/reset-password`
```json
{
  "email": "juan@example.com",
  "password": "NuevaPass123!",
  "password_confirmation": "NuevaPass123!",
  "token": "abc123resettoken456"
}
```

---

### 1.7 Verificacion de Email

**Reenviar verificacion:** `POST /api/v1/auth/email/resend`
```json
{
  "email": "juan@example.com"
}
```

La verificacion se hace via URL firmada enviada por correo.

---

### 1.8 OAuth - Google

**Redireccion:** `GET /api/v1/auth/oauth/google/redirect`

| Parametro | Tipo | Requerido | Descripcion |
|-----------|------|-----------|-------------|
| `action` | string | Si | `login` o `register` |
| `platform` | string | Si | `web` o `mobile` |
| `device_id` | UUID | Si (mobile) | identificador dispositivo |

```
GET /api/v1/auth/oauth/google/redirect?action=login&platform=mobile&device_id=550e8400-e29b-41d4-a716-446655440000
```

**Callback Mobile (deep link):**
```
subwayapp://oauth/callback?token=xxx&customer_id=123&is_new_customer=1
```

---

### 1.9 OAuth - Apple

**Redireccion:** `GET /api/v1/auth/oauth/apple/redirect`

Mismos parametros que Google. Apple solo envia nombre/email en la PRIMERA autenticacion.

---

### 1.10 Reactivar Cuenta Eliminada

**Endpoint:** `POST /api/v1/auth/reactivate`

Solo funciona si no han pasado mas de 30 dias desde la eliminacion.

```json
{
  "email": "juan@example.com",
  "password": "SecurePass123!",
  "device_identifier": "550e8400-e29b-41d4-a716-446655440000"
}
```

**Response 200:**
```json
{
  "message": "Cuenta reactivada exitosamente. Bienvenido de nuevo.",
  "data": {
    "customer": { ... },
    "token": "1|abc123xyz...",
    "points": 150
  }
}
```

---

# 2. Sistema de Precios

### Campos de Redencion por Puntos

Productos y variantes pueden ser canjeables por puntos:

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| `is_redeemable` | boolean | Si el producto/variante es canjeable por puntos |
| `points_cost` | integer | Costo en puntos (solo si `is_redeemable: true`) |

**Para productos SIN variantes:**
```json
{
  "id": 5,
  "name": "Cookie",
  "has_variants": false,
  "is_redeemable": true,
  "points_cost": 50
}
```

**Para productos CON variantes:** Cada variante tiene sus propios valores de redencion.
```json
{
  "id": 10,
  "name": "Sub Italian BMT",
  "has_variants": true,
  "variants": [
    {
      "id": 25,
      "name": "15cm",
      "is_redeemable": true,
      "points_cost": 100
    },
    {
      "id": 26,
      "name": "30cm",
      "is_redeemable": true,
      "points_cost": 150
    }
  ]
}
```

**Nota:** Los productos con variantes NO tienen `is_redeemable` ni `points_cost` a nivel de producto - estos campos solo aparecen en cada variante.

---

### Los 4 Campos de Precio

Cada producto y combo tiene 4 precios diferentes:

| Campo | Zona | Servicio |
|-------|------|----------|
| `precio_pickup_capital` | Capital | Pickup |
| `precio_domicilio_capital` | Capital | Delivery |
| `precio_pickup_interior` | Interior | Pickup |
| `precio_domicilio_interior` | Interior | Delivery |

### Precios en el Menu

El menu retorna TODOS los precios en cada producto:

```json
{
  "price": 45.00,
  "prices": {
    "pickup_capital": 45.00,
    "delivery_capital": 48.00,
    "pickup_interior": 47.00,
    "delivery_interior": 50.00
  }
}
```

**Flutter debe mostrar el precio correcto segun el modo seleccionado:**

| Modo del Usuario | Campo a mostrar | Como obtener la zona |
|------------------|-----------------|---------------------|
| Solo ver menu | `price` | No necesita zona |
| Pickup | `prices.pickup_{zona}` | De `restaurant.zone` |
| Delivery | `prices.delivery_{zona}` | De `address.zone` |

### Donde obtener la zona

**Restaurantes** (`GET /api/v1/restaurants`):
```json
{
  "id": 2,
  "name": "Subway Pradera",
  "zone": "capital"
}
```

**Direcciones** (`GET /api/v1/addresses`):
```json
{
  "id": 5,
  "label": "Casa",
  "zone": "capital"
}
```

---

# 3. Menu y Promociones

## 3.1 Sub del Dia

Sub del Dia es una promocion con precio especial fijo que aplica a ciertos productos en dias especificos de la semana.

### Endpoint

**GET** `/api/v1/menu/promotions/daily`

| Parametro | Tipo | Default | Descripcion |
|-----------|------|---------|-------------|
| `today` | integer | 0 | Si es `1`, filtra items validos en el momento actual |

### Response Completo

```json
{
  "data": {
    "promotion": {
      "id": 3,
      "name": "Sub del Dia",
      "type": "daily_special",
      "items": [
        {
          "id": 14,
          "product_id": 5,
          "variant_id": 12,
          "special_price_pickup_capital": 22.00,
          "special_price_delivery_capital": 22.00,
          "special_price_pickup_interior": 24.00,
          "special_price_delivery_interior": 24.00,
          "discount_percentage": null,
          "discounted_prices": {
            "pickup_capital": 22.00,
            "delivery_capital": 22.00,
            "pickup_interior": 24.00,
            "delivery_interior": 24.00
          },
          "weekdays": [2],
          "time_from": "11:00",
          "time_until": "15:00",
          "product": {
            "id": 5,
            "name": "Sub de Pollo",
            "image_url": "https://..."
          },
          "variant": {
            "id": 12,
            "name": "15 cm",
            "size": "15cm"
          }
        }
      ]
    },
    "today": {
      "weekday": 2,
      "weekday_name": "Martes",
      "date": "2024-01-15"
    }
  }
}
```

### Logica de Precios Sub del Dia

Se configuran **4 precios independientes** en el admin:

| Campo | Descripcion | Ejemplo |
|-------|-------------|---------|
| `special_price_pickup_capital` | Pickup en zona capital | Q22.00 |
| `special_price_delivery_capital` | Delivery en zona capital | Q22.00 |
| `special_price_pickup_interior` | Pickup en zona interior | Q24.00 |
| `special_price_delivery_interior` | Delivery en zona interior | Q24.00 |

El API tambien devuelve `discounted_prices` con los mismos 4 valores en formato estandarizado:

```json
"discounted_prices": {
  "pickup_capital": 22.00,
  "delivery_capital": 22.00,
  "pickup_interior": 24.00,
  "delivery_interior": 24.00
}
```

**Nota:** Normalmente pickup y delivery tienen el mismo precio por zona, pero el sistema permite configurarlos diferente si se requiere.

### Como Consumir en Flutter

```dart
// 1. Obtener el precio segun zona y servicio del usuario
final zone = userConfig.zone;           // 'capital' o 'interior'
final service = userConfig.serviceType; // 'pickup' o 'delivery'

// 2. Construir la key del precio
final priceKey = '${service}_$zone';    // ej: 'pickup_capital'

// 3. Obtener el precio de discounted_prices
final specialPrice = item.discountedPrices[priceKey];
```

### Filtrado por Dia

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| `weekdays` | array[int] | Dias de la semana (ISO-8601: 1=Lunes, 7=Domingo) |
| `today.weekday` | int | Dia actual |
| `today.weekday_name` | string | Nombre en espanol ("Martes") |

**Ejemplo:** Si `weekdays: [2]`, el item solo aplica los **Martes**.

### UI Recomendada

```
┌─────────────────────────┐
│  [SUB DEL DIA]          │  ← Badge amarillo
│                         │
│   (imagen producto)     │
│                         │
├─────────────────────────┤
│  Sub de Pollo 15cm      │
│  ~~Q45.00~~ Q22.00      │  ← precio original vs discounted_prices
│  Solo Martes            │  ← mostrar dias de weekdays
└─────────────────────────┘
```

### Campos Importantes para Flutter

| Campo | Uso |
|-------|-----|
| `type == "daily_special"` | Identificar que es Sub del Dia |
| `discounted_prices.pickup_capital` | Precio con descuento (usar segun zona/servicio) |
| `weekdays` | Filtrar items por dia actual |
| `today.weekday_name` | Mostrar "Sub del Dia - Martes" |
| `product.name` | Nombre del producto |
| `product.image_url` | Imagen del producto |

---

## 3.2 Combos y Combinados

### Diferencia: Combos vs Combinados

| Aspecto | Combos (Permanentes) | Combinados (Temporales) |
|---------|----------------------|-------------------------|
| Endpoint | `GET /api/v1/menu/combos` | `GET /api/v1/menu/promotions/combinados` |
| Vigencia | Siempre disponibles | Tienen fechas, horarios, dias |
| Tabla | `combos` | `promotions` (type=bundle_special) |
| Precios | 4 precios (pickup/delivery x capital/interior) | 2 precios (bundle_capital/interior) |

### Importante: No participan en otras promociones

Los **Combos y Combinados NO participan** en promociones como:
- Descuento porcentaje
- Sub del Dia
- 2x1

Los combos/combinados **ya tienen su propio precio especial** configurado.

---

### 3.2.1 Combos Permanentes

**Endpoint:** `GET /api/v1/menu/combos`

```json
{
  "data": {
    "combos": [
      {
        "id": 1,
        "name": "Combo Sub + Bebida",
        "description": "Incluye sub 15cm + bebida mediana",
        "image_url": "https://...",
        "price": 55.00,
        "prices": {
          "pickup_capital": 55.00,
          "delivery_capital": 60.00,
          "pickup_interior": 52.00,
          "delivery_interior": 58.00
        },
        "is_redeemable": false,
        "points_cost": null,
        "is_available": true,
        "items": [
          {
            "id": 1,
            "is_choice_group": true,
            "choice_label": "Elige tu Sub",
            "quantity": 1,
            "product": null,
            "variant": null,
            "options": [
              {
                "id": 1,
                "product_id": 10,
                "variant_id": 25,
                "product_name": "Italian BMT",
                "variant_name": "15cm"
              },
              {
                "id": 2,
                "product_id": 11,
                "variant_id": 26,
                "product_name": "Subway Club",
                "variant_name": "15cm"
              }
            ]
          },
          {
            "id": 2,
            "is_choice_group": false,
            "choice_label": null,
            "quantity": 1,
            "product": { "id": 50, "name": "Bebida Mediana" },
            "variant": null,
            "options": null
          }
        ]
      }
    ]
  }
}
```

---

### 3.2.2 Combinados (Promociones Temporales)

**Endpoint:** `GET /api/v1/menu/promotions/combinados`

```json
{
  "data": {
    "promotions": [
      {
        "id": 7,
        "name": "Combo Verano",
        "description": "2 Subs + 2 Bebidas",
        "type": "bundle_special",
        "image_url": "https://...",
        "special_bundle_price_capital": 150.00,
        "special_bundle_price_interior": 160.00,
        "valid_from": "2026-01-01",
        "valid_until": "2026-03-31",
        "time_from": "11:00",
        "time_until": "22:00",
        "weekdays": [1, 2, 3, 4, 5],
        "bundle_items": [
          {
            "id": 1,
            "is_choice_group": true,
            "choice_label": "Elige tu primer sub",
            "quantity": 1,
            "product": null,
            "variant": null,
            "options": [
              {
                "id": 1,
                "product_id": 10,
                "variant_id": 25,
                "product_name": "Italian B.M.T.",
                "variant_name": "15cm"
              }
            ]
          },
          {
            "id": 2,
            "is_choice_group": false,
            "choice_label": null,
            "quantity": 2,
            "product": { "id": 50, "name": "Bebida Mediana" },
            "variant": null,
            "options": null
          }
        ]
      }
    ]
  }
}
```

---

### 3.2.3 Estructura de Items

| Campo | Descripcion |
|-------|-------------|
| `is_choice_group` | `true` = usuario debe elegir, `false` = producto fijo |
| `choice_label` | Texto a mostrar: "Elige tu Sub" |
| `quantity` | Cantidad de este item en el combo |
| `options` | Array de opciones cuando `is_choice_group: true` |
| `product` | Producto fijo cuando `is_choice_group: false` |

### 3.2.4 Flujo para Flutter: Personalizar Producto del Combo

Cuando el usuario elige un producto de un `choice_group`, debe poder personalizarlo (pan, vegetales, salsas).

**Paso 1:** Usuario selecciona opcion del choice_group
```dart
// Usuario elige "Italian BMT 15cm" del combo
final selectedOption = bundleItem.options[0];
final productId = selectedOption.productId;
```

**Paso 2:** Consultar secciones del producto elegido
```
GET /api/v1/menu/products/{productId}
```

**Paso 3:** Mostrar secciones de personalizacion
```json
{
  "data": {
    "product": {
      "id": 10,
      "name": "Italian B.M.T.",
      "sections": [
        {
          "id": 1,
          "name": "Tipo de Pan",
          "is_required": true,
          "min_selections": 1,
          "max_selections": 1,
          "options": [
            { "id": 1, "name": "Pan Italiano", "price": 0 },
            { "id": 2, "name": "Pan Integral", "price": 0 }
          ]
        },
        {
          "id": 2,
          "name": "Vegetales",
          "is_required": false,
          "min_selections": 0,
          "max_selections": 10,
          "options": [
            { "id": 10, "name": "Lechuga", "price": 0 },
            { "id": 11, "name": "Tomate", "price": 0 }
          ]
        }
      ]
    }
  }
}
```

### 3.2.5 UI Recomendada para Combos

```
┌─────────────────────────────────────┐
│  COMBO SUB + BEBIDA      Q55.00     │
├─────────────────────────────────────┤
│                                     │
│  1. Elige tu Sub:                   │
│     ○ Italian BMT 15cm              │
│     ● Subway Club 15cm  ← selected  │
│     ○ Pollo Teriyaki 15cm           │
│                                     │
│  [Personalizar Sub →]               │  ← abre secciones
│                                     │
│  2. Bebida Mediana (incluida)       │
│     Coca-Cola                       │
│                                     │
├─────────────────────────────────────┤
│  [AGREGAR AL CARRITO]               │
└─────────────────────────────────────┘
```

### 3.2.6 Obtener Precio segun Zona/Servicio

**Para Combos (4 precios):**
```dart
final priceKey = '${serviceType}_$zone';  // ej: 'pickup_capital'
final price = combo.prices[priceKey];
```

**Para Combinados (2 precios):**
```dart
// Solo diferencia por zona, no por tipo de servicio
final price = zone == 'capital'
    ? combinado.specialBundlePriceCapital
    : combinado.specialBundlePriceInterior;
```

---

## 3.3 Productos Destacados (Featured)

**Endpoint:** `GET /api/v1/menu/featured`

| Parametro | Default | Descripcion |
|-----------|---------|-------------|
| `limit` | 10 | Items por tipo de badge (max: 50) |

**Response 200:**
```json
{
  "data": {
    "badge_types": [
      { "id": 1, "name": "Popular", "color": "orange", "sort_order": 1 },
      { "id": 2, "name": "Nuevo", "color": "green", "sort_order": 2 }
    ],
    "products": [
      {
        "id": 5,
        "name": "Italian B.M.T.",
        "badges": [
          {
            "badge_type": { "id": 1, "name": "Popular", "color": "orange", "text_color": "white" },
            "is_valid_now": true
          }
        ],
        "active_promotion": {
          "id": 3,
          "type": "daily_special",
          "discount_percent": null,
          "discounted_prices": { ... }
        }
      }
    ],
    "combos": [ ... ]
  }
}
```

**Uso recomendado:** Crear carruseles en Home agrupados por `badge_type`.

---

## 3.4 Banners Promocionales

**Endpoint:** `GET /api/v1/menu/banners`

**Response 200:**
```json
{
  "data": {
    "horizontal": [
      {
        "id": 1,
        "title": "Sub del Dia a Q22",
        "image_url": "https://...",
        "display_seconds": 5,
        "link": {
          "type": "product",
          "id": 5,
          "url": null
        }
      }
    ],
    "vertical": [ ... ]
  }
}
```

**Link Types para navegacion:**

| Tipo | Accion |
|------|--------|
| `product` | Navegar a detalle de producto |
| `combo` | Navegar a detalle de combo |
| `category` | Navegar a categoria |
| `promotion` | Navegar a detalle de promocion |
| `url` | Abrir URL externa |
| `null` | Sin accion |

---

## 3.5 Badges en Productos

```json
{
  "badges": [
    {
      "badge_type": {
        "name": "Popular",
        "color": "#FF5722",
        "text_color": "#FFFFFF"
      },
      "is_valid_now": true
    }
  ]
}
```

**Siempre filtrar por `is_valid_now: true`**

---

## 3.6 Promociones y Precios con Descuento

Cuando un producto tiene una promocion activa, el API **ya calcula los precios con descuento**. No necesitas calcular nada en Flutter.

### Estructura Completa de Respuesta

```json
{
  "id": 5,
  "name": "Italian B.M.T.",
  "has_variants": false,

  "price": 45.00,
  "prices": {
    "pickup_capital": 45.00,
    "delivery_capital": 48.00,
    "pickup_interior": 47.00,
    "delivery_interior": 50.00
  },

  "active_promotion": {
    "id": 8,
    "type": "percentage_discount",
    "name": "20% de descuento",
    "discount_percent": 20,
    "special_prices": {
      "pickup_capital": null,
      "delivery_capital": null,
      "pickup_interior": null,
      "delivery_interior": null
    },
    "discounted_prices": {
      "pickup_capital": 36.00,
      "delivery_capital": 38.40,
      "pickup_interior": 37.60,
      "delivery_interior": 40.00
    },
    "badge": {
      "name": "20% OFF",
      "color": "#FF5733",
      "text_color": "#FFFFFF"
    }
  }
}
```

### Campos Importantes

| Campo | Descripcion |
|-------|-------------|
| `prices` | Precios ORIGINALES (para mostrar tachado) |
| `active_promotion.discount_percent` | Porcentaje de descuento (para badge) |
| `active_promotion.discounted_prices` | Precios YA CALCULADOS con descuento |
| `active_promotion.badge` | Colores para el badge visual |

### Logica del Backend

El backend calcula asi:
```
precio_con_descuento = precio_original * (1 - porcentaje/100)
```

Ejemplo con 20%:
```
45.00 * (1 - 20/100) = 45.00 * 0.80 = 36.00
```

### Como Mostrar en la Card

| Elemento | Campo del API | Ejemplo |
|----------|---------------|---------|
| Precio original (tachado) | `prices.pickup_capital` | ~~Q45.00~~ |
| Precio con descuento | `active_promotion.discounted_prices.pickup_capital` | **Q36.00** |
| Badge de descuento | `active_promotion.discount_percent` | **-20%** |
| Color del badge | `active_promotion.badge.color` | `#FF5733` |

### UI Recomendada (Estilo Amazon/Temu)

```
┌─────────────────────────┐
│  [BADGE: -20%]          │  ← active_promotion.badge
│                         │
│   (imagen producto)     │
│                         │
├─────────────────────────┤
│  Italian B.M.T.         │
│  ~~Q45.00~~ Q36.00      │  ← prices vs discounted_prices
└─────────────────────────┘
```

### Tipos de Promocion

| Tipo | Como viene en API |
|------|-------------------|
| Descuento porcentaje | `discount_percent: 20`, `discounted_prices` calculados por el backend |
| Precio especial fijo | `special_prices` con 4 precios, `discounted_prices` con esos valores |
| Sub del Dia | Usa `special_prices` con 4 precios fijos |
| 2x1 | Se aplica en el carrito, no a nivel producto |

### Ejemplo: Producto SIN Promocion

```json
{
  "id": 10,
  "name": "Cookie",
  "prices": {
    "pickup_capital": 8.00,
    "delivery_capital": 8.00
  },
  "active_promotion": null
}
```

Cuando `active_promotion` es `null`, mostrar solo el precio normal sin descuento.

**Nota:** Las promociones 2x1 se aplican en el carrito, no a nivel producto individual.

---

## 3.7 Promocion 2x1

Las promociones 2x1 **NO** se muestran a nivel de producto. Se aplican automaticamente en el carrito cuando hay 2 o mas productos que califican.

### Como Funciona

1. El cliente agrega productos al carrito normalmente
2. El backend detecta si hay promocion 2x1 activa para esos productos
3. Si hay 2+ productos que califican, el **mas barato** se pone a Q0
4. El descuento aparece en el response del carrito

### Logica del Backend

```
1. Filtrar items que califican para el 2x1 (por categoria/producto)
2. Ordenar por precio de MENOR a MAYOR
3. Calcular items gratis: floor(cantidad / 2)
4. El MAS BARATO se descuenta (precio = 0)
```

### Ejemplo: 2 productos diferentes

```
Carrito:
- Sub BMT: Q50 x 1
- Sub Pollo: Q40 x 1

Calculo:
- Total items: 2
- Items gratis: floor(2/2) = 1
- Ordenados por precio: [Q40, Q50]
- Se descuenta Q40 (el mas barato)

Resultado:
- Sub BMT: Q50 (paga)
- Sub Pollo: Q0 (gratis)
- Total: Q50
```

### Ejemplo: 4 productos iguales

```
Carrito:
- Sub BMT: Q50 x 4

Calculo:
- Total items: 4
- Items gratis: floor(4/2) = 2
- Descuento: Q50 x 2 = Q100

Resultado:
- Subtotal: Q200
- Descuento: Q100
- Total: Q100 (paga 2, lleva 4)
```

### Response del Carrito con 2x1

```json
{
  "items": [
    {
      "id": 1,
      "name": "Sub BMT",
      "quantity": 1,
      "unit_price": 50.00,
      "subtotal": 50.00,
      "discount_amount": 0,
      "final_price": 50.00,
      "applied_promotion": null
    },
    {
      "id": 2,
      "name": "Sub Pollo",
      "quantity": 1,
      "unit_price": 40.00,
      "subtotal": 40.00,
      "discount_amount": 40.00,
      "final_price": 0.00,
      "applied_promotion": {
        "id": 5,
        "name": "2x1 en Subs",
        "type": "two_for_one",
        "value": "2x1"
      }
    }
  ],
  "summary": {
    "subtotal": "90.00",
    "total_discount": "40.00",
    "total": "50.00"
  }
}
```

### UI Recomendada en Carrito

```
┌─────────────────────────────────────┐
│ Sub BMT                      Q50.00 │
│ Sub Pollo    [2x1]    ~~Q40~~ Q0.00 │  ← mostrar badge y precio tachado
├─────────────────────────────────────┤
│ Subtotal                     Q90.00 │
│ Descuento 2x1               -Q40.00 │  ← mostrar en rojo/verde
│ TOTAL                        Q50.00 │
└─────────────────────────────────────┘
```

### Puntos Clave para Flutter

| Aspecto | Detalle |
|---------|---------|
| Donde se aplica | Solo en carrito, NO en menu |
| Quien calcula | El backend automaticamente |
| Cual es gratis | El de **menor precio** |
| Como detectar | `applied_promotion.type == "two_for_one"` |
| Como mostrar | Badge "2x1" + precio tachado + Q0.00 |

---

# 4. Endpoints del Carrito

## 4.1 Obtener Carrito

**Endpoint:** `GET /api/v1/cart`

**Response:**
```json
{
  "data": {
    "id": 1,
    "restaurant": { "id": 2, "name": "Subway Pradera" },
    "service_type": "pickup",
    "zone": "capital",
    "items": [
      {
        "id": 1,
        "quantity": 2,
        "unit_price": 45.00,
        "subtotal": 90.00,
        "discount_amount": 5.00,
        "final_price": 85.00,
        "applied_promotion": {
          "id": 1,
          "name": "Descuento 10%",
          "type": "percentage_discount"
        }
      }
    ],
    "summary": {
      "subtotal": "90.00",
      "total_discount": "5.00",
      "total": "85.00",
      "points_to_earn": 8
    },
    "can_checkout": true,
    "validation_messages": []
  }
}
```

**Campos importantes:**
- `can_checkout` - Si es `true`, puede crear orden
- `validation_messages` - Errores si hay problemas

---

## 4.2 Agregar Item

**Endpoint:** `POST /api/v1/cart/items`

**Para producto:**
```json
{
  "product_id": 10,
  "variant_id": 25,
  "quantity": 2,
  "selected_options": [
    { "section_id": 1, "option_id": 3 }
  ],
  "notes": "Sin cebolla"
}
```

**Para combo:**
```json
{
  "combo_id": 5,
  "quantity": 1,
  "combo_selections": [ ... ]
}
```

---

## 4.3 Seleccionar Restaurante (Pickup)

**Endpoint:** `PUT /api/v1/cart/restaurant`

```json
{
  "restaurant_id": 2
}
```

**El backend:**
1. Asigna `service_type = 'pickup'`
2. Asigna zona segun `restaurant.price_location`
3. Recalcula todos los precios

---

## 4.4 Asignar Direccion (Delivery)

**Endpoint:** `PUT /api/v1/cart/delivery-address`

```json
{
  "delivery_address_id": 5
}
```

**Si esta fuera de zona (422):**
```json
{
  "message": "No tenemos cobertura de delivery en esta ubicacion",
  "error_code": "ADDRESS_OUTSIDE_DELIVERY_ZONE",
  "data": {
    "nearest_pickup_locations": [
      { "id": 5, "name": "Subway Miraflores", "distance_km": 2.5 }
    ]
  }
}
```

---

# 5. Crear Orden

**Endpoint:** `POST /api/v1/orders`

| Campo | Tipo | Requerido | Valores |
|-------|------|-----------|---------|
| `restaurant_id` | integer | SI | ID del restaurante |
| `service_type` | string | SI | `"pickup"` o `"delivery"` |
| `payment_method` | string | SI | `"cash"` o `"card"` |
| `delivery_address_id` | integer | Solo delivery | ID direccion |
| `nit_id` | integer | NO | ID del NIT |
| `notes` | string | NO | Max 500 chars |

**Request Pickup:**
```json
{
  "restaurant_id": 2,
  "service_type": "pickup",
  "payment_method": "cash"
}
```

**Request Delivery:**
```json
{
  "restaurant_id": 2,
  "service_type": "delivery",
  "payment_method": "card",
  "delivery_address_id": 5
}
```

**Response 201:**
```json
{
  "data": {
    "id": 123,
    "order_number": "ORD-20260107-0001",
    "status": "pending",
    "summary": {
      "subtotal": 90.00,
      "discount_total": 5.00,
      "total": 85.00
    },
    "points": {
      "earned": 8,
      "redeemed": 0
    }
  },
  "message": "Orden creada exitosamente"
}
```

---

# 6. Gestion de Ordenes

## Estados de Orden

| Estado | Descripcion |
|--------|-------------|
| `pending` | Pendiente de confirmacion |
| `confirmed` | Confirmada por restaurante |
| `preparing` | En preparacion |
| `ready` | Lista para recoger/entregar |
| `out_for_delivery` | En camino (solo delivery) |
| `delivered` | Entregada (solo delivery) |
| `completed` | Completada |
| `cancelled` | Cancelada |

---

## 6.1 Historial de Ordenes

**Endpoint:** `GET /api/v1/orders`

| Parametro | Tipo | Default | Descripcion |
|-----------|------|---------|-------------|
| `per_page` | integer | 15 | Items por pagina (max: 50) |
| `status` | string | - | Filtrar por estado |

---

## 6.2 Ordenes Activas

**Endpoint:** `GET /api/v1/orders/active`

Retorna ordenes en estados: `pending`, `confirmed`, `preparing`, `ready`, `out_for_delivery`, `delivered`

---

## 6.3 Detalle de Orden

**Endpoint:** `GET /api/v1/orders/{id}`

**Response incluye:** items, summary, status, payment, review (si existe)

---

## 6.4 Tracking de Orden

**Endpoint:** `GET /api/v1/orders/{id}/track`

```json
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

---

## 6.5 Cancelar Orden

**Endpoint:** `POST /api/v1/orders/{id}/cancel`

```json
{
  "reason": "Cambie de opinion"
}
```

**Solo se puede cancelar en estados:** `pending`, `confirmed`

---

## 6.6 Reordenar

**Endpoint:** `POST /api/v1/orders/{id}/reorder`

**Que hace:**
1. Toma los items de una orden anterior
2. Crea un NUEVO carrito con esos items
3. El usuario puede modificar y hacer checkout

**Muy util** para clientes que quieren repetir un pedido anterior.

**Response:**
```json
{
  "data": {
    "cart_id": 456,
    "items_count": 3
  },
  "message": "Carrito creado exitosamente con los items de la orden"
}
```

**Nota:** Items no disponibles (productos inactivos) no se agregan.

---

## 6.7 Resena de Orden

**Endpoint:** `POST /api/v1/orders/{id}/review`

**Solo disponible despues de que el pedido este `completed` o `delivered`.**

```json
{
  "overall_rating": 5,
  "quality_rating": 4,
  "speed_rating": 5,
  "service_rating": 4,
  "comment": "Muy buena comida!"
}
```

| Campo | Requerido | Valores |
|-------|-----------|---------|
| `overall_rating` | SI | 1-5 |
| `quality_rating` | NO | 1-5 |
| `speed_rating` | NO | 1-5 |
| `service_rating` | NO | 1-5 |
| `comment` | NO | max 1000 chars |

---

## 6.8 Ordenes Recientes

**Endpoint:** `GET /api/v1/me/recent-orders`

Retorna las ultimas 5 ordenes completadas para facilitar reordenado.

```json
{
  "data": [
    {
      "id": 123,
      "order_number": "ORD-20251215-0001",
      "total": 85.00,
      "items_summary": "Italian BMT, Coca-Cola...",
      "can_reorder": true
    }
  ]
}
```

---

# 7. Perfil del Cliente

## 7.1 Obtener Perfil

**Endpoint:** `GET /api/v1/profile`

**Response:**
```json
{
  "data": {
    "customer": {
      "id": 1,
      "first_name": "Juan",
      "last_name": "Perez",
      "full_name": "Juan Perez",
      "email": "juan@example.com",
      "email_verified_at": "2025-01-01T00:00:00Z",
      "phone": "+50212345678",
      "birth_date": "1990-05-15",
      "gender": "male",
      "points": 500,
      "has_password": true,
      "has_google_linked": true,
      "has_apple_linked": false,
      "customer_type": {
        "name": "Gold",
        "points_multiplier": 1.5
      },
      "addresses": [ ... ],
      "nits": [ ... ]
    }
  }
}
```

---

## 7.2 Actualizar Perfil

**Endpoint:** `PUT /api/v1/profile`

```json
{
  "first_name": "Juan Carlos",
  "phone": "+50287654321",
  "email_offers_enabled": false
}
```

| Campo | Validacion |
|-------|------------|
| `first_name` | max: 255 |
| `last_name` | max: 255 |
| `email` | email valido, unico |
| `phone` | max: 20 chars |
| `birth_date` | YYYY-MM-DD, anterior a hoy |
| `gender` | `male`, `female`, `other` |
| `email_offers_enabled` | boolean |

---

## 7.3 Cambiar Contrasena

**Endpoint:** `PUT /api/v1/profile/password`

**Si tiene contrasena existente:**
```json
{
  "current_password": "OldPass123!",
  "password": "NewPass456!",
  "password_confirmation": "NewPass456!"
}
```

**Si es cuenta OAuth creando primera contrasena:**
```json
{
  "password": "MiPrimeraPass123!",
  "password_confirmation": "MiPrimeraPass123!"
}
```

---

## 7.4 Avatar (Opcional)

**Actualizar:** `POST /api/v1/profile/avatar`
```json
{
  "avatar": "https://example.com/avatar.jpg"
}
```

**Eliminar:** `DELETE /api/v1/profile/avatar`

**Nota:** El avatar no es esencial para la funcionalidad de la app.

---

## 7.5 Eliminar Cuenta

**Endpoint:** `DELETE /api/v1/profile`

Soft delete con 30 dias de gracia para reactivar.

**Response:**
```json
{
  "message": "Cuenta eliminada exitosamente.",
  "data": {
    "can_reactivate_until": "2025-02-06T10:30:00Z",
    "days_to_reactivate": 30
  }
}
```

---

## 7.6 Direcciones

**Listar:** `GET /api/v1/addresses`

**Crear:** `POST /api/v1/addresses`
```json
{
  "label": "Casa",
  "address_line": "10 Calle 5-20 Zona 10",
  "latitude": 14.6017,
  "longitude": -90.5250,
  "delivery_notes": "Casa amarilla",
  "is_default": true
}
```

**Actualizar:** `PUT /api/v1/addresses/{id}`

**Eliminar:** `DELETE /api/v1/addresses/{id}`

**Marcar predeterminada:** `POST /api/v1/addresses/{id}/set-default`

**Validar ubicacion:** `POST /api/v1/addresses/validate`
```json
{
  "latitude": 14.6017,
  "longitude": -90.5250
}
```

Response indica si tiene cobertura de delivery y que restaurante asignar.

---

## 7.7 NITs (Facturacion)

**Listar:** `GET /api/v1/nits`

**Crear:** `POST /api/v1/nits`
```json
{
  "nit": "123456789",
  "nit_name": "Juan Perez",
  "nit_type": "personal",
  "is_default": true
}
```

| Campo | Validacion |
|-------|------------|
| `nit` | max: 20, unico por cliente |
| `nit_type` | `personal`, `company`, `other` |
| `nit_name` | max: 255 |

**Actualizar:** `PUT /api/v1/nits/{id}`

**Eliminar:** `DELETE /api/v1/nits/{id}`

**Marcar predeterminado:** `POST /api/v1/nits/{id}/set-default`

---

# 8. Sistema de Puntos

## 8.1 Balance de Puntos

**Endpoint:** `GET /api/v1/points/balance`

```json
{
  "data": {
    "points_balance": 250,
    "points_updated_at": "2025-12-10T15:30:00Z",
    "points_value_in_currency": 25.00,
    "conversion_rate": {
      "quetzales_per_point": 10,
      "point_value": 0.10
    }
  }
}
```

---

## 8.2 Historial de Puntos

**Endpoint:** `GET /api/v1/points/history`

| Parametro | Descripcion |
|-----------|-------------|
| `page` | Numero de pagina |
| `type` | Filtrar: `earned`, `redeemed`, `expired`, `bonus`, `adjustment` |

```json
{
  "data": [
    {
      "id": 123,
      "points": 9,
      "type": "earned",
      "description": "Puntos ganados en orden #ORD-20251215-0001",
      "created_at": "2025-12-15T15:30:00Z"
    },
    {
      "id": 122,
      "points": -50,
      "type": "redeemed",
      "description": "Puntos canjeados en orden #ORD-20251214-0003",
      "created_at": "2025-12-14T12:00:00Z"
    }
  ]
}
```

---

## 8.3 Expiracion de Puntos

**Endpoint:** `GET /api/v1/points/expiring`

```json
{
  "data": {
    "points_balance": 250,
    "will_expire": true,
    "expires_at": "2026-06-15T10:30:00Z",
    "days_until_expiration": 45,
    "warning_level": "warning",
    "message": "En 45 dias todos tus puntos expiraran si no realizas una compra."
  }
}
```

| warning_level | Significado |
|---------------|-------------|
| `critical` | <= 7 dias |
| `warning` | <= 30 dias |
| `safe` | > 30 dias |
| `none` | Sin puntos |

---

# 9. Favoritos

## 9.1 Listar Favoritos

**Endpoint:** `GET /api/v1/favorites`

```json
{
  "data": [
    {
      "id": 1,
      "favorable_type": "Product",
      "favorable_id": 42,
      "favorable": {
        "id": 42,
        "name": "Italian BMT",
        "price": 45.00,
        "image_url": "/storage/products/italian-bmt.jpg"
      },
      "created_at": "2025-12-10T15:30:00Z"
    }
  ]
}
```

---

## 9.2 Agregar a Favoritos

**Endpoint:** `POST /api/v1/favorites`

```json
{
  "favorable_type": "product",
  "favorable_id": 42
}
```

| favorable_type | Descripcion |
|----------------|-------------|
| `product` | Producto |
| `combo` | Combo |

---

## 9.3 Eliminar de Favoritos

**Endpoint:** `DELETE /api/v1/favorites/{type}/{id}`

Ejemplo: `DELETE /api/v1/favorites/product/42`

---

# 10. Soporte y Documentos Legales

## 10.1 Terminos y Condiciones

**Endpoint:** `GET /api/v1/legal/terms`

**No requiere autenticacion.**

```json
{
  "data": {
    "version": "1.0",
    "content_html": "<h1>Terminos...</h1>",
    "published_at": "2024-01-15T10:30:00Z"
  }
}
```

---

## 10.2 Politica de Privacidad

**Endpoint:** `GET /api/v1/legal/privacy`

**No requiere autenticacion.**

---

## 10.3 Motivos de Soporte

**Endpoint:** `GET /api/v1/support/reasons`

```json
{
  "data": {
    "reasons": [
      { "id": 1, "name": "Problema con mi pedido", "slug": "order_issue" },
      { "id": 2, "name": "Consulta sobre puntos", "slug": "points_inquiry" }
    ]
  }
}
```

---

## 10.4 Listar Tickets

**Endpoint:** `GET /api/v1/support/tickets`

```json
{
  "data": {
    "tickets": [
      {
        "id": 1,
        "reason": { "name": "Problema con mi pedido" },
        "status": "open",
        "priority": "medium",
        "unread_count": 2,
        "latest_message": {
          "message": "Hemos recibido tu solicitud...",
          "is_from_admin": true
        }
      }
    ]
  }
}
```

| status | Descripcion |
|--------|-------------|
| `open` | Abierto |
| `in_progress` | En proceso |
| `resolved` | Resuelto |
| `closed` | Cerrado |

---

## 10.5 Crear Ticket

**Endpoint:** `POST /api/v1/support/tickets`

**Content-Type:** `multipart/form-data`

| Campo | Tipo | Requerido | Descripcion |
|-------|------|-----------|-------------|
| `reason_id` | integer | Si | ID del motivo |
| `message` | string | Si | max 5000 chars |
| `attachments[]` | file[] | No | max 4 imagenes, 5MB c/u |

---

## 10.6 Ver Ticket

**Endpoint:** `GET /api/v1/support/tickets/{id}`

Incluye todos los mensajes. Marca automaticamente como leidos.

---

## 10.7 Enviar Mensaje

**Endpoint:** `POST /api/v1/support/tickets/{id}/messages`

**Content-Type:** `multipart/form-data`

```
message: "Aqui te envio la foto"
attachments[]: foto.jpg
```

**No se puede enviar mensajes a tickets cerrados.**

---

# 11. Metodos de Pago

| Valor | Descripcion |
|-------|-------------|
| `cash` | Efectivo - pago al recibir/recoger |
| `card` | Tarjeta POS - pago con terminal al recibir/recoger |

**Solo estos 2 metodos. No hay pasarela de pago en linea.**

```dart
enum PaymentMethod {
  cash,  // "Efectivo"
  card;  // "Tarjeta/POS"
}
```

---

# 12. Manejo de Errores

| Error | Codigo | Accion |
|-------|--------|--------|
| Direccion fuera de zona | `ADDRESS_OUTSIDE_DELIVERY_ZONE` | Mostrar restaurantes cercanos |
| Producto no disponible | 422 | Refrescar menu |
| Carrito invalido | `can_checkout = false` | Mostrar `validation_messages` |
| Cuenta OAuth requerida | `oauth_account_required` | Redirigir a login OAuth |
| Cuenta eliminada | `account_deleted_recoverable` | Ofrecer reactivacion |
| Token invalido | 401 | Redirigir a login |

---

# 13. Checklist de Implementacion

## Autenticacion
- [ ] Registro con validacion de campos
- [ ] Login con manejo de errores OAuth
- [ ] Refresh token automatico
- [ ] Logout
- [ ] Recuperacion de contrasena
- [ ] OAuth Google
- [ ] OAuth Apple
- [ ] Verificacion de email
- [ ] Reactivacion de cuenta eliminada

## Menu
- [ ] Mostrar disclaimer de precios
- [ ] Badges en productos
- [ ] Promociones y precios con descuento
- [ ] Sub del Dia
- [ ] Combos Promocionales
- [ ] Banners

## Carrito
- [ ] Usar precios de la respuesta API
- [ ] Mostrar promociones aplicadas
- [ ] Verificar `can_checkout`
- [ ] Manejar `ADDRESS_OUTSIDE_DELIVERY_ZONE`

## Ordenes
- [ ] Crear orden con campos requeridos
- [ ] Historial paginado
- [ ] Tracking de estado
- [ ] Cancelacion (solo pending/confirmed)
- [ ] Reordenar
- [ ] Resena despues de completar

## Perfil
- [ ] Ver/Editar perfil
- [ ] Cambiar contrasena
- [ ] CRUD direcciones
- [ ] CRUD NITs
- [ ] Eliminar cuenta

## Puntos
- [ ] Mostrar balance
- [ ] Historial de transacciones
- [ ] Alerta de expiracion

## Favoritos
- [ ] Listar
- [ ] Agregar/Eliminar

## Soporte
- [ ] Ver terminos y privacidad
- [ ] Crear ticket con adjuntos
- [ ] Ver conversacion
- [ ] Enviar mensajes

---

## Documentacion de Referencia

- [API-CART-GUIDE.md](API-CART-GUIDE.md) - Documentacion completa del carrito
- [API-MENU-GUIDE.md](API-MENU-GUIDE.md) - Documentacion completa del menu

---

*Ultima actualizacion: Enero 2026*
