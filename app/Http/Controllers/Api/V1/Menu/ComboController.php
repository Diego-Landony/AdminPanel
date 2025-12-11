<?php

namespace App\Http\Controllers\Api\V1\Menu;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Menu\ComboResource;
use App\Models\Menu\Combo;
use Illuminate\Http\JsonResponse;

class ComboController extends Controller
{
    /**
     * Get list of active combos.
     *
     * @OA\Get(
     *     path="/api/v1/menu/combos",
     *     tags={"Menu"},
     *     summary="Get list of combos",
     *     description="Returns list of active and available combos.",
     *
     *     @OA\Response(
     *         response=200,
     *         description="Combos retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="combos", type="array",
     *
     *                     @OA\Items(ref="#/components/schemas/Combo")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        $combos = Combo::query()
            ->active()
            ->available()
            ->ordered()
            ->with([
                'items' => function ($query) {
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
                'badges',
            ])
            ->get();

        return response()->json([
            'data' => [
                'combos' => ComboResource::collection($combos),
            ],
        ]);
    }

    /**
     * Get combo details.
     *
     * @OA\Get(
     *     path="/api/v1/menu/combos/{id}",
     *     tags={"Menu"},
     *     summary="Get combo details",
     *     description="Returns combo with items and options.",
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Combo ID",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Combo retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="combo", ref="#/components/schemas/Combo")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=404, description="Combo not found")
     * )
     */
    public function show(int $id): JsonResponse
    {
        $combo = Combo::query()
            ->active()
            ->with([
                'items' => function ($query) {
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
                'badges',
            ])
            ->findOrFail($id);

        return response()->json([
            'data' => [
                'combo' => ComboResource::make($combo),
            ],
        ]);
    }
}
