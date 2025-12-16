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
     *
     * @OA\Get(
     *     path="/api/v1/addresses",
     *     tags={"Addresses"},
     *     summary="Listar direcciones",
     *     description="Retorna todas las direcciones de entrega guardadas por el cliente, ordenadas por dirección predeterminada primero.",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Direcciones obtenidas exitosamente",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="array",
     *
     *                 @OA\Items(
     *
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="label", type="string", example="Casa"),
     *                     @OA\Property(property="address_line", type="string", example="10 Calle 5-20 Zona 10"),
     *                     @OA\Property(property="latitude", type="number", format="float", example=14.6017),
     *                     @OA\Property(property="longitude", type="number", format="float", example=-90.5250),
     *                     @OA\Property(property="delivery_notes", type="string", nullable=true, example="Casa color amarillo, portón negro"),
     *                     @OA\Property(property="zone", type="string", enum={"capital","interior"}, example="capital", description="Zona de precios determinada automáticamente por geofencing"),
     *                     @OA\Property(property="is_default", type="boolean", example=true),
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
     *
     * @OA\Post(
     *     path="/api/v1/addresses",
     *     tags={"Addresses"},
     *     summary="Crear dirección",
     *     description="Crea una nueva dirección de entrega para el cliente. Si `is_default` es true, desmarca las demás direcciones como predeterminadas.",
     *     security={{"sanctum": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"label","address_line","latitude","longitude"},
     *
     *             @OA\Property(property="label", type="string", maxLength=100, example="Casa", description="Etiqueta de la dirección"),
     *             @OA\Property(property="address_line", type="string", maxLength=500, example="10 Calle 5-20 Zona 10", description="Dirección completa"),
     *             @OA\Property(property="latitude", type="number", format="float", example=14.6017, description="Latitud (entre -90 y 90)"),
     *             @OA\Property(property="longitude", type="number", format="float", example=-90.5250, description="Longitud (entre -180 y 180)"),
     *             @OA\Property(property="delivery_notes", type="string", maxLength=500, nullable=true, example="Casa color amarillo, portón negro", description="Notas adicionales para el repartidor"),
     *             @OA\Property(property="is_default", type="boolean", nullable=true, example=false, description="Marcar como dirección predeterminada")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Dirección creada exitosamente",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Dirección creada exitosamente"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="label", type="string", example="Casa"),
     *                 @OA\Property(property="address_line", type="string", example="10 Calle 5-20 Zona 10"),
     *                 @OA\Property(property="latitude", type="number", format="float", example=14.6017),
     *                 @OA\Property(property="longitude", type="number", format="float", example=-90.5250),
     *                 @OA\Property(property="zone", type="string", enum={"capital","interior"}, example="capital", description="Zona determinada automáticamente"),
     *                 @OA\Property(property="is_default", type="boolean", example=false)
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
     *             @OA\Property(property="message", type="string", example="La etiqueta es requerida"),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="label", type="array", @OA\Items(type="string", example="La etiqueta es requerida")),
     *                 @OA\Property(property="latitude", type="array", @OA\Items(type="string", example="La latitud debe estar entre -90 y 90"))
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="No autenticado")
     * )
     */
    public function store(StoreCustomerAddressRequest $request): JsonResponse
    {
        $customer = auth()->user();

        if ($request->boolean('is_default')) {
            $customer->addresses()->update(['is_default' => false]);
        }

        // Determinar zona automáticamente via geofencing
        $result = $this->deliveryValidation->validateCoordinates(
            $request->float('latitude'),
            $request->float('longitude')
        );

        $data = $request->validated();
        $data['zone'] = $result->zone ?? 'capital';

        $address = $customer->addresses()->create($data);

        return response()->json([
            'data' => new CustomerAddressResource($address),
            'message' => 'Dirección creada exitosamente',
        ], 201);
    }

    /**
     * GET /api/v1/addresses/{address}
     * Ver dirección específica
     *
     * @OA\Get(
     *     path="/api/v1/addresses/{address}",
     *     tags={"Addresses"},
     *     summary="Ver dirección",
     *     description="Obtiene los detalles de una dirección específica del cliente.",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="address",
     *         in="path",
     *         description="ID de la dirección",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(response=200, description="Dirección obtenida exitosamente"),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="No tienes permiso para acceder a esta dirección"),
     *     @OA\Response(response=404, description="Dirección no encontrada")
     * )
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
     *
     * @OA\Put(
     *     path="/api/v1/addresses/{address}",
     *     tags={"Addresses"},
     *     summary="Actualizar dirección",
     *     description="Actualiza los datos de una dirección existente.",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="address",
     *         in="path",
     *         description="ID de la dirección",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"label","address_line","latitude","longitude"},
     *
     *             @OA\Property(property="label", type="string", example="Oficina"),
     *             @OA\Property(property="address_line", type="string", example="Avenida Reforma 12-00 Zona 9"),
     *             @OA\Property(property="latitude", type="number", format="float", example=14.5950),
     *             @OA\Property(property="longitude", type="number", format="float", example=-90.5200),
     *             @OA\Property(property="delivery_notes", type="string", nullable=true, example="Edificio azul, piso 5"),
     *             @OA\Property(property="is_default", type="boolean", nullable=true, example=true)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Dirección actualizada exitosamente",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Dirección actualizada exitosamente"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="No tienes permiso"),
     *     @OA\Response(response=404, description="Dirección no encontrada"),
     *     @OA\Response(response=422, description="Error de validación")
     * )
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

        $data = $request->validated();

        // Recalcular zona si cambiaron las coordenadas
        $latChanged = $request->has('latitude') && (float) $request->latitude !== (float) $address->latitude;
        $lngChanged = $request->has('longitude') && (float) $request->longitude !== (float) $address->longitude;

        if ($latChanged || $lngChanged) {
            $result = $this->deliveryValidation->validateCoordinates(
                $request->float('latitude'),
                $request->float('longitude')
            );
            $data['zone'] = $result->zone ?? 'capital';
        }

        $address->update($data);

        return response()->json([
            'data' => new CustomerAddressResource($address->fresh()),
            'message' => 'Dirección actualizada exitosamente',
        ]);
    }

    /**
     * DELETE /api/v1/addresses/{address}
     * Eliminar dirección
     *
     * @OA\Delete(
     *     path="/api/v1/addresses/{address}",
     *     tags={"Addresses"},
     *     summary="Eliminar dirección",
     *     description="Elimina una dirección del cliente.",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="address",
     *         in="path",
     *         description="ID de la dirección",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Dirección eliminada exitosamente",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Dirección eliminada exitosamente")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="No tienes permiso"),
     *     @OA\Response(response=404, description="Dirección no encontrada")
     * )
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
     *
     * @OA\Post(
     *     path="/api/v1/addresses/{address}/set-default",
     *     tags={"Addresses"},
     *     summary="Establecer dirección predeterminada",
     *     description="Marca una dirección como predeterminada y desmarca las demás.",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="address",
     *         in="path",
     *         description="ID de la dirección",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Dirección marcada como predeterminada",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Dirección marcada como predeterminada"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="No tienes permiso"),
     *     @OA\Response(response=404, description="Dirección no encontrada")
     * )
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
     *
     * @OA\Post(
     *     path="/api/v1/addresses/validate",
     *     tags={"Addresses"},
     *     summary="Validar ubicación para delivery",
     *     description="Valida si las coordenadas proporcionadas están dentro de una zona de cobertura de delivery utilizando geocercas (geofencing).
     *
     * **Validación de Geofencing:**
     * - Verifica si la ubicación está dentro del polígono de cobertura de algún restaurante
     * - Asigna automáticamente el restaurante correspondiente
     * - Determina la zona de precios (capital/interior)
     * - Si no hay cobertura, sugiere restaurantes cercanos para pickup
     *
     * Esta validación es **obligatoria** antes de crear una orden de delivery.",
     *     security={{"sanctum": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"latitude","longitude"},
     *
     *             @OA\Property(property="latitude", type="number", format="float", example=14.6017, description="Latitud a validar"),
     *             @OA\Property(property="longitude", type="number", format="float", example=-90.5250, description="Longitud a validar")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Validación completada - Ver campo `is_valid` para determinar si hay cobertura",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="is_valid", type="boolean", example=true, description="true = dentro de zona de cobertura, false = fuera de zona"),
     *                 @OA\Property(property="delivery_available", type="boolean", example=true),
     *                 @OA\Property(property="restaurant", type="object", nullable=true, description="Solo cuando is_valid=true",
     *                     @OA\Property(property="id", type="integer", example=5),
     *                     @OA\Property(property="name", type="string", example="Subway Pradera Zona 10"),
     *                     @OA\Property(property="address", type="string", example="Centro Comercial Pradera Zona 10"),
     *                     @OA\Property(property="estimated_delivery_time", type="integer", example=30, description="Tiempo estimado en minutos")
     *                 ),
     *                 @OA\Property(property="zone", type="string", enum={"capital","interior"}, nullable=true, example="capital", description="Solo cuando is_valid=true"),
     *                 @OA\Property(property="message", type="string", nullable=true, example="Esta ubicación está fuera de nuestra zona de cobertura de delivery", description="Solo cuando is_valid=false"),
     *                 @OA\Property(property="nearest_pickup_locations", type="array", nullable=true, description="Solo cuando is_valid=false",
     *
     *                     @OA\Items(
     *
     *                         @OA\Property(property="id", type="integer", example=3),
     *                         @OA\Property(property="name", type="string", example="Subway Oakland"),
     *                         @OA\Property(property="distance_km", type="number", format="float", example=2.5)
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=422, description="Coordenadas inválidas")
     * )
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
