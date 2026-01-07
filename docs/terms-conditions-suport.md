⏺ He actualizado el plan para incluir:

  - Nueva tabla support_message_attachments para almacenar las imágenes
  - Almacenamiento en storage/app/public/support/tickets/{ticket_id}/
  - Validación: máximo 4 imágenes por mensaje, formatos jpeg/png/gif/webp, 5MB máximo
  - Modelo SupportMessageAttachment con relaciones

  Los tickets requieren autenticación Sanctum (usuario logueado desde SubwayApp).

──────────────────────────────────────────────────────────────────────────────────────────────────────────
 Ready to code?

 Here is Claude's plan:
╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌
 Plan: Sección de Soporte en Panel Admin

 Resumen

 Crear sección "Soporte" en la sidebar con 3 subsecciones:
 1. Términos y Condiciones - Editor TipTap con versionado
 2. Política de Privacidad - Editor TipTap con versionado
 3. Chat de Soporte - Sistema de chat con WebSockets (Laravel Reverb)

 ---
 1. Base de Datos

 Tabla legal_documents (T&C y Política de Privacidad)

 Schema::create('legal_documents', function (Blueprint $table) {
     $table->id();
     $table->enum('type', ['terms_and_conditions', 'privacy_policy']);
     $table->longText('content_json');  // TipTap JSON format
     $table->longText('content_html');  // HTML renderizado para Flutter
     $table->string('version')->default('1.0');
     $table->foreignId('created_by')->constrained('users');
     $table->boolean('is_published')->default(false);
     $table->timestamp('published_at')->nullable();
     $table->timestamps();
     $table->softDeletes();
 });

 Tabla support_tickets

 Schema::create('support_tickets', function (Blueprint $table) {
     $table->id();
     $table->foreignId('customer_id')->constrained()->onDelete('cascade');
     $table->string('subject');
     $table->enum('status', ['open', 'in_progress', 'resolved', 'closed'])->default('open');
     $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
     $table->foreignId('assigned_to')->nullable()->constrained('users');
     $table->timestamp('resolved_at')->nullable();
     $table->timestamps();
     $table->softDeletes();
 });

 Tabla support_messages

 Schema::create('support_messages', function (Blueprint $table) {
     $table->id();
     $table->foreignId('support_ticket_id')->constrained()->onDelete('cascade');
     $table->morphs('sender'); // Customer o User (admin)
     $table->text('message')->nullable(); // Puede ser null si solo envía imagen
     $table->boolean('is_read')->default(false);
     $table->timestamps();
 });

 Tabla support_message_attachments

 Schema::create('support_message_attachments', function (Blueprint $table) {
     $table->id();
     $table->foreignId('support_message_id')->constrained()->onDelete('cascade');
     $table->string('file_path');           // Ruta: support/tickets/{ticket_id}/{filename}
     $table->string('file_name');           // Nombre original del archivo
     $table->string('mime_type');           // image/jpeg, image/png, etc.
     $table->unsignedInteger('file_size');  // Tamaño en bytes
     $table->timestamps();
 });

 ---
 2. Modelos

 Archivos a crear:

 - app/Models/LegalDocument.php
 - app/Models/SupportTicket.php
 - app/Models/SupportMessage.php
 - app/Models/SupportMessageAttachment.php

 Relaciones clave:

 - SupportTicket → belongsTo Customer
 - SupportTicket → belongsTo User (assigned_to)
 - SupportTicket → hasMany SupportMessage
 - SupportMessage → morphTo sender
 - SupportMessage → hasMany SupportMessageAttachment
 - SupportMessageAttachment → belongsTo SupportMessage

 ---
 3. Controladores

 Backend (Panel Admin)

 app/Http/Controllers/Support/
 ├── LegalDocumentController.php      # CRUD T&C y Política
 ├── SupportTicketController.php      # Lista, ver, asignar, resolver
 └── SupportMessageController.php     # Enviar mensajes

 API (App móvil)

 app/Http/Controllers/Api/V1/Support/
 ├── LegalDocumentController.php      # GET términos y política
 ├── SupportTicketController.php      # Crear ticket, listar mis tickets
 └── SupportMessageController.php     # Enviar/recibir mensajes

 ---
 4. WebSockets (Laravel Reverb)

 Instalación

 php artisan install:broadcasting

 Eventos a crear:

 - app/Events/SupportMessageSent.php - Notifica nuevo mensaje
 - app/Events/TicketStatusChanged.php - Notifica cambio de estado

 Canales:

 - private-support.ticket.{ticketId} - Canal privado por ticket
 - private-support.admin - Canal para admins (nuevos tickets)

 Configuración .env:

 BROADCAST_CONNECTION=reverb
 REVERB_APP_ID=...
 REVERB_APP_KEY=...
 REVERB_APP_SECRET=...

 ---
 5. Frontend (Panel Admin)

 Páginas React:

 resources/js/Pages/support/
 ├── terms-and-conditions/
 │   ├── index.tsx      # Lista de versiones
 │   └── edit.tsx       # Editor TipTap
 ├── privacy-policy/
 │   ├── index.tsx      # Lista de versiones
 │   └── edit.tsx       # Editor TipTap
 └── tickets/
     ├── index.tsx      # Lista de tickets
     └── show.tsx       # Chat del ticket

 Componentes:

 resources/js/components/
 ├── tiptap-editor.tsx         # Editor TipTap reutilizable
 └── support-chat.tsx          # Componente de chat

 Dependencias npm a instalar:

 npm install @tiptap/react @tiptap/starter-kit @tiptap/extension-link @tiptap/extension-underline
 laravel-echo pusher-js

 ---
 6. Sidebar

 Modificar app-sidebar.tsx:

 Agregar al array systemPages:
 {
     name: 'support-terms',
     title: 'Términos y Condiciones',
     href: '/support/terms-and-conditions',
     icon: FileText,
     group: 'Soporte',
     permission: 'support.legal.view',
 },
 {
     name: 'support-privacy',
     title: 'Política de Privacidad',
     href: '/support/privacy-policy',
     icon: Shield,
     group: 'Soporte',
     permission: 'support.legal.view',
 },
 {
     name: 'support-tickets',
     title: 'Chat de Soporte',
     href: '/support/tickets',
     icon: MessageSquare,
     group: 'Soporte',
     permission: 'support.tickets.view',
 },

 Agregar a groupIcons:
 Soporte: Headset,

 ---
 7. API Endpoints

 Documentos legales (públicos):

 - GET /api/v1/legal/terms - Obtener términos vigentes
 - GET /api/v1/legal/privacy - Obtener política vigente

 Tickets (autenticados con Sanctum):

 - POST /api/v1/support/tickets - Crear ticket
 - GET /api/v1/support/tickets - Listar mis tickets
 - GET /api/v1/support/tickets/{id} - Ver ticket con mensajes
 - POST /api/v1/support/tickets/{id}/messages - Enviar mensaje (con imágenes opcionales)

 ---
 10. Almacenamiento de Imágenes

 Estructura de carpetas:

 storage/app/public/
 └── support/
     └── tickets/
         └── {ticket_id}/
             └── {uuid}.{ext}   # Ej: abc123.jpg

 Configuración:

 - Disco: public (accesible via URL)
 - Límite: máximo 4 imágenes por mensaje
 - Formatos: jpeg, png, gif, webp
 - Tamaño máximo: 5MB por imagen

 Validación en FormRequest:

 'attachments' => ['nullable', 'array', 'max:4'],
 'attachments.*' => ['image', 'mimes:jpeg,png,gif,webp', 'max:5120'],

 URL pública:

 /storage/support/tickets/{ticket_id}/{filename}

 ---
 8. Permisos

 Crear permisos en seeder:
 - support.legal.view
 - support.legal.edit
 - support.tickets.view
 - support.tickets.manage
 - support.tickets.assign

 ---
 11. Orden de Implementación

 1. Migraciones y Modelos
   - Crear migraciones para las 4 tablas
   - Crear modelos con relaciones
   - Crear carpeta storage/app/public/support
 2. Documentos Legales (Backend + Frontend)
   - Controlador panel admin
   - Instalar TipTap
   - Crear componente editor
   - Páginas de T&C y Política
   - API para app móvil
 3. Sistema de Tickets (Backend)
   - Controladores panel admin y API
   - Form Requests para validación (incluyendo attachments)
   - Resources para API
   - Lógica de almacenamiento de imágenes
 4. WebSockets
   - Instalar y configurar Reverb
   - Crear eventos de broadcast
   - Configurar canales privados
 5. Chat Frontend
   - Componente de chat con Laravel Echo
   - Página de tickets
   - Página de conversación con visor de imágenes
 6. Sidebar y Permisos
   - Agregar items a sidebar
   - Crear permisos
   - Asignar a roles

 ---
 Archivos Clave a Modificar

 | Archivo                                 | Acción                             |
 |-----------------------------------------|------------------------------------|
 | resources/js/components/app-sidebar.tsx | Agregar grupo Soporte              |
 | routes/web.php                          | Agregar rutas panel admin          |
 | routes/api.php                          | Agregar rutas API                  |
 | database/seeders/PermissionSeeder.php   | Agregar permisos                   |
 | package.json                            | Agregar dependencias TipTap y Echo |
 | .env                                    | Configurar Reverb                  |

 ---
 Notas Técnicas

 TipTap para Flutter

 - Guardar contenido en JSON (para edición futura) y HTML (para renderizar en Flutter)
 - Flutter puede usar flutter_html o flutter_widget_from_html para renderizar el HTML
 - La API devolverá el campo content_html para la app móvil

 WebSockets

 - Laravel Reverb es first-party y usa protocolo Pusher
 - Requiere ejecutar php artisan reverb:start en producción
 - El queue worker debe estar corriendo para broadcasting

 Versionado

 - Cada edición crea un nuevo registro con versión incrementada
 - Solo un documento puede estar "published" por tipo a la vez

 Configuración .env para Laravel Reverb

  # Broadcasting
  BROADCAST_CONNECTION=reverb

  # Reverb Server (donde corre el servidor WebSocket)
  REVERB_SERVER_HOST=0.0.0.0
  REVERB_SERVER_PORT=8080

  # Reverb App Credentials (genera valores únicos para producción)
  REVERB_APP_ID=1
  REVERB_APP_KEY=tu-app-key-unico
  REVERB_APP_SECRET=tu-app-secret-unico

  # Reverb Client (cómo los clientes se conectan)
  REVERB_HOST=localhost          # En producción: tu-dominio.com
  REVERB_PORT=8080               # En producción: 443
  REVERB_SCHEME=http             # En producción: https

  Para iniciar WebSockets:
  php artisan reverb:start      # Servidor WebSocket
  php artisan queue:work        # Worker para broadcasting

  ---
  Documentación para Desarrollador Flutter

  Endpoints API

  | Método | Endpoint                              | Auth    | Descripción                   |
  |--------|---------------------------------------|---------|-------------------------------|
  | GET    | /api/v1/legal/terms                   | No      | Términos y condiciones (HTML) |
  | GET    | /api/v1/legal/privacy                 | No      | Política de privacidad (HTML) |
  | GET    | /api/v1/support/tickets               | Sanctum | Listar mis tickets            |
  | POST   | /api/v1/support/tickets               | Sanctum | Crear ticket                  |
  | GET    | /api/v1/support/tickets/{id}          | Sanctum | Ver ticket con mensajes       |
  | POST   | /api/v1/support/tickets/{id}/messages | Sanctum | Enviar mensaje                |

  Documentos Legales (Público)

  // GET /api/v1/legal/terms
  {
    "data": {
      "type": "terms_and_conditions",
      "content_html": "<h1>Términos...</h1>",  // Renderizar con flutter_html
      "version": "1.0",
      "published_at": "2026-01-06T19:00:00Z"
    }
  }

  Crear Ticket

  // POST /api/v1/support/tickets
  // Headers: Authorization: Bearer {token}
  {
    "subject": "Problema con mi pedido",
    "message": "No recibí mi orden #123",
    "priority": "medium"  // low, medium, high
  }

  Enviar Mensaje con Imágenes

  // POST /api/v1/support/tickets/{id}/messages
  // Content-Type: multipart/form-data
  // Headers: Authorization: Bearer {token}

  FormData:
    - message: "Adjunto foto del problema"
    - attachments[]: File (max 4 imágenes, 5MB c/u)
    - attachments[]: File

  Respuesta de Mensaje

  {
    "data": {
      "id": 1,
      "message": "Texto del mensaje",
      "sender_type": "customer",  // o "admin"
      "is_read": false,
      "attachments": [
        {
          "id": 1,
          "url": "https://dominio.com/storage/support/tickets/1/abc.jpg",
          "file_name": "foto.jpg",
          "mime_type": "image/jpeg",
          "file_size": 102400
        }
      ],
      "created_at": "2026-01-06T19:15:00Z"
    }
  }

  WebSockets (Opcional - Tiempo Real)

  // Canal privado: private-support.ticket.{ticketId}
  // Evento: message.sent
  // Evento: ticket.status.changed

  // Usar pusher_client o laravel_echo para Flutter

  ---
  Validación del Plan ✅

  | Componente                                                                                      | Estado |
  |-------------------------------------------------------------------------------------------------|--------|
  | 4 Migraciones (legal_documents, support_tickets, support_messages, support_message_attachments) | ✅     |
  | 4 Modelos con relaciones                                                                        | ✅     |
  | Controladores Admin (LegalDocument, SupportTicket)                                              | ✅     |
  | Controladores API con Resources                                                                 | ✅     |
  | Componente TipTap Editor                                                                        | ✅     |
  | 6 Páginas Frontend (terms, privacy, tickets)                                                    | ✅     |
  | Sidebar con grupo Soporte                                                                       | ✅     |
  | Rutas web.php y api.php                                                                         | ✅     |
  | Eventos Broadcast (SupportMessageSent, TicketStatusChanged)                                     | ✅     |
  | Permissions Seeder (5 permisos)                                                                 | ✅     |
  | Form Requests con validación                                                                    | ✅     |
  | Storage para imágenes                                                                           | ✅     |

  Todo el plan fue implementado correctamente.
