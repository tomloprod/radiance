<?php

declare(strict_types=1);

namespace Tomloprod\Radiance\Enums;

use Tomloprod\Colority\Colors\HexColor;

enum EasterEgg: string
{
    case PHP = 'PHP';
    case LARAVEL = 'LARAVEL';

    public function getColor(): HexColor
    {
        return match ($this) {
            self::PHP => new HexColor('#4f5b93'),
            self::LARAVEL => new HexColor('#F05340'),
        };
    }
}
