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
 *     schema="CustomerType",
 *     type="object",
 *     title="CustomerType",
 *     description="Tipo de cliente basado en puntos de lealtad",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Gold", description="Nombre del tipo de cliente"),
 *     @OA\Property(property="points_required", type="integer", example=500, description="Puntos requeridos para alcanzar este nivel"),
 *     @OA\Property(property="multiplier", type="number", format="float", example=1.5, description="Multiplicador de puntos para este nivel"),
 *     @OA\Property(property="color", type="string", example="#FFD700", description="Color representativo del nivel"),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-10T08:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-20T15:45:00Z")
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
 *     @OA\Property(property="oauth_provider", type="string", enum={"local","google","apple"}, nullable=true, example="local", description="Proveedor OAuth si aplica. null para registro local con email/password"),
 *     @OA\Property(property="subway_card", type="string", example="802056895224", description="Unique loyalty card number (10-12 digits)"),
 *     @OA\Property(property="birth_date", type="string", format="date", nullable=true, example="1990-05-15"),
 *     @OA\Property(property="gender", type="string", enum={"male","female","other"}, nullable=true, example="male"),
 *     @OA\Property(property="phone", type="string", nullable=true, example="12345678", description="8-digit phone number (Guatemala)"),
 *     @OA\Property(property="email_offers_enabled", type="boolean", example=true, description="Si el cliente acepta recibir ofertas por email"),
 *     @OA\Property(property="last_login_at", type="string", format="date-time", nullable=true, example="2024-01-20T15:45:00Z"),
 *     @OA\Property(property="last_activity_at", type="string", format="date-time", nullable=true, example="2024-01-20T16:00:00Z"),
 *     @OA\Property(property="last_purchase_at", type="string", format="date-time", nullable=true, example="2024-01-18T12:30:00Z"),
 *     @OA\Property(property="points", type="integer", example=150, description="Available loyalty points"),
 *     @OA\Property(property="points_updated_at", type="string", format="date-time", nullable=true, example="2024-01-18T12:30:00Z"),
 *     @OA\Property(property="status", type="string", enum={"active","inactive","suspended"}, example="active"),
 *     @OA\Property(property="is_online", type="boolean", example=true, description="True if customer is currently active"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-10T08:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-20T15:45:00Z"),
 *     @OA\Property(property="has_password", type="boolean", example=true, description="True si el usuario puede iniciar sesión con contraseña (oauth_provider=local)"),
 *     @OA\Property(property="has_google_linked", type="boolean", example=false, description="True si el usuario tiene cuenta de Google vinculada"),
 *     @OA\Property(property="has_apple_linked", type="boolean", example=false, description="True si el usuario tiene cuenta de Apple vinculada"),
 *     @OA\Property(property="customer_type", ref="#/components/schemas/CustomerType", nullable=true, description="Tipo de cliente basado en puntos de lealtad"),
 *     @OA\Property(
 *         property="next_tier_info",
 *         type="object",
 *         nullable=true,
 *         description="Información del siguiente nivel de lealtad. Null si ya está en el nivel máximo (Platino).",
 *         @OA\Property(property="id", type="integer", example=3, description="ID del siguiente nivel"),
 *         @OA\Property(property="name", type="string", example="Plata", description="Nombre del siguiente nivel"),
 *         @OA\Property(property="points_required", type="integer", example=500, description="Puntos totales requeridos para alcanzar el nivel"),
 *         @OA\Property(property="points_needed", type="integer", example=350, description="Puntos adicionales que faltan para subir"),
 *         @OA\Property(property="multiplier", type="number", format="float", example=1.25, description="Multiplicador de puntos del siguiente nivel"),
 *         @OA\Property(property="color", type="string", example="#C0C0C0", description="Color representativo del siguiente nivel")
 *     ),
 *     @OA\Property(property="addresses", type="array", nullable=true, description="Direcciones del cliente (cuando se carga la relación)", @OA\Items(ref="#/components/schemas/CustomerAddress")),
 *     @OA\Property(property="nits", type="array", nullable=true, description="NITs del cliente (cuando se carga la relación)", @OA\Items(ref="#/components/schemas/CustomerNit")),
 *     @OA\Property(property="devices", type="array", nullable=true, description="Dispositivos activos del cliente (cuando se carga la relación)", @OA\Items(ref="#/components/schemas/CustomerDevice")),
 *     @OA\Property(property="addresses_count", type="integer", nullable=true, example=2, description="Número de direcciones (cuando se incluye el count)"),
 *     @OA\Property(property="nits_count", type="integer", nullable=true, example=1, description="Número de NITs (cuando se incluye el count)"),
 *     @OA\Property(property="devices_count", type="integer", nullable=true, example=3, description="Número de dispositivos (cuando se incluye el count)")
 * )
 *
 * @OA\Schema(
 *     schema="CustomerDevice",
 *     type="object",
 *     title="CustomerDevice",
 *     description="Dispositivo del cliente registrado para notificaciones push",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="device_identifier", type="string", example="550e8400-e29b-41d4-a716-446655440000", description="UUID único del dispositivo"),
 *     @OA\Property(property="device_name", type="string", nullable=true, example="iPhone 14 Pro de Juan", description="Nombre del dispositivo"),
 *     @OA\Property(property="last_used_at", type="string", format="date-time", nullable=true, example="2024-01-20T15:45:00Z", description="Última vez que se usó el dispositivo"),
 *     @OA\Property(property="is_active", type="boolean", example=true, description="Si el dispositivo está activo"),
 *     @OA\Property(property="login_count", type="integer", example=15, description="Número de veces que se ha iniciado sesión"),
 *     @OA\Property(property="is_current_device", type="boolean", example=true, description="True si es el dispositivo actual de esta sesión"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-10T08:00:00Z")
 * )
 *
 * @OA\Schema(
 *     schema="CustomerAddress",
 *     type="object",
 *     title="CustomerAddress",
 *     description="Dirección de entrega del cliente",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="label", type="string", example="Casa", description="Etiqueta de la dirección (Casa, Trabajo, etc.)"),
 *     @OA\Property(property="address_line", type="string", example="12 Calle 1-25 Zona 10"),
 *     @OA\Property(property="latitude", type="number", format="float", example=14.6349),
 *     @OA\Property(property="longitude", type="number", format="float", example=-90.5069),
 *     @OA\Property(property="delivery_notes", type="string", nullable=true, example="Portón negro, tocar timbre"),
 *     @OA\Property(property="zone", type="string", enum={"capital","interior"}, example="capital", description="Zona de precios"),
 *     @OA\Property(property="is_default", type="boolean", example=true, description="Si es la dirección predeterminada"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-10T08:00:00Z")
 * )
 *
 * @OA\Schema(
 *     schema="CustomerNit",
 *     type="object",
 *     title="CustomerNit",
 *     description="NIT del cliente para facturación",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="nit", type="string", example="12345678-9", description="Número de NIT"),
 *     @OA\Property(property="nit_name", type="string", example="Juan Pérez", description="Nombre asociado al NIT"),
 *     @OA\Property(property="nit_type", type="string", enum={"personal","company","other"}, example="personal", description="Tipo de NIT"),
 *     @OA\Property(property="is_default", type="boolean", example=true, description="Si es el NIT predeterminado"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-10T08:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-20T15:45:00Z")
 * )
 *
 * @OA\Schema(
 *     schema="PriceZones",
 *     type="object",
 *     title="Price Zones",
 *     description="Sistema de precios con 4 zonas: pickup capital, delivery capital, pickup interior, delivery interior",
 *
 *     @OA\Property(property="pickup_capital", type="number", format="float", example=45.00, description="Precio para pickup en Ciudad de Guatemala"),
 *     @OA\Property(property="delivery_capital", type="number", format="float", example=50.00, description="Precio para delivery en Ciudad de Guatemala"),
 *     @OA\Property(property="pickup_interior", type="number", format="float", example=48.00, description="Precio para pickup en el interior"),
 *     @OA\Property(property="delivery_interior", type="number", format="float", example=53.00, description="Precio para delivery en el interior")
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
 *     @OA\Property(property="orientation", type="string", enum={"horizontal", "vertical"}, example="horizontal", description="Orientacion del banner. Aspect ratios: horizontal=16:9 (1280x720px), vertical=3:4 (1200x1600px)"),
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
 *     @OA\Property(property="price", type="number", format="float", example=45.00, description="Precio de referencia (pickup_capital) para mostrar en UI"),
 *     @OA\Property(property="prices", ref="#/components/schemas/PriceZones", description="Precios por zona (4 zonas)"),
 *     @OA\Property(property="sort_order", type="integer", example=1, description="Orden de visualización"),
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
 *         @OA\Items(ref="#/components/schemas/Badge")
 *     ),
 *
 *     @OA\Property(
 *         property="active_promotion",
 *         type="object",
 *         nullable=true,
 *         description="Promoción activa aplicable a este producto. Usar para mostrar precios tachados estilo Amazon/Temu.",
 *         @OA\Property(property="id", type="integer", example=5, description="ID de la promoción"),
 *         @OA\Property(property="type", type="string", example="percentage_discount", description="Tipo: percentage_discount, two_for_one, daily_special, bundle_special"),
 *         @OA\Property(property="name", type="string", example="Promo Verano 15%", description="Nombre de la promoción"),
 *         @OA\Property(property="discount_percent", type="number", format="float", nullable=true, example=15.0, description="Porcentaje de descuento si aplica"),
 *         @OA\Property(
 *             property="special_prices",
 *             type="object",
 *             nullable=true,
 *             description="Precios especiales fijos si la promoción los define (4 zonas)",
 *             @OA\Property(property="pickup_capital", type="number", format="float", nullable=true, example=35.00),
 *             @OA\Property(property="delivery_capital", type="number", format="float", nullable=true, example=40.00),
 *             @OA\Property(property="pickup_interior", type="number", format="float", nullable=true, example=38.00),
 *             @OA\Property(property="delivery_interior", type="number", format="float", nullable=true, example=43.00)
 *         ),
 *         @OA\Property(
 *             property="discounted_prices",
 *             type="object",
 *             nullable=true,
 *             description="Precios finales con descuento aplicado (para mostrar en UI)",
 *             @OA\Property(property="pickup_capital", type="number", format="float", example=38.25),
 *             @OA\Property(property="delivery_capital", type="number", format="float", example=42.50),
 *             @OA\Property(property="pickup_interior", type="number", format="float", example=40.80),
 *             @OA\Property(property="delivery_interior", type="number", format="float", example=45.05)
 *         ),
 *         @OA\Property(
 *             property="badge",
 *             type="object",
 *             nullable=true,
 *             description="Badge visual de la promoción para mostrar en el producto",
 *             @OA\Property(property="name", type="string", example="15% OFF"),
 *             @OA\Property(property="color", type="string", example="#ef4444", description="Color de fondo del badge"),
 *             @OA\Property(property="text_color", type="string", example="#ffffff", description="Color del texto del badge")
 *         )
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
 *     @OA\Property(property="price", type="number", format="float", example=35.00, description="Precio de referencia (pickup_capital) para mostrar en UI"),
 *     @OA\Property(property="prices", ref="#/components/schemas/PriceZones"),
 *     @OA\Property(property="is_daily_special", type="boolean", example=false, description="Si es Sub del Día"),
 *     @OA\Property(property="daily_special_days", type="array", nullable=true, @OA\Items(type="integer"), example={1,3,5}, description="Días de la semana (1=Lunes, 7=Domingo)"),
 *     @OA\Property(
 *         property="daily_special_prices",
 *         type="object",
 *         nullable=true,
 *         description="Precios especiales para Sub del Día (4 zonas)",
 *         @OA\Property(property="pickup_capital", type="number", format="float", example=35.00),
 *         @OA\Property(property="delivery_capital", type="number", format="float", example=40.00),
 *         @OA\Property(property="pickup_interior", type="number", format="float", example=38.00),
 *         @OA\Property(property="delivery_interior", type="number", format="float", example=43.00)
 *     ),
 *     @OA\Property(
 *         property="active_promotion",
 *         type="object",
 *         nullable=true,
 *         description="Promoción activa aplicable a esta variante. Usar para mostrar precios tachados estilo Amazon/Temu.",
 *         @OA\Property(property="id", type="integer", example=5, description="ID de la promoción"),
 *         @OA\Property(property="type", type="string", example="percentage_discount", description="Tipo: percentage_discount, two_for_one, daily_special, bundle_special"),
 *         @OA\Property(property="name", type="string", example="Promo Verano 15%", description="Nombre de la promoción"),
 *         @OA\Property(property="discount_percent", type="number", format="float", nullable=true, example=15.0, description="Porcentaje de descuento si aplica"),
 *         @OA\Property(
 *             property="special_prices",
 *             type="object",
 *             nullable=true,
 *             description="Precios especiales fijos si la promoción los define (4 zonas)",
 *             @OA\Property(property="pickup_capital", type="number", format="float", nullable=true, example=35.00),
 *             @OA\Property(property="delivery_capital", type="number", format="float", nullable=true, example=40.00),
 *             @OA\Property(property="pickup_interior", type="number", format="float", nullable=true, example=38.00),
 *             @OA\Property(property="delivery_interior", type="number", format="float", nullable=true, example=43.00)
 *         ),
 *         @OA\Property(
 *             property="discounted_prices",
 *             type="object",
 *             nullable=true,
 *             description="Precios finales con descuento aplicado (para mostrar en UI)",
 *             @OA\Property(property="pickup_capital", type="number", format="float", example=38.25),
 *             @OA\Property(property="delivery_capital", type="number", format="float", example=42.50),
 *             @OA\Property(property="pickup_interior", type="number", format="float", example=40.80),
 *             @OA\Property(property="delivery_interior", type="number", format="float", example=45.05)
 *         ),
 *         @OA\Property(
 *             property="badge",
 *             type="object",
 *             nullable=true,
 *             description="Badge visual de la promoción para mostrar en la variante",
 *             @OA\Property(property="name", type="string", example="15% OFF"),
 *             @OA\Property(property="color", type="string", example="#ef4444", description="Color de fondo del badge"),
 *             @OA\Property(property="text_color", type="string", example="#ffffff", description="Color del texto del badge")
 *         )
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
 *     @OA\Property(property="price", type="number", format="float", example=75.00, description="Precio de referencia (pickup_capital) para mostrar en UI"),
 *     @OA\Property(property="prices", ref="#/components/schemas/PriceZones"),
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
 *         @OA\Items(ref="#/components/schemas/Badge")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="Badge",
 *     type="object",
 *     title="Badge",
 *     description="Badge de producto o combo",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(
 *         property="badge_type",
 *         type="object",
 *         description="Tipo de badge",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="name", type="string", example="Nuevo"),
 *         @OA\Property(property="color", type="string", example="#22c55e", description="Color de fondo del badge"),
 *         @OA\Property(property="text_color", type="string", example="#ffffff", description="Color del texto del badge")
 *     ),
 *     @OA\Property(property="validity_type", type="string", example="permanent", description="permanent, date_range, weekdays"),
 *     @OA\Property(property="is_valid_now", type="boolean", example=true, description="Si el badge está activo actualmente")
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
 *     @OA\Property(property="image_url", type="string", nullable=true, example="https://admin.subwaycardgt.com/storage/promotions/combo-2x1.jpg", description="URL de la imagen de la promoción"),
 *     @OA\Property(property="type", type="string", enum={"daily_special","two_for_one","percentage_discount","bundle_special"}, example="daily_special", description="Tipo de promoción"),
 *     @OA\Property(
 *         property="prices",
 *         type="object",
 *         nullable=true,
 *         description="Precios para promociones tipo bundle_special (4 zonas)",
 *         @OA\Property(property="pickup_capital", type="number", format="float", nullable=true, example=85.00),
 *         @OA\Property(property="delivery_capital", type="number", format="float", nullable=true, example=90.00),
 *         @OA\Property(property="pickup_interior", type="number", format="float", nullable=true, example=88.00),
 *         @OA\Property(property="delivery_interior", type="number", format="float", nullable=true, example=93.00)
 *     ),
 *     @OA\Property(property="valid_from", type="string", format="date", nullable=true, example="2025-01-01"),
 *     @OA\Property(property="valid_until", type="string", format="date", nullable=true, example="2025-12-31"),
 *     @OA\Property(property="time_from", type="string", format="time", nullable=true, example="11:00:00"),
 *     @OA\Property(property="time_until", type="string", format="time", nullable=true, example="22:00:00"),
 *     @OA\Property(property="weekdays", type="array", nullable=true, @OA\Items(type="integer"), example={1,3,5}),
 *     @OA\Property(property="sort_order", type="integer", example=1),
 *     @OA\Property(
 *         property="badge",
 *         type="object",
 *         nullable=true,
 *         description="Badge visual para mostrar en productos con esta promoción",
 *         @OA\Property(property="name", type="string", example="2x1", description="Texto del badge"),
 *         @OA\Property(property="color", type="string", example="#ef4444", description="Color de fondo"),
 *         @OA\Property(property="text_color", type="string", example="#ffffff", description="Color del texto")
 *     ),
 *     @OA\Property(
 *         property="items",
 *         type="array",
 *         description="Items de la promoción (para Sub del Día, 2x1, porcentaje)",
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
 *     @OA\Property(property="zone", type="string", enum={"capital","interior"}, example="capital", description="Zona de precios del restaurante"),
 *     @OA\Property(property="delivery_active", type="boolean", example=true, description="Si tiene servicio de delivery activo"),
 *     @OA\Property(property="pickup_active", type="boolean", example=true, description="Si tiene servicio de pickup activo"),
 *     @OA\Property(
 *         property="schedule",
 *         type="object",
 *         nullable=true,
 *         description="Horario semanal del restaurante por día",
 *         @OA\Property(property="monday", type="object", @OA\Property(property="is_open", type="boolean"), @OA\Property(property="open", type="string", nullable=true, example="10:00"), @OA\Property(property="close", type="string", nullable=true, example="21:00")),
 *         @OA\Property(property="tuesday", type="object", @OA\Property(property="is_open", type="boolean"), @OA\Property(property="open", type="string", nullable=true), @OA\Property(property="close", type="string", nullable=true)),
 *         @OA\Property(property="wednesday", type="object", @OA\Property(property="is_open", type="boolean"), @OA\Property(property="open", type="string", nullable=true), @OA\Property(property="close", type="string", nullable=true)),
 *         @OA\Property(property="thursday", type="object", @OA\Property(property="is_open", type="boolean"), @OA\Property(property="open", type="string", nullable=true), @OA\Property(property="close", type="string", nullable=true)),
 *         @OA\Property(property="friday", type="object", @OA\Property(property="is_open", type="boolean"), @OA\Property(property="open", type="string", nullable=true), @OA\Property(property="close", type="string", nullable=true)),
 *         @OA\Property(property="saturday", type="object", @OA\Property(property="is_open", type="boolean"), @OA\Property(property="open", type="string", nullable=true), @OA\Property(property="close", type="string", nullable=true)),
 *         @OA\Property(property="sunday", type="object", @OA\Property(property="is_open", type="boolean"), @OA\Property(property="open", type="string", nullable=true), @OA\Property(property="close", type="string", nullable=true))
 *     ),
 *     @OA\Property(property="estimated_delivery_time", type="integer", nullable=true, example=30, description="Tiempo estimado de entrega en minutos"),
 *     @OA\Property(property="estimated_pickup_time", type="integer", nullable=true, example=15, description="Tiempo estimado de pickup en minutos"),
 *     @OA\Property(property="minimum_order_amount", type="number", format="float", nullable=true, example=50.00, description="Monto mínimo de orden"),
 *     @OA\Property(property="has_geofence", type="boolean", example=true, description="Si tiene geofence configurado para delivery"),
 *     @OA\Property(property="is_open_now", type="boolean", example=true, description="Si está abierto en este momento"),
 *     @OA\Property(property="today_schedule", type="string", nullable=true, example="10:00 - 21:00", description="Horario de hoy formateado como texto legible"),
 *     @OA\Property(property="status_text", type="string", example="Delivery + Pickup", description="Texto de servicios disponibles"),
 *     @OA\Property(property="distance_km", type="number", format="float", nullable=true, example=2.45, description="Distancia en km (solo cuando se envían coordenadas)")
 * )
 */
abstract class Controller
{
    //
}
