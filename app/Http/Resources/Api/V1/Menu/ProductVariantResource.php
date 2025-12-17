<?php

namespace App\Http\Resources\Api\V1\Menu;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $this->name,
            'size' => $this->size,
            'prices' => [
                'pickup_capital' => (float) $this->precio_pickup_capital,
                'domicilio_capital' => (float) $this->precio_domicilio_capital,
                'pickup_interior' => (float) $this->precio_pickup_interior,
                'domicilio_interior' => (float) $this->precio_domicilio_interior,
            ],
            'is_redeemable' => (bool) $this->is_redeemable,
            'points_cost' => $this->points_cost,
            'is_daily_special' => $this->is_daily_special,
            'daily_special_days' => $this->daily_special_days,
            'daily_special_prices' => [
                'pickup_capital' => (float) $this->daily_special_precio_pickup_capital,
                'domicilio_capital' => (float) $this->daily_special_precio_domicilio_capital,
                'pickup_interior' => (float) $this->daily_special_precio_pickup_interior,
                'domicilio_interior' => (float) $this->daily_special_precio_domicilio_interior,
            ],
            'sort_order' => $this->sort_order,
        ];
    }
}
