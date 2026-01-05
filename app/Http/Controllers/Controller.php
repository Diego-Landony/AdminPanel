<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     version="1.0.3",
 *     title="Subway Guatemala Customer API",
 *     description="API REST para la aplicación móvil de clientes de Subway Guatemala. Incluye autenticación multi-canal (email/password y Google OAuth), gestión de perfil, direcciones con validación de geofencing, NITs para facturación, sistema de favoritos, sistema de puntos de lealtad, dispositivos FCM para notificaciones push, sistema de pedidos y más.",
 *
 *     @OA\Contact(
 *         email="dev@subwayguatemala.com"
 *     )
 * )
 *
 * @OA\Server(
 *     url="http://localhost:8000",
 *     description="Local Development Server"
 * )
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="Application Server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Laravel Sanctum Bearer Token. Obtain from /api/v1/auth/login, /api/v1/auth/register, or OAuth endpoints."
 * )
 *
 * @OA\Tag(
 *     name="Authentication",
 *     description="Endpoints para registro, inicio de sesión, recuperación de contraseña y verificación de email"
 * )
 * @OA\Tag(
 *     name="OAuth",
 *     description="Endpoints para autenticación con Google OAuth"
 * )
 * @OA\Tag(
 *     name="Profile",
 *     description="Gestión del perfil del cliente"
 * )
 * @OA\Tag(
 *     name="Devices",
 *     description="Gestión de dispositivos y tokens FCM para notificaciones push"
 * )
 * @OA\Tag(
 *     name="Addresses",
 *     description="Gestión de direcciones de entrega del cliente"
 * )
 * @OA\Tag(
 *     name="NITs",
 *     description="Gestión de NITs para facturación"
 * )
 * @OA\Tag(
 *     name="Favorites",
 *     description="Gestión de productos y combos favoritos"
 * )
 * @OA\Tag(
 *     name="Points",
 *     description="Sistema de puntos de lealtad y recompensas"
 * )
 * @OA\Tag(
 *     name="Menu",
 *     description="Endpoints públicos para acceder al menú completo, categorías, productos, combos y promociones"
 * )
 * @OA\Tag(
 *     name="Restaurants",
 *     description="Endpoints públicos para listar restaurantes, buscar cercanos y ver reseñas"
 * )
 * @OA\Tag(
 *     name="Product Views",
 *     description="Endpoints autenticados para registrar vistas de productos y combos"
 * )
 * @OA\Tag(
 *     name="Cart",
 *     description="Gestión del carrito de compras"
 * )
 * @OA\Tag(
 *     name="Orders",
 *     description="Creación y gestión de órdenes"
 * )
 *
 * @OA\Schema(
 *     schema="Customer",
 *     type="object",
 *     title="Customer",
 *     description="Customer model with profile information and loyalty data",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="first_name", type="string", example="Juan"),
 *     @OA\Property(property="last_name", type="string", example="Pérez"),
 *     @OA\Property(property="full_name", type="string", example="Juan Pérez", description="Computed full name"),
 *     @OA\Property(property="email", type="string", format="email", example="juan@example.com"),
 *     @OA\Property(property="email_verified_at", type="string", format="date-time", nullable=true, example="2024-01-15T10:30:00Z"),
 *     @OA\Property(property="avatar", type="string", nullable=true, example="https://example.com/avatar.jpg"),
 *     @OA\Property(property="oauth_provider", type="string", enum={"local","google"}, example="local"),
 *     @OA\Property(property="subway_card", type="string", example="802056895224", description="Unique loyalty card number (10-12 digits)"),
 *     @OA\Property(property="birth_date", type="string", format="date", nullable=true, example="1990-05-15"),
 *     @OA\Property(property="gender", type="string", enum={"male","female","other"}, nullable=true, example="male"),
 *     @OA\Property(property="phone", type="string", nullable=true, example="12345678", description="8-digit phone number (Guatemala)"),
 *     @OA\Property(property="last_login_at", type="string", format="date-time", nullable=true, example="2024-01-20T15:45:00Z"),
 *     @OA\Property(property="last_activity_at", type="string", format="date-time", nullable=true, example="2024-01-20T16:00:00Z"),
 *     @OA\Property(property="last_purchase_at", type="string", format="date-time", nullable=true, example="2024-01-18T12:30:00Z"),
 *     @OA\Property(property="points", type="integer", example=150, description="Available loyalty points"),
 *     @OA\Property(property="points_updated_at", type="string", format="date-time", nullable=true, example="2024-01-18T12:30:00Z"),
 *     @OA\Property(property="status", type="string", enum={"active","inactive","suspended"}, example="active"),
 *     @OA\Property(property="is_online", type="boolean", example=true, description="True if customer is currently active"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-10T08:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-20T15:45:00Z")
 * )
 *
 * @OA\Schema(
 *     schema="CustomerDevice",
 *     type="object",
 *     title="CustomerDevice",
 *     description="Customer device registered for push notifications",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="customer_id", type="integer", example=1),
 *     @OA\Property(property="sanctum_token_id", type="integer", nullable=true, example=5),
 *     @OA\Property(property="fcm_token", type="string", nullable=true, example="fKw8h4Xj..."),
 *     @OA\Property(property="device_identifier", type="string", example="550e8400-e29b-41d4-a716-446655440000"),
 *     @OA\Property(property="device_name", type="string", nullable=true, example="iPhone 14 Pro de Juan"),
 *     @OA\Property(property="last_used_at", type="string", format="date-time", nullable=true, example="2024-01-20T15:45:00Z"),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(property="login_count", type="integer", example=15),
 *     @OA\Property(property="is_current_device", type="boolean", example=true, description="True if this device is associated with current token"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-10T08:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-20T15:45:00Z")
 * )
 *
 * @OA\Schema(
 *     schema="PriceZones",
 *     type="object",
 *     title="Price Zones",
 *     description="Sistema de precios con 4 zonas: pickup capital, domicilio capital, pickup interior, domicilio interior",
 *
 *     @OA\Property(property="pickup_capital", type="number", format="float", example=45.00, description="Precio para pickup en Ciudad de Guatemala"),
 *     @OA\Property(property="domicilio_capital", type="number", format="float", example=50.00, description="Precio para domicilio en Ciudad de Guatemala"),
 *     @OA\Property(property="pickup_interior", type="number", format="float", example=48.00, description="Precio para pickup en el interior"),
 *     @OA\Property(property="domicilio_interior", type="number", format="float", example=53.00, description="Precio para domicilio en el interior")
 * )
 *
 * @OA\Schema(
 *     schema="Category",
 *     type="object",
 *     title="Category",
 *     description="Categoría del menú",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Sandwiches"),
 *     @OA\Property(property="description", type="string", nullable=true, example="Los mejores subs de Subway"),
 *     @OA\Property(property="image_url", type="string", nullable=true, example="https://admin.subwaycardgt.com/storage/categories/sandwiches.jpg"),
 *     @OA\Property(property="uses_variants", type="boolean", example=true, description="Si la categoría usa variantes (ej: tamaños)"),
 *     @OA\Property(property="variant_definitions", type="object", nullable=true, description="Definiciones de variantes para esta categoría"),
 *     @OA\Property(property="is_combo_category", type="boolean", example=false),
 *     @OA\Property(property="sort_order", type="integer", example=1),
 *     @OA\Property(
 *         property="products",
 *         type="array",
 *         description="Productos de esta categoría (solo cuando se carga la relación)",
 *
 *         @OA\Items(ref="#/components/schemas/Product")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="Banner",
 *     type="object",
 *     title="Banner",
 *     description="Banner promocional para carrusel",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="title", type="string", example="Nuevo Menu de Verano"),
 *     @OA\Property(property="description", type="string", nullable=true, example="Prueba nuestros nuevos subs"),
 *     @OA\Property(property="image_url", type="string", example="https://admin.subwaycardgt.com/storage/banners/summer.jpg"),
 *     @OA\Property(property="display_seconds", type="integer", example=5, description="Segundos a mostrar en carrusel"),
 *     @OA\Property(property="link", type="object", nullable=true, description="Accion al tap",
 *         @OA\Property(property="type", type="string", example="product", description="product, combo, category, promotion, url"),
 *         @OA\Property(property="id", type="integer", example=42, nullable=true, description="ID de la entidad si type no es url"),
 *         @OA\Property(property="url", type="string", example="https://example.com", nullable=true, description="URL si type es url")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="Product",
 *     type="object",
 *     title="Product",
 *     description="Producto del menú con sistema de precios de 4 zonas",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Italian BMT"),
 *     @OA\Property(property="description", type="string", nullable=true, example="Salami Genoa, salami picante y jamón"),
 *     @OA\Property(property="image_url", type="string", nullable=true, example="https://admin.subwaycardgt.com/storage/products/italian-bmt.jpg"),
 *     @OA\Property(property="category_id", type="integer", example=1),
 *     @OA\Property(property="has_variants", type="boolean", example=true, description="Si el producto tiene variantes de tamaño"),
 *     @OA\Property(property="precio", type="number", format="float", example=45.00, description="Precio de referencia (pickup_capital) para mostrar en UI"),
 *     @OA\Property(property="prices", ref="#/components/schemas/PriceZones", description="Precios por zona (4 zonas)"),
 *     @OA\Property(property="is_redeemable", type="boolean", example=false, description="Si se puede canjear con puntos"),
 *     @OA\Property(property="points_cost", type="integer", nullable=true, example=500, description="Costo en puntos si is_redeemable=true"),
 *     @OA\Property(
 *         property="variants",
 *         type="array",
 *         description="Variantes del producto (ej: 15cm, 30cm)",
 *
 *         @OA\Items(ref="#/components/schemas/ProductVariant")
 *     ),
 *
 *     @OA\Property(
 *         property="sections",
 *         type="array",
 *         description="Secciones de personalización (vegetales, salsas, etc)",
 *
 *         @OA\Items(type="object")
 *     ),
 *
 *     @OA\Property(
 *         property="badges",
 *         type="array",
 *         description="Badges del producto (ej: Nuevo, Popular)",
 *
 *         @OA\Items(type="object")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="ProductVariant",
 *     type="object",
 *     title="Product Variant",
 *     description="Variante de producto con precios por zona",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="sku", type="string", example="ITL-15CM"),
 *     @OA\Property(property="name", type="string", example="15 cm"),
 *     @OA\Property(property="size", type="string", nullable=true, example="15cm"),
 *     @OA\Property(property="precio", type="number", format="float", example=35.00, description="Precio de referencia (pickup_capital) para mostrar en UI"),
 *     @OA\Property(property="prices", ref="#/components/schemas/PriceZones"),
 *     @OA\Property(property="is_redeemable", type="boolean", example=false),
 *     @OA\Property(property="points_cost", type="integer", nullable=true, example=300),
 *     @OA\Property(property="is_daily_special", type="boolean", example=false, description="Si es Sub del Día"),
 *     @OA\Property(property="daily_special_days", type="array", nullable=true, @OA\Items(type="integer"), example={1,3,5}, description="Días de la semana (1=Lunes, 7=Domingo)"),
 *     @OA\Property(
 *         property="daily_special_prices",
 *         type="object",
 *         nullable=true,
 *         description="Precios especiales para Sub del Día",
 *         @OA\Property(property="pickup_capital", type="number", format="float", example=35.00),
 *         @OA\Property(property="domicilio_capital", type="number", format="float", example=40.00),
 *         @OA\Property(property="pickup_interior", type="number", format="float", example=38.00),
 *         @OA\Property(property="domicilio_interior", type="number", format="float", example=43.00)
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="Combo",
 *     type="object",
 *     title="Combo",
 *     description="Combo del menú con precios por zona",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Combo Clásico"),
 *     @OA\Property(property="description", type="string", nullable=true, example="Sub de 15cm + papas + bebida"),
 *     @OA\Property(property="image_url", type="string", nullable=true, example="https://admin.subwaycardgt.com/storage/combos/combo-clasico.jpg"),
 *     @OA\Property(property="precio", type="number", format="float", example=75.00, description="Precio de referencia (pickup_capital) para mostrar en UI"),
 *     @OA\Property(property="prices", ref="#/components/schemas/PriceZones"),
 *     @OA\Property(property="is_redeemable", type="boolean", example=false),
 *     @OA\Property(property="points_cost", type="integer", nullable=true, example=1000),
 *     @OA\Property(property="is_available", type="boolean", example=true, description="Si el combo está disponible actualmente"),
 *     @OA\Property(property="category_id", type="integer", example=2),
 *     @OA\Property(property="sort_order", type="integer", example=1),
 *     @OA\Property(
 *         property="items",
 *         type="array",
 *         description="Items del combo",
 *
 *         @OA\Items(type="object")
 *     ),
 *
 *     @OA\Property(
 *         property="badges",
 *         type="array",
 *         description="Badges del combo",
 *
 *         @OA\Items(type="object")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="Promotion",
 *     type="object",
 *     title="Promotion",
 *     description="Promoción activa (Sub del Día o Combinado)",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Sub del Día - Lunes"),
 *     @OA\Property(property="description", type="string", nullable=true, example="Italian BMT 15cm a precio especial"),
 *     @OA\Property(property="image_url", type="string", nullable=true, example=null),
 *     @OA\Property(property="type", type="string", example="daily_special", description="Tipo: daily_special o bundle"),
 *     @OA\Property(
 *         property="prices",
 *         type="object",
 *         description="Precios para promociones tipo bundle",
 *         @OA\Property(property="bundle_capital", type="number", format="float", nullable=true, example=85.00),
 *         @OA\Property(property="bundle_interior", type="number", format="float", nullable=true, example=90.00)
 *     ),
 *     @OA\Property(property="valid_from", type="string", format="date", nullable=true, example="2025-01-01"),
 *     @OA\Property(property="valid_until", type="string", format="date", nullable=true, example="2025-12-31"),
 *     @OA\Property(property="time_from", type="string", format="time", nullable=true, example="11:00:00"),
 *     @OA\Property(property="time_until", type="string", format="time", nullable=true, example="22:00:00"),
 *     @OA\Property(property="weekdays", type="array", nullable=true, @OA\Items(type="integer"), example={1,3,5}),
 *     @OA\Property(property="sort_order", type="integer", example=1),
 *     @OA\Property(
 *         property="items",
 *         type="array",
 *         description="Items de la promoción (para Sub del Día)",
 *
 *         @OA\Items(type="object")
 *     ),
 *
 *     @OA\Property(
 *         property="bundle_items",
 *         type="array",
 *         description="Items del bundle (para Combinados)",
 *
 *         @OA\Items(type="object")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="Restaurant",
 *     type="object",
 *     title="Restaurant",
 *     description="Restaurante con información de ubicación, horarios y servicios",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Subway Pradera Concepción"),
 *     @OA\Property(property="address", type="string", example="Centro Comercial Pradera Concepción, Local 123"),
 *     @OA\Property(property="phone", type="string", nullable=true, example="22345678"),
 *     @OA\Property(property="email", type="string", nullable=true, format="email", example="pradera@subway.com.gt"),
 *     @OA\Property(property="latitude", type="number", format="float", nullable=true, example=14.6349),
 *     @OA\Property(property="longitude", type="number", format="float", nullable=true, example=-90.5069),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(property="delivery_active", type="boolean", example=true, description="Si tiene servicio de domicilio activo"),
 *     @OA\Property(property="pickup_active", type="boolean", example=true, description="Si tiene servicio de pickup activo"),
 *     @OA\Property(property="schedule", type="object", nullable=true, description="Horario semanal del restaurante"),
 *     @OA\Property(property="estimated_delivery_time", type="integer", nullable=true, example=30, description="Tiempo estimado de entrega en minutos"),
 *     @OA\Property(property="estimated_pickup_time", type="integer", nullable=true, example=15, description="Tiempo estimado de pickup en minutos"),
 *     @OA\Property(property="minimum_order_amount", type="number", format="float", nullable=true, example=50.00, description="Monto mínimo de orden"),
 *     @OA\Property(property="has_geofence", type="boolean", example=true, description="Si tiene geofence configurado para delivery"),
 *     @OA\Property(property="is_open_now", type="boolean", example=true, description="Si está abierto en este momento"),
 *     @OA\Property(property="today_schedule", type="object", nullable=true, description="Horario de hoy"),
 *     @OA\Property(property="status_text", type="string", example="Abierto", description="Texto del estado actual"),
 *     @OA\Property(property="distance_km", type="number", format="float", nullable=true, example=2.45, description="Distancia en km (solo en búsqueda nearby)")
 * )
 */
abstract class Controller
{
    //
}
