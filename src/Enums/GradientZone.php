<?php

declare(strict_types=1);

namespace Tomloprod\Radiance\Enums;

/**
 * Defines the positioning zones for the mesh gradient
 */
enum GradientZone: int
{
    case TopLeft = 0;
    case TopRight = 1;
    case BottomRight = 2;
    case BottomLeft = 3;
    case Center = 4;
    case CenterBottom = 5;
    case Filler = 6;

    /**
     * Get the base [X, Y] coordinates (in %) for this zone.
     *
     * These positions are designed to create overlapping color regions
     * that blend together naturally, similar to CSS mesh gradients.
     *
     * @return array{int, int}
     */
    public function coordinates(): array
    {
        return match ($this) {
            self::TopLeft => [5, 5],
            self::TopRight => [85, 5],
            self::BottomRight => [85, 85],
            self::BottomLeft => [5, 85],
            self::Center => [50, 50],
            self::CenterBottom => [50, 70],
            self::Filler => [35, 35],
        };
    }

    /**
     * Get the allowed random variation (in %) for this zone.
     *
     * Larger variations create more organic, less predictable gradients.
     */
    public function variation(): int
    {
        return match ($this) {
            self::Center => 30,
            self::Filler => 50,
            self::CenterBottom => 25,
            default => 20,
        };
    }
}
