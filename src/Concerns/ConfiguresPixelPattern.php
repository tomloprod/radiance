<?php

declare(strict_types=1);

namespace Tomloprod\Radiance\Concerns;

use Tomloprod\Radiance\Enums\PixelColorMode;
use Tomloprod\Radiance\Enums\PixelShape;

trait ConfiguresPixelPattern
{
    /**
     * Determine if the pixel pattern overlay is enabled.
     */
    private bool $enablePixelPattern = true;

    /**
     * Number of cells in the pixel grid (rows and columns).
     *
     * Must be an odd number for symmetry.
     */
    private int $pixelGridSize = 13;

    /**
     * Determine the opacity of the pixel pattern.
     *
     * Range: 0.0 (invisible) to 1.0 (fully visible).
     */
    private float $pixelOpacity = 0.3;

    /**
     * Color mode for the pixels.
     *
     * @see PixelColorMode
     */
    private PixelColorMode $pixelColorMode = PixelColorMode::Gradient;

    /**
     * Density of pixels in the pattern.
     *
     * Range: 0.0 (no pixels) to 1.0 (maximum pixels).
     */
    private float $pixelDensity = 0.25;

    /**
     * Determine the shape of the pixels.
     *
     * @see PixelShape
     */
    private PixelShape $pixelShape = PixelShape::Squares;

    /**
     * Enable or disable the pixel pattern overlay.
     */
    public function enablePixelPattern(bool $enable = true): self
    {
        $this->enablePixelPattern = $enable;

        return $this;
    }

    /**
     * Set the pixel grid size (3, 5, 7, etc.). Must be an odd number for symmetry.
     */
    public function pixelGridSize(int $size): self
    {
        // Ensure the size is an odd number.
        $size = ($size % 2 === 0) ? $size + 1 : $size;

        $this->pixelGridSize = max(3, $size);

        return $this;
    }

    /**
     * Set the pixel pattern opacity (0.0 to 1.0).
     */
    public function pixelOpacity(float $opacity): self
    {
        $this->pixelOpacity = max(0.0, min(1.0, $opacity));

        return $this;
    }

    /**
     * Use gradient color mode for pixels (colors derived from the avatar gradient).
     */
    public function pixelColorGradient(): self
    {
        $this->pixelColorMode = PixelColorMode::Gradient;

        return $this;
    }

    /**
     * Use monochrome color mode for pixels (white pixels only).
     */
    public function pixelColorMonochrome(): self
    {
        $this->pixelColorMode = PixelColorMode::Monochrome;

        return $this;
    }

    /**
     * Use accent color mode for pixels (complementary color to base).
     */
    public function pixelColorAccent(): self
    {
        $this->pixelColorMode = PixelColorMode::Accent;

        return $this;
    }

    /**
     * Set the pixel density (0.0 to 1.0).
     */
    public function pixelDensity(float $density): self
    {
        $this->pixelDensity = max(0.0, min(1.0, $density));

        return $this;
    }

    /**
     * Use square shape for pixels.
     */
    public function pixelShapeSquares(): self
    {
        $this->pixelShape = PixelShape::Squares;

        return $this;
    }

    /**
     * Use circle shape for pixels.
     */
    public function pixelShapeCircles(): self
    {
        $this->pixelShape = PixelShape::Circles;

        return $this;
    }

    /**
     * Use mixed shapes for pixels (deterministic mix based on seed).
     */
    public function pixelShapeMix(): self
    {
        $this->pixelShape = PixelShape::Mix;

        return $this;
    }
}
