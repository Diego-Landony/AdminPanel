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
            $snapshot = $item->product_snapshot ?? [];

            return [
                'name' => $snapshot['name'] ?? 'Unknown',
                'variant' => $snapshot['variant'] ?? null,
                'category_name' => $snapshot['category'] ?? null,
                'quantity' => $item->quantity,
                'unit_price' => (float) ($item->unit_price ?? 0),
                'total_price' => (float) ($item->subtotal ?? 0),
                'applied_promotion' => $snapshot['applied_promotion'] ?? $item->promotion_snapshot['name'] ?? null,
                'is_available' => $isAvailable,
            ];
        })->values();

        $canReorder = $itemsData->every(fn ($item) => $item['is_available']);

        // Delivery address snapshot
        $deliveryAddress = null;
        if ($this->service_type === 'delivery' && $this->delivery_address_snapshot) {
            $addressSnapshot = is_string($this->delivery_address_snapshot)
                ? json_decode($this->delivery_address_snapshot, true)
                : $this->delivery_address_snapshot;
            $deliveryAddress = [
                'address' => $addressSnapshot['address_line'] ?? $addressSnapshot['address'] ?? null,
            ];
        }

        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'ordered_at' => $this->created_at->toIso8601String(),
            'service_type' => $this->service_type,
            'restaurant' => $this->when($this->relationLoaded('restaurant'), fn () => [
                'id' => $this->restaurant->id,
                'name' => $this->restaurant->name,
                'address' => $this->restaurant->address,
                'latitude' => $this->restaurant->latitude ? (float) $this->restaurant->latitude : null,
                'longitude' => $this->restaurant->longitude ? (float) $this->restaurant->longitude : null,
            ]),
            'delivery_address' => $deliveryAddress,
            'total' => (float) $this->total,
            'points_earned' => (int) ($this->points_earned ?? 0),
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

        $names = $items->map(function ($item) {
            $name = $item->product_snapshot['name'] ?? 'Unknown';
            $variant = $item->product_snapshot['variant'] ?? null;

            return $variant ? "{$name} ({$variant})" : $name;
        });
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
