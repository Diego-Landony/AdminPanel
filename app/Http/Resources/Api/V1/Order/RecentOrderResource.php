<?php

namespace App\Http\Resources\Api\V1\Order;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecentOrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $items = $this->relationLoaded('items') ? $this->items : collect();

        $itemsData = $items->map(function ($item) {
            $isAvailable = $this->isItemAvailable($item);

            return [
                'name' => $item->product_snapshot['name'] ?? 'Unknown',
                'quantity' => $item->quantity,
                'is_available' => $isAvailable,
            ];
        })->values();

        $canReorder = $itemsData->every(fn ($item) => $item['is_available']);

        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'ordered_at' => $this->created_at->toIso8601String(),
            'restaurant' => $this->when($this->relationLoaded('restaurant'), fn () => [
                'id' => $this->restaurant->id,
                'name' => $this->restaurant->name,
            ]),
            'total' => (float) $this->total,
            'items_summary' => $this->generateItemsSummary($items),
            'items' => $itemsData,
            'can_reorder' => $canReorder,
        ];
    }

    /**
     * Generate a comma-separated summary of items (max 50 chars).
     */
    private function generateItemsSummary($items): string
    {
        if ($items->isEmpty()) {
            return '';
        }

        $names = $items->map(fn ($item) => $item->product_snapshot['name'] ?? 'Unknown');
        $summary = $names->join(', ');

        if (mb_strlen($summary) > 50) {
            return mb_substr($summary, 0, 47).'...';
        }

        return $summary;
    }

    /**
     * Check if an order item is currently available.
     */
    private function isItemAvailable($item): bool
    {
        if ($item->isCombo()) {
            return $item->relationLoaded('combo') && $item->combo && $item->combo->is_active;
        }

        return $item->relationLoaded('product') && $item->product && $item->product->is_active;
    }
}
