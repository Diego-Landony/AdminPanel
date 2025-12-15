<?php

namespace App\Http\Resources\Api\V1\Order;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
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
            'order_number' => $this->order_number,
            'restaurant' => $this->when($this->relationLoaded('restaurant'), fn () => [
                'id' => $this->restaurant->id,
                'name' => $this->restaurant->name,
                'address' => $this->restaurant->address,
            ]),
            'service_type' => $this->service_type,
            'zone' => $this->zone,
            'delivery_address' => $this->when($this->service_type === 'delivery', $this->delivery_address_snapshot),
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'promotions' => $this->when($this->relationLoaded('promotions'), fn () => $this->promotions->map(fn ($p) => [
                'name' => $p->promotion_name,
                'type' => $p->promotion_type,
                'discount' => (float) $p->discount_amount,
            ])
            ),
            'summary' => [
                'subtotal' => (float) $this->subtotal,
                'discount_total' => (float) $this->discount_total,
                'delivery_fee' => (float) $this->delivery_fee,
                'tax' => (float) $this->tax,
                'total' => (float) $this->total,
            ],
            'status' => $this->status,
            'payment' => [
                'method' => $this->payment_method,
                'status' => $this->payment_status,
                'paid_at' => $this->paid_at?->toIso8601String(),
            ],
            'points' => [
                'earned' => $this->points_earned,
                'redeemed' => $this->points_redeemed,
            ],
            'timestamps' => [
                'estimated_ready_at' => $this->estimated_ready_at?->toIso8601String(),
                'ready_at' => $this->ready_at?->toIso8601String(),
                'delivered_at' => $this->delivered_at?->toIso8601String(),
                'scheduled_for' => $this->scheduled_for?->toIso8601String(),
                'created_at' => $this->created_at->toIso8601String(),
            ],
            'delivery_person_rating' => $this->delivery_person_rating,
            'delivery_person_comment' => $this->when($this->delivery_person_rating, $this->delivery_person_comment),
            'notes' => $this->notes,
            'cancellation_reason' => $this->when($this->status === 'cancelled', $this->cancellation_reason),
            'can_cancel' => $this->when(method_exists($this->resource, 'canBeCancelled'), fn () => (bool) $this->canBeCancelled()),
            'has_review' => $this->review()->exists(),
            'review' => $this->when($this->relationLoaded('review') && $this->review, fn () => new OrderReviewResource($this->review)),
        ];
    }
}
