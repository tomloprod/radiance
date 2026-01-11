<?php

declare(strict_types=1);

namespace Tomloprod\Radiance\Concerns;

use Tomloprod\Colority\Colors\Color;
use Tomloprod\Radiance\Enums\PixelColorMode;
use Tomloprod\Radiance\Enums\PixelShape;

trait GeneratesPixelPattern
{
    /**
     * Generate the pixel pattern SVG overlay.
     */
    private function generatePixelPatternSvg(string $clipPathId, string $hash): string
    {
        if (! $this->enablePixelPattern) {
            return '';
        }

        $pixels = $this->generatePixelPattern($hash);

        return "<g clip-path=\"url(#{$clipPathId})\" opacity=\"{$this->pixelOpacity}\">{$pixels}</g>";
    }

    /**
     * Generate the deterministic pixel pattern based on seed.
     */
    private function generatePixelPattern(string $hash): string
    {
        $gridSize = $this->pixelGridSize;
        $pixelSize = $this->size / $gridSize;
        $halfGrid = (int) floor($gridSize / 2);

        $rects = [];

        // Generate only half the grid + center column for symmetry
        for ($y = 0; $y < $gridSize; $y++) {
            for ($x = 0; $x <= $halfGrid; $x++) {

                $index = ($y * ($halfGrid + 1)) + $x;

                // Use hash to determine if this pixel is "on" based on density
                $hashValue = $this->getHashNumber($hash, $index % 32, 2);

                $threshold = $hashValue % 100;

                $densityThreshold = (int) ($this->pixelDensity * 100);

                $isOn = $threshold < $densityThreshold;

                if ($isOn) {
                    $color = $this->getPixelColor($index, $hash);

                    // Add the pixel on the left/center
                    $xPos = $x * $pixelSize;
                    $yPos = $y * $pixelSize;
                    $rects[] = $this->createPixelElement($xPos, $yPos, $pixelSize, $color, $index, $hash);

                    // Mirror to the right (skip if it's the center column)
                    if ($x < $halfGrid) {
                        $mirrorX = ($gridSize - 1 - $x) * $pixelSize;
                        $rects[] = $this->createPixelElement($mirrorX, $yPos, $pixelSize, $color, $index, $hash);
                    }
                }
            }
        }

        return implode('', $rects);
    }

    /**
     * Create a single pixel SVG element (square, circle, or mix based on pixelShape setting).
     */
    private function createPixelElement(float $x, float $y, float $size, string $color, int $index, string $hash): string
    {
        $useCircle = match ($this->pixelShape) {
            PixelShape::Circles => true,
            PixelShape::Mix => $this->shouldUseCircle($index, $hash),
            default => false,
        };

        if ($useCircle) {
            $radius = $size / 2;
            $cx = $x + $radius;
            $cy = $y + $radius;

            return sprintf(
                '<circle cx="%.2f" cy="%.2f" r="%.2f" fill="%s"/>',
                $cx,
                $cy,
                $radius,
                $color
            );
        }

        return sprintf(
            '<rect x="%.2f" y="%.2f" width="%.2f" height="%.2f" fill="%s"/>',
            $x,
            $y,
            $size,
            $size,
            $color
        );
    }

    /**
     * Determine if a pixel should be a circle in 'mix' mode (deterministic based on seed).
     */
    private function shouldUseCircle(int $index, string $hash): bool
    {
        // Use hash to deterministically decide shape for each pixel
        $hashValue = $this->getHashNumber($hash, ($index * 5) % 32, 1);

        return ($hashValue % 2) === 0;
    }

    /**
     * Get the color for a pixel based on the selected color mode.
     */
    private function getPixelColor(int $index, string $hash): string
    {
        return match ($this->pixelColorMode) {
            PixelColorMode::Monochrome => '#ffffff',
            PixelColorMode::Accent => $this->getAccentColor($hash),
            default => $this->getGradientPixelColor($index, $hash),
        };
    }

    /**
     * Get a color from a harmonious palette for pixels (complementary color scheme).
     */
    private function getGradientPixelColor(int $index, string $hash): string
    {
        // Generate base hue from hash
        $baseHue = $this->getHashNumber($hash, 0, 4) % 360;

        /**
         * Create a color scheme using color theory:
         * - Base color
         * - Complementary (opposite, 180°)
         * - Split-complementary (150° and 210°)
         * - Triadic (120° and 240°)
         * - Analogous (±30°)
         *
         * @var array<int, int> $colorScheme
         */
        $colorScheme = [
            $baseHue, // Base
            ($baseHue + 180) % 360, // Complementary
            ($baseHue + 150) % 360, // Split-complementary 1
            ($baseHue + 210) % 360, // Split-complementary 2
            ($baseHue + 120) % 360, // Triadic 1
            ($baseHue + 240) % 360, // Triadic 2
            ($baseHue + 30) % 360, // Analogous 1
            ($baseHue - 30 + 360) % 360, // Analogous 2
        ];

        // Select color from scheme based on pixel index
        $colorIndex = $index % count($colorScheme);

        $hue = $colorScheme[$colorIndex];

        // Vary saturation and lightness slightly for depth
        $saturationVariation = $this->getHashNumber($hash, ($index * 2) % 32, 1) % 15;

        $lightnessVariation = $this->getHashNumber($hash, ($index * 3) % 32, 1) % 15;

        $saturation = 80 + $saturationVariation;

        $lightness = 55 + $lightnessVariation;

        // Convert HSL to HEX for better compatibility
        return colority()->fromHsl([$hue, $saturation, $lightness])->toHex()->getValueColor();
    }

    /**
     * Get an accent/complementary color for pixels.
     */
    private function getAccentColor(string $hash): string
    {
        if ($this->baseColor instanceof Color) {
            $baseHue = (int) $this->baseColor->toHsl()->getArrayValueColor()[0];

            $complementary = ($baseHue + 180) % 360;

            // Convert HSL to HEX for better compatibility
            return colority()->fromHsl([$complementary, 80, 65])->toHex()->getValueColor();
        }

        $hue = $this->getHashNumber($hash, 0, 4) % 360;

        // Convert HSL to HEX for better compatibility
        return colority()->fromHsl([$hue, 80, 65])->toHex()->getValueColor();
    }
}
