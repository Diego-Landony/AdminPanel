<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\RestaurantUser
 */
class RestaurantUserResource extends JsonResource
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
            'restaurant_id' => $this->restaurant_id,
            'restaurant' => $this->whenLoaded('restaurant', fn () => [
                'id' => $this->restaurant->id,
                'name' => $this->restaurant->name,
            ]),
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'role_label' => $this->getRoleLabel(),
            'is_active' => $this->is_active,
            'is_online' => $this->is_online,
            'status' => $this->status,
            'email_verified_at' => $this->email_verified_at,
            'last_activity_at' => $this->last_activity_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * Obtiene la etiqueta del rol.
     */
    protected function getRoleLabel(): string
    {
        return match ($this->role) {
            'owner' => 'Propietario',
            'manager' => 'Gerente',
            'staff' => 'Personal',
            default => $this->role,
        };
    }
}
