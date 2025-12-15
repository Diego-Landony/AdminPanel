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
