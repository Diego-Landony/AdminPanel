# Guía de Integración: Carrito de Compras (Cart API)

Esta guía documenta cómo consumir correctamente la API del carrito para la aplicación móvil.

---

## Tabla de Contenidos

1. [Conceptos Clave](#conceptos-clave)
2. [Flujo del Carrito](#flujo-del-carrito)
3. [Endpoints Disponibles](#endpoints-disponibles)
4. [Flujo Recomendado para la App](#flujo-recomendado-para-la-app)
5. [Errores Comunes](#errores-comunes)

---

## Conceptos Clave

### Estructura del Carrito

El carrito tiene dos componentes principales:

| Componente | Descripción |
|------------|-------------|
| `Cart` | Contenedor principal con zona, tipo de servicio y restaurante |
| `CartItem` | Items individuales (productos, variantes, combos) |

### Estados del Carrito

| Estado | Descripción |
|--------|-------------|
| `active` | Carrito activo, puede ser modificado |
| `converted` | Carrito convertido en orden |
| `abandoned` | Carrito abandonado (expirado) |

### Tipos de Servicio

| Tipo | Descripción |
|------|-------------|
| `pickup` | Recoger en restaurante |
| `delivery` | Entrega a domicilio |

### Zonas de Precio

| Zona | Descripción |
|------|-------------|
| `capital` | Ciudad de Guatemala y alrededores |
| `interior` | Resto del país |

### Matriz de Precios

Los precios varían según la combinación de zona y tipo de servicio:

| Zona | Servicio | Campo de Precio |
|------|----------|-----------------|
| capital | pickup | `precio_pickup_capital` |
| capital | delivery | `precio_domicilio_capital` |
| interior | pickup | `precio_pickup_interior` |
| interior | delivery | `precio_domicilio_interior` |

> **Importante:** Los precios se recalculan automáticamente cuando cambia la zona o tipo de servicio.

---

## Flujo del Carrito

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                           FLUJO DEL CARRITO                                  │
└─────────────────────────────────────────────────────────────────────────────┘

1. OBTENER/CREAR CARRITO
   GET /api/v1/cart
   └── Si no existe carrito activo, se crea automáticamente
   └── Valores por defecto: zone=capital, service_type=pickup

2. AGREGAR PRODUCTOS
   POST /api/v1/cart/items
   └── Agregar productos, variantes o combos
   └── El precio se calcula según zona + service_type actuales

3. SELECCIONAR TIPO DE SERVICIO

   ┌─────────────────┐          ┌─────────────────┐
   │     PICKUP      │          │    DELIVERY     │
   └────────┬────────┘          └────────┬────────┘
            │                            │
            ▼                            ▼
   PUT /cart/restaurant         PUT /cart/delivery-address
   (Seleccionar tienda)         (Seleccionar dirección)
            │                            │
            │                            ├── Valida geofence
            │                            ├── Asigna restaurante automático
            │                            └── Asigna zona automática
            │                            │
            ▼                            ▼
   ┌─────────────────────────────────────────────┐
   │     PRECIOS RECALCULADOS AUTOMÁTICAMENTE    │
   └─────────────────────────────────────────────┘

4. VALIDAR Y CHECKOUT
   POST /api/v1/cart/validate  →  Verificar disponibilidad
   POST /api/v1/orders         →  Crear orden desde carrito
```

---

## Endpoints Disponibles

### Autenticación Requerida

Todos los endpoints requieren header:
```
Authorization: Bearer {token}
```

---

### 1. Obtener Carrito Actual

```http
GET /api/v1/cart
```

**Response exitoso (200):**
```json
{
  "data": {
    "id": 1,
    "restaurant": {
      "id": 2,
      "name": "Subway Pradera"
    },
    "service_type": "pickup",
    "zone": "capital",
    "items": [
      {
        "id": 101,
        "type": "product",
        "product": {
          "id": 10,
          "name": "Italian BMT",
          "image_url": "https://..."
        },
        "variant": {
          "id": 25,
          "name": "30cm"
        },
        "quantity": 2,
        "unit_price": 45.00,
        "subtotal": 90.00,
        "discount_amount": 0.00,
        "final_price": 90.00,
        "is_daily_special": false,
        "applied_promotion": null,
        "selected_options": [
          {"name": "Extra queso", "price": 5.00}
        ],
        "notes": "Sin cebolla"
      }
    ],
    "summary": {
      "subtotal": "90.00",
      "promotions_applied": [],
      "total_discount": "0.00",
      "total": "90.00",
      "points_to_earn": 9
    },
    "can_checkout": true,
    "validation_messages": [],
    "expires_at": "2025-12-22T10:00:00.000000Z",
    "created_at": "2025-12-15T10:00:00.000000Z"
  }
}
```

---

### 2. Agregar Item al Carrito

```http
POST /api/v1/cart/items
```

**Request - Producto simple:**
```json
{
  "product_id": 10,
  "quantity": 2,
  "notes": "Sin cebolla"
}
```

**Request - Producto con variante:**
```json
{
  "product_id": 10,
  "variant_id": 25,
  "quantity": 1,
  "selected_options": [
    {"section_id": 1, "option_id": 3}
  ],
  "notes": "Extra salsa"
}
```

**Request - Combo:**
```json
{
  "combo_id": 5,
  "quantity": 1,
  "combo_selections": [
    {
      "slot_id": 1,
      "product_id": 10,
      "variant_id": 25
    },
    {
      "slot_id": 2,
      "product_id": 50
    }
  ]
}
```

**Response exitoso (201):**
```json
{
  "data": {
    "item": {
      "id": 102,
      "type": "product",
      "quantity": 2,
      "unit_price": 45.00,
      "subtotal": 90.00
    },
    "message": "Item agregado al carrito"
  }
}
```

**Errores posibles:**

| Código | Mensaje | Causa |
|--------|---------|-------|
| 422 | "El producto no está disponible" | Producto inactivo |
| 422 | "El combo no está disponible" | Combo inactivo |
| 422 | "Se requiere product_id o combo_id" | Falta parámetro |

---

### 3. Actualizar Item del Carrito

```http
PUT /api/v1/cart/items/{id}
```

**Request:**
```json
{
  "quantity": 3,
  "selected_options": [
    {"section_id": 1, "option_id": 5}
  ],
  "notes": "Nuevo comentario"
}
```

**Response exitoso (200):**
```json
{
  "data": {
    "item": {
      "id": 102,
      "quantity": 3,
      "unit_price": 45.00,
      "subtotal": 135.00
    },
    "message": "Item actualizado"
  }
}
```

---

### 4. Eliminar Item del Carrito

```http
DELETE /api/v1/cart/items/{id}
```

**Response exitoso (200):**
```json
{
  "data": {
    "message": "Item eliminado del carrito"
  }
}
```

---

### 5. Vaciar Carrito

```http
DELETE /api/v1/cart
```

**Response exitoso (200):**
```json
{
  "data": {
    "message": "Carrito vaciado"
  }
}
```

---

### 6. Seleccionar Restaurante (Para Pickup)

```http
PUT /api/v1/cart/restaurant
```

**Request:**
```json
{
  "restaurant_id": 2
}
```

**Response exitoso (200):**
```json
{
  "data": {
    "restaurant": {
      "id": 2,
      "name": "Subway Pradera"
    },
    "service_type": "pickup",
    "zone": "capital",
    "prices_updated": true
  },
  "message": "Restaurante seleccionado para pickup"
}
```

> **Nota:** Este endpoint automáticamente:
> - Establece `service_type` como `pickup`
> - Establece `zone` según la ubicación del restaurante (`price_location`)
> - Limpia cualquier `delivery_address_id` previo
> - Recalcula todos los precios de los items

---

### 7. Establecer Dirección de Entrega (Para Delivery)

```http
PUT /api/v1/cart/delivery-address
```

**Request:**
```json
{
  "delivery_address_id": 1
}
```

**Response exitoso (200):**
```json
{
  "data": {
    "delivery_address": {
      "id": 1,
      "alias": "Casa",
      "street_address": "5ta Avenida 10-50, Zona 14",
      "city": "Guatemala",
      "latitude": 14.5890,
      "longitude": -90.5150
    },
    "assigned_restaurant": {
      "id": 3,
      "name": "Subway Zona 14"
    },
    "zone": "capital",
    "prices_updated": true
  },
  "message": "Dirección de entrega asignada exitosamente"
}
```

**Error - Fuera de zona de entrega (422):**
```json
{
  "message": "La dirección está fuera de la zona de entrega",
  "error_code": "ADDRESS_OUTSIDE_DELIVERY_ZONE",
  "data": {
    "nearest_pickup_locations": [
      {
        "id": 5,
        "name": "Subway Miraflores",
        "address": "Calzada Roosevelt 22-43, Zona 11",
        "distance_km": 2.5
      }
    ]
  }
}
```

> **Nota:** Este endpoint automáticamente:
> - Valida que la dirección esté dentro de un geofence de delivery
> - Asigna el restaurante más cercano que cubra esa zona
> - Establece `service_type` como `delivery`
> - Establece `zone` según la ubicación
> - Recalcula todos los precios de los items

---

### 8. Cambiar Tipo de Servicio Manualmente

```http
PUT /api/v1/cart/service-type
```

**Request:**
```json
{
  "service_type": "delivery",
  "zone": "interior"
}
```

> **Nota:** Este endpoint es para casos especiales. Normalmente se debe usar:
> - `PUT /cart/restaurant` para pickup
> - `PUT /cart/delivery-address` para delivery

---

### 9. Validar Carrito

```http
POST /api/v1/cart/validate
```

**Response exitoso (200):**
```json
{
  "data": {
    "is_valid": true,
    "errors": []
  }
}
```

**Response con errores:**
```json
{
  "data": {
    "is_valid": false,
    "errors": [
      "El producto 'Italian BMT' ya no está disponible",
      "El combo 'Combo del Día' tiene items no disponibles"
    ]
  }
}
```

---

## Flujo Recomendado para la App

### Pantalla de Catálogo/Menú

```
1. Usuario navega el menú
2. Al agregar producto → POST /api/v1/cart/items
3. Mostrar badge con cantidad de items en carrito
```

### Pantalla de Carrito

```
1. Al abrir → GET /api/v1/cart
2. Mostrar items con precios actuales
3. Permitir editar cantidad → PUT /api/v1/cart/items/{id}
4. Permitir eliminar → DELETE /api/v1/cart/items/{id}
5. Mostrar resumen (subtotal, descuentos, total)
6. Mostrar puntos a ganar
```

### Pantalla de Checkout - Tipo de Servicio

```
┌─────────────────────────────────────────────────┐
│           ¿Cómo quieres recibir tu pedido?      │
├─────────────────────────────────────────────────┤
│                                                  │
│   ┌─────────────┐       ┌─────────────┐         │
│   │   PICKUP    │       │  DELIVERY   │         │
│   │             │       │             │         │
│   │  Recoger en │       │  Entrega a  │         │
│   │   tienda    │       │  domicilio  │         │
│   └─────────────┘       └─────────────┘         │
│                                                  │
└─────────────────────────────────────────────────┘
```

### Flujo Pickup

```
1. Usuario selecciona "Pickup"
2. Mostrar lista de restaurantes → GET /api/v1/restaurants
3. Usuario selecciona restaurante
4. Llamar → PUT /api/v1/cart/restaurant
5. Refrescar carrito → GET /api/v1/cart (precios actualizados)
6. Continuar a checkout
```

### Flujo Delivery

```
1. Usuario selecciona "Delivery"
2. Mostrar direcciones guardadas → GET /api/v1/addresses
3. Usuario selecciona dirección (o crea nueva)
4. Llamar → PUT /api/v1/cart/delivery-address
5. Si error ADDRESS_OUTSIDE_DELIVERY_ZONE:
   └── Mostrar restaurantes cercanos para pickup
   └── Ofrecer opción de cambiar a pickup
6. Si éxito:
   └── Refrescar carrito → GET /api/v1/cart
   └── Continuar a checkout
```

### Pantalla de Confirmación de Orden

```
1. Validar carrito → POST /api/v1/cart/validate
2. Si is_valid = false:
   └── Mostrar errores
   └── Permitir editar carrito
3. Si is_valid = true:
   └── Mostrar resumen final
   └── Seleccionar método de pago
   └── Opcionalmente seleccionar NIT para factura
   └── Crear orden → POST /api/v1/orders
```

---

## Errores Comunes y Cómo Manejarlos

### Error: Carrito Vacío al Hacer Checkout

| Campo | Valor |
|-------|-------|
| **Causa** | El carrito expiró (7 días de inactividad) o fue convertido en orden |
| **Código HTTP** | 422 |
| **Solución** | Verificar `cart.items.length` antes de proceder. Redirigir al menú si está vacío |

### Error: Producto No Disponible

| Campo | Valor |
|-------|-------|
| **Causa** | El producto fue desactivado después de agregarlo al carrito |
| **Código HTTP** | 422 |
| **Solución** | Siempre llamar `POST /cart/validate` antes del checkout. Mostrar errores al usuario |

### Error: Dirección Fuera de Zona

| Campo | Valor |
|-------|-------|
| **Causa** | La dirección no está cubierta por ningún restaurante con delivery |
| **Código HTTP** | 422 |
| **Error Code** | `ADDRESS_OUTSIDE_DELIVERY_ZONE` |
| **Datos Extra** | `nearest_pickup_locations` - Array de restaurantes cercanos para pickup |
| **Solución** | Ofrecer cambiar a pickup o agregar otra dirección |

### Error: Restaurante Cerrado

| Campo | Valor |
|-------|-------|
| **Causa** | El restaurante seleccionado está fuera de horario |
| **Código HTTP** | 422 |
| **Error Code** | `RESTAURANT_CLOSED` |
| **Solución** | Mostrar horarios del restaurante, sugerir otro restaurante |

---

## Diagrama de Estados del Carrito

```
                                    ┌──────────────┐
                                    │   CREACIÓN   │
                                    │  GET /cart   │
                                    └──────┬───────┘
                                           │
                                           ▼
┌─────────────────────────────────────────────────────────────────┐
│                         CARRITO ACTIVO                          │
│                                                                  │
│  • Agregar items      POST /cart/items                          │
│  • Editar items       PUT /cart/items/{id}                      │
│  • Eliminar items     DELETE /cart/items/{id}                   │
│  • Cambiar servicio   PUT /cart/restaurant                      │
│                       PUT /cart/delivery-address                │
│  • Vaciar             DELETE /cart                              │
│                                                                  │
│  expires_at: created_at + 7 días                                │
│                                                                  │
└───────────────────┬───────────────────────┬─────────────────────┘
                    │                       │
         ┌──────────▼──────────┐  ┌────────▼────────┐
         │  POST /api/v1/orders │  │   7 días sin    │
         │   (crear orden)      │  │   actividad     │
         └──────────┬──────────┘  └────────┬────────┘
                    │                       │
                    ▼                       ▼
            ┌───────────────┐       ┌───────────────┐
            │   CONVERTED   │       │   ABANDONED   │
            │               │       │               │
            │ Carrito se    │       │ Carrito       │
            │ convirtió en  │       │ expirado      │
            │ orden         │       │               │
            └───────────────┘       └───────────────┘
```

---

## Resumen de Endpoints

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/v1/cart` | Obtener carrito actual |
| POST | `/api/v1/cart/items` | Agregar item |
| PUT | `/api/v1/cart/items/{id}` | Actualizar item |
| DELETE | `/api/v1/cart/items/{id}` | Eliminar item |
| DELETE | `/api/v1/cart` | Vaciar carrito |
| PUT | `/api/v1/cart/restaurant` | Seleccionar restaurante (pickup) |
| PUT | `/api/v1/cart/delivery-address` | Establecer dirección (delivery) |
| PUT | `/api/v1/cart/service-type` | Cambiar tipo de servicio |
| POST | `/api/v1/cart/validate` | Validar carrito |

---

*Última actualización: Enero 2026*
