<?php

declare(strict_types=1);

namespace Tomloprod\Radiance\Concerns;

trait ConfiguresText
{
    /**
     * The text to display in the center of the avatar.
     */
    private ?string $text = null;

    /**
     * The font family for the text.
     */
    private string $fontFamily = 'monospace';

    /**
     * The font size as a ratio of the avatar size.
     *
     * Range: 0.01 to 1.0.
     */
    private float $fontSizeRatio = 0.5;

    /**
     * The intensity of the text shadow.
     *
     * Minimum value is 0 (no shadow).
     */
    private float $textShadow = 1.0;

    /**
     * Whether automatic font size ratio is enabled.
     */
    private bool $fontSizeRatioAuto = true;

    /**
     * Set the text to display in the center of the avatar.
     */
    public function text(string $text): self
    {
        $this->text = ($text === '') ? null : $text;

        return $this;
    }

    /**
     * Set the font family for the text.
     */
    public function fontFamily(string $fontFamily): self
    {
        $this->fontFamily = $fontFamily;

        return $this;
    }

    /**
     * Set the font size as a ratio of the avatar size.
     *
     * Calling this method disables automatic font size ratio.
     *
     * Range: 0.01 to 1.0.
     */
    public function fontSizeRatio(float $ratio): self
    {
        $this->fontSizeRatio = max(0.01, min(1.0, $ratio));
        $this->fontSizeRatioAuto = false;

        return $this;
    }

    /**
     * Set the intensity of the text shadow.
     */
    public function textShadow(float $intensity): self
    {
        $this->textShadow = max(0, $intensity);

        return $this;
    }

    /**
     * Enable or disable automatic font size ratio.
     */
    public function fontSizeRatioAuto(bool $enabled = true): self
    {
        $this->fontSizeRatioAuto = $enabled;

        return $this;
    }
}
