# WebSockets - Documentación Técnica

## Arquitectura General

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                                SERVIDOR                                          │
│                                                                                  │
│  ┌─────────────┐     ┌─────────────┐     ┌─────────────┐     ┌─────────────┐   │
│  │   Caddy     │────►│   Laravel   │────►│   Redis     │────►│   Reverb    │   │
│  │  (HTTPS)    │     │   (App)     │     │   (Queue)   │     │  (WS:8080)  │   │
│  └─────────────┘     └─────────────┘     └─────────────┘     └─────────────┘   │
│        │                                                            │           │
│        │ wss://ws.subwaycardgt.com ─────────────────────────────────┘           │
│        │ wss://appmobile.subwaycardgt.com/app/* ────────────────────┘           │
│                                                                                  │
└─────────────────────────────────────────────────────────────────────────────────┘
```

## Componentes

| Componente | Descripción | Puerto |
|------------|-------------|--------|
| **Caddy** | Reverse proxy con SSL automático | 443 |
| **Laravel Reverb** | Servidor WebSocket (protocolo Pusher) | 8080 (interno) |
| **Redis** | Cola de trabajos para broadcasts | 6379 |
| **Horizon** | Procesador de colas | - |

---

## URLs de Conexión

| Cliente | URL WebSocket | Auth Endpoint |
|---------|---------------|---------------|
| Flutter (móvil) | `wss://ws.subwaycardgt.com/app/{KEY}` | `POST /api/v1/broadcasting/auth` |
| Panel Restaurante | `wss://appmobile.subwaycardgt.com/app/{KEY}` | `POST /restaurant/broadcasting/auth` |

**KEY:** `hgmel6i1yqVbGQx1K8SxAXLVOeSupVaV`

---

## Canales Privados

Los canales privados requieren autenticación. El cliente debe enviar una solicitud al endpoint de auth antes de suscribirse.

### Canales de Órdenes

| Canal | Quién puede suscribirse | Eventos que recibe |
|-------|-------------------------|-------------------|
| `private-customer.{id}.orders` | Customer con ese ID | `order.status.updated`, `order.review.requested`, `order.review.submitted` |
| `private-restaurant.{id}.orders` | RestaurantUser de ese restaurante | `order.status.updated` |

### Autorización (routes/channels.php)

```php
// Canal para el cliente (Flutter)
Broadcast::channel('customer.{customerId}.orders', function ($user, $customerId) {
    if ($user instanceof \App\Models\Customer) {
        return $user->id === (int) $customerId;
    }
    return false;
});

// Canal para el restaurante (Panel Web)
Broadcast::channel('restaurant.{restaurantId}.orders', function ($user, $restaurantId) {
    if ($user instanceof \App\Models\RestaurantUser) {
        return $user->restaurant_id === (int) $restaurantId;
    }
    return false;
});
```

---

## Eventos de Broadcast

### 1. OrderStatusUpdated

**Cuándo:** Cada vez que cambia el estado de una orden.

**Canales:** `customer.{id}.orders` + `restaurant.{id}.orders`

**Nombre del evento:** `order.status.updated`

**Payload:**
```json
{
  "order": { /* OrderResource completo */ },
  "previous_status": "pending",
  "new_status": "preparing"
}
```

**Archivo:** `app/Events/OrderStatusUpdated.php`

---

### 2. RequestOrderReview

**Cuándo:** La orden llega al estado `completed`.

**Canal:** `customer.{id}.orders`

**Nombre del evento:** `order.review.requested`

**Payload:**
```json
{
  "order_id": 59,
  "order_number": "SUB-260125-00004",
  "restaurant": {
    "id": 142,
    "name": "Subway Pacific Center"
  },
  "total": 50.00,
  "service_type": "pickup",
  "completed_at": "2026-01-25T18:30:00Z"
}
```

**Archivo:** `app/Events/RequestOrderReview.php`

---

### 3. OrderReviewSubmitted

**Cuándo:** El cliente envía una calificación.

**Canal:** `customer.{id}.orders`

**Nombre del evento:** `order.review.submitted`

**Payload:**
```json
{
  "order_id": 59,
  "order_number": "SUB-260125-00004",
  "review": {
    "id": 1,
    "overall_rating": 5,
    "quality_rating": 5,
    "speed_rating": 4,
    "service_rating": 5,
    "comment": "Excelente servicio",
    "created_at": "2026-01-25T18:35:00Z"
  }
}
```

**Archivo:** `app/Events/OrderReviewSubmitted.php`

---

## Flujo Completo: Cambio de Estado

```
1. Panel Restaurante: POST /restaurant/orders/{id}/accept
                            │
                            ▼
2. Restaurant\OrderController::accept()
                            │
                            ▼
3. OrderService::transitionTo($order, 'preparing')
                            │
                            ├──► DB: $order->update(['status' => 'preparing'])
                            ├──► DB: OrderStatusHistory::create(...)
                            │
                            ▼
4. event(new OrderStatusUpdated($order, 'pending'))
                            │
                            ▼
5. Job encolado en Redis (BroadcastEvent)
                            │
                            ▼
6. Horizon procesa el job
                            │
                            ▼
7. Reverb broadcast a canales:
   - private-customer.{customer_id}.orders
   - private-restaurant.{restaurant_id}.orders
                            │
              ┌─────────────┴─────────────┐
              ▼                           ▼
8. Flutter recibe              Panel Web recibe
   "order.status.updated"      "order.status.updated"
```

---

## Configuración Flutter

```dart
import 'package:pusher_client/pusher_client.dart';

class OrderWebSocketService {
  late PusherClient pusher;
  late Channel channel;

  void connect(String customerId, String authToken) {
    pusher = PusherClient(
      'hgmel6i1yqVbGQx1K8SxAXLVOeSupVaV',
      PusherOptions(
        host: 'ws.subwaycardgt.com',
        wsPort: 443,
        wssPort: 443,
        encrypted: true,
        auth: PusherAuth(
          'https://appmobile.subwaycardgt.com/api/v1/broadcasting/auth',
          headers: {
            'Authorization': 'Bearer $authToken',
            'Accept': 'application/json',
          },
        ),
      ),
    );

    pusher.connect();

    // Suscribirse al canal privado
    channel = pusher.subscribe('private-customer.$customerId.orders');

    // Escuchar cambios de estado
    channel.bind('order.status.updated', (event) {
      final data = jsonDecode(event!.data!);
      print('Orden ${data['order']['order_number']} → ${data['new_status']}');
      // Actualizar UI
    });

    // Escuchar solicitud de calificación
    channel.bind('order.review.requested', (event) {
      final data = jsonDecode(event!.data!);
      // Mostrar modal de calificación
    });

    // Confirmar que la calificación fue enviada
    channel.bind('order.review.submitted', (event) {
      final data = jsonDecode(event!.data!);
      // Actualizar UI para mostrar que ya calificó
    });
  }

  void disconnect() {
    channel.unbind('order.status.updated');
    channel.unbind('order.review.requested');
    channel.unbind('order.review.submitted');
    pusher.unsubscribe('private-customer.$customerId.orders');
    pusher.disconnect();
  }
}
```

---

## Configuración Panel Web (React + Laravel Echo)

### echo.ts
```typescript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: 'appmobile.subwaycardgt.com',
    wsPort: 443,
    wssPort: 443,
    forceTLS: true,
    enabledTransports: ['ws', 'wss'],
    authEndpoint: '/restaurant/broadcasting/auth',
});
```

### Hook useOrderWebSocket.ts
```typescript
import { useEffect, useRef } from 'react';

export function useOrderWebSocket(restaurantId: number, callbacks: {
    onOrderUpdate?: (data: any) => void;
    onNewOrder?: (data: any) => void;
}) {
    const channelRef = useRef<any>(null);

    useEffect(() => {
        if (!window.Echo || !restaurantId) return;

        const channel = window.Echo.private(`restaurant.${restaurantId}.orders`);
        channelRef.current = channel;

        channel.listen('.order.status.updated', (data: any) => {
            callbacks.onOrderUpdate?.(data);

            // Si es orden nueva (pending), disparar callback especial
            if (data.new_status === 'pending') {
                callbacks.onNewOrder?.(data);
            }
        });

        return () => {
            channel.stopListening('.order.status.updated');
            window.Echo.leave(`restaurant.${restaurantId}.orders`);
        };
    }, [restaurantId]);

    return { channel: channelRef.current };
}
```

---

## Estados de Orden

```
PICKUP:
pending ──► preparing ──► ready ──► completed

DELIVERY:
pending ──► preparing ──► ready ──► out_for_delivery ──► delivered ──► completed

CANCELACIÓN (antes de ready):
[any] ──► cancelled
```

Cada transición dispara `OrderStatusUpdated`.

---

## Configuración del Servidor

### Variables de Entorno (.env)

```env
# Broadcasting
BROADCAST_CONNECTION=reverb

# Reverb (interno)
REVERB_APP_ID=subway-app
REVERB_APP_KEY=hgmel6i1yqVbGQx1K8SxAXLVOeSupVaV
REVERB_APP_SECRET=PrSPcNRpDXheLTizUEVcA6pALX2CAaPb
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http

# Reverb Server
REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8080

# Vite (frontend)
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_WS_HOST=appmobile.subwaycardgt.com
VITE_WS_PORT=443
VITE_WS_SCHEME=https
```

### Caddy (reverse proxy)

```caddyfile
# WebSocket subdomain
ws.subwaycardgt.com {
    reverse_proxy localhost:8080 {
        header_up Host 127.0.0.1
        header_up Connection {http.request.header.Connection}
        header_up Upgrade {http.request.header.Upgrade}
    }
}

# App principal con WebSocket en /app/*
appmobile.subwaycardgt.com {
    @websocket {
        path /app/*
        path /apps/*
    }
    handle @websocket {
        reverse_proxy localhost:8080 {
            header_up Host 127.0.0.1
            header_up Connection {http.request.header.Connection}
            header_up Upgrade {http.request.header.Upgrade}
        }
    }
    # ... resto de la config
}
```

### Supervisor (reverb.conf)

```ini
[program:reverb]
process_name=%(program_name)s
command=php /var/www/html/AdminPanel/artisan reverb:start --host=0.0.0.0 --port=8080
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/html/AdminPanel/storage/logs/reverb.log
```

---

## Comandos Útiles

```bash
# Estado de servicios
sudo supervisorctl status reverb
sudo systemctl status horizon

# Reiniciar servicios
sudo supervisorctl restart reverb
sudo systemctl restart horizon

# Ver logs
tail -f storage/logs/reverb.log
tail -f storage/logs/horizon.log

# Probar conexión WebSocket
wscat -c "wss://ws.subwaycardgt.com/app/hgmel6i1yqVbGQx1K8SxAXLVOeSupVaV"

# Disparar evento de prueba
php artisan tinker
>>> $order = \App\Models\Order::find(59);
>>> event(new \App\Events\OrderStatusUpdated($order, 'pending'));

# Ver colas en Redis
redis-cli LLEN queues:default

# Dashboard de colas
# https://appmobile.subwaycardgt.com/horizon
```

---

## Troubleshooting

### Error: "Failed host lookup"
- Verificar que el DNS esté propagado: `dig ws.subwaycardgt.com`
- Usar `appmobile.subwaycardgt.com` como alternativa

### Error 500 en WebSocket
- Verificar que Reverb esté corriendo: `sudo supervisorctl status reverb`
- Verificar logs: `tail -f storage/logs/reverb.log`
- Reiniciar Reverb: `sudo supervisorctl restart reverb`

### Eventos no llegan
- Verificar que Horizon esté procesando: `sudo systemctl status horizon`
- Verificar Redis: `redis-cli ping`
- Ver jobs pendientes: `redis-cli LLEN queues:default`

### Auth failed en canal privado
- Verificar token Bearer válido
- Verificar que el endpoint de auth esté accesible
- Verificar guards en `routes/channels.php`
