<?php

namespace App\Enums;

enum Gender: string
{
    case Male = 'male';
    case Female = 'female';
    case PreferNotToSay = 'prefer_not_to_say';

    public function label(): string
    {
        return match ($this) {
            self::Male => 'Masculino',
            self::Female => 'Femenino',
            self::PreferNotToSay => 'Prefiero no decirlo',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
