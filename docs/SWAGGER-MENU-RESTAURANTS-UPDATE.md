# Documentación Swagger - Menu y Restaurants Endpoints

## Resumen
Se ha actualizado la documentación Swagger/OpenAPI para incluir todos los endpoints del menú y restaurantes de la API móvil de Subway Guatemala.

## Fecha de actualización
2025-12-15

## Endpoints Documentados

### Menu (10 endpoints - PÚBLICOS)

#### 1. GET /api/v1/menu
**Tag:** Menu
**Descripción:** Obtener menú completo con categorías, productos, combos y promociones activas
**Autenticación:** No requerida
**Respuesta:** Categorías con productos, combos activos, y promociones activas

#### 2. GET /api/v1/menu/categories
**Tag:** Menu
**Descripción:** Listar todas las categorías activas
**Autenticación:** No requerida

#### 3. GET /api/v1/menu/categories/{id}
**Tag:** Menu
**Descripción:** Obtener detalle de categoría con sus productos
**Autenticación:** No requerida
**Parámetros:** 
- `id` (path): ID de la categoría

#### 4. GET /api/v1/menu/products
**Tag:** Menu
**Descripción:** Listar productos con filtros opcionales
**Autenticación:** No requerida
**Query Parameters:**
- `category_id` (opcional): Filtrar por categoría
- `search` (opcional): Buscar por nombre
- `has_variants` (opcional): Filtrar por productos con/sin variantes

#### 5. GET /api/v1/menu/products/{id}
**Tag:** Menu
**Descripción:** Obtener detalle de producto con variantes, secciones y badges
**Autenticación:** No requerida
**Parámetros:**
- `id` (path): ID del producto

#### 6. GET /api/v1/menu/combos
**Tag:** Menu
**Descripción:** Listar combos activos y disponibles
**Autenticación:** No requerida

#### 7. GET /api/v1/menu/combos/{id}
**Tag:** Menu
**Descripción:** Obtener detalle de combo con items y opciones
**Autenticación:** No requerida
**Parámetros:**
- `id` (path): ID del combo

#### 8. GET /api/v1/menu/promotions
**Tag:** Menu
**Descripción:** Listar todas las promociones activas
**Autenticación:** No requerida

#### 9. GET /api/v1/menu/promotions/daily
**Tag:** Menu
**Descripción:** Obtener promoción "Sub del Día" activa
**Autenticación:** No requerida
**Respuesta:** Promoción del día o error 404 si no hay

#### 10. GET /api/v1/menu/promotions/combinados
**Tag:** Menu
**Descripción:** Obtener promociones tipo "Combinado" válidas ahora
**Autenticación:** No requerida

### Restaurants (4 endpoints - PÚBLICOS)

#### 11. GET /api/v1/restaurants
**Tag:** Restaurants
**Descripción:** Listar restaurantes activos
**Autenticación:** No requerida
**Query Parameters:**
- `delivery_active` (opcional): Filtrar por servicio de domicilio activo
- `pickup_active` (opcional): Filtrar por servicio de pickup activo

#### 12. GET /api/v1/restaurants/nearby
**Tag:** Restaurants
**Descripción:** Buscar restaurantes cercanos usando geolocalización
**Autenticación:** No requerida
**Query Parameters:**
- `lat` (requerido): Latitud (ejemplo: 14.6349)
- `lng` (requerido): Longitud (ejemplo: -90.5069)
- `radius_km` (opcional): Radio de búsqueda en km (default: 10, max: 50)
- `delivery_active` (opcional): Filtrar por domicilio activo
- `pickup_active` (opcional): Filtrar por pickup activo
**Algoritmo:** Fórmula de Haversine para calcular distancia
**Respuesta:** Restaurantes ordenados por distancia con campo `distance_km`

#### 13. GET /api/v1/restaurants/{id}
**Tag:** Restaurants
**Descripción:** Obtener detalles de restaurante con horarios y servicios
**Autenticación:** No requerida
**Parámetros:**
- `id` (path): ID del restaurante

#### 14. GET /api/v1/restaurants/{id}/reviews
**Tag:** Restaurants
**Descripción:** Obtener reseñas paginadas del restaurante
**Autenticación:** No requerida
**Parámetros:**
- `id` (path): ID del restaurante
- `per_page` (query, opcional): Reseñas por página (default: 10)
**Respuesta:** Reseñas paginadas + resumen con promedio de rating y total

### Product Views (3 endpoints - AUTENTICADOS)

#### 15. POST /api/v1/products/{product}/view
**Tag:** Product Views
**Descripción:** Registrar vista de producto
**Autenticación:** Bearer token requerido
**Parámetros:**
- `product` (path): ID del producto

#### 16. POST /api/v1/combos/{combo}/view
**Tag:** Product Views
**Descripción:** Registrar vista de combo
**Autenticación:** Bearer token requerido
**Parámetros:**
- `combo` (path): ID del combo

#### 17. GET /api/v1/me/recently-viewed
**Tag:** Product Views
**Descripción:** Obtener últimos 20 productos/combos vistos por el cliente
**Autenticación:** Bearer token requerido
**Respuesta:** Array de objetos con `type` (product/combo), `data`, y `viewed_at`

## Sistema de Precios (4 Zonas)

Todos los productos, variantes y combos incluyen precios para 4 zonas diferentes:

```json
"prices": {
  "pickup_capital": 45.00,        // Pickup en Ciudad de Guatemala
  "domicilio_capital": 50.00,     // Domicilio en Ciudad de Guatemala
  "pickup_interior": 48.00,       // Pickup en el interior del país
  "domicilio_interior": 53.00     // Domicilio en el interior del país
}
```

## Schemas Documentados

### PriceZones
Schema reutilizable que define las 4 zonas de precios

### Category
- Incluye información sobre variantes (uses_variants, variant_definitions)
- Relación con productos (cuando se carga)

### Product
- Sistema de precios de 4 zonas
- Información de redención de puntos (is_redeemable, points_cost)
- Relaciones: variants, sections, badges
- URL de imagen (image_url)

### ProductVariant
- Precios por zona
- Información de "Sub del Día" (is_daily_special, daily_special_days, daily_special_prices)
- SKU y tamaño

### Combo
- Precios por zona
- Disponibilidad (is_available)
- Items y badges
- Información de redención de puntos

### Promotion
- Tipos: daily_special o bundle
- Precios especiales para bundles (bundle_capital, bundle_interior)
- Horarios y días válidos (valid_from, valid_until, time_from, time_until, weekdays)
- Items de promoción y bundle_items

### Restaurant
- Información de ubicación (latitude, longitude)
- Servicios disponibles (delivery_active, pickup_active)
- Horarios (schedule, today_schedule, is_open_now)
- Tiempos estimados (estimated_delivery_time, estimated_pickup_time)
- Geofence (has_geofence)
- Distancia calculada (distance_km - solo en búsqueda nearby)

## Tags Agregados

Se agregaron los siguientes tags al archivo Controller.php:

1. **Menu**: Endpoints públicos para acceder al menú completo
2. **Restaurants**: Endpoints públicos para restaurantes
3. **Product Views**: Endpoints autenticados para vistas de productos

## Archivos Modificados

1. `/app/Http/Controllers/Controller.php`
   - Agregados schemas: PriceZones, Category, Product, ProductVariant, Combo, Promotion, Restaurant
   - Agregados tags: Menu, Restaurants, Product Views

2. `/app/Http/Controllers/Api/V1/Menu/MenuController.php`
   - Ya tenía documentación Swagger (sin cambios)

3. `/app/Http/Controllers/Api/V1/Menu/CategoryController.php`
   - Ya tenía documentación Swagger (sin cambios)

4. `/app/Http/Controllers/Api/V1/Menu/ProductController.php`
   - Ya tenía documentación Swagger (sin cambios)

5. `/app/Http/Controllers/Api/V1/Menu/ComboController.php`
   - Ya tenía documentación Swagger (sin cambios)

6. `/app/Http/Controllers/Api/V1/Menu/PromotionController.php`
   - Ya tenía documentación Swagger (sin cambios)

7. `/app/Http/Controllers/Api/V1/Menu/RestaurantController.php`
   - Actualizados paths de `/api/v1/menu/restaurants` a `/api/v1/restaurants`
   - Cambiado tag de "Menu - Restaurants" a "Restaurants"
   - Traducidas descripciones a español

8. `/app/Http/Controllers/Api/V1/ProductViewController.php`
   - Agregada documentación Swagger completa para los 3 endpoints
   - Tag: "Product Views"

## Verificación

Para verificar la documentación generada:

```bash
# Regenerar documentación
php artisan l5-swagger:generate

# Ver en navegador
http://localhost:8000/api/documentation
```

## Notas Importantes

1. **Rate Limiting:** Los endpoints de menú y restaurantes tienen rate limiting de 60 requests por minuto
2. **Autenticación:** Solo los endpoints de Product Views requieren autenticación Bearer token
3. **Paginación:** El endpoint de reviews soporta paginación con parámetro `per_page`
4. **Geolocalización:** El endpoint nearby usa fórmula de Haversine con radio máximo de 50km
5. **Imágenes:** Las URLs de imágenes se generan usando Storage::url() y apuntan al storage público

## Estado de la API

Todos los endpoints están implementados, probados y documentados completamente en Swagger/OpenAPI 3.0.
