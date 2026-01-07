<?php

namespace App\Http\Controllers\Api\V1\Menu;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Menu\RestaurantResource;
use App\Http\Resources\Api\V1\Order\OrderReviewResource;
use App\Models\OrderReview;
use App\Models\Restaurant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RestaurantController extends Controller
{
    /**
     * List active restaurants with optional filters.
     *
     * @OA\Get(
     *     path="/api/v1/restaurants",
     *     tags={"Restaurants"},
     *     summary="Listar restaurantes activos",
     *     description="Retorna lista de restaurantes activos con filtros opcionales para servicios de delivery y pickup.",
     *
     *     @OA\Parameter(
     *         name="delivery_active",
     *         in="query",
     *         description="Filtrar por servicio de domicilio activo",
     *         required=false,
     *
     *         @OA\Schema(type="boolean", example=true)
     *     ),
     *
     *     @OA\Parameter(
     *         name="pickup_active",
     *         in="query",
     *         description="Filtrar por servicio de pickup activo",
     *         required=false,
     *
     *         @OA\Schema(type="boolean", example=true)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Lista obtenida exitosamente",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="restaurants", type="array",
     *
     *                     @OA\Items(ref="#/components/schemas/Restaurant")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $query = Restaurant::query()->active()->ordered();

        if ($request->boolean('delivery_active')) {
            $query->deliveryActive();
        }

        if ($request->boolean('pickup_active')) {
            $query->pickupActive();
        }

        $restaurants = $query->get();

        return response()->json([
            'data' => [
                'restaurants' => RestaurantResource::collection($restaurants),
            ],
        ]);
    }

    /**
     * Show restaurant details.
     *
     * @OA\Get(
     *     path="/api/v1/restaurants/{id}",
     *     tags={"Restaurants"},
     *     summary="Obtener detalles de restaurante",
     *     description="Retorna información detallada de un restaurante específico con horarios y servicios.",
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Restaurant ID",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Detalles de restaurante obtenidos exitosamente",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="restaurant", ref="#/components/schemas/Restaurant")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=404, description="Restaurante no encontrado")
     * )
     */
    public function show(int $id): JsonResponse
    {
        $restaurant = Restaurant::query()
            ->active()
            ->findOrFail($id);

        return response()->json([
            'data' => [
                'restaurant' => RestaurantResource::make($restaurant),
            ],
        ]);
    }

    /**
     * Get nearby restaurants based on user location.
     *
     * @OA\Get(
     *     path="/api/v1/restaurants/nearby",
     *     tags={"Restaurants"},
     *     summary="Buscar restaurantes cercanos",
     *     description="Retorna restaurantes cercanos a una ubicación específica usando fórmula de Haversine para calcular distancia. Resultados ordenados por distancia.",
     *
     *     @OA\Parameter(
     *         name="lat",
     *         in="query",
     *         description="Latitud de la ubicación del usuario",
     *         required=true,
     *
     *         @OA\Schema(type="number", format="float", example=14.6349)
     *     ),
     *
     *     @OA\Parameter(
     *         name="lng",
     *         in="query",
     *         description="Longitud de la ubicación del usuario",
     *         required=true,
     *
     *         @OA\Schema(type="number", format="float", example=-90.5069)
     *     ),
     *
     *     @OA\Parameter(
     *         name="radius_km",
     *         in="query",
     *         description="Radio de búsqueda en kilómetros (default: 10km, max: 50km)",
     *         required=false,
     *
     *         @OA\Schema(type="number", format="float", example=5)
     *     ),
     *
     *     @OA\Parameter(
     *         name="delivery_active",
     *         in="query",
     *         description="Filtrar por servicio de domicilio activo",
     *         required=false,
     *
     *         @OA\Schema(type="boolean", example=true)
     *     ),
     *
     *     @OA\Parameter(
     *         name="pickup_active",
     *         in="query",
     *         description="Filtrar por servicio de pickup activo",
     *         required=false,
     *
     *         @OA\Schema(type="boolean", example=true)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Restaurantes cercanos obtenidos exitosamente",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="restaurants", type="array",
     *
     *                     @OA\Items(
     *                         allOf={
     *
     *                             @OA\Schema(ref="#/components/schemas/Restaurant"),
     *                             @OA\Schema(
     *
     *                                 @OA\Property(property="distance_km", type="number", format="float", example=2.45, description="Distancia en kilómetros desde la ubicación del usuario")
     *                             )
     *                         }
     *                     )
     *                 ),
     *                 @OA\Property(property="search_radius_km", type="number", format="float", example=5, description="Radio de búsqueda usado"),
     *                 @OA\Property(property="total_found", type="integer", example=3, description="Total de restaurantes encontrados dentro del radio")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="El campo lat es requerido."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function nearby(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'radius_km' => ['nullable', 'numeric', 'min:0.1', 'max:50'],
        ]);

        $lat = $validated['lat'] ?? null;
        $lng = $validated['lng'] ?? null;
        $radiusKm = $validated['radius_km'] ?? 10;

        // Si no hay coordenadas, devolver error para que la app pida activar ubicación
        if ($lat === null || $lng === null) {
            return response()->json([
                'error' => 'location_required',
                'message' => 'Por favor activa tu ubicación para ver los restaurantes cercanos.',
                'data' => [
                    'restaurants' => [],
                    'search_radius_km' => null,
                    'total_found' => 0,
                ],
            ], 422);
        }

        $query = Restaurant::query()
            ->active()
            ->withCoordinates();

        if ($request->boolean('delivery_active')) {
            $query->deliveryActive();
        }

        if ($request->boolean('pickup_active')) {
            $query->pickupActive();
        }

        // Haversine formula to calculate distance
        // Earth's radius in kilometers
        $earthRadius = 6371;

        $restaurants = $query
            ->select('restaurants.*')
            ->selectRaw(
                "({$earthRadius} * acos(
                    cos(radians(?)) *
                    cos(radians(latitude)) *
                    cos(radians(longitude) - radians(?)) +
                    sin(radians(?)) *
                    sin(radians(latitude))
                )) AS distance_km",
                [$lat, $lng, $lat]
            )
            ->havingRaw('distance_km <= ?', [$radiusKm])
            ->orderBy('distance_km', 'asc')
            ->get();

        return response()->json([
            'data' => [
                'restaurants' => RestaurantResource::collection($restaurants),
                'search_radius_km' => (float) $radiusKm,
                'total_found' => $restaurants->count(),
            ],
        ]);
    }

    /**
     * Get reviews for a restaurant.
     *
     * @OA\Get(
     *     path="/api/v1/restaurants/{id}/reviews",
     *     tags={"Menu - Restaurants"},
     *     summary="Get restaurant reviews",
     *     description="Returns paginated reviews for a specific restaurant.",
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Restaurant ID",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Reviews per page",
     *         required=false,
     *
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Reviews retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="reviews", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="summary", type="object",
     *                     @OA\Property(property="average_rating", type="number", format="float", example=4.5),
     *                     @OA\Property(property="total_reviews", type="integer", example=100)
     *                 )
     *             ),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     ),
     *
     *     @OA\Response(response=404, description="Restaurant not found")
     * )
     */
    public function reviews(int $id, Request $request): JsonResponse
    {
        $restaurant = Restaurant::query()->active()->findOrFail($id);
        $perPage = $request->input('per_page', 10);

        $reviews = OrderReview::query()
            ->where('restaurant_id', $restaurant->id)
            ->with('customer')
            ->latest()
            ->paginate($perPage);

        $summary = OrderReview::query()
            ->where('restaurant_id', $restaurant->id)
            ->selectRaw('AVG(overall_rating) as average_rating, COUNT(*) as total_reviews')
            ->first();

        return response()->json([
            'data' => [
                'reviews' => OrderReviewResource::collection($reviews),
                'summary' => [
                    'average_rating' => round((float) $summary->average_rating, 1),
                    'total_reviews' => (int) $summary->total_reviews,
                ],
            ],
            'meta' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
            ],
        ]);
    }
}
