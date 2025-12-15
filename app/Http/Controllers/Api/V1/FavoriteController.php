<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Favorite\StoreFavoriteRequest;
use App\Http\Resources\Api\V1\FavoriteResource;
use App\Models\Menu\Combo;
use App\Models\Menu\Product;
use Illuminate\Http\JsonResponse;

class FavoriteController extends Controller
{
    /**
     * GET /api/v1/favorites
     * Lista todos los favoritos del cliente autenticado
     */
    public function index(): JsonResponse
    {
        $favorites = auth()->user()
            ->favorites()
            ->with('favorable')
            ->latest()
            ->get();

        return response()->json([
            'data' => FavoriteResource::collection($favorites),
        ]);
    }

    /**
     * POST /api/v1/favorites
     * Agregar producto o combo a favoritos
     */
    public function store(StoreFavoriteRequest $request): JsonResponse
    {
        $customer = auth()->user();
        $favorableType = $request->input('favorable_type');
        $favorableId = $request->integer('favorable_id');

        $modelClass = $favorableType === 'product' ? Product::class : Combo::class;

        $existing = $customer->favorites()
            ->where('favorable_type', $modelClass)
            ->where('favorable_id', $favorableId)
            ->first();

        if ($existing) {
            return response()->json([
                'data' => new FavoriteResource($existing),
                'message' => 'Este item ya está en tus favoritos',
            ]);
        }

        $favorite = $customer->favorites()->create([
            'favorable_type' => $modelClass,
            'favorable_id' => $favorableId,
        ]);

        $favorite->load('favorable');

        return response()->json([
            'data' => new FavoriteResource($favorite),
            'message' => 'Agregado a favoritos exitosamente',
        ], 201);
    }

    /**
     * DELETE /api/v1/favorites/{type}/{id}
     * Quitar de favoritos
     */
    public function destroy(string $type, int $id): JsonResponse
    {
        $customer = auth()->user();
        $modelClass = $type === 'product' ? Product::class : Combo::class;

        if (! in_array($type, ['product', 'combo'])) {
            abort(422, 'Tipo de favorito inválido');
        }

        $favorite = $customer->favorites()
            ->where('favorable_type', $modelClass)
            ->where('favorable_id', $id)
            ->first();

        if (! $favorite) {
            abort(404, 'Favorito no encontrado');
        }

        $favorite->delete();

        return response()->json([
            'message' => 'Removido de favoritos exitosamente',
        ]);
    }
}
