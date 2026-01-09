<?php

namespace App\Http\Resources\Api\V1\Rewards;

use App\Models\Menu\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RewardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $item = $this->resource['item'];
        $type = $this->resource['type'];

        $data = [
            'id' => $item->id,
            'type' => $type,
            'name' => $item->name,
            'image' => $item->getImageUrl(),
            'points_cost' => $item->points_cost,
        ];

        // Solo productos pueden tener variantes
        if ($type === 'product' && $item instanceof Product) {
            $variants = $item->variants;

            if ($variants->isNotEmpty()) {
                $data['variants'] = $variants->map(fn ($v) => [
                    'id' => $v->id,
                    'name' => $v->name,
                    'points_cost' => $v->points_cost,
                ]);

                // Si tiene variantes canjeables, el producto base no tiene points_cost directo
                if (! $item->is_redeemable) {
                    $data['points_cost'] = null;
                }
            }
        }

        return $data;
    }
}
