<?php

namespace App\Enums;

enum OrderCancellationReason: string
{
    case ChangedMind = 'changed_mind';
    case LongWaitTime = 'long_wait_time';
    case OrderedByMistake = 'ordered_by_mistake';
    case WrongAddress = 'wrong_address';
    case FoundBetterOption = 'found_better_option';
    case NoLongerNeeded = 'no_longer_needed';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::ChangedMind => 'Cambié de opinión',
            self::LongWaitTime => 'Tiempo de espera muy largo',
            self::OrderedByMistake => 'Pedí por error',
            self::WrongAddress => 'Dirección de entrega incorrecta',
            self::FoundBetterOption => 'Encontré una mejor opción',
            self::NoLongerNeeded => 'Ya no necesito el pedido',
            self::Other => 'Otro',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function toArray(): array
    {
        return array_map(
            fn (self $case) => [
                'value' => $case->value,
                'label' => $case->label(),
            ],
            self::cases()
        );
    }
}
