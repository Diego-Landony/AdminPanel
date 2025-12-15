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
     *
     * @OA\Get(
     *     path="/api/v1/favorites",
     *     tags={"Favorites"},
     *     summary="Listar favoritos",
     *     description="Retorna todos los productos, combos y restaurantes marcados como favoritos por el cliente.",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Favoritos obtenidos exitosamente",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="array",
     *
     *                 @OA\Items(
     *
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="favorable_type", type="string", enum={"Product","Combo"}, example="Product"),
     *                     @OA\Property(property="favorable_id", type="integer", example=42),
     *                     @OA\Property(property="favorable", type="object",
     *                         @OA\Property(property="id", type="integer", example=42),
     *                         @OA\Property(property="name", type="string", example="Italian BMT"),
     *                         @OA\Property(property="description", type="string", nullable=true, example="Pepperoni, salami y jamón"),
     *                         @OA\Property(property="price", type="number", format="float", nullable=true, example=45.00),
     *                         @OA\Property(property="image", type="string", nullable=true, example="/storage/products/italian-bmt.jpg")
     *                     ),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2025-12-10T15:30:00Z")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="No autenticado")
     * )
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
     *
     * @OA\Post(
     *     path="/api/v1/favorites",
     *     tags={"Favorites"},
     *     summary="Agregar a favoritos",
     *     description="Agrega un producto o combo a la lista de favoritos del cliente. Si ya existe, retorna el favorito existente.",
     *     security={{"sanctum": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"favorable_type","favorable_id"},
     *
     *             @OA\Property(property="favorable_type", type="string", enum={"product","combo"}, example="product", description="Tipo de elemento a favorizar"),
     *             @OA\Property(property="favorable_id", type="integer", example=42, description="ID del producto o combo")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Agregado a favoritos exitosamente",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Agregado a favoritos exitosamente"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="favorable_type", type="string", example="Product"),
     *                 @OA\Property(property="favorable_id", type="integer", example=42),
     *                 @OA\Property(property="favorable", type="object",
     *                     @OA\Property(property="id", type="integer", example=42),
     *                     @OA\Property(property="name", type="string", example="Italian BMT")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Ya existe en favoritos",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Este item ya está en tus favoritos"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="El producto seleccionado no existe."),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="favorable_type", type="array", @OA\Items(type="string", example="El tipo de favorito debe ser producto o combo")),
     *                 @OA\Property(property="favorable_id", type="array", @OA\Items(type="string", example="El producto seleccionado no existe."))
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="No autenticado")
     * )
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
     *
     * @OA\Delete(
     *     path="/api/v1/favorites/{type}/{id}",
     *     tags={"Favorites"},
     *     summary="Eliminar de favoritos",
     *     description="Remueve un producto o combo de la lista de favoritos del cliente.",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="type",
     *         in="path",
     *         description="Tipo de favorito",
     *         required=true,
     *
     *         @OA\Schema(type="string", enum={"product","combo"}, example="product")
     *     ),
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del producto o combo",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=42)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Removido de favoritos exitosamente",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Removido de favoritos exitosamente")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=404, description="Favorito no encontrado"),
     *     @OA\Response(response=422, description="Tipo de favorito inválido")
     * )
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
