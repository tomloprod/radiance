<?php

declare(strict_types=1);

namespace Tomloprod\Radiance\Concerns;

use Tomloprod\Colority\Colors\Color;

trait ConfiguresColors
{
    /**
     * The base color for the avatar.
     *
     * Most generated colors will be within this color's hue range.
     */
    private ?Color $baseColor = null;

    /**
     * The solid color for the avatar.
     *
     * This will be used instead of the gradient.
     */
    private ?Color $solidColor = null;

    /**
     * Set a base color.
     *
     * @param  string  $color  The color to set as the base color. If the color is
     *                         not a valid color, it will be ignored.
     *
     * Examples: #8e8dcb, rgb(142 141 203), hsl(240.97, 37.35, 67.45).
     */
    public function baseColor(string $color): self
    {
        $this->baseColor = colority()->parse($color);

        return $this;
    }

    /**
     * Set a solid color.
     *
     * @param  string  $color  The color to set as the solid color. If the color is
     *                         not a valid color, it will be ignored.
     *
     * Examples: #8e8dcb, rgb(142 141 203), hsl(240.97, 37.35, 67.45).
     */
    public function solidColor(string $color): self
    {
        $this->solidColor = colority()->parse($color);

        return $this;
    }
}
