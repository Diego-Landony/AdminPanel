<?php

namespace App\Http\Controllers\Api\V1\Menu;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Menu\PromotionResource;
use App\Models\Menu\Promotion;
use Illuminate\Http\JsonResponse;

class PromotionController extends Controller
{
    /**
     * Get list of active promotions.
     *
     * @OA\Get(
     *     path="/api/v1/menu/promotions",
     *     tags={"Menu"},
     *     summary="Get list of promotions",
     *     description="Returns list of active promotions.",
     *
     *     @OA\Response(
     *         response=200,
     *         description="Promotions retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="promotions", type="array",
     *
     *                     @OA\Items(ref="#/components/schemas/Promotion")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        $promotions = Promotion::query()
            ->active()
            ->orderBy('sort_order')
            ->with([
                'items' => function ($query) {
                    $query->with(['product', 'variant', 'category']);
                },
                'bundleItems' => function ($query) {
                    $query->orderBy('sort_order')
                        ->with([
                            'product',
                            'variant',
                            'options' => function ($q) {
                                $q->orderBy('sort_order')
                                    ->with(['product', 'variant']);
                            },
                        ]);
                },
            ])
            ->get();

        return response()->json([
            'data' => [
                'promotions' => PromotionResource::collection($promotions),
            ],
        ]);
    }

    /**
     * Get daily special (Sub del Día).
     *
     * @OA\Get(
     *     path="/api/v1/menu/promotions/daily",
     *     tags={"Menu"},
     *     summary="Get daily special",
     *     description="Returns active daily special promotion (Sub del Día).",
     *
     *     @OA\Response(
     *         response=200,
     *         description="Daily special retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="promotion", ref="#/components/schemas/Promotion")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="No daily special available",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="No hay Sub del Día disponible.")
     *         )
     *     )
     * )
     */
    public function daily(): JsonResponse
    {
        $promotion = Promotion::query()
            ->active()
            ->dailySpecial()
            ->with([
                'items' => function ($query) {
                    $query->with(['product', 'variant', 'category']);
                },
            ])
            ->first();

        if (! $promotion) {
            return response()->json([
                'message' => 'No hay Sub del Día disponible.',
            ], 404);
        }

        return response()->json([
            'data' => [
                'promotion' => PromotionResource::make($promotion),
            ],
        ]);
    }

    /**
     * Get active bundle specials (Combinados).
     *
     * @OA\Get(
     *     path="/api/v1/menu/promotions/combinados",
     *     tags={"Menu"},
     *     summary="Get bundle specials",
     *     description="Returns active bundle special promotions valid now (Combinados).",
     *
     *     @OA\Response(
     *         response=200,
     *         description="Bundle specials retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="promotions", type="array",
     *
     *                     @OA\Items(ref="#/components/schemas/Promotion")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function combinados(): JsonResponse
    {
        $promotions = Promotion::query()
            ->validNowCombinados()
            ->available()
            ->ordered()
            ->with([
                'bundleItems' => function ($query) {
                    $query->orderBy('sort_order')
                        ->with([
                            'product',
                            'variant',
                            'options' => function ($q) {
                                $q->orderBy('sort_order')
                                    ->with(['product', 'variant']);
                            },
                        ]);
                },
            ])
            ->get();

        return response()->json([
            'data' => [
                'promotions' => PromotionResource::collection($promotions),
            ],
        ]);
    }
}
