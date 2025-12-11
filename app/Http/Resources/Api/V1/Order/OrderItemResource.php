<?php

namespace App\Http\Resources\Api\V1\Order;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
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
            'type' => $this->when(method_exists($this->resource, 'isCombo'), function () {
                return $this->isCombo() ? 'combo' : 'product';
            }, $this->combo_id ? 'combo' : 'product'),
            'product_snapshot' => $this->product_snapshot,
            'quantity' => $this->quantity,
            'unit_price' => (float) $this->unit_price,
            'options_price' => (float) $this->options_price,
            'subtotal' => (float) $this->subtotal,
            'selected_options' => $this->selected_options,
            'combo_selections' => $this->combo_selections,
            'notes' => $this->notes,
        ];
    }
}
