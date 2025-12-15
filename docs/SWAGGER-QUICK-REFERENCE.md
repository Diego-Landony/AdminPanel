# Swagger/OpenAPI Quick Reference

## Acceso Rápido

| Recurso | URL |
|---------|-----|
| Swagger UI | http://localhost:8000/api/documentation |
| JSON | http://localhost:8000/docs/api-docs.json |
| YAML | http://localhost:8000/docs/api-docs.yaml |

## Regenerar Documentación

```bash
php artisan l5-swagger:generate
```

## Estructura de la Documentación

### Ubicación de Anotaciones

Todas las anotaciones Swagger están en los controladores:

```
app/Http/Controllers/
├── Controller.php                              # Info, Tags, Schemas
├── Api/V1/
│   ├── Auth/
│   │   ├── AuthController.php                 # 10 endpoints Auth
│   │   └── OAuthController.php                # 4 endpoints OAuth
│   ├── ProfileController.php                  # 6 endpoints Profile
│   └── DeviceController.php                   # 3 endpoints Devices
```

### Schemas Principales

1. **Customer** - Cliente con perfil y lealtad
2. **CustomerDevice** - Dispositivo con FCM token
3. **Category** - Categoría de menú
4. **Product** - Producto del menú
5. **ProductVariant** - Variante de producto
6. **Combo** - Combo del menú
7. **Promotion** - Promoción activa
8. **CustomerAddress** - Dirección de entrega
9. **Restaurant** - Ubicación de restaurante

## Endpoints Auth & Profile (23 total)

### Authentication (10)
- POST `/api/v1/auth/register`
- POST `/api/v1/auth/login`
- POST `/api/v1/auth/logout`
- POST `/api/v1/auth/logout-all`
- POST `/api/v1/auth/refresh`
- POST `/api/v1/auth/forgot-password`
- POST `/api/v1/auth/reset-password`
- POST `/api/v1/auth/email/verify/{id}/{hash}`
- POST `/api/v1/auth/email/resend`
- POST `/api/v1/auth/reactivate`

### OAuth (4)
- GET `/api/v1/auth/oauth/google/redirect`
- GET `/api/v1/auth/oauth/google/callback`
- GET `/api/v1/auth/oauth/apple/redirect`
- GET `/api/v1/auth/oauth/apple/callback`

### Profile (6)
- GET `/api/v1/profile`
- PUT `/api/v1/profile`
- DELETE `/api/v1/profile`
- POST `/api/v1/profile/avatar`
- DELETE `/api/v1/profile/avatar`
- PUT `/api/v1/profile/password`

### Devices (3)
- GET `/api/v1/devices`
- POST `/api/v1/devices/register`
- DELETE `/api/v1/devices/{device}`

## Agregar Nuevo Endpoint

### 1. Agregar anotaciones en el controlador

```php
/**
 * @OA\Get(
 *     path="/api/v1/example",
 *     tags={"Example"},
 *     summary="Descripción breve",
 *     description="Descripción detallada",
 *     security={{"sanctum":{}}},
 *
 *     @OA\Response(
 *         response=200,
 *         description="Éxito",
 *         @OA\JsonContent(
 *             @OA\Property(property="data", type="object")
 *         )
 *     ),
 *     @OA\Response(response=401, description="No autenticado")
 * )
 */
public function example() { }
```

### 2. Agregar Tag si es nuevo (Controller.php)

```php
/**
 * @OA\Tag(
 *     name="Example",
 *     description="Descripción del tag"
 * )
 */
```

### 3. Agregar Schema si es necesario (Controller.php)

```php
/**
 * @OA\Schema(
 *     schema="Example",
 *     type="object",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="name", type="string")
 * )
 */
```

### 4. Regenerar documentación

```bash
php artisan l5-swagger:generate
```

## Configuración L5-Swagger

**Archivo:** `config/l5-swagger.php`

**Configuraciones importantes:**
- `routes.api`: `/api/documentation` (UI)
- `routes.docs`: `/docs` (JSON/YAML)
- `paths.annotations`: `[base_path('app')]`
- `paths.docs`: `storage_path('api-docs')`

## Testing

### Probar un endpoint desde Swagger UI

1. Visita http://localhost:8000/api/documentation
2. Busca el endpoint que quieres probar
3. Click en "Try it out"
4. Si requiere auth:
   - Primero ejecuta POST `/api/v1/auth/login`
   - Copia el token del response
   - Click en "Authorize" (botón verde superior)
   - Pega el token
   - Click "Authorize"
5. Llena los parámetros
6. Click "Execute"

### Probar desde cURL

```bash
# Login
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "juan@example.com",
    "password": "Pass123",
    "device_identifier": "test-device"
  }'

# Usar token
curl http://localhost:8000/api/v1/profile \
  -H "Authorization: Bearer 1|abc123xyz..."
```

## Troubleshooting

### Error: Schema not found

```
$ref "#/components/schemas/Example" not found
```

**Solución:** Agregar schema en `Controller.php`:

```php
/**
 * @OA\Schema(
 *     schema="Example",
 *     type="object",
 *     @OA\Property(property="id", type="integer")
 * )
 */
```

### Error: Multiple annotations

```
Multiple root @OA\OpenApi() annotations detected
```

**Solución:** Solo debe haber una anotación `@OA\Info()` en `Controller.php`

### Documentación no se actualiza

```bash
# Limpiar cache
php artisan l5-swagger:generate

# Si persiste, borrar y regenerar
rm -rf storage/api-docs/api-docs.json
php artisan l5-swagger:generate
```

## Buenas Prácticas

1. **Descripción clara**: Siempre incluir `summary` y `description`
2. **Tags apropiados**: Agrupar endpoints relacionados
3. **Ejemplos realistas**: Usar datos de ejemplo que tengan sentido
4. **Todos los responses**: Documentar 200, 401, 422, etc.
5. **Security**: Marcar endpoints protegidos con `security={{"sanctum":{}}}`
6. **Validaciones**: Incluir en description las reglas importantes
7. **Schemas reutilizables**: Usar `ref="#/components/schemas/..."` en lugar de repetir

## Enlaces Útiles

- [OpenAPI 3.0 Specification](https://swagger.io/specification/)
- [L5-Swagger Documentation](https://github.com/DarkaOnLine/L5-Swagger)
- [Swagger Editor](https://editor.swagger.io/) - Validar/editar JSON
- [Laravel Sanctum](https://laravel.com/docs/12.x/sanctum)

## Estadísticas Actuales

- **Total Endpoints**: 55
- **Tags**: 10
- **Schemas**: 9
- **Security Schemes**: 1 (Sanctum Bearer Token)
- **Versión API**: 1.0.2
