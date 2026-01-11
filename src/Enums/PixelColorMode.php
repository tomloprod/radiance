<?php

declare(strict_types=1);

namespace Tomloprod\Radiance\Enums;

/**
 * Available color modes for pixel pattern generation.
 */
enum PixelColorMode: string
{
    case Gradient = 'gradient';
    case Monochrome = 'monochrome';
    case Accent = 'accent';
}
