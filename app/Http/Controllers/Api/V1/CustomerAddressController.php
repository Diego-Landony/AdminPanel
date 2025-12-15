<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CustomerAddress\StoreCustomerAddressRequest;
use App\Http\Requests\Api\V1\CustomerAddress\UpdateCustomerAddressRequest;
use App\Http\Requests\Api\V1\CustomerAddress\ValidateLocationRequest;
use App\Http\Resources\Api\V1\CustomerAddressResource;
use App\Models\CustomerAddress;
use App\Services\DeliveryValidationService;
use Illuminate\Http\JsonResponse;

class CustomerAddressController extends Controller
{
    public function __construct(
        private DeliveryValidationService $deliveryValidation
    ) {}

    /**
     * GET /api/v1/addresses
     * Lista todas las direcciones del cliente autenticado
     */
    public function index(): JsonResponse
    {
        $addresses = auth()->user()->addresses()
            ->orderByDesc('is_default')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'data' => CustomerAddressResource::collection($addresses),
        ]);
    }

    /**
     * POST /api/v1/addresses
     * Crear nueva dirección
     */
    public function store(StoreCustomerAddressRequest $request): JsonResponse
    {
        $customer = auth()->user();

        if ($request->boolean('is_default')) {
            $customer->addresses()->update(['is_default' => false]);
        }

        $address = $customer->addresses()->create($request->validated());

        return response()->json([
            'data' => new CustomerAddressResource($address),
            'message' => 'Dirección creada exitosamente',
        ], 201);
    }

    /**
     * GET /api/v1/addresses/{address}
     * Ver dirección específica
     */
    public function show(CustomerAddress $address): JsonResponse
    {
        $this->authorizeAddress($address);

        return response()->json([
            'data' => new CustomerAddressResource($address),
        ]);
    }

    /**
     * PUT /api/v1/addresses/{address}
     * Actualizar dirección
     */
    public function update(UpdateCustomerAddressRequest $request, CustomerAddress $address): JsonResponse
    {
        $this->authorizeAddress($address);
        $customer = auth()->user();

        if ($request->boolean('is_default')) {
            $customer->addresses()
                ->where('id', '!=', $address->id)
                ->update(['is_default' => false]);
        }

        $address->update($request->validated());

        return response()->json([
            'data' => new CustomerAddressResource($address->fresh()),
            'message' => 'Dirección actualizada exitosamente',
        ]);
    }

    /**
     * DELETE /api/v1/addresses/{address}
     * Eliminar dirección
     */
    public function destroy(CustomerAddress $address): JsonResponse
    {
        $this->authorizeAddress($address);

        $address->delete();

        return response()->json([
            'message' => 'Dirección eliminada exitosamente',
        ]);
    }

    /**
     * POST /api/v1/addresses/{address}/set-default
     * Marcar como dirección predeterminada
     */
    public function setDefault(CustomerAddress $address): JsonResponse
    {
        $this->authorizeAddress($address);
        $customer = auth()->user();

        $customer->addresses()->update(['is_default' => false]);

        $address->update(['is_default' => true]);

        return response()->json([
            'data' => new CustomerAddressResource($address->fresh()),
            'message' => 'Dirección marcada como predeterminada',
        ]);
    }

    /**
     * POST /api/v1/addresses/validate
     * Validar coordenadas contra geocercas
     */
    public function validateLocation(ValidateLocationRequest $request): JsonResponse
    {
        $result = $this->deliveryValidation->validateCoordinates(
            $request->float('latitude'),
            $request->float('longitude')
        );

        if (! $result->isValid) {
            return response()->json([
                'data' => [
                    'is_valid' => false,
                    'delivery_available' => false,
                    'message' => $result->errorMessage,
                    'nearest_pickup_locations' => $result->nearbyPickupRestaurants,
                ],
            ]);
        }

        return response()->json([
            'data' => [
                'is_valid' => true,
                'delivery_available' => true,
                'restaurant' => [
                    'id' => $result->restaurant->id,
                    'name' => $result->restaurant->name,
                    'address' => $result->restaurant->address,
                    'estimated_delivery_time' => $result->restaurant->estimated_delivery_time,
                ],
                'zone' => $result->zone,
            ],
        ]);
    }

    /**
     * Verifica que la dirección pertenece al cliente autenticado
     */
    private function authorizeAddress(CustomerAddress $address): void
    {
        if ($address->customer_id !== auth()->id()) {
            abort(403, 'No tienes permiso para acceder a esta dirección');
        }
    }
}
