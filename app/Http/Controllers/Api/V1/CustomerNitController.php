<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CustomerNit\StoreCustomerNitRequest;
use App\Http\Requests\Api\V1\CustomerNit\UpdateCustomerNitRequest;
use App\Http\Resources\Api\V1\CustomerNitResource;
use App\Models\CustomerNit;
use Illuminate\Http\JsonResponse;

class CustomerNitController extends Controller
{
    /**
     * GET /api/v1/nits
     * Lista todos los NITs del cliente autenticado
     *
     * @OA\Get(
     *     path="/api/v1/nits",
     *     tags={"NITs"},
     *     summary="Listar NITs para facturación",
     *     description="Retorna todos los NITs registrados por el cliente para facturación, ordenados por NIT predeterminado primero.",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="NITs obtenidos exitosamente",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="array",
     *
     *                 @OA\Items(
     *
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="nit", type="string", example="123456789", description="Número de NIT"),
     *                     @OA\Property(property="nit_name", type="string", nullable=true, example="Juan Pérez", description="Nombre asociado al NIT"),
     *                     @OA\Property(property="nit_type", type="string", enum={"personal","company","other"}, nullable=true, example="company"),
     *                     @OA\Property(property="is_default", type="boolean", example=true),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time")
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
        $nits = auth()->user()->nits()
            ->orderByDesc('is_default')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'data' => CustomerNitResource::collection($nits),
        ]);
    }

    /**
     * POST /api/v1/nits
     * Crear nuevo NIT
     *
     * @OA\Post(
     *     path="/api/v1/nits",
     *     tags={"NITs"},
     *     summary="Crear NIT",
     *     description="Registra un nuevo NIT para facturación. El NIT debe ser único para este cliente.",
     *     security={{"sanctum": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"nit"},
     *
     *             @OA\Property(property="nit", type="string", maxLength=20, example="123456789", description="Número de NIT (máx. 20 caracteres)"),
     *             @OA\Property(property="nit_type", type="string", enum={"personal","company","other"}, nullable=true, example="personal", description="Tipo de NIT"),
     *             @OA\Property(property="nit_name", type="string", maxLength=255, nullable=true, example="Juan Pérez", description="Nombre asociado al NIT"),
     *             @OA\Property(property="is_default", type="boolean", nullable=true, example=false, description="Marcar como NIT predeterminado")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="NIT creado exitosamente",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="NIT creado exitosamente"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="nit", type="string", example="123456789"),
     *                 @OA\Property(property="nit_name", type="string", nullable=true),
     *                 @OA\Property(property="nit_type", type="string", nullable=true),
     *                 @OA\Property(property="is_default", type="boolean")
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
     *             @OA\Property(property="message", type="string", example="Este NIT ya está registrado en tu cuenta"),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="nit", type="array", @OA\Items(type="string", example="Este NIT ya está registrado en tu cuenta"))
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="No autenticado")
     * )
     */
    public function store(StoreCustomerNitRequest $request): JsonResponse
    {
        $customer = auth()->user();

        if ($request->boolean('is_default')) {
            $customer->nits()->update(['is_default' => false]);
        }

        $nit = $customer->nits()->create($request->validated());

        return response()->json([
            'data' => new CustomerNitResource($nit),
            'message' => 'NIT creado exitosamente',
        ], 201);
    }

    /**
     * GET /api/v1/nits/{nit}
     * Ver NIT específico
     *
     * @OA\Get(
     *     path="/api/v1/nits/{nit}",
     *     tags={"NITs"},
     *     summary="Ver NIT",
     *     description="Obtiene los detalles de un NIT específico del cliente.",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="nit",
     *         in="path",
     *         description="ID del NIT",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(response=200, description="NIT obtenido exitosamente"),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="No tienes permiso para acceder a este NIT"),
     *     @OA\Response(response=404, description="NIT no encontrado")
     * )
     */
    public function show(CustomerNit $nit): JsonResponse
    {
        $this->authorizeNit($nit);

        return response()->json([
            'data' => new CustomerNitResource($nit),
        ]);
    }

    /**
     * PUT /api/v1/nits/{nit}
     * Actualizar NIT
     *
     * @OA\Put(
     *     path="/api/v1/nits/{nit}",
     *     tags={"NITs"},
     *     summary="Actualizar NIT",
     *     description="Actualiza los datos de un NIT existente.",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="nit",
     *         in="path",
     *         description="ID del NIT",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"nit"},
     *
     *             @OA\Property(property="nit", type="string", example="987654321"),
     *             @OA\Property(property="nit_type", type="string", enum={"personal","company","other"}, nullable=true),
     *             @OA\Property(property="nit_name", type="string", nullable=true, example="Juan Pérez"),
     *             @OA\Property(property="is_default", type="boolean", nullable=true)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="NIT actualizado exitosamente",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="NIT actualizado exitosamente"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="No tienes permiso"),
     *     @OA\Response(response=404, description="NIT no encontrado"),
     *     @OA\Response(response=422, description="Error de validación")
     * )
     */
    public function update(UpdateCustomerNitRequest $request, CustomerNit $nit): JsonResponse
    {
        $this->authorizeNit($nit);
        $customer = auth()->user();

        if ($request->boolean('is_default')) {
            $customer->nits()
                ->where('id', '!=', $nit->id)
                ->update(['is_default' => false]);
        }

        $nit->update($request->validated());

        return response()->json([
            'data' => new CustomerNitResource($nit->fresh()),
            'message' => 'NIT actualizado exitosamente',
        ]);
    }

    /**
     * DELETE /api/v1/nits/{nit}
     * Eliminar NIT
     *
     * @OA\Delete(
     *     path="/api/v1/nits/{nit}",
     *     tags={"NITs"},
     *     summary="Eliminar NIT",
     *     description="Elimina un NIT del cliente.",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="nit",
     *         in="path",
     *         description="ID del NIT",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="NIT eliminado exitosamente",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="NIT eliminado exitosamente")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="No tienes permiso"),
     *     @OA\Response(response=404, description="NIT no encontrado")
     * )
     */
    public function destroy(CustomerNit $nit): JsonResponse
    {
        $this->authorizeNit($nit);

        $nit->delete();

        return response()->json([
            'message' => 'NIT eliminado exitosamente',
        ]);
    }

    /**
     * POST /api/v1/nits/{nit}/set-default
     * Marcar como NIT predeterminado
     *
     * @OA\Post(
     *     path="/api/v1/nits/{nit}/set-default",
     *     tags={"NITs"},
     *     summary="Establecer NIT predeterminado",
     *     description="Marca un NIT como predeterminado y desmarca los demás.",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="nit",
     *         in="path",
     *         description="ID del NIT",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="NIT marcado como predeterminado",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="NIT marcado como predeterminado"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="No tienes permiso"),
     *     @OA\Response(response=404, description="NIT no encontrado")
     * )
     */
    public function setDefault(CustomerNit $nit): JsonResponse
    {
        $this->authorizeNit($nit);

        $nit->markAsDefault();

        return response()->json([
            'data' => new CustomerNitResource($nit->fresh()),
            'message' => 'NIT marcado como predeterminado',
        ]);
    }

    /**
     * Verifica que el NIT pertenece al cliente autenticado
     */
    private function authorizeNit(CustomerNit $nit): void
    {
        if ($nit->customer_id !== auth()->id()) {
            abort(403, 'No tienes permiso para acceder a este NIT');
        }
    }
}
