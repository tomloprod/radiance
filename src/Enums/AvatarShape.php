<?php

declare(strict_types=1);

namespace Tomloprod\Radiance\Enums;

/**
 * Available shapes for avatar generation.
 */
enum AvatarShape: string
{
    case Square = 'square';
    case Circle = 'circle';
    case Squircle = 'squircle';
}
