<?php

namespace App\Http\Controllers\Api\V1\Support;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Support\SupportReasonResource;
use App\Models\SupportReason;
use Illuminate\Http\JsonResponse;

class SupportReasonController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/support/reasons",
     *     tags={"Support"},
     *     summary="List available support reasons",
     *     description="Returns a list of active support reasons that customers can select when creating a ticket",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Reasons retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="reasons", type="array",
     *
     *                     @OA\Items(
     *
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="Problema con mi pedido"),
     *                         @OA\Property(property="slug", type="string", example="order_issue")
     *                     )
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        $reasons = SupportReason::active()->ordered()->get();

        return response()->json([
            'data' => [
                'reasons' => SupportReasonResource::collection($reasons),
            ],
        ]);
    }
}
