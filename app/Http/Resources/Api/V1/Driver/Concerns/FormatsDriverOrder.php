<?php

namespace App\Http\Resources\Api\V1\Driver\Concerns;

/**
 * Trait compartido para formatear órdenes de motoristas.
 *
 * Proporciona métodos comunes para DriverOrderResource y DriverOrderDetailResource.
 */
trait FormatsDriverOrder
{
    /**
     * Get human-readable status label.
     */
    protected function getStatusLabel(): string
    {
        return match ($this->status) {
            'pending' => 'Pendiente',
            'preparing' => 'Preparando',
            'ready' => 'Lista para envío',
            'out_for_delivery' => 'En camino',
            'delivered' => 'Entregada',
            'completed' => 'Completada',
            'cancelled' => 'Cancelada',
            'refunded' => 'Reembolsada',
            default => ucfirst($this->status),
        };
    }

    /**
     * Format items for driver: one line per unit (expanded by quantity).
     * Format: "Categoría - Producto - Variante" or "Categoría - Producto"
     *
     * @return array<int, string>
     */
    protected function formatItemsForDriver(): array
    {
        $lines = [];

        foreach ($this->items as $item) {
            $snapshot = $item->product_snapshot ?? [];

            $category = $snapshot['category'] ?? null;
            $name = $snapshot['name'] ?? 'Producto';
            $variant = $snapshot['variant'] ?? null;

            // Build line: "Categoría - Producto - Variante"
            $parts = array_filter([$category, $name, $variant]);
            $line = implode(' - ', $parts);

            // Expand by quantity (one line per unit)
            $quantity = $item->quantity ?? 1;
            for ($i = 0; $i < $quantity; $i++) {
                $lines[] = $line;
            }
        }

        return $lines;
    }

    /**
     * Get human-readable payment method label.
     */
    protected function getPaymentMethodLabel(): string
    {
        return match ($this->payment_method) {
            'cash' => 'Efectivo',
            'card' => 'Tarjeta',
            'online' => 'Pago en línea',
            default => ucfirst($this->payment_method ?? 'Desconocido'),
        };
    }

    /**
     * Get delivery notes from address snapshot.
     */
    protected function getDeliveryNotes(): ?string
    {
        $addressSnapshot = $this->delivery_address_snapshot ?? [];

        return $addressSnapshot['reference']
            ?? $addressSnapshot['delivery_notes']
            ?? $addressSnapshot['notes']
            ?? $this->notes
            ?? null;
    }

    /**
     * Get formatted restaurant data.
     *
     * @return array<string, mixed>|null
     */
    protected function getRestaurantData(): ?array
    {
        if (! $this->relationLoaded('restaurant') || ! $this->restaurant) {
            return null;
        }

        return [
            'id' => $this->restaurant->id,
            'name' => $this->restaurant->name,
            'address' => $this->restaurant->address,
            'phone' => $this->restaurant->phone,
            'coordinates' => [
                'latitude' => $this->restaurant->latitude ? (float) $this->restaurant->latitude : null,
                'longitude' => $this->restaurant->longitude ? (float) $this->restaurant->longitude : null,
            ],
        ];
    }

    /**
     * Get formatted customer data.
     *
     * @return array<string, mixed>
     */
    protected function getCustomerData(): array
    {
        $name = 'Cliente';

        if ($this->customer) {
            // Prioridad: full_name > name > email > 'Cliente'
            $name = filled($this->customer->full_name)
                ? $this->customer->full_name
                : (filled($this->customer->name)
                    ? $this->customer->name
                    : (filled($this->customer->email)
                        ? explode('@', $this->customer->email)[0]
                        : 'Cliente'));
        }

        return [
            'name' => $name,
            'phone' => $this->customer?->phone,
        ];
    }

    /**
     * Get formatted delivery address data.
     *
     * @return array<string, mixed>
     */
    protected function getDeliveryAddressData(): array
    {
        $addressSnapshot = $this->delivery_address_snapshot ?? [];

        return [
            'formatted' => $addressSnapshot['formatted_address']
                ?? $addressSnapshot['address']
                ?? $addressSnapshot['address_line']
                ?? null,
            'latitude' => $addressSnapshot['latitude'] ?? null,
            'longitude' => $addressSnapshot['longitude'] ?? null,
            'reference' => $addressSnapshot['reference']
                ?? $addressSnapshot['delivery_notes']
                ?? null,
        ];
    }

    /**
     * Get formatted payment data.
     *
     * @return array<string, mixed>
     */
    protected function getPaymentData(): array
    {
        return [
            'method' => $this->payment_method,
            'method_label' => $this->getPaymentMethodLabel(),
            'is_paid' => $this->payment_status === 'paid',
            'amount_to_collect' => $this->payment_status !== 'paid' ? (float) $this->total : 0,
        ];
    }
}
