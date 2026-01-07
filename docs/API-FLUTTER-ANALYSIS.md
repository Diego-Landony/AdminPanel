# Guia de API para Flutter - Subway Guatemala Loyalty App

**Base URL:** `https://admin.subwaycardgt.com/api/v1`

**Documentacion completa en:** https://admin.subwaycardgt.com/docs

**Autenticacion:** Bearer Token (Sanctum)
- Header: `Authorization: Bearer $token`
- Content-Type: `application/json`
- Accept: `application/json`

---

## Indice

1. [Autenticacion](#1-autenticacion)
2. [Usuario](#2-usuario)
   - [Perfil](#21-perfil)
   - [Direcciones](#22-direcciones)
   - [NITs (Facturacion)](#23-nits-facturacion)
   - [Dispositivos (FCM)](#24-dispositivos-fcm)
   - [Puntos y Recompensas](#25-puntos-y-recompensas)
   - [Favoritos](#26-favoritos)
   - [Historial de Pedidos](#27-historial-de-pedidos)
3. [Menu](#3-menu)
4. [Restaurantes](#4-restaurantes)
5. [Carrito](#5-carrito)
6. [Ordenes](#6-ordenes)
7. [Soporte (Tickets)](#7-soporte-tickets)

---

## Flujo General de la App

```
EXPLORAR MENU (Sin ubicacion)
├── Usuario puede ver menu SIN iniciar sesion
├── Usuario puede ver menu SIN seleccionar ubicacion
├── Precios mostrados: PICKUP CAPITAL (precio de referencia)
└── OBLIGATORIO mostrar disclaimer del API

 
```

### Widget de Disclaimer (OBLIGATORIO en pantallas de menu)

El API devuelve `price_disclaimer` en la respuesta. Usar el valor de `data.price_disclaimer`.

Ejemplo: "Precio referencia, pickup, capital. Este podría variar según tipo de servicio y ubicación."

---

## 1. Autenticacion

### 1.1 Registro

**POST** `/auth/register`

> **TERMINOS Y CONDICIONES (OBLIGATORIO)**
> El campo `terms_accepted` es **requerido**. Flutter debe mostrar un checkbox.
> Si es `false` o no se envia, retorna error 422.

**Request:**

| Campo | Tipo | Requerido | Descripcion |
|-------|------|-----------|-------------|
| first_name | string | Si | Nombre |
| last_name | string | Si | Apellido |
| email | string | Si | Email |
| password | string | Si | Contrasena |
| password_confirmation | string | Si | Confirmacion |
| phone | string | Si | Telefono |
| birth_date | string | Si | Fecha nacimiento (YYYY-MM-DD) |
| gender | string | Si | "male", "female", "other" |
| device_identifier | string | Si | UUID del dispositivo |
| terms_accepted | boolean | Si | Aceptacion de T&C |

**Response 201:**

| Campo | Descripcion |
|-------|-------------|
| message | "Registro exitoso. Por favor verifica tu email." |
| data.token | Token de autenticacion |
| data.token_type | "Bearer" |
| data.customer | Datos del cliente |

---

### 1.2 Login

**POST** `/auth/login`

**Request:**

| Campo | Tipo | Requerido |
|-------|------|-----------|
| email | string | Si |
| password | string | Si |
| device_identifier | string | Si |

**Response 200 - Exito:**

| Campo | Descripcion |
|-------|-------------|
| message | "Inicio de sesion exitoso." |
| data.token | Token de autenticacion |
| data.customer | Datos del cliente |

**Response 409 - Cuenta OAuth:**

| Campo | Descripcion |
|-------|-------------|
| error_code | "oauth_account_required" |
| data.oauth_provider | "google" |
| data.email | Email de la cuenta |

**Accion:** Redirigir a Google Sign-In

**Response 409 - Cuenta eliminada:**

| Campo | Descripcion |
|-------|-------------|
| error_code | "account_deleted_recoverable" |
| data.days_until_permanent_deletion | Dias restantes |
| data.points | Puntos pendientes |
| data.can_reactivate | true |

**Accion:** Ofrecer reactivacion

---

### 1.3 OAuth (Google)

**Flujo Mobile con Deep Link:**

1. Abrir navegador: `$baseUrl/auth/oauth/google/redirect?action=login&platform=mobile&device_id=$deviceId`
2. Escuchar deep link: `subwayapp://oauth/callback?token=...&customer_id=...&is_new_customer=...`

**Configuracion Android:**
- scheme: `subwayapp`
- host: `oauth`

---

### 1.4 Logout

| Endpoint | Descripcion |
|----------|-------------|
| POST `/auth/logout` | Cerrar sesion actual |
| POST `/auth/logout-all` | Cerrar todas las sesiones |

---

### 1.5 Renovar Token

**POST** `/auth/refresh`

**Response 200:**

| Campo | Descripcion |
|-------|-------------|
| data.token | Nuevo token |
| data.customer | Datos del cliente |

---

### 1.6 Recuperar Contrasena

**POST** `/auth/forgot-password`

| Campo | Tipo | Requerido |
|-------|------|-----------|
| email | string | Si |

**POST** `/auth/reset-password`

| Campo | Tipo | Requerido |
|-------|------|-----------|
| email | string | Si |
| password | string | Si |
| password_confirmation | string | Si |
| token | string | Si |

---

### 1.7 Verificacion de Email

> **IMPORTANTE:** Las ordenes requieren email verificado. Si el usuario intenta crear una orden sin email verificado, recibira error 403 con `error_code: "EMAIL_NOT_VERIFIED"`.

**GET** `/auth/email/verify/{id}/{hash}`

Verifica el email del usuario. Este endpoint es accedido via el link enviado por email.

| Parametro | Tipo | Descripcion |
|-----------|------|-------------|
| id | int | ID del usuario |
| hash | string | Hash de verificacion |

**POST** `/auth/email/resend`

Reenvia el email de verificacion al usuario autenticado.

**Response 200:**

| Campo | Descripcion |
|-------|-------------|
| message | "Email de verificacion enviado." |

**Errores posibles:**

| Codigo | Descripcion |
|--------|-------------|
| 429 | Rate limit - esperar antes de reenviar |

---

### 1.8 Reactivar Cuenta Eliminada

**POST** `/auth/reactivate`

**Request (cuenta local):**

| Campo | Tipo | Requerido |
|-------|------|-----------|
| email | string | Si |
| password | string | Si |
| device_identifier | string | Si |

**Request (cuenta OAuth):**

| Campo | Tipo | Requerido |
|-------|------|-----------|
| email | string | Si |
| device_identifier | string | Si |

---

## 2. Usuario

Esta seccion agrupa todos los endpoints relacionados con los datos del usuario: perfil, direcciones, NITs, dispositivos, puntos, favoritos e historial de pedidos.

---

### 2.1 Perfil

#### 2.1.1 Obtener Perfil

**GET** `/profile`

**Response 200 - data.customer:**

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | int | ID del cliente |
| first_name | string | Nombre |
| last_name | string | Apellido |
| email | string | Email |
| phone | string | Telefono |
| points | int | Puntos acumulados |
| oauth_provider | string | "local" o "google" |
| has_password | boolean | Puede usar email+contrasena |
| has_google_linked | boolean | Puede usar Google Sign-In |
| customer_type | object | Tipo de cliente |
| addresses | array | Direcciones guardadas |
| nits | array | NITs para facturacion |

---

#### 2.1.2 Actualizar Perfil

**PUT** `/profile`

> Todos los campos son opcionales. Si se cambia el email, la cuenta queda como NO verificada.

**Request (todos opcionales):**

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| first_name | string | Nombre |
| last_name | string | Apellido |
| email | string | Si cambia, marca como NO verificado |
| phone | string | Telefono |
| birth_date | string | Fecha nacimiento (YYYY-MM-DD) |
| gender | string | "male", "female", "other" |
| email_offers_enabled | boolean | Recibir ofertas por email |

**Response 200:**

| Campo | Descripcion |
|-------|-------------|
| message | "Perfil actualizado exitosamente." |
| data.customer | Datos actualizados |
| data.customer.email_verified | false si cambio email |

---

#### 2.1.3 Eliminar Cuenta

**DELETE** `/profile`

No requiere body.

**Response 200:**

| Campo | Descripcion |
|-------|-------------|
| message | "Cuenta eliminada exitosamente." |
| data.can_reactivate_until | Fecha limite para reactivar |
| data.days_to_reactivate | 30 dias |

> La cuenta se puede recuperar dentro de 30 dias usando `POST /auth/reactivate`

---

#### 2.1.4 Avatar

| Endpoint | Descripcion |
|----------|-------------|
| POST `/profile/avatar` | Subir avatar (body: `{ "avatar": "url" }`) |
| DELETE `/profile/avatar` | Eliminar avatar |

---

#### 2.1.5 Cambiar/Crear Contrasena

**PUT** `/profile/password`

> Endpoint unificado para cambiar contrasena (cuenta local) o crear primera contrasena (cuenta OAuth).

**Request (cuenta LOCAL):**

| Campo | Tipo | Requerido |
|-------|------|-----------|
| current_password | string | Si |
| password | string | Si |
| password_confirmation | string | Si |

**Request (cuenta OAUTH):**

| Campo | Tipo | Requerido |
|-------|------|-----------|
| password | string | Si |
| password_confirmation | string | Si |

> NO enviar `current_password` para cuentas OAuth

**Response 200:**

| Campo | Descripcion |
|-------|-------------|
| message | "Contrasena actualizada exitosamente." |
| data.password_created | true si era OAuth creando primera contrasena |
| data.can_use_password_login | true |

**Validaciones:**
- Minimo 8 caracteres
- Al menos 1 letra
- Al menos 1 numero
- Al menos 1 simbolo especial (!@#$%^&*...)
- No puede ser igual a la actual (solo para cambio)

**Errores posibles:**

| Codigo | Campo | Mensaje |
|--------|-------|---------|
| 422 | current_password | La contrasena actual es incorrecta |
| 422 | password | Las contrasenas no coinciden |
| 422 | password | La nueva contrasena debe ser diferente a la actual |

---

#### 2.1.6 Sistema de Vinculacion de Cuentas (OAuth Linking)

> El sistema maneja automaticamente la vinculacion de cuentas.

**Escenario 1: Usuario local hace login con Google**
- Resultado: Se vincula google_id, oauth_provider SIGUE siendo 'local', puede usar AMBOS metodos

**Escenario 2: Usuario Google crea contrasena**
- Resultado: Se guarda contrasena, oauth_provider cambia a 'local', puede usar AMBOS metodos

**Escenario 3: Usuario intenta login local con cuenta OAuth**
- Resultado: Error 409 con error_code='oauth_account_required'
- Accion: Redirigir al flujo OAuth de Google

**Campo oauth_provider:**

| Valor | Significado |
|-------|-------------|
| local | Puede usar email+contrasena (y puede tener Google vinculado) |
| google | Solo puede usar Google (hasta que cree contrasena) |

---

### 2.2 Direcciones

#### 2.2.1 Listar

**GET** `/addresses`

**Response 200 - data[]:**

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | int | ID |
| label | string | Etiqueta (Casa, Trabajo, etc.) |
| address_line | string | Direccion completa |
| latitude | float | Latitud |
| longitude | float | Longitud |
| zone | string | "capital" o "interior" |
| is_default | boolean | Si es la direccion por defecto |

---

#### 2.2.2 Ver Direccion

**GET** `/addresses/{id}`

Retorna los detalles de una direccion especifica.

**Response 200 - data:**

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | int | ID |
| label | string | Etiqueta |
| address_line | string | Direccion completa |
| latitude | float | Latitud |
| longitude | float | Longitud |
| zone | string | "capital" o "interior" |
| delivery_notes | string | Notas de entrega |
| is_default | boolean | Si es la direccion por defecto |

---

#### 2.2.3 Crear

**POST** `/addresses`

| Campo | Tipo | Requerido |
|-------|------|-----------|
| label | string | Si |
| address_line | string | Si |
| latitude | float | Si |
| longitude | float | Si |
| delivery_notes | string | No |
| is_default | boolean | No |

---

#### 2.2.4 Otros Endpoints

| Endpoint | Descripcion |
|----------|-------------|
| PUT `/addresses/{id}` | Actualizar |
| DELETE `/addresses/{id}` | Eliminar |
| POST `/addresses/{id}/set-default` | Establecer como default |

---

#### 2.2.5 Validar Ubicacion (Geofence)

**POST** `/addresses/validate`

| Campo | Tipo | Requerido |
|-------|------|-----------|
| latitude | float | Si |
| longitude | float | Si |

**Response 200 - Valida:**

| Campo | Descripcion |
|-------|-------------|
| data.is_valid | true |
| data.delivery_available | true |
| data.restaurant | Restaurante asignado |
| data.zone | "capital" o "interior" |

**Response 200 - Fuera de zona:**

| Campo | Descripcion |
|-------|-------------|
| data.is_valid | false |
| data.delivery_available | false |
| data.nearest_pickup_locations | Array de restaurantes cercanos |

---

### 2.3 NITs (Facturacion)

#### 2.3.1 Listar

**GET** `/nits`

Retorna todos los NITs del usuario.

---

#### 2.3.2 Ver NIT

**GET** `/nits/{id}`

Retorna los detalles de un NIT especifico.

**Response 200 - data:**

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | int | ID |
| nit | string | Numero de NIT |
| nit_name | string | Nombre asociado al NIT |
| nit_type | string | "personal", "company" o "other" |
| is_default | boolean | Si es el NIT por defecto |

---

#### 2.3.3 Crear

**POST** `/nits`

| Campo | Tipo | Requerido |
|-------|------|-----------|
| nit | string | Si |
| nit_type | string | No ("personal", "company", "other") |
| nit_name | string | No |
| is_default | boolean | No |

---

#### 2.3.4 Otros Endpoints

| Endpoint | Descripcion |
|----------|-------------|
| PUT `/nits/{id}` | Actualizar |
| DELETE `/nits/{id}` | Eliminar |
| POST `/nits/{id}/set-default` | Establecer como default |

---

### 2.4 Dispositivos (FCM)

#### 2.4.1 Registrar Dispositivo

**POST** `/devices/register`

| Campo | Tipo | Requerido |
|-------|------|-----------|
| fcm_token | string | Si |
| device_identifier | string | Si |
| device_name | string | No |

#### 2.4.2 Otros Endpoints

| Endpoint | Descripcion |
|----------|-------------|
| GET `/devices` | Listar |
| DELETE `/devices/{id}` | Desactivar |

---

### 2.5 Puntos y Recompensas

#### Sistema de Puntos

- **Acumulacion:** Configurable en admin (por defecto: 1 punto por cada Q10 gastados)
- **Canjeo:** Solo en tienda fisica (no disponible en la app)
- **Expiracion:** Configurable en admin (por defecto: 6 meses de inactividad)
  - Metodo Total: Todos los puntos expiran de golpe si hay inactividad
  - Metodo FIFO: Solo expiran los puntos mas antiguos

> **Nota:** La configuracion de puntos se gestiona desde el panel de administracion en Configuracion > Puntos.

#### Endpoints

| Endpoint | Descripcion |
|----------|-------------|
| GET `/points/balance` | Balance de puntos |
| GET `/points/history` | Historial de puntos |
| GET `/points/expiring` | Puntos proximos a expirar |
| GET `/rewards` | Catalogo de recompensas |

#### GET /points/expiring

Retorna informacion sobre los puntos que estan proximos a expirar.

**Response 200:**

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| data.points_expiring | int | Cantidad de puntos por expirar |
| data.expiration_date | string | Fecha de expiracion (ISO 8601) |
| data.days_until_expiration | int | Dias restantes |

**Tipos de transaccion:** `earned`, `redeemed`, `expired`, `bonus`, `adjustment`

---

### 2.6 Favoritos

| Endpoint | Descripcion |
|----------|-------------|
| GET `/favorites` | Listar favoritos |
| POST `/favorites` | Agregar (body: favorable_type, favorable_id) |
| DELETE `/favorites/{type}/{id}` | Eliminar (ej: /favorites/product/42) |

**favorable_type:** `product` o `combo`

---

### 2.7 Historial de Pedidos

| Endpoint | Descripcion |
|----------|-------------|
| GET `/orders` | Historial completo (params: per_page, status) |
| GET `/orders/active` | Ordenes activas |
| GET `/me/recent-orders` | Ultimas 5 ordenes |

---

## 3. Menu

> **IMPORTANTE - Disclaimer de Precios**
>
> El API devuelve `price_disclaimer` en la respuesta del menu.
> **Flutter DEBE mostrar este disclaimer** junto a los precios si no se ha elegido tipo de servicio o si es modo invitado.
>
> El campo `price` muestra el **precio de PICKUP en CAPITAL** (precio base de referencia).
>
> **El precio final se calcula automaticamente cuando:**
> - Selecciona restaurante para pickup → `PUT /cart/restaurant`

> - Selecciona direccion para delivery → `PUT /cart/delivery-address`

---

### 3.1 Menu Completo

**GET** `/menu`

**Response 200 (~112KB) - data:**

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| price_disclaimer | string | Disclaimer para mostrar en UI |
| categories | array | Categorias con productos |
| combos | array | Combos disponibles |

**Estructura de Categoria:**

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | int | ID |
| name | string | Nombre |
| description | string | Descripcion |
| image_url | string | URL de imagen |
| uses_variants | boolean | Si usa variantes |
| variant_definitions | array | ["15cm", "30cm"] |
| is_combo_category | boolean | Si es categoria de combos |
| sort_order | int | Orden |
| products | array | Productos |

**Estructura de Producto:**

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | int | ID |
| name | string | Nombre |
| price | float | Precio referencia (pickup_capital) |
| prices | object | Todos los precios por zona/servicio |
| badges | array | Badges activos |
| variants | array | Variantes disponibles |
| sections | array | Secciones de personalizacion |

**Estructura de prices:**

| Campo | Descripcion |
|-------|-------------|
| pickup_capital | Precio pickup zona capital |
| delivery_capital | Precio delivery zona capital |
| pickup_interior | Precio pickup zona interior |
| delivery_interior | Precio delivery zona interior |

---

### 3.1.1 Sistema de Badges

| Badge | Color | Uso |
|-------|-------|-----|
| nuevo | #f97316 (naranja) | Producto recien agregado |

**Tipos de validez:**
- `permanent` - Siempre visible
- `date_range` - Visible entre fechas especificas
- `weekdays` - Visible solo ciertos dias de la semana

> Solo se retornan badges **activos y validos**.

---

### 3.2 Menu Lite (RECOMENDADO)

**GET** `/menu?lite=1`

Response ~2KB (50x mas rapido). Retorna solo categorias con id, name, image_url, products_count.

**Flujo Recomendado:**
1. `GET /menu?lite=1` → Carga rapida
2. Usuario toca categoria
3. `GET /menu/categories/{id}` → Productos
4. `POST /cart/items` → Agregar al carrito

---

### 3.3 Otros Endpoints de Menu

| Endpoint | Descripcion |
|----------|-------------|
| GET `/menu/categories/{id}` | Categoria con productos |
| GET `/menu/products/{id}` | Producto detalle |
| GET `/menu/combos` | Todos los combos |
| GET `/menu/combos/{id}` | Combo detalle |
| GET `/menu/promotions` | Todas las promociones activas |
| GET `/menu/promotions/daily` | Sub del Dia |
| GET `/menu/promotions/daily?today=1` | Solo subs de hoy |
| GET `/menu/promotions/combinados` | Bundle Specials |

---

### 3.4 Featured (Para Home Screen)

**GET** `/menu/featured`

Query params: `limit` (default: 10, max: 50)

Retorna productos/combos con badges activos y tipos de badges disponibles.

**Response:**

| Campo | Descripcion |
|-------|-------------|
| badge_types | Tipos de badges con id, name, color, sort_order |
| products | Productos con badges activos |
| combos | Combos con badges activos |

**Uso:** Agrupar por badge_type_id para crear carruseles dinamicos.

---

### 3.5 Banners Promocionales

**GET** `/menu/banners`

**Aspect Ratios Pre-definidos:**

| Orientacion | Aspect Ratio | Dimensiones | Uso |
|-------------|--------------|-------------|-----|
| horizontal | 16:9 | 1920x1080px | Carrusel principal |
| vertical | 9:16 | 1080x1920px | Stories |

> Las imagenes ya vienen recortadas desde Admin Panel. Flutter NO debe recortar ni modificar proporciones.

**Response:**

| Campo | Descripcion |
|-------|-------------|
| horizontal | Banners 16:9 para carrusel principal |
| vertical | Banners 9:16 para stories |

**Estructura de Banner:**

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | int | ID |
| title | string | Titulo (opcional) |
| description | string | Descripcion (opcional) |
| image_url | string | URL de imagen |
| display_seconds | int | Tiempo de display (1-30) |
| link | object | Link de navegacion (opcional) |

**Tipos de Link:**

| Tipo | Accion |
|------|--------|
| product | Navegar a ProductDetailScreen(id) |
| combo | Navegar a ComboDetailScreen(id) |
| category | Navegar a CategoryScreen(id) |
| promotion | Navegar a PromotionDetailScreen(id) |
| url | Abrir en navegador |
| null | Sin accion |

---

## 4. Restaurantes

### 4.1 Listar

**GET** `/restaurants`

Query params opcionales:
- `delivery_active=true`
- `pickup_active=true`

---

### 4.2 Detalle

**GET** `/restaurants/{id}`

| Campo | Descripcion |
|-------|-------------|
| id | ID |
| name | Nombre |
| address | Direccion |
| latitude | Latitud |
| longitude | Longitud |
| is_open_now | Si esta abierto |
| today_schedule | Horario de hoy |

---

### 4.3 Restaurantes Cercanos

**GET** `/restaurants/nearby?lat=...&lng=...`

| Param | Tipo | Requerido | Default |
|-------|------|-----------|---------|
| lat | float | Si | - |
| lng | float | Si | - |
| radius_km | int | No | 10 (max: 50) |

---

### 4.4 Resenas

**GET** `/restaurants/{id}/reviews`

---

## 5. Carrito

### 5.1 Obtener Carrito

**GET** `/cart`

**Response - data:**

| Campo | Descripcion |
|-------|-------------|
| id | ID del carrito |
| restaurant | Restaurante asignado |
| service_type | "pickup" o "delivery" |
| zone | "capital" o "interior" |
| items | Items en el carrito |
| summary | Resumen de precios |
| can_checkout | Si puede hacer checkout |
| validation_messages | Mensajes de validacion |

**Estructura de Item:**

| Campo | Descripcion |
|-------|-------------|
| id | ID del item |
| product | Producto |
| variant | Variante seleccionada |
| quantity | Cantidad |
| unit_price | Precio unitario |
| subtotal | Subtotal |
| discount_amount | Descuento aplicado |
| final_price | Precio despues del descuento |
| is_daily_special | Si aplica Sub del Dia |
| applied_promotion | Promocion aplicada (null si ninguna) |

**Para mostrar precio tachado:**
- Si `discount_amount > 0`: mostrar `subtotal` tachado y `final_price` como precio actual
- Si `applied_promotion != null`: mostrar badge con nombre de promocion

---

### 5.2 Agregar Item

**POST** `/cart/items`

**Para Producto:**

| Campo | Tipo | Requerido |
|-------|------|-----------|
| product_id | int | Si |
| variant_id | int | No |
| quantity | int | Si |
| selected_options | array | No |
| notes | string | No |

**Para Combo:**

| Campo | Tipo | Requerido |
|-------|------|-----------|
| combo_id | int | Si |
| quantity | int | Si |
| combo_selections | array | Si |

---

### 5.3 Otros Endpoints

| Endpoint | Descripcion |
|----------|-------------|
| PUT `/cart/items/{id}` | Actualizar item (quantity, notes) |
| DELETE `/cart/items/{id}` | Eliminar item |
| DELETE `/cart` | Vaciar carrito |
| PUT `/cart/restaurant` | Cambiar restaurante |
| PUT `/cart/service-type` | Cambiar tipo de servicio |
| POST `/cart/validate` | Validar carrito |

---

### 5.4 Asignar Direccion de Entrega

**PUT** `/cart/delivery-address`

| Campo | Tipo | Requerido |
|-------|------|-----------|
| delivery_address_id | int | Si |

**Response 200 - Exito:**

| Campo | Descripcion |
|-------|-------------|
| delivery_address | Direccion asignada |
| assigned_restaurant | Restaurante asignado |
| zone | "capital" o "interior" |
| prices_updated | true |

**Response 422 - Fuera de zona:**

| Campo | Descripcion |
|-------|-------------|
| error_code | "ADDRESS_OUTSIDE_DELIVERY_ZONE" |
| nearest_pickup_locations | Restaurantes cercanos para pickup |

---

## 6. Ordenes

### 6.1 Crear Orden

**POST** `/orders`

> **REQUIERE EMAIL VERIFICADO**
> Si el email no esta verificado, retorna error 403 con `error_code: "EMAIL_NOT_VERIFIED"`.
> Flutter debe mostrar pantalla para verificar email y ofrecer reenvio (`POST /auth/email/resend`).

**Request (Pickup):**

| Campo | Tipo | Requerido |
|-------|------|-----------|
| restaurant_id | int | Si |
| service_type | string | Si ("pickup") |
| scheduled_pickup_time | string | No (ISO 8601) |
| payment_method | string | Si |
| nit_id | int | No |
| notes | string | No |

**Request (Delivery):**

| Campo | Tipo | Requerido |
|-------|------|-----------|
| service_type | string | Si ("delivery") |
| delivery_address_id | int | Si |
| payment_method | string | Si |
| nit_id | int | No |
| notes | string | No |

**payment_method:** `cash`, `card`, `online`

> El pago se procesa en POS, no en la app.

---

### 6.2 Endpoints de Orden

| Endpoint | Descripcion |
|----------|-------------|
| GET `/orders/{id}` | Detalle de orden |
| GET `/orders/{id}/track` | Tracking de orden |
| POST `/orders/{id}/cancel` | Cancelar (solo pending/confirmed) |
| POST `/orders/{id}/reorder` | Reordenar |
| POST `/orders/{id}/review` | Calificar (solo completed/delivered) |

---

### 6.3 Flujo de Estados

**Pickup:** `pending` → `confirmed` → `preparing` → `ready` → `completed`

**Delivery:** `pending` → `confirmed` → `preparing` → `ready` → `out_for_delivery` → `delivered` → `completed`

---

## Manejo de Errores

| Codigo | Significado | Accion |
|--------|-------------|--------|
| 401 | No autenticado | Redirigir a login |
| 403 | Sin permisos / Email no verificado | Ver campo `error_code` |
| 409 | Conflicto (OAuth/cuenta eliminada) | Ver campo `error_code` |
| 422 | Error de validacion | Mostrar errores |
| 429 | Rate limit | Esperar y reintentar |

---

## Sistema de Precios y Zonas

| Tipo de Servicio | Zona | Campo |
|------------------|------|-------|
| Pickup | Capital | precio_pickup_capital |
| Pickup | Interior | precio_pickup_interior |
| Delivery | Capital | precio_domicilio_capital |
| Delivery | Interior | precio_domicilio_interior |

**Para Pickup:**
- Zona determinada por `restaurant.price_location`
- `PUT /cart/restaurant` → service_type='pickup', zone=restaurant.price_location, precios recalculados

**Para Delivery:**
- Zona determinada por `address.zone`
- `PUT /cart/delivery-address` → valida geofence, asigna restaurante, service_type='delivery', zone=address.zone, precios recalculados

---

## Flujos Principales

### Flujo de Registro/Login

1. POST /auth/register o POST /auth/login
2. Guardar token en secure storage
3. POST /devices/register (FCM token)
4. GET /profile

### Flujo de Pedido (Pickup)

1. GET /menu?lite=1 → Carga rapida
2. GET /menu/categories/{id} → Productos
3. POST /cart/items → Agregar (precio temporal)
4. GET /restaurants?pickup_active=true → Lista restaurantes
5. PUT /cart/restaurant → Seleccionar (precios recalculados)
6. GET /cart → Ver precios correctos
7. POST /cart/validate → Validar
8. POST /orders → Crear orden
9. GET /orders/{id}/track → Seguimiento

### Flujo de Pedido (Delivery)

1. GET /menu?lite=1
2. POST /cart/items → Agregar (precio temporal)
3. GET /addresses → Direcciones guardadas
4. PUT /cart/delivery-address → Seleccionar (precios recalculados)
5. Si error 422 → Mostrar pickup locations cercanos
6. GET /cart → Ver precios correctos
7. POST /cart/validate
8. POST /orders
9. GET /orders/{id}/track

---

## 7. Soporte (Tickets)

Sistema de comunicacion entre clientes y Subway Guatemala para quejas, consultas y soporte.

### 7.1 Listar Motivos de Soporte

**GET** `/support/reasons`

Retorna la lista de motivos disponibles que el cliente puede seleccionar al crear un ticket.

**Response 200 - data.reasons[]:**

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | int | ID del motivo |
| name | string | Nombre a mostrar ("Problema con mi pedido") |
| slug | string | Identificador interno ("order_issue") |

**Ejemplo de respuesta:**
```json
{
  "data": {
    "reasons": [
      { "id": 1, "name": "Problema con mi pedido", "slug": "order_issue" },
      { "id": 2, "name": "Problema con pago", "slug": "payment" },
      { "id": 3, "name": "Mi cuenta", "slug": "account" },
      { "id": 4, "name": "Sugerencia", "slug": "suggestion" },
      { "id": 5, "name": "Otro", "slug": "other" }
    ]
  }
}
```

**Flutter UI:** Mostrar como dropdown o lista de opciones al crear ticket.

---

### 7.2 Listar Tickets

**GET** `/support/tickets`

Retorna todos los tickets del cliente autenticado.

**Response 200 - data.tickets[]:**

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | int | ID del ticket |
| reason | object | Motivo del ticket (id, name, slug) |
| status | string | "open" o "closed" |
| priority | string | "low", "medium", "high" |
| unread_count | int | Mensajes no leidos del admin |
| latest_message | object | Ultimo mensaje |
| assigned_to | object | Admin asignado (id, name) |
| created_at | string | Fecha creacion (ISO 8601) |
| updated_at | string | Fecha actualizacion |

**Estructura de reason:**

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | int | ID del motivo |
| name | string | Nombre del motivo |
| slug | string | Identificador |

**Estructura de latest_message:**

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| message | string | Contenido del mensaje |
| created_at | string | Fecha (ISO 8601) |
| is_from_admin | boolean | true si es del admin |

---

### 7.3 Crear Ticket

**POST** `/support/tickets`

Content-Type: `multipart/form-data`

**Request:**

| Campo | Tipo | Requerido | Descripcion |
|-------|------|-----------|-------------|
| reason_id | int | Si | ID del motivo (de /support/reasons) |
| message | string | Si | Mensaje inicial (max 5000) |
| attachments[] | file | No | Imagenes adjuntas (max 4) |

**Validacion de attachments:**
- Maximo 4 imagenes por mensaje
- Maximo 5MB por imagen
- Formatos: jpeg, png, gif, webp

**Response 201:**

| Campo | Descripcion |
|-------|-------------|
| message | "Ticket creado exitosamente." |
| data.ticket | Ticket completo con mensajes |

**Ejemplo:**
```
POST /support/tickets
Content-Type: multipart/form-data

reason_id: 1
message: "No recibi mi sub completo, faltaba..."
attachments[0]: [imagen.jpg]
```

**Errores:**

| Codigo | Campo | Descripcion |
|--------|-------|-------------|
| 422 | reason_id | El motivo es obligatorio |
| 422 | reason_id | El motivo seleccionado no es valido |

---

### 7.4 Ver Ticket

**GET** `/support/tickets/{id}`

Retorna el ticket con todos sus mensajes. Automaticamente marca como leidos los mensajes del admin.

**Response 200 - data.ticket:**

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | int | ID del ticket |
| reason | object | Motivo del ticket |
| status | string | Estado actual |
| priority | string | Prioridad |
| messages | array | Todos los mensajes |
| assigned_to | object | Admin asignado |
| resolved_at | string | Fecha resolucion (si aplica) |
| created_at | string | Fecha creacion |

**Estructura de message:**

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | int | ID del mensaje |
| message | string | Contenido (puede ser null si solo imagen) |
| is_from_admin | boolean | true si es del admin |
| is_read | boolean | Si fue leido |
| sender | object | Quien envio (type, name) |
| attachments | array | Imagenes adjuntas |
| created_at | string | Fecha (ISO 8601) |

**Estructura de attachment:**

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | int | ID |
| url | string | URL de la imagen |
| file_name | string | Nombre original |
| mime_type | string | Tipo MIME |
| file_size | int | Tamano en bytes |

**Errores:**

| Codigo | Descripcion |
|--------|-------------|
| 403 | No tienes acceso a este ticket |

---

### 7.5 Enviar Mensaje

**POST** `/support/tickets/{id}/messages`

Content-Type: `multipart/form-data`

**Request:**

| Campo | Tipo | Requerido | Descripcion |
|-------|------|-----------|-------------|
| message | string | Condicional | Texto (requerido si no hay attachments) |
| attachments[] | file | Condicional | Imagenes (requerido si no hay message) |

**Response 201:**

| Campo | Descripcion |
|-------|-------------|
| message | "Mensaje enviado exitosamente." |
| data.message | Mensaje creado |

**Errores:**

| Codigo | Descripcion |
|--------|-------------|
| 403 | No tienes acceso a este ticket |
| 422 | No puedes enviar mensajes a un ticket cerrado |

---

### 7.6 Estados del Ticket

| Estado | Descripcion | Puede enviar mensajes |
|--------|-------------|----------------------|
| open | Ticket abierto/activo | Si |
| closed | Ticket cerrado | No |

---

### 7.7 Flujo de Soporte

```
OBTENER MOTIVOS
├── GET /support/reasons
└── Cachear lista de motivos
        ↓
CREAR TICKET
├── Usuario selecciona motivo (dropdown)
├── POST /support/tickets
│   ├── reason_id (obligatorio)
│   ├── message (obligatorio)
│   └── attachments[] (opcional)
        ↓
VER TICKETS
├── GET /support/tickets
├── Listar todos mis tickets
└── Ver unread_count para badge
        ↓
CONVERSACION
├── GET /support/tickets/{id} → Ver mensajes
├── POST /support/tickets/{id}/messages → Responder
└── Repetir hasta resolucion
        ↓
TICKET CERRADO
├── Status cambia a "closed"
└── No se pueden enviar mas mensajes
```

---

### 7.8 Tiempo Real (Opcional)

El sistema soporta WebSockets via Laravel Reverb para actualizaciones en tiempo real.

**Canal:** `private-support.ticket.{ticketId}`

**Eventos:**
- `message.sent` - Nuevo mensaje en el ticket
- `ticket.status.changed` - Cambio de estado

**Autenticacion WebSocket:**
Flutter debe autenticarse con el mismo Bearer token via `/broadcasting/auth`.

> Nota: La implementacion de WebSockets en Flutter es opcional. El sistema funciona perfectamente con polling o refresh manual.

---

### 7.8 UI Recomendada

**Pantalla de Lista de Tickets:**
- Lista de tickets ordenados por fecha
- Badge con unread_count
- Indicador de estado (abierto/cerrado)
- Preview del ultimo mensaje

**Pantalla de Chat:**
- Mensajes tipo chat (cliente izquierda, admin derecha)
- Input de texto con boton de adjuntar imagen
- Vista previa de imagenes antes de enviar
- Deshabilitar input si ticket cerrado

---

## Historial de Cambios

| Fecha | Cambio |
|-------|--------|
| 2026-01-07 | Agregada seccion 7: Soporte (Tickets) - sistema de comunicacion cliente-soporte |
| 2026-01-07 | Reorganizada seccion Usuario (perfil, direcciones, NITs, dispositivos, puntos, favoritos) |
| 2026-01-07 | Agregado GET /addresses/{id} |
| 2026-01-07 | Agregado GET /nits/{id} |
| 2026-01-07 | Agregado GET /points/expiring |
| 2026-01-07 | Agregado POST /auth/email/resend |
| 2026-01-07 | Agregado GET /auth/email/verify/{id}/{hash} |
| 2026-01-05 | Agregado has_password, has_google_linked al perfil |
| 2026-01-05 | Documentado sistema de vinculacion OAuth |
| 2026-01-05 | Banners pre-recortados (16:9 horizontal, 9:16 vertical) |
| 2025-12-23 | Agregado GET /menu/banners |
| 2025-12-23 | Agregado GET /menu/featured |
| 2025-12-23 | POST /auth/register requiere terms_accepted |
| 2025-12-23 | POST /orders requiere email verificado |
| 2025-12-23 | GET /menu devuelve price_disclaimer |
| 2025-12-22 | Sistema de Precios y Zonas |
| 2025-12-22 | Campos de descuento por item en carrito |
| 2025-12-22 | Endpoint /menu?lite=1 |
