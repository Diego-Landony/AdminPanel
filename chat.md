Listo. Aquí está la documentación completa actualizada:

📱 Guía de Integración del Sistema de Soporte para Flutter
🎯 ¿Qué es el Sistema de Soporte?
El módulo de Soporte permite a los clientes comunicarse directamente con el equipo de atención de la app para resolver dudas, problemas o enviar sugerencias.

Para usuarios autenticados (in-app):
Problema con mi pedido: Retrasos, pedidos incorrectos, productos faltantes
Problema con pago: Cargos duplicados, reembolsos, métodos de pago
No aparecen mis puntos: Puntos no reflejados, puntos perdidos
Mi cuenta: Actualizar datos, direcciones, métodos de pago
Sugerencia: Ideas para mejorar la app o el servicio
Otro: Cualquier otra consulta
Para usuarios sin acceso (no pueden iniciar sesión):
Problemas de acceso: No encuentra su cuenta, no puede iniciar sesión, cuenta bloqueada
El equipo de soporte contactará al usuario por email o teléfono directamente
Nota: Para recuperar contraseña, ya existe el flujo de "Olvidé mi contraseña" en /api/v1/auth/forgot-password

🔓 PARTE 1: Endpoint Público (SIN autenticación)
Este endpoint es para usuarios que NO pueden iniciar sesión.

Reportar Problema de Acceso

POST /api/v1/support/access-issues
Content-Type: application/json
Request:


{
    "email": "usuario@email.com",
    "phone": "12345678",
    "issue_type": "cant_login",
    "description": "No puedo iniciar sesión, me dice que mi contraseña es incorrecta pero estoy seguro que es correcta."
}
Campo	Tipo	Requerido	Descripción
email	string	✅	Email del usuario
phone	string	❌	Teléfono de contacto (opcional pero recomendado)
issue_type	string	✅	Tipo de problema (ver valores abajo)
description	string	✅	Descripción detallada (máx 2000 chars)
Valores de issue_type:

Valor	Descripción en UI
cant_find_account	No encuentro mi cuenta
cant_login	No puedo iniciar sesión
account_locked	Cuenta bloqueada
other	Otro problema de acceso
Response (201):


{
    "message": "Reporte recibido. Nuestro equipo te contactará pronto por correo o teléfono."
}
Rate Limit: 5 solicitudes por minuto (para prevenir abuso)

Ejemplo Dart:


Future<void> reportAccessIssue({
  required String email,
  String? phone,
  required String issueType,
  required String description,
}) async {
  final response = await http.post(
    Uri.parse('$baseUrl/api/v1/support/access-issues'),
    headers: {'Content-Type': 'application/json'},
    body: jsonEncode({
      'email': email,
      'phone': phone,
      'issue_type': issueType,
      'description': description,
    }),
  );
  
  if (response.statusCode == 201) {
    // Mostrar mensaje de éxito
  } else if (response.statusCode == 422) {
    // Error de validación
  } else if (response.statusCode == 429) {
    // Rate limit - muchas solicitudes
  }
}
UI sugerida en pantalla de Login:


┌─────────────────────────────────────────┐
│            INICIAR SESIÓN               │
│                                         │
│  Email: [________________]              │
│  Contraseña: [____________]             │
│                                         │
│  [    INICIAR SESIÓN    ]               │
│                                         │
│  ─────────────────────────              │
│  ¿Olvidaste tu contraseña? ← Ya existe  │
│                                         │
│  ¿No puedes acceder a tu cuenta?        │
│  [Reportar problema de acceso]  ← NUEVO │
└─────────────────────────────────────────┘
🔐 PARTE 2: Endpoints Autenticados
Todos estos endpoints requieren autenticación con Sanctum (Bearer Token).


Authorization: Bearer {token}
1. Obtener Motivos de Soporte

GET /api/v1/support/reasons
Response:


{
    "data": {
        "reasons": [
            { "id": 1, "name": "Problema con mi pedido", "slug": "order_issue" },
            { "id": 2, "name": "Problema con pago", "slug": "payment" },
            { "id": 3, "name": "No aparecen mis puntos", "slug": "points_issue" },
            { "id": 4, "name": "Mi cuenta", "slug": "account" },
            { "id": 5, "name": "Sugerencia", "slug": "suggestion" },
            { "id": 6, "name": "Otro", "slug": "other" }
        ]
    }
}
2. Listar Tickets del Cliente

GET /api/v1/support/tickets
Response:


{
    "data": {
        "tickets": [
            {
                "id": 1,
                "reason": {
                    "id": 3,
                    "name": "No aparecen mis puntos",
                    "slug": "points_issue"
                },
                "status": "open",
                "priority": "medium",
                "unread_count": 2,
                "assigned_to": {
                    "id": 5,
                    "name": "Carlos Soporte"
                },
                "latest_message": {
                    "message": "Hola, revisando tus puntos...",
                    "created_at": "2026-01-23T10:30:00+00:00",
                    "is_from_admin": true
                },
                "resolved_at": null,
                "created_at": "2026-01-22T15:00:00+00:00",
                "updated_at": "2026-01-23T10:30:00+00:00"
            }
        ]
    }
}
Campo	Tipo	Descripción
status	string	"open" o "closed"
priority	string	"low", "medium", "high"
unread_count	int	Mensajes del admin sin leer
3. Crear Nuevo Ticket

POST /api/v1/support/tickets
Content-Type: multipart/form-data
Request:

Campo	Tipo	Requerido	Descripción
reason_id	integer	✅	ID del motivo (de /reasons)
message	string	✅	Mensaje inicial (máx 5000 chars)
attachments[]	file[]	❌	Imágenes (máx 4, 5MB c/u, jpeg/png/gif/webp)
Ejemplo Dart:


Future<Map<String, dynamic>> createTicket({
  required int reasonId,
  required String message,
  List<File>? attachments,
}) async {
  final request = http.MultipartRequest(
    'POST',
    Uri.parse('$baseUrl/api/v1/support/tickets'),
  );
  
  request.headers['Authorization'] = 'Bearer $token';
  request.fields['reason_id'] = reasonId.toString();
  request.fields['message'] = message;
  
  if (attachments != null) {
    for (var file in attachments) {
      request.files.add(await http.MultipartFile.fromPath(
        'attachments[]',
        file.path,
      ));
    }
  }
  
  final response = await request.send();
  final responseBody = await response.stream.bytesToString();
  return jsonDecode(responseBody);
}
Response (201):


{
    "message": "Ticket creado",
    "data": {
        "ticket": {
            "id": 5,
            "reason": { "id": 3, "name": "No aparecen mis puntos", "slug": "points_issue" },
            "status": "open",
            "priority": "medium",
            "messages": [
                {
                    "id": 1,
                    "message": "Hice una compra ayer y no me aparecen los puntos",
                    "is_from_admin": false,
                    "is_read": false,
                    "sender": { "type": "customer", "name": "Juan Pérez" },
                    "attachments": [
                        {
                            "id": 1,
                            "url": "https://api.example.com/storage/support/ticket_5_abc123.jpg",
                            "file_name": "captura.jpg",
                            "mime_type": "image/jpeg",
                            "file_size": 245678
                        }
                    ],
                    "created_at": "2026-01-23T12:00:00+00:00"
                }
            ],
            "created_at": "2026-01-23T12:00:00+00:00",
            "updated_at": "2026-01-23T12:00:00+00:00"
        }
    }
}
4. Ver Ticket con Mensajes

GET /api/v1/support/tickets/{id}
Nota: Al llamar este endpoint, automáticamente marca como leídos los mensajes del admin.

Response:


{
    "data": {
        "ticket": {
            "id": 5,
            "reason": { "id": 3, "name": "No aparecen mis puntos", "slug": "points_issue" },
            "status": "open",
            "priority": "medium",
            "assigned_to": { "id": 5, "name": "Carlos Soporte" },
            "messages": [
                {
                    "id": 1,
                    "message": "Hice una compra ayer y no me aparecen los puntos",
                    "is_from_admin": false,
                    "is_read": true,
                    "sender": { "type": "customer", "name": "Juan Pérez" },
                    "attachments": [],
                    "created_at": "2026-01-23T12:00:00+00:00"
                },
                {
                    "id": 2,
                    "message": "Hola Juan, revisando tu cuenta...",
                    "is_from_admin": true,
                    "is_read": true,
                    "sender": { "type": "admin", "name": "Carlos Soporte" },
                    "attachments": [],
                    "created_at": "2026-01-23T12:05:00+00:00"
                }
            ],
            "resolved_at": null,
            "created_at": "2026-01-23T12:00:00+00:00",
            "updated_at": "2026-01-23T12:05:00+00:00"
        }
    }
}
5. Enviar Mensaje

POST /api/v1/support/tickets/{id}/messages
Content-Type: multipart/form-data
Campo	Tipo	Requerido	Descripción
message	string	Condicional	Texto (requerido si no hay attachments)
attachments[]	file[]	Condicional	Imágenes (requerido si no hay message)
Response (201):


{
    "message": "Mensaje enviado",
    "data": {
        "message": {
            "id": 3,
            "message": "Gracias, adjunto mi recibo",
            "is_from_admin": false,
            "is_read": false,
            "sender": { "type": "customer", "name": "Juan Pérez" },
            "attachments": [],
            "created_at": "2026-01-23T12:10:00+00:00"
        }
    }
}
Errores:

403: "Sin acceso al ticket" (ticket de otro cliente)
422: "Ticket cerrado, no permite mensajes"
🔴 PARTE 3: WebSocket - Mensajes en Tiempo Real
Configuración del Cliente WebSocket
URL del servidor Reverb:


wss://your-domain.com/app/{app_key}
Autenticación del Canal Privado
Antes de suscribirse, Flutter debe autenticar el canal:


POST /api/v1/broadcasting/auth
Authorization: Bearer {token}
Content-Type: application/x-www-form-urlencoded

socket_id={socket_id}&channel_name=private-support.ticket.{ticket_id}
Response:


{
    "auth": "app_key:signature"
}
Suscripción al Canal

Canal: private-support.ticket.{ticket_id}
Evento: .message.sent
Payload del Evento message.sent:


{
    "message": {
        "id": 4,
        "message": "Respuesta del admin",
        "is_from_admin": true,
        "is_read": false,
        "sender": { "type": "admin", "name": "Carlos Soporte" },
        "attachments": [],
        "created_at": "2026-01-23T12:15:00+00:00"
    },
    "ticket_id": 5
}
Ejemplo Completo con laravel_echo (Dart)

import 'package:laravel_echo/laravel_echo.dart';
import 'package:pusher_client/pusher_client.dart';

class SupportChatService {
  late Echo echo;
  
  void connect(String token) {
    PusherClient pusher = PusherClient(
      'your-reverb-app-key',
      PusherOptions(
        host: 'your-domain.com',
        wsPort: 443,
        wssPort: 443,
        encrypted: true,
        auth: PusherAuth(
          'https://your-domain.com/api/v1/broadcasting/auth',
          headers: {
            'Authorization': 'Bearer $token',
            'Accept': 'application/json',
          },
        ),
      ),
    );
    
    echo = Echo(
      broadcaster: EchoBroadcasterType.Pusher,
      client: pusher,
    );
  }
  
  void subscribeToTicket(int ticketId, Function(dynamic) onMessage) {
    echo.private('support.ticket.$ticketId')
      .listen('.message.sent', (data) {
        // data['message'] contiene el mensaje nuevo
        // data['ticket_id'] contiene el ID del ticket
        onMessage(data);
      });
  }
  
  void unsubscribe(int ticketId) {
    echo.leave('support.ticket.$ticketId');
  }
  
  void disconnect() {
    echo.disconnect();
  }
}
📱 Flujo Completo de la App

┌─────────────────────────────────────────────────────────────────────┐
│                     PANTALLA DE LOGIN                                │
│  ┌─────────────────────────────────────────────────────────────┐    │
│  │ ¿Olvidaste tu contraseña?                                   │    │
│  │ → POST /auth/forgot-password (ya existente)                 │    │
│  │                                                             │    │
│  │ ¿No puedes acceder a tu cuenta?                             │    │
│  │ [Reportar problema] → POST /support/access-issues (público) │    │
│  └─────────────────────────────────────────────────────────────┘    │
├─────────────────────────────────────────────────────────────────────┤
│                     USUARIO AUTENTICADO                              │
├─────────────────────────────────────────────────────────────────────┤
│ 1. PANTALLA DE SOPORTE (Lista)                                      │
│    └─ GET /support/reasons → Cargar motivos disponibles             │
│    └─ GET /support/tickets → Mostrar tickets existentes             │
│       └─ Badge con unread_count por ticket                          │
│       └─ Botón "Nuevo ticket"                                       │
├─────────────────────────────────────────────────────────────────────┤
│ 2. CREAR NUEVO TICKET                                               │
│    └─ Usuario selecciona reason_id de la lista:                     │
│       • Problema con mi pedido                                      │
│       • Problema con pago                                           │
│       • No aparecen mis puntos  ← NUEVO                             │
│       • Mi cuenta                                                   │
│       • Sugerencia                                                  │
│       • Otro                                                        │
│    └─ Usuario escribe mensaje descriptivo                           │
│    └─ Opcionalmente adjunta capturas de pantalla                    │
│    └─ POST /support/tickets (multipart/form-data)                   │
│    └─ Navegar al chat del ticket creado                             │
├─────────────────────────────────────────────────────────────────────┤
│ 3. PANTALLA DE CHAT                                                 │
│    └─ GET /support/tickets/{id} → Cargar historial de mensajes      │
│    └─ Conectar WebSocket: private-support.ticket.{id}               │
│    └─ Escuchar evento: .message.sent                                │
│    └─ Al recibir mensaje nuevo → agregarlo a la lista               │
│    └─ Al enviar → POST /support/tickets/{id}/messages               │
│    └─ Si ticket.status == "closed" → deshabilitar input             │
├─────────────────────────────────────────────────────────────────────┤
│ 4. AL SALIR DEL CHAT                                                │
│    └─ Desconectar del canal WebSocket                               │
│    └─ Al volver a la lista, refrescar tickets                       │
└─────────────────────────────────────────────────────────────────────┘
⚠️ Consideraciones Importantes
Tema	Detalle
Tickets cerrados	No permiten enviar mensajes nuevos (status: "closed")
Imágenes	Máximo 4 por mensaje, 5MB cada una, formatos: jpeg/png/gif/webp
Mensajes sin leer	unread_count cuenta mensajes del admin sin leer
Marcar como leído	Se resetea automáticamente al hacer GET del ticket
WebSocket reconexión	Implementar reconexión automática ante pérdida de conexión
App en background	Volver a suscribirse cuando la app vuelve del background
is_from_admin	true = mensaje del equipo, false = mensaje del cliente
Rate limit público	El endpoint de access-issues tiene límite de 5 req/min
Recuperar contraseña	Usar /auth/forgot-password, NO el endpoint de access-issues
🛡️ Manejo de Errores

switch (response.statusCode) {
  case 200:
  case 201:
    // Éxito
    break;
  case 401:
    // No autenticado - redirigir a login
    break;
  case 403:
    // Sin permiso (ticket de otro usuario)
    showError("No tienes acceso a este ticket");
    break;
  case 422:
    // Error de validación o ticket cerrado
    final errors = jsonDecode(response.body);
    showValidationErrors(errors);
    break;
  case 429:
    // Rate limit
    showError("Demasiadas solicitudes. Intenta en un momento.");
    break;
  case 500:
    // Error del servidor
    showError("Error del servidor. Intenta más tarde.");
    break;
}
📋 Resumen de Endpoints
Método	Endpoint	Auth	Descripción
POST	/api/v1/support/access-issues	❌ No	Reportar problema de acceso
GET	/api/v1/support/reasons	✅ Sí	Listar motivos de soporte
GET	/api/v1/support/tickets	✅ Sí	Listar tickets del cliente
POST	/api/v1/support/tickets	✅ Sí	Crear nuevo ticket
GET	/api/v1/support/tickets/{id}	✅ Sí	Ver ticket con mensajes
POST	/api/v1/support/tickets/{id}/messages	✅ Sí	Enviar mensaje
POST	/api/v1/broadcasting/auth	✅ Sí	Autenticar canal WebSocket
Archivos modificados:

Archivo	Cambio
app/Http/Requests/Api/V1/Support/ReportAccessIssueRequest.php	Actualizado issue_type values
app/Models/AccessIssueReport.php	Actualizado labels
app/Http/Controllers/Api/V1/Support/SupportTicketController.php	Actualizado OpenAPI docs
database/seeders/SupportReasonSeeder.php	Agregado "No aparecen mis puntos"
