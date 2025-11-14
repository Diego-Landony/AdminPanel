<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     version="1.0.2",
 *     title="Subway Guatemala Customer API",
 *     description="API REST para la aplicación móvil de clientes de Subway Guatemala. Incluye autenticación multi-canal (email/password y Google OAuth), gestión de perfil, dispositivos FCM para notificaciones push y futuras funcionalidades de pedidos y lealtad.",
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
 *
 * @OA\Schema(
 *     schema="Customer",
 *     type="object",
 *     title="Customer",
 *     description="Customer model with profile information",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="first_name", type="string", example="Juan"),
 *     @OA\Property(property="last_name", type="string", example="Pérez"),
 *     @OA\Property(property="full_name", type="string", example="Juan Pérez", description="Computed full name"),
 *     @OA\Property(property="email", type="string", format="email", example="juan@example.com"),
 *     @OA\Property(property="phone", type="string", nullable=true, example="+50212345678"),
 *     @OA\Property(property="birth_date", type="string", format="date", nullable=true, example="1990-05-15"),
 *     @OA\Property(property="gender", type="string", enum={"male","female","other"}, nullable=true, example="male"),
 *     @OA\Property(property="avatar", type="string", nullable=true, example="https://example.com/avatar.jpg"),
 *     @OA\Property(property="oauth_provider", type="string", enum={"local","google"}, example="local"),
 *     @OA\Property(property="email_verified_at", type="string", format="date-time", nullable=true, example="2024-01-15T10:30:00Z"),
 *     @OA\Property(property="last_login_at", type="string", format="date-time", nullable=true, example="2024-01-20T15:45:00Z"),
 *     @OA\Property(property="customer_type_id", type="integer", nullable=true, example=1),
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
 */
abstract class Controller
{
    //
}
