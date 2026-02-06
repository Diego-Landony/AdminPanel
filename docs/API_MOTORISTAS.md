# API de Motoristas - Documentación Técnica

## Información General

| Atributo | Valor |
|----------|-------|
| Base URL | `https://appmobile.subwaycardgt.com/api/v1/driver` |
| Versión | v1 |
| Autenticación | Bearer Token (Laravel Sanctum) |
| Content-Type | `application/json` |

---

## Headers Requeridos

```http
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

---

## Formato de Respuestas

### Respuesta Exitosa
```json
{
    "success": true,
    "data": { ... },
    "message": "Mensaje descriptivo"
}
```

### Respuesta de Error
```json
{
    "success": false,
    "message": "Descripción del error",
    "error_code": "ERROR_CODE"
}
```

---

## Códigos de Error

| HTTP | Error Code | Descripción |
|------|------------|-------------|
| 401 | `UNAUTHENTICATED` | Token inválido o expirado |
| 403 | `DRIVER_INACTIVE` | Cuenta desactivada |
| 403 | `ORDER_NOT_ASSIGNED` | Orden no asignada al motorista |
| 409 | `DRIVER_HAS_ACTIVE_ORDER` | Ya tiene una orden activa |
| 422 | `INVALID_ORDER_STATE` | Estado de orden no permite la acción |
| 422 | `INVALID_PASSWORD` | Contraseña actual incorrecta |
| 429 | `TOO_MANY_REQUESTS` | Rate limit excedido |

---

# Endpoints

## 1. Autenticación

### POST `/auth/login`

**Request:**
```json
{
    "email": "motorista@ejemplo.com",
    "password": "contraseña123",
    "device_name": "iPhone 14 Pro"
}
```

**Response 200:**
```json
{
    "success": true,
    "data": {
        "id": 15,
        "name": "Juan Pérez",
        "email": "motorista@ejemplo.com",
        "restaurant": {
            "id": 3,
            "name": "Subway Zona 10",
            "address": "6a Avenida 12-45, Zona 10",
            "phone": "+502 2222-3333"
        },
        "is_active": true,
        "is_available": false,
        "rating": 4.7,
        "total_deliveries": 234,
        "token": "1|abc123xyz...",
        "token_type": "Bearer"
    },
    "message": "Inicio de sesión exitoso."
}
```

---

### POST `/auth/logout`

**Response 200:**
```json
{
    "success": true,
    "message": "Sesión cerrada exitosamente."
}
```

---

### GET `/auth/me`

**Response 200:**
```json
{
    "success": true,
    "data": {
        "id": 15,
        "name": "Juan Pérez",
        "email": "motorista@ejemplo.com",
        "restaurant": {
            "id": 3,
            "name": "Subway Zona 10"
        },
        "is_active": true,
        "is_available": true,
        "rating": 4.7,
        "total_deliveries": 234
    }
}
```

---

## 2. Perfil

> **Nota:** `is_available` es calculado automáticamente (no editable por el driver).

### GET `/profile`

**Response 200:**
```json
{
    "success": true,
    "data": {
        "id": 15,
        "name": "Juan Pérez",
        "email": "motorista@ejemplo.com",
        "restaurant": {
            "id": 3,
            "name": "Subway Zona 10"
        },
        "is_active": true,
        "is_available": true,
        "rating": 4.7,
        "total_deliveries": 234,
        "stats": {
            "deliveries_today": 8,
            "average_delivery_time": 22.5,
            "rating": 4.7,
            "total_deliveries": 234
        },
        "has_active_order": false
    }
}
```

---

### PUT `/profile/password`

**Request:**
```json
{
    "current_password": "contraseña123",
    "password": "nuevaContraseña456",
    "password_confirmation": "nuevaContraseña456"
}
```

**Response 200:**
```json
{
    "success": true,
    "message": "Contraseña actualizada exitosamente."
}
```

---

## 3. Ubicación

### POST `/location`

**Request:**
```json
{
    "latitude": 14.6034567,
    "longitude": -90.5067890
}
```

**Response 200:**
```json
{
    "success": true,
    "message": "Ubicación actualizada."
}
```

---

## 4. Dispositivo (FCM)

### POST `/device/fcm-token`

**Request:**
```json
{
    "fcm_token": "dJK8xk9e_Rg:APA91bHun4Mxw..."
}
```

**Response 200:**
```json
{
    "success": true,
    "data": null,
    "message": "Token FCM registrado correctamente."
}
```

---

### DELETE `/device/fcm-token`

**Response 200:**
```json
{
    "success": true,
    "data": null,
    "message": "Token FCM eliminado correctamente."
}
```

---

## 5. Órdenes

### GET `/orders/pending`

Lista órdenes asignadas pendientes de aceptar (status: `ready`).

**Response 200:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1042,
            "order_number": "ORD-20260204-0042",
            "status": "ready",
            "status_label": "Lista para envío",
            "assigned_at": "2026-02-04T14:25:00.000000Z",
            "customer": {
                "name": "María García",
                "phone": "+502 5555-6666"
            },
            "delivery_address": {
                "formatted": "12 Calle 5-67, Zona 9, Guatemala",
                "latitude": "14.59870000",
                "longitude": "-90.51230000",
                "reference": "Edificio azul, apartamento 3B"
            },
            "summary": {
                "items_count": 3,
                "subtotal": 135.50,
                "discount": 10.00,
                "total": 125.50
            },
            "payment": {
                "method": "card",
                "status": "paid",
                "is_paid": true,
                "amount_to_collect": 0
            },
            "timestamps": {
                "created_at": "2026-02-04T14:15:00.000000Z",
                "ready_at": "2026-02-04T14:25:00.000000Z"
            },
            "notes": null
        }
    ],
    "message": "Órdenes pendientes obtenidas."
}
```

---

### GET `/orders/active`

Retorna la orden activa actual (status: `out_for_delivery`).

**Response 200 (con orden):**
```json
{
    "success": true,
    "data": {
        "id": 1041,
        "order_number": "ORD-20260204-0041",
        "status": "out_for_delivery",
        "status_label": "En camino",
        "assigned_at": "2026-02-04T14:10:00.000000Z",
        "restaurant": {
            "id": 3,
            "name": "Subway Zona 10",
            "address": "6a Avenida 12-45, Zona 10",
            "phone": "+502 2222-3333",
            "coordinates": {
                "latitude": 14.6034,
                "longitude": -90.5067
            }
        },
        "customer": {
            "name": "Carlos López",
            "phone": "+502 4444-5555"
        },
        "delivery_address": {
            "formatted": "Avenida Reforma 8-90, Zona 10",
            "latitude": "14.60120000",
            "longitude": "-90.52340000",
            "reference": "Torre Empresarial"
        },
        "summary": {
            "items_count": 3,
            "subtotal": 95.00,
            "discount": 10.00,
            "total": 85.00
        },
        "payment": {
            "method": "cash",
            "status": "pending",
            "is_paid": false,
            "amount_to_collect": 85.00
        },
        "timestamps": {
            "created_at": "2026-02-04T13:55:00.000000Z",
            "ready_at": "2026-02-04T14:05:00.000000Z"
        },
        "notes": "Llamar al llegar"
    }
}
```

**Response 200 (sin orden):**
```json
{
    "success": true,
    "data": null,
    "message": "No tienes entregas activas."
}
```

---

### GET `/orders/{order}`

Retorna detalle completo de una orden asignada.

**Response 200:**
```json
{
    "success": true,
    "data": {
        "id": 1042,
        "order_number": "ORD-20260204-0042",
        "status": "ready",
        "status_label": "Listo",
        "assigned_at": "2026-02-04T14:25:00.000000Z",
        "restaurant": {
            "id": 3,
            "name": "Subway Zona 10",
            "address": "6a Avenida 12-45, Zona 10",
            "phone": "+502 2222-3333",
            "coordinates": {
                "latitude": "14.60340000",
                "longitude": "-90.50670000"
            }
        },
        "customer": {
            "name": "María García",
            "phone": "+502 5555-6666"
        },
        "delivery_address": {
            "formatted": "12 Calle 5-67, Zona 9, Guatemala",
            "latitude": "14.59870000",
            "longitude": "-90.51230000",
            "reference": "Edificio azul, apartamento 3B"
        },
        "items": [
            {
                "name": "Sub de Carne",
                "quantity": 1,
                "unit_price": "45.00",
                "subtotal": "45.00",
                "options": "30cm, pan integral, extra queso"
            },
            {
                "name": "Bebida Grande",
                "quantity": 2,
                "unit_price": "15.00",
                "subtotal": "30.00",
                "options": "Coca-Cola"
            }
        ],
        "summary": {
            "items_count": 3,
            "subtotal": "135.00",
            "discount": "10.00",
            "total": "125.00"
        },
        "payment": {
            "method": "cash",
            "status": "pending",
            "is_paid": false,
            "amount_to_collect": "125.00"
        },
        "timestamps": {
            "created_at": "2026-02-04T14:15:00.000000Z",
            "ready_at": "2026-02-04T14:25:00.000000Z",
            "accepted_at": null
        },
        "notes": null
    }
}
```

---

### POST `/orders/{order}/accept`

Acepta una orden. Cambia status: `ready` → `out_for_delivery`

**Request:**
```json
{
    "latitude": 14.6034,
    "longitude": -90.5067
}
```

**Response 200:**
```json
{
    "success": true,
    "data": {
        "id": 1042,
        "order_number": "ORD-20260204-0042",
        "status": "out_for_delivery",
        "status_label": "En camino",
        ...
    },
    "message": "Entrega iniciada. El cliente ha sido notificado."
}
```

**Response 409 (ya tiene orden activa):**
```json
{
    "success": false,
    "message": "Ya tienes una entrega en progreso.",
    "error_code": "DRIVER_HAS_ACTIVE_ORDER"
}
```

---

### POST `/orders/{order}/deliver`

Marca orden como entregada. Cambia status: `out_for_delivery` → `delivered`

**Request:**
```json
{
    "latitude": 14.5988,
    "longitude": -90.5124,
    "notes": "Entregado a persona en recepción"
}
```

**Response 200:**
```json
{
    "success": true,
    "data": {
        "id": 1042,
        "order_number": "ORD-20260204-0042",
        "status": "delivered",
        "status_label": "Entregado",
        ...
    },
    "message": "Entrega completada exitosamente."
}
```

---

## 6. Historial

### GET `/history`

**Query Parameters:** `page`, `per_page`, `from`, `to`

**Response 200:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1040,
            "order_number": "ORD-20260204-0040",
            "delivered_at": "2026-02-04T13:30:00.000000Z",
            "delivery_time_minutes": 18,
            "customer_name": "Pedro Martínez",
            "delivery_address": "Av. Las Américas 15-20, Zona 13",
            "total": "75.00",
            "payment_method": "card",
            "rating": 5
        }
    ],
    "meta": {
        "current_page": 1,
        "last_page": 12,
        "per_page": 15,
        "total": 178
    }
}
```

---

## 7. Estadísticas

### GET `/stats`

**Query Parameters:** `period` (today, week, month, year)

**Response 200:**
```json
{
    "success": true,
    "data": {
        "period": "month",
        "period_label": "Febrero 2026",
        "deliveries": {
            "total": 89,
            "completed": 87,
            "cancelled": 2
        },
        "timing": {
            "average_minutes": 22
        },
        "rating": {
            "average": 4.8,
            "total_reviews": 72
        }
    }
}
```

---

## 8. Push Notifications (FCM)

### Notificación: `new_order_assigned`

Se envía cuando el restaurante asigna una orden.

```json
{
    "notification": {
        "title": "Nueva orden asignada",
        "body": "Tienes una nueva orden #ORD-20260204-0042 pendiente de aceptar."
    },
    "data": {
        "type": "new_order_assigned",
        "order_id": "1042",
        "order_number": "ORD-20260204-0042",
        "restaurant_name": "Subway Zona 10",
        "total": "125.50"
    }
}
```

---

## Flujo de Estados de Orden

```
[ready] + driver_id  →  Driver acepta  →  [out_for_delivery]  →  Driver entrega  →  [delivered]
```

- Restaurante asigna driver → status queda en `ready`, se envía push notification
- Driver acepta → status cambia a `out_for_delivery`
- Driver entrega → status cambia a `delivered`

---

*API de Motoristas v1.0*
