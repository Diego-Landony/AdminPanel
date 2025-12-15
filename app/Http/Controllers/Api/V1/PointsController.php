<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Points\RedeemPointsRequest;
use App\Http\Resources\Api\V1\Points\PointsBalanceResource;
use App\Http\Resources\Api\V1\Points\PointsTransactionResource;
use App\Http\Resources\Api\V1\Points\RewardResource;
use App\Models\CustomerPointsTransaction;
use App\Models\Menu\Combo;
use App\Models\Menu\Product;
use App\Models\Menu\ProductVariant;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PointsController extends Controller
{
    /**
     * Get customer's current points balance
     */
    public function balance(Request $request): JsonResponse
    {
        $customer = $request->user();

        return response()->json([
            'data' => PointsBalanceResource::make($customer),
        ]);
    }

    /**
     * Get customer's points transaction history
     */
    public function history(Request $request): JsonResponse
    {
        $customer = $request->user();

        $transactions = CustomerPointsTransaction::query()
            ->where('customer_id', $customer->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'data' => PointsTransactionResource::collection($transactions),
            'meta' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ],
        ]);
    }

    /**
     * Redeem points on an order
     */
    public function redeem(RedeemPointsRequest $request): JsonResponse
    {
        $customer = $request->user();
        $validated = $request->validated();

        $order = Order::query()
            ->where('id', $validated['order_id'])
            ->where('customer_id', $customer->id)
            ->firstOrFail();

        if ($customer->points < $validated['points_to_redeem']) {
            throw ValidationException::withMessages([
                'points_to_redeem' => ['No tienes suficientes puntos disponibles.'],
            ]);
        }

        DB::beginTransaction();

        try {
            $transaction = CustomerPointsTransaction::create([
                'customer_id' => $customer->id,
                'points' => -$validated['points_to_redeem'],
                'type' => 'redeemed',
                'reference_type' => Order::class,
                'reference_id' => $order->id,
                'description' => "Redimidos {$validated['points_to_redeem']} puntos en orden #{$order->id}",
            ]);

            $customer->update([
                'points' => $customer->points - $validated['points_to_redeem'],
                'points_updated_at' => now(),
            ]);

            $customer->updateCustomerType();

            DB::commit();

            return response()->json([
                'message' => 'Puntos redimidos exitosamente.',
                'data' => [
                    'transaction' => PointsTransactionResource::make($transaction),
                    'new_balance' => PointsBalanceResource::make($customer->fresh()),
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get list of redeemable rewards (products, variants, and combos)
     */
    public function rewards(Request $request): JsonResponse
    {
        $products = Product::query()
            ->where('is_redeemable', true)
            ->whereNotNull('points_cost')
            ->where('is_active', true)
            ->get();

        $variants = ProductVariant::query()
            ->where('is_redeemable', true)
            ->whereNotNull('points_cost')
            ->where('is_active', true)
            ->get();

        $combos = Combo::query()
            ->where('is_redeemable', true)
            ->whereNotNull('points_cost')
            ->where('is_active', true)
            ->get();

        $rewards = collect()
            ->merge($products)
            ->merge($variants)
            ->merge($combos)
            ->sortBy('points_cost')
            ->values();

        return response()->json([
            'data' => RewardResource::collection($rewards),
            'meta' => [
                'total' => $rewards->count(),
                'products_count' => $products->count(),
                'variants_count' => $variants->count(),
                'combos_count' => $combos->count(),
            ],
        ]);
    }
}
