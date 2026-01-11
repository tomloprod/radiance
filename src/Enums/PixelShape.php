<?php

declare(strict_types=1);

namespace Tomloprod\Radiance\Enums;

/**
 * Available shapes for pixel pattern generation.
 */
enum PixelShape: string
{
    case Squares = 'squares';
    case Circles = 'circles';
    case Mix = 'mix';
}
