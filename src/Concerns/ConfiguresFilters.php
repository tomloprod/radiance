<?php

declare(strict_types=1);

namespace Tomloprod\Radiance\Concerns;

trait ConfiguresFilters
{
    /**
     * The saturation level for the avatar gradient.
     *
     * Acts as a multiplier: 1.0 = original colors, > 1 = more vivid, < 1 = more desaturated.
     *
     * Minimum value is 0 (grayscale).
     */
    private float $saturation = 1.0;

    /**
     * The contrast level for the avatar gradient.
     *
     * Acts as a multiplier: 1.0 = original contrast, > 1 = higher contrast, < 1 = lower contrast.
     *
     * Minimum value is 0.
     */
    private float $contrast = 1.0;

    /**
     * Size multiplier for the gradient circles.
     *
     * Lower values (40) = smaller circles, colors more separated.
     * Higher values (200) = larger circles, colors blend together.
     *
     * Range: 40-200.
     */
    private int $fadeDistance = 50;

    /**
     * Set the saturation level for the avatar gradient.
     */
    public function saturation(float $value): self
    {
        $this->saturation = max(0, $value);

        return $this;
    }

    /**
     * Set the contrast level for the gradient.
     */
    public function contrast(float $value): self
    {
        $this->contrast = max(0, $value);

        return $this;
    }

    /**
     * Set the size multiplier for the gradient circles.
     */
    public function fadeDistance(int $value): self
    {
        $this->fadeDistance = max(40, min(200, $value));

        return $this;
    }
}
