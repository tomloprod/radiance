<?php

declare(strict_types=1);

namespace Tomloprod\Radiance\Concerns;

use Tomloprod\Radiance\Enums\AvatarShape;

trait ConfiguresShape
{
    /**
     * The size of the avatar in pixels.
     */
    private int $size = 512;

    /**
     * The shape of the avatar.
     *
     * @see AvatarShape
     */
    private AvatarShape $shape = AvatarShape::Square;

    /**
     * Set the size of the avatar in pixels.
     *
     * @param  int  $sizeInPixels  The size of the avatar in pixels.
     */
    public function size(int $sizeInPixels): self
    {
        $this->size = $sizeInPixels;

        return $this;
    }

    /**
     * Set the shape of the avatar to circle.
     */
    public function circle(): self
    {
        $this->shape = AvatarShape::Circle;

        return $this;
    }

    /**
     * Set the shape of the avatar to square.
     */
    public function square(): self
    {
        $this->shape = AvatarShape::Square;

        return $this;
    }

    /**
     * Set the shape of the avatar to squircle (rounded square).
     */
    public function squircle(): self
    {
        $this->shape = AvatarShape::Squircle;

        return $this;
    }
}
