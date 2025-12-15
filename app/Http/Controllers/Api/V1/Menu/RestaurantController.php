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
     *     path="/api/v1/menu/restaurants",
     *     tags={"Menu - Restaurants"},
     *     summary="List active restaurants",
     *     description="Returns list of active restaurants with optional filters for delivery and pickup services.",
     *
     *     @OA\Parameter(
     *         name="delivery_active",
     *         in="query",
     *         description="Filter by delivery active status",
     *         required=false,
     *
     *         @OA\Schema(type="boolean", example=true)
     *     ),
     *
     *     @OA\Parameter(
     *         name="pickup_active",
     *         in="query",
     *         description="Filter by pickup active status",
     *         required=false,
     *
     *         @OA\Schema(type="boolean", example=true)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="List retrieved successfully",
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
     *     path="/api/v1/menu/restaurants/{id}",
     *     tags={"Menu - Restaurants"},
     *     summary="Get restaurant details",
     *     description="Returns detailed information about a specific restaurant.",
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
     *         description="Restaurant details retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="restaurant", ref="#/components/schemas/Restaurant")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=404, description="Restaurant not found")
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
     *     path="/api/v1/menu/restaurants/nearby",
     *     tags={"Menu - Restaurants"},
     *     summary="Get nearby restaurants",
     *     description="Returns restaurants near a specific location using Haversine formula to calculate distance. Results are ordered by distance.",
     *
     *     @OA\Parameter(
     *         name="lat",
     *         in="query",
     *         description="Latitude of user location",
     *         required=true,
     *
     *         @OA\Schema(type="number", format="float", example=14.6349)
     *     ),
     *
     *     @OA\Parameter(
     *         name="lng",
     *         in="query",
     *         description="Longitude of user location",
     *         required=true,
     *
     *         @OA\Schema(type="number", format="float", example=-90.5069)
     *     ),
     *
     *     @OA\Parameter(
     *         name="radius_km",
     *         in="query",
     *         description="Search radius in kilometers (default: 10km, max: 50km)",
     *         required=false,
     *
     *         @OA\Schema(type="number", format="float", example=5)
     *     ),
     *
     *     @OA\Parameter(
     *         name="delivery_active",
     *         in="query",
     *         description="Filter by delivery active status",
     *         required=false,
     *
     *         @OA\Schema(type="boolean", example=true)
     *     ),
     *
     *     @OA\Parameter(
     *         name="pickup_active",
     *         in="query",
     *         description="Filter by pickup active status",
     *         required=false,
     *
     *         @OA\Schema(type="boolean", example=true)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Nearby restaurants retrieved successfully",
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
     *                                 @OA\Property(property="distance_km", type="number", format="float", example=2.45, description="Distance in kilometers from user location")
     *                             )
     *                         }
     *                     )
     *                 ),
     *                 @OA\Property(property="search_radius_km", type="number", format="float", example=5, description="Search radius used"),
     *                 @OA\Property(property="total_found", type="integer", example=3, description="Total restaurants found within radius")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="The lat field is required."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function nearby(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'radius_km' => ['nullable', 'numeric', 'min:0.1', 'max:50'],
            'delivery_active' => ['nullable', 'boolean'],
            'pickup_active' => ['nullable', 'boolean'],
        ]);

        $lat = $validated['lat'];
        $lng = $validated['lng'];
        $radiusKm = $validated['radius_km'] ?? 10;

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
