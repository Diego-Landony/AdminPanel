<?php

namespace App\Enums;

enum OperatingSystem: string
{
    case iOS = 'ios';
    case Android = 'android';
    case Web = 'web';

    public function label(): string
    {
        return match ($this) {
            self::iOS => 'iOS',
            self::Android => 'Android',
            self::Web => 'Web',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
