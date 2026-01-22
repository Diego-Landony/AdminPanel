<?php

namespace App\Http\Controllers\Api\V1\Menu;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Menu\RestaurantResource;
use App\Http\Resources\Api\V1\Order\OrderReviewResource;
use App\Models\CustomerAddress;
use App\Models\OrderReview;
use App\Models\Restaurant;
use App\Services\Geofence\GeofenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RestaurantController extends Controller
{
    public function __construct(
        private GeofenceService $geofenceService
    ) {}

    /**
     * List active restaurants with optional filters and location-based sorting.
     *
     * @OA\Get(
     *     path="/api/v1/restaurants",
     *     tags={"Restaurants"},
     *     summary="Listar restaurantes activos",
     *     description="Retorna lista de restaurantes activos. Si se proporcionan coordenadas (lat, lng), los resultados se ordenan por distancia. Ideal para mostrar en mapa y listar ubicaciones.",
     *
     *     @OA\Parameter(
     *         name="lat",
     *         in="query",
     *         description="Latitud del usuario (opcional). Si se proporciona junto con lng, ordena por distancia.",
     *         required=false,
     *
     *         @OA\Schema(type="number", format="float", example=14.6349)
     *     ),
     *
     *     @OA\Parameter(
     *         name="lng",
     *         in="query",
     *         description="Longitud del usuario (opcional). Si se proporciona junto con lat, ordena por distancia.",
     *         required=false,
     *
     *         @OA\Schema(type="number", format="float", example=-90.5069)
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
     *         description="Lista obtenida exitosamente",
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
     *                                 @OA\Property(property="distance_km", type="number", format="float", nullable=true, example=2.45, description="Distancia en km (solo si se proporcionan coordenadas)")
     *                             )
     *                         }
     *                     )
     *                 ),
     *                 @OA\Property(property="total", type="integer", example=5, description="Total de restaurantes"),
     *                 @OA\Property(property="ordered_by_distance", type="boolean", example=true, description="Indica si están ordenados por distancia")
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $lat = $validated['lat'] ?? null;
        $lng = $validated['lng'] ?? null;
        $hasCoordinates = $lat !== null && $lng !== null;

        $query = Restaurant::query()->active();

        if ($request->boolean('delivery_active')) {
            $query->deliveryActive();
        }

        if ($request->boolean('pickup_active')) {
            $query->pickupActive();
        }

        // Si hay coordenadas, calcular distancia y ordenar por ella
        if ($hasCoordinates) {
            $earthRadius = 6371;

            $query->withCoordinates()
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
                ->orderBy('distance_km', 'asc');
        } else {
            // Sin coordenadas, ordenar alfabéticamente
            $query->ordered();
        }

        $restaurants = $query->get();

        return response()->json([
            'data' => [
                'restaurants' => RestaurantResource::collection($restaurants),
                'total' => $restaurants->count(),
                'ordered_by_distance' => $hasCoordinates,
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
                'message' => 'Activa tu ubicación para ver restaurantes',
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
     * Get restaurant for delivery based on saved address geofence.
     *
     * @OA\Get(
     *     path="/api/v1/restaurants/for-delivery",
     *     tags={"Restaurants"},
     *     summary="Obtener restaurante para delivery",
     *     description="Retorna el restaurante que cubre la dirección guardada del cliente usando geocercas. Requiere autenticación y una dirección con coordenadas válidas.",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="address_id",
     *         in="query",
     *         description="ID de la dirección guardada del cliente",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Restaurante encontrado para delivery",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="restaurant", ref="#/components/schemas/Restaurant", nullable=true),
     *                 @OA\Property(property="delivery_available", type="boolean", example=true),
     *                 @OA\Property(property="address", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="label", type="string", example="Casa"),
     *                     @OA\Property(property="address_line", type="string", example="Zona 10, Guatemala"),
     *                     @OA\Property(property="latitude", type="number", format="float", example=14.6349),
     *                     @OA\Property(property="longitude", type="number", format="float", example=-90.5069)
     *                 ),
     *                 @OA\Property(property="zone", type="string", example="capital", description="Zona de precios: capital o interior")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Dirección no encontrada o sin cobertura",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="error", type="string", example="no_coverage"),
     *             @OA\Property(property="message", type="string", example="No tenemos cobertura de delivery en esta dirección"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="restaurant", type="null"),
     *                 @OA\Property(property="delivery_available", type="boolean", example=false)
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=422, description="Error de validación")
     * )
     */
    public function forDelivery(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'address_id' => ['required', 'integer', 'exists:customer_addresses,id'],
        ]);

        /** @var \App\Models\Customer $customer */
        $customer = Auth::guard('sanctum')->user();

        $address = CustomerAddress::query()
            ->where('id', $validated['address_id'])
            ->where('customer_id', $customer->id)
            ->first();

        if (! $address) {
            return response()->json([
                'error' => 'address_not_found',
                'message' => 'Dirección no válida',
                'data' => [
                    'restaurant' => null,
                    'delivery_available' => false,
                ],
            ], 404);
        }

        $restaurant = $this->geofenceService->getBestRestaurantForDelivery(
            $address->latitude,
            $address->longitude
        );

        if (! $restaurant) {
            return response()->json([
                'error' => 'no_coverage',
                'message' => 'Sin cobertura delivery en esta ubicación. Prueba pickup en restaurantes.',
                'data' => [
                    'restaurant' => null,
                    'delivery_available' => false,
                    'address' => [
                        'id' => $address->id,
                        'label' => $address->label,
                        'address_line' => $address->address_line,
                        'latitude' => (float) $address->latitude,
                        'longitude' => (float) $address->longitude,
                    ],
                ],
            ], 200);
        }

        return response()->json([
            'data' => [
                'restaurant' => RestaurantResource::make($restaurant),
                'delivery_available' => true,
                'address' => [
                    'id' => $address->id,
                    'label' => $address->label,
                    'address_line' => $address->address_line,
                    'latitude' => (float) $address->latitude,
                    'longitude' => (float) $address->longitude,
                ],
                'zone' => $restaurant->price_location,
            ],
        ]);
    }

    /**
     * Get all restaurants for pickup ordered by distance.
     *
     * @OA\Get(
     *     path="/api/v1/restaurants/for-pickup",
     *     tags={"Restaurants"},
     *     summary="Listar restaurantes para pickup",
     *     description="Retorna TODOS los restaurantes con pickup activo, ordenados por distancia desde la ubicación del cliente. No hay límite de radio.",
     *
     *     @OA\Parameter(
     *         name="lat",
     *         in="query",
     *         description="Latitud de la ubicación actual del cliente",
     *         required=true,
     *
     *         @OA\Schema(type="number", format="float", example=14.6349)
     *     ),
     *
     *     @OA\Parameter(
     *         name="lng",
     *         in="query",
     *         description="Longitud de la ubicación actual del cliente",
     *         required=true,
     *
     *         @OA\Schema(type="number", format="float", example=-90.5069)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Lista de restaurantes para pickup",
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
     *                                 @OA\Property(property="distance_km", type="number", format="float", example=2.45, description="Distancia en kilómetros desde la ubicación del cliente")
     *                             )
     *                         }
     *                     )
     *                 ),
     *                 @OA\Property(property="total_found", type="integer", example=5, description="Total de restaurantes con pickup disponible")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Ubicación requerida",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="error", type="string", example="location_required"),
     *             @OA\Property(property="message", type="string", example="Por favor activa tu ubicación para ver los restaurantes ordenados por distancia."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="restaurants", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="total_found", type="integer", example=0)
     *             )
     *         )
     *     )
     * )
     */
    public function forPickup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $lat = $validated['lat'] ?? null;
        $lng = $validated['lng'] ?? null;

        // Si no hay coordenadas, devolver todos los restaurantes sin ordenar por distancia
        if ($lat === null || $lng === null) {
            $restaurants = Restaurant::query()
                ->active()
                ->pickupActive()
                ->ordered()
                ->get();

            return response()->json([
                'data' => [
                    'restaurants' => RestaurantResource::collection($restaurants),
                    'total_found' => $restaurants->count(),
                    'ordered_by_distance' => false,
                ],
            ]);
        }

        // Haversine formula - Earth's radius in kilometers
        $earthRadius = 6371;

        $restaurants = Restaurant::query()
            ->active()
            ->pickupActive()
            ->withCoordinates()
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
            ->orderBy('distance_km', 'asc')
            ->get();

        return response()->json([
            'data' => [
                'restaurants' => RestaurantResource::collection($restaurants),
                'total_found' => $restaurants->count(),
                'ordered_by_distance' => true,
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
