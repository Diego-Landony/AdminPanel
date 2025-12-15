<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Menu\ComboResource;
use App\Http\Resources\Api\V1\Menu\ProductResource;
use App\Models\Menu\Combo;
use App\Models\Menu\Product;
use App\Models\ProductView;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductViewController extends Controller
{
    public function recordProductView(Request $request, Product $product): JsonResponse
    {
        $customer = $request->user();

        ProductView::recordView($customer, $product);

        return response()->json([
            'message' => 'Vista registrada exitosamente',
        ]);
    }

    public function recordComboView(Request $request, Combo $combo): JsonResponse
    {
        $customer = $request->user();

        ProductView::recordView($customer, $combo);

        return response()->json([
            'message' => 'Vista registrada exitosamente',
        ]);
    }

    public function getRecentlyViewed(Request $request): JsonResponse
    {
        $customer = $request->user();

        $views = ProductView::where('customer_id', $customer->id)
            ->with('viewable')
            ->orderBy('viewed_at', 'desc')
            ->limit(20)
            ->get();

        $items = $views->map(function ($view) {
            if (! $view->viewable) {
                return null;
            }

            if ($view->viewable_type === Product::class) {
                return [
                    'type' => 'product',
                    'data' => new ProductResource($view->viewable),
                    'viewed_at' => $view->viewed_at->toIso8601String(),
                ];
            } elseif ($view->viewable_type === Combo::class) {
                return [
                    'type' => 'combo',
                    'data' => new ComboResource($view->viewable),
                    'viewed_at' => $view->viewed_at->toIso8601String(),
                ];
            }

            return null;
        })->filter()->values();

        return response()->json([
            'data' => $items,
        ]);
    }
}
