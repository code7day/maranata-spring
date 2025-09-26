<?php

namespace App\Enums;

enum TransportEnum: string
{
    case BUS = 'bus';
    case INDIVIDUAL = 'individual';

    /**
     * Devuelve una etiqueta legible para el caso del enum.
     */
    public function getLabel(): ?string
    {
        return match($this) {
            self::BUS => 'Quiero ir en el Bus',
            self::INDIVIDUAL => 'Iré por mi cuenta',
        };
    }

    /**
     * Devuelve todos los valores de los casos como un array.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function asSelectArray(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(fn($case) => $case->getLabel(), self::cases())
        );
    }
}

