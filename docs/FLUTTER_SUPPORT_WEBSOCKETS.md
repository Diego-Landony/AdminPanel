# WebSockets para Tickets de Soporte - Guía Flutter

## Resumen de Arquitectura

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                           FLUJO DE SOPORTE                                       │
├─────────────────────────────────────────────────────────────────────────────────┤
│                                                                                  │
│  Flutter App                          Backend                    Admin Panel    │
│  ───────────                          ───────                    ───────────    │
│       │                                  │                           │          │
│       │ 1. POST /api/v1/support/tickets  │                           │          │
│       │────────────────────────────────►│                           │          │
│       │                                  │                           │          │
│       │ 2. Suscribirse canal privado     │                           │          │
│       │   private-customer.{id}          │                           │          │
│       │   private-support.ticket.{id}    │                           │          │
│       │                                  │                           │          │
│       │◄─────────────────────────────────│                           │          │
│       │ 3. Evento: message.sent          │ ◄─────────────────────────│          │
│       │    (mensaje del admin)           │    Admin responde         │          │
│       │                                  │                           │          │
│       │ 4. POST mensaje                  │                           │          │
│       │────────────────────────────────►│                           │          │
│       │                                  │─────────────────────────►│          │
│       │                                  │ Evento: message.sent      │          │
│       │                                  │                           │          │
└─────────────────────────────────────────────────────────────────────────────────┘
```

## Canales Disponibles

| Canal | Descripción | Eventos |
|-------|-------------|---------|
| `private-customer.{customerId}` | Notificaciones generales del cliente | `message.sent` (cuando admin responde desde cualquier ticket) |
| `private-support.ticket.{ticketId}` | Canal específico del ticket | `message.sent`, `ticket.status.changed` |

## Eventos WebSocket

### 1. `message.sent` - Nuevo mensaje en ticket

```dart
// Datos recibidos
{
  "message": {
    "id": 123,
    "message": "Texto del mensaje",
    "is_from_admin": true,
    "is_read": false,
    "sender": {
      "type": "admin",  // o "customer"
      "name": "Nombre del Admin"
    },
    "attachments": [
      {
        "id": 1,
        "url": "https://...",
        "file_name": "image.jpg",
        "mime_type": "image/jpeg",
        "file_size": 12345
      }
    ],
    "created_at": "2024-01-25T10:30:00.000Z"
  },
  "ticket_id": 456
}
```

### 2. `ticket.status.changed` - Cambio de estado del ticket

```dart
// Datos recibidos
{
  "ticket_id": 456,
  "status": "closed",     // "open" | "closed"
  "assigned_to": 1,       // ID del admin o null
  "resolved_at": "2024-01-25T10:30:00.000Z"  // o null
}
```

## Implementación en Flutter

### 1. Configuración de Pusher/Reverb

```dart
import 'package:pusher_channels_flutter/pusher_channels_flutter.dart';

class WebSocketService {
  late PusherChannelsFlutter pusher;

  // Configuración
  static const String appKey = 'hgmel6i1yqVbGQx1K8SxAXLVOeSupVaV';  // REVERB_APP_KEY
  static const String wsHost = 'ws.subwaycardgt.com';              // o appmobile.subwaycardgt.com
  static const String authEndpoint = 'https://appmobile.subwaycardgt.com/api/v1/broadcasting/auth';

  Future<void> init(String bearerToken) async {
    pusher = PusherChannelsFlutter.getInstance();

    await pusher.init(
      apiKey: appKey,
      cluster: '',  // Dejar vacío para Reverb
      options: PusherChannelsOptions(
        host: wsHost,
        wsPort: 443,
        wssPort: 443,
        encrypted: true,
        auth: PusherChannelsAuthOptions(
          endpoint: authEndpoint,
          headers: {
            'Authorization': 'Bearer $bearerToken',
            'Accept': 'application/json',
          },
        ),
      ),
      onConnectionStateChange: (currentState, previousState) {
        print('WebSocket: $previousState -> $currentState');
      },
      onError: (message, code, error) {
        print('WebSocket Error: $message ($code)');
      },
    );

    await pusher.connect();
  }
}
```

### 2. Suscripción al Canal de Soporte

```dart
class SupportWebSocketService {
  final PusherChannelsFlutter pusher;
  PusherChannel? _ticketChannel;
  PusherChannel? _customerChannel;

  SupportWebSocketService(this.pusher);

  /// Suscribirse al canal de un ticket específico
  Future<void> subscribeToTicket({
    required int ticketId,
    required Function(Map<String, dynamic>) onNewMessage,
    required Function(Map<String, dynamic>) onStatusChanged,
  }) async {
    final channelName = 'private-support.ticket.$ticketId';

    _ticketChannel = await pusher.subscribe(
      channelName: channelName,
      onSubscriptionSucceeded: (channelName, data) {
        print('Suscrito a: $channelName');
      },
      onSubscriptionError: (message, error) {
        print('Error suscripción: $message');
      },
    );

    // Escuchar nuevos mensajes
    await _ticketChannel?.bind(
      eventName: 'message.sent',
      eventHandler: (event) {
        final data = jsonDecode(event.data);
        onNewMessage(data);
      },
    );

    // Escuchar cambios de estado
    await _ticketChannel?.bind(
      eventName: 'ticket.status.changed',
      eventHandler: (event) {
        final data = jsonDecode(event.data);
        onStatusChanged(data);
      },
    );
  }

  /// Suscribirse al canal general del cliente (para notificaciones)
  Future<void> subscribeToCustomerChannel({
    required int customerId,
    required Function(Map<String, dynamic>) onSupportMessage,
  }) async {
    final channelName = 'private-customer.$customerId';

    _customerChannel = await pusher.subscribe(
      channelName: channelName,
      onSubscriptionSucceeded: (channelName, data) {
        print('Suscrito a canal cliente: $channelName');
      },
    );

    // Mensajes de soporte
    await _customerChannel?.bind(
      eventName: 'message.sent',
      eventHandler: (event) {
        final data = jsonDecode(event.data);
        onSupportMessage(data);
      },
    );
  }

  /// Desuscribirse del ticket actual
  Future<void> unsubscribeFromTicket(int ticketId) async {
    await pusher.unsubscribe(channelName: 'private-support.ticket.$ticketId');
    _ticketChannel = null;
  }

  /// Desuscribirse del canal del cliente
  Future<void> unsubscribeFromCustomer(int customerId) async {
    await pusher.unsubscribe(channelName: 'private-customer.$customerId');
    _customerChannel = null;
  }
}
```

### 3. Uso en un Widget de Chat

```dart
class SupportChatScreen extends StatefulWidget {
  final int ticketId;

  const SupportChatScreen({required this.ticketId});

  @override
  State<SupportChatScreen> createState() => _SupportChatScreenState();
}

class _SupportChatScreenState extends State<SupportChatScreen> {
  late SupportWebSocketService _wsService;
  List<Message> _messages = [];
  String _ticketStatus = 'open';

  @override
  void initState() {
    super.initState();
    _initWebSocket();
  }

  Future<void> _initWebSocket() async {
    _wsService = SupportWebSocketService(pusher);

    await _wsService.subscribeToTicket(
      ticketId: widget.ticketId,
      onNewMessage: _handleNewMessage,
      onStatusChanged: _handleStatusChanged,
    );
  }

  void _handleNewMessage(Map<String, dynamic> data) {
    final message = data['message'];

    // Solo procesar mensajes del admin (los propios ya los agregaste al enviar)
    if (message['is_from_admin'] == true) {
      setState(() {
        _messages.add(Message.fromJson(message));
      });

      // Mostrar notificación si la app está en background
      _showLocalNotification(
        title: 'Nuevo mensaje de soporte',
        body: message['message'] ?? 'Archivo adjunto',
      );

      // Marcar como leído
      _markMessageAsRead(message['id']);
    }
  }

  void _handleStatusChanged(Map<String, dynamic> data) {
    setState(() {
      _ticketStatus = data['status'];
    });

    if (data['status'] == 'closed') {
      _showSnackBar('El ticket ha sido cerrado');
    }
  }

  @override
  void dispose() {
    _wsService.unsubscribeFromTicket(widget.ticketId);
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('Soporte'),
        actions: [
          // Indicador de conexión
          StreamBuilder<PusherConnectionState>(
            stream: pusher.connectionStateStream,
            builder: (context, snapshot) {
              final isConnected = snapshot.data == PusherConnectionState.connected;
              return Icon(
                isConnected ? Icons.wifi : Icons.wifi_off,
                color: isConnected ? Colors.green : Colors.red,
              );
            },
          ),
        ],
      ),
      body: Column(
        children: [
          // Lista de mensajes
          Expanded(
            child: ListView.builder(
              itemCount: _messages.length,
              itemBuilder: (context, index) {
                final msg = _messages[index];
                return MessageBubble(
                  message: msg,
                  isFromMe: !msg.isFromAdmin,
                );
              },
            ),
          ),
          // Input de mensaje (solo si está abierto)
          if (_ticketStatus == 'open')
            MessageInput(
              onSend: _sendMessage,
            )
          else
            Container(
              padding: EdgeInsets.all(16),
              child: Text('Este ticket está cerrado'),
            ),
        ],
      ),
    );
  }
}
```

## Endpoint de Autenticación

### Request
```http
POST /api/v1/broadcasting/auth
Authorization: Bearer {token}
Content-Type: application/x-www-form-urlencoded

socket_id=123456.789&channel_name=private-support.ticket.1
```

### Response (éxito)
```json
{
  "auth": "hgmel6i1yqVbGQx1K8SxAXLVOeSupVaV:signature..."
}
```

### Response (error - no autorizado)
```json
{
  "message": "Unauthorized"
}
```

## Endpoints de API para Soporte

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/v1/support/tickets` | Listar tickets del cliente |
| POST | `/api/v1/support/tickets` | Crear nuevo ticket |
| GET | `/api/v1/support/tickets/{id}` | Ver detalle del ticket |
| POST | `/api/v1/support/tickets/{id}/messages` | Enviar mensaje |
| GET | `/api/v1/support/reasons` | Motivos de soporte disponibles |

## Manejo de Reconexión

```dart
class WebSocketManager {
  Timer? _reconnectTimer;
  int _reconnectAttempts = 0;
  static const int maxAttempts = 5;

  void handleDisconnection() {
    if (_reconnectAttempts >= maxAttempts) {
      print('Máximo de intentos alcanzado');
      return;
    }

    // Backoff exponencial
    final delay = Duration(seconds: pow(2, _reconnectAttempts).toInt());

    _reconnectTimer = Timer(delay, () async {
      _reconnectAttempts++;
      try {
        await pusher.connect();
        _reconnectAttempts = 0;  // Reset en éxito
      } catch (e) {
        handleDisconnection();  // Reintentar
      }
    });
  }

  // Reconectar cuando la app vuelve a primer plano
  void onAppResumed() {
    if (pusher.connectionState != PusherConnectionState.connected) {
      _reconnectAttempts = 0;
      handleDisconnection();
    }
  }
}
```

## Variables de Entorno

```env
# Producción
REVERB_APP_KEY=hgmel6i1yqVbGQx1K8SxAXLVOeSupVaV
WS_HOST=ws.subwaycardgt.com
WS_PORT=443
API_URL=https://appmobile.subwaycardgt.com
AUTH_ENDPOINT=/api/v1/broadcasting/auth
```

## Debugging

### Verificar conexión WebSocket
```bash
# Terminal - probar conexión
wscat -c "wss://ws.subwaycardgt.com/app/hgmel6i1yqVbGQx1K8SxAXLVOeSupVaV"
```

### Disparar evento de prueba (backend)
```bash
php artisan tinker
>>> $ticket = \App\Models\SupportTicket::find(1);
>>> $message = $ticket->messages()->first();
>>> event(new \App\Events\SupportMessageSent($message));
```

### Logs en Flutter
```dart
PusherChannelsFlutter.enableLogging = true;  // Habilitar logs detallados
```

## Diferencias con Órdenes

| Aspecto | Órdenes | Soporte |
|---------|---------|---------|
| Canal principal | `private-customer.{id}.orders` | `private-support.ticket.{ticketId}` |
| Eventos | `order.status.updated` | `message.sent`, `ticket.status.changed` |
| Bidireccional | No (solo recibe) | Sí (envía y recibe mensajes) |
| Tipo de datos | Estado de orden | Mensajes de chat |
