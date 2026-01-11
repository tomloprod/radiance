<?php

declare(strict_types=1);

namespace Tomloprod\Radiance\Services;

use Exception;
use Tomloprod\Colority\Colors\Color;
use Tomloprod\Colority\Colors\HslColor;
use Tomloprod\Radiance\Concerns\ConfiguresColors;
use Tomloprod\Radiance\Concerns\ConfiguresFilters;
use Tomloprod\Radiance\Concerns\ConfiguresPixelPattern;
use Tomloprod\Radiance\Concerns\ConfiguresShape;
use Tomloprod\Radiance\Concerns\ConfiguresText;
use Tomloprod\Radiance\Concerns\GeneratesPixelPattern;
use Tomloprod\Radiance\Enums\AvatarShape;
use Tomloprod\Radiance\Enums\EasterEgg;
use Tomloprod\Radiance\Enums\GradientZone;

final class RadianceManager
{
    use ConfiguresColors;
    use ConfiguresFilters;
    use ConfiguresPixelPattern;
    use ConfiguresShape;
    use ConfiguresText;
    use GeneratesPixelPattern;

    private static ?RadianceManager $instance = null;

    private ?string $seed = null;

    private function __construct() {}

    public function __clone()
    {
        throw new Exception('Cannot clone singleton');
    }

    public function __wakeup()
    {
        throw new Exception('Cannot unserialize singleton');
    }

    public static function instance(): self
    {
        if (! isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Flush the singleton instance.
     * Useful for testing to clear singleton state between tests.
     */
    public static function flush(): void
    {
        self::$instance = null;
    }

    public function seed(string $seed): self
    {
        $this->seed = ($seed === '') ? null : $seed;

        return $this;
    }

    public function toSvg(): string
    {
        $hashPrefix = hash('sha256', (string) $this->seed);

        $textShadowFilter = '';
        $textElement = '';

        if ($this->text !== null) {

            $filterId = "shadow-{$hashPrefix}";

            $shadowDy = 2 * $this->textShadow;
            $shadowBlur = 2.5 * $this->textShadow;
            $shadowOpacity = 0.4 * $this->textShadow;

            // Use feGaussianBlur + feOffset for better compatibility than feDropShadow
            $textShadowFilter = <<<SVG
                <filter id="{$filterId}" x="-50%" y="-50%" width="200%" height="200%">
                    <feGaussianBlur in="SourceAlpha" stdDeviation="{$shadowBlur}"/>
                    <feOffset dx="0" dy="{$shadowDy}" result="offsetblur"/>
                    <feComponentTransfer>
                        <feFuncA type="linear" slope="{$shadowOpacity}"/>
                    </feComponentTransfer>
                    <feMerge>
                        <feMergeNode/>
                        <feMergeNode in="SourceGraphic"/>
                    </feMerge>
                </filter>
            SVG;

            // Add comprehensive font fallbacks for better compatibility
            $fontFamily = htmlspecialchars($this->fontFamily, ENT_XML1, 'UTF-8');
            $fontFamilyWithFallbacks = "{$fontFamily}, -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif";

            // Calculate font size ratio based on text length if auto mode is enabled.
            $ratio = $this->fontSizeRatio;

            if ($this->fontSizeRatioAuto) {
                /**
                 * Use inverse proportion: shorter text = larger font, longer text = smaller font.
                 * Formula: 1.2 / textLength gives smooth scaling across all lengths.
                 */
                $textLength = mb_strlen($this->text);

                $ratio = match (true) {
                    $textLength <= 1 => 0.5,
                    $textLength === 2 => 0.45,
                    default => max(0.05, min(0.4, 1.2 / $textLength)),
                };
            }

            $fontSize = (int) ($this->size * $ratio);
            $escapedText = htmlspecialchars($this->text, ENT_XML1, 'UTF-8');

            $textElement = <<<SVG
                <text
                    x="50%"
                    y="50%"
                    dy="0.35em"
                    text-anchor="middle"
                    fill="#ffffff"
                    fill-opacity="1"
                    font-family="{$fontFamilyWithFallbacks}"
                    font-size="{$fontSize}"
                    font-weight="600"
                    filter="url(#{$filterId})"
                >{$escapedText}</text>
            SVG;
        }

        $clipPathId = "clip-{$hashPrefix}";
        $gradientDefs = $this->generateGradientDefs($hashPrefix);
        $backgroundElements = $this->generateBackgroundElements($clipPathId, $hashPrefix);

        return <<<SVG
            <svg
                xmlns="http://www.w3.org/2000/svg"
                width="{$this->size}"
                height="{$this->size}"
                viewBox="0 0 {$this->size} {$this->size}"
            >
                <defs>
                    {$this->generateClipPath($clipPathId)}
                    {$gradientDefs}
                    {$textShadowFilter}
                </defs>
                {$backgroundElements}
                {$this->generatePixelPatternSvg($clipPathId, $hashPrefix)}
                <g clip-path="url(#{$clipPathId})">{$textElement}</g>
            </svg>
        SVG;
    }

    public function toBase64(): string
    {
        return 'data:image/svg+xml;base64,'.base64_encode($this->toSvg());
    }

    /**
     * Generate mesh gradient data (colors, positions, fade distances).
     *
     * @return array{colors: array<int, array{int, int, int}>, positions: array<int, array{int, int}>, fadeDistances: array<int, int>, baseColor: ?HslColor, solidColor: ?string}
     */
    private function meshGradientData(): array
    {
        if ($this->seed === null) {
            $this->seed = bin2hex(random_bytes(16));
        }

        // :-)
        if (($easterEgg = EasterEgg::tryFrom(strtoupper($this->seed))) instanceof EasterEgg) {
            $this->solidColor = $easterEgg->getColor();
        }

        if ($this->solidColor instanceof Color) {
            return [
                'colors' => [],
                'positions' => [],
                'fadeDistances' => [],
                'baseColor' => null,
                'solidColor' => $this->solidColor->toHex()->getValueColor(),
            ];
        }

        $hash = hash('sha256', $this->seed);

        /**
         * Calculate the first sector (degrees) where the first color is assigned.
         *
         * The remaining colors are distributed evenly from this position,
         * incrementing by 51 degrees each.
         *
         * @see https://www.w3schools.com/colors/colors_hsl.asp
         */
        $baseHue = $this->getHashNumber(hash: $hash, offset: 0, length: 4) % 360;

        /** @var array<int, array{int, int, int}> HSL (hue, saturation, lightness) */
        $colors = [];

        /** @var array<int, array{int, int}> X,Y coordinates (%) for gradient center */
        $positions = [];

        /** @var array<int, int> Fade-out radius (%) */
        $fadeDistances = [];

        // Each separation between the 7 colors is 51 degrees
        $hueStep = (int) (360 / 7);

        // Generate colors and positions for each layer.
        for ($iColor = 0; $iColor < 7; $iColor++) {

            /**
             * SHA-256 produces 64 hex characters, so each color uses 8 characters.
             * 8 x 7 = 56. There would still be room for an additional color.
             */
            $offset = $iColor * 8;

            if ($this->baseColor instanceof Color) {

                // Force most colors to be within baseColor's hue range
                $targetHue = (int) $this->baseColor->toHsl()->getArrayValueColor()[0];

                /**
                 * The first 5 colors are analogous to the base color.
                 * The last 2 colors are complementary (accent) colors for contrast and depth.
                 */
                if ($iColor < 5) {
                    /**
                     * Analogous colors: within ±40° of the base hue.
                     * This maintains color harmony while allowing some variation.
                     */
                    $analogousVariation = 40;

                    $variationRange = ($analogousVariation * 2) + 1;
                    $hueVariation = ($this->getHashNumber($hash, $offset, 2) % $variationRange) - $analogousVariation;

                    $hue = ($targetHue + $hueVariation + 360) % 360;
                } else {
                    /**
                     * Complementary/accent colors: 180° opposite with ±30° variation.
                     * These add visual interest and depth to the gradient.
                     */
                    $accentVariation = 30;
                    $variationRange = ($accentVariation * 2) + 1;
                    $hueVariation = ($this->getHashNumber($hash, $offset, 2) % $variationRange) - $accentVariation;

                    // 180° rotation to find the complementary color
                    $accentOffset = 180;

                    $hue = ($targetHue + $accentOffset + $hueVariation + 360) % 360;
                }
            } else {

                // Random variation (in degrees) added to the hue to prevent the colors from looking too uniform.
                $hueVariation = 15;

                $variationRange = ($hueVariation * 2) + 1;
                $segmentVariation = ($this->getHashNumber($hash, $offset, 2) % $variationRange) - $hueVariation;

                $hue = ($baseHue + ($iColor * $hueStep) + $segmentVariation + 360) % 360;
            }

            // Minimum saturation percentage to ensure colors are vibrant and avoid dull tones.
            $saturationBaseMin = 60;

            // Range of random variation (percentage) added to the base saturation
            $saturationVariation = 21;

            // Minimum lightness percentage value to prevent the avatar from being too dark.
            $lightnessBaseMin = 45;

            // Range of random variation (percentage) added to the lightness to create depth.
            $lightnessVariation = 26;

            /**
             * Offsets used to sample different parts of the hash to ensure
             * properties (saturation, light, fade) are randomized independently of hue.
             */
            $offsetSat = 2;
            $offsetLight = 4;
            $offsetFade = 7;

            /**
             * Always use vibrant saturation and lightness values
             * The baseColor only affects the HUE, not saturation/lightness
             * This ensures gradients remain visually appealing regardless of baseColor
             */
            $baseSaturation = $saturationBaseMin + ($this->getHashNumber($hash, $offset + $offsetSat, 2) % $saturationVariation);
            $baseLightness = $lightnessBaseMin + ($this->getHashNumber($hash, $offset + $offsetLight, 2) % $lightnessVariation);

            $colors[] = [$hue, $baseSaturation, $baseLightness];

            $positions[] = $this->resolvePosition($iColor, $hash, $offset);

            // Size controls how far each gradient fades out
            $fadeDistances[] = $this->fadeDistance + ($this->getHashNumber($hash, $offset + $offsetFade, 1) % 41);
        }

        $baseColor = $this->calculateBaseColor($colors);

        return [
            'colors' => $colors,
            'positions' => $positions,
            'fadeDistances' => $fadeDistances,
            'baseColor' => $baseColor,
            'solidColor' => null,
        ];
    }

    /**
     * Generate SVG gradient definitions with improved color transitions.
     */
    private function generateGradientDefs(string $hashPrefix): string
    {
        $data = $this->meshGradientData();

        if ($data['solidColor'] !== null) {
            return '';
        }

        $gradientDefs = [];

        for ($i = 0; $i < 7; $i++) {
            [$h, $s, $l] = $data['colors'][$i];

            // Use colors as-is - saturation/contrast will be applied via feColorMatrix filter
            $adjustedS = $s;
            $adjustedL = $l;

            // Convert HSL to HEX for better compatibility
            $color = colority()->fromHsl([$h, $adjustedS, $adjustedL])->toHex()->getValueColor();

            $gradientId = "gradient-{$hashPrefix}-{$i}";

            /**
             * Use multiple stops with smooth easing curve to replicate CSS radial-gradient behavior.
             * CSS gradients have a more natural falloff than linear SVG stops.
             *
             * The curve simulates: opacity = 1 - (distance/radius)^2
             */
            $gradientDefs[] = <<<SVG
                <radialGradient id="{$gradientId}">
                    <stop offset="0%" stop-color="{$color}" stop-opacity="1"/>
                    <stop offset="15%" stop-color="{$color}" stop-opacity="0.97"/>
                    <stop offset="30%" stop-color="{$color}" stop-opacity="0.91"/>
                    <stop offset="45%" stop-color="{$color}" stop-opacity="0.80"/>
                    <stop offset="60%" stop-color="{$color}" stop-opacity="0.64"/>
                    <stop offset="75%" stop-color="{$color}" stop-opacity="0.44"/>
                    <stop offset="85%" stop-color="{$color}" stop-opacity="0.28"/>
                    <stop offset="92%" stop-color="{$color}" stop-opacity="0.14"/>
                    <stop offset="100%" stop-color="{$color}" stop-opacity="0"/>
                </radialGradient>
            SVG;
        }

        /**
         * Add blur filter for smoother gradient blending
         * Blur amount is proportional to size for consistent visual effect
         */
        $blurFilterId = "blur-{$hashPrefix}";
        $blurAmount = max(1, $this->size * 0.008);
        $gradientDefs[] = <<<SVG
            <filter id="{$blurFilterId}" x="-25%" y="-25%" width="150%" height="150%">
                <feGaussianBlur in="SourceGraphic" stdDeviation="{$blurAmount}"/>
            </filter>
        SVG;

        // Add saturation and contrast filter using feColorMatrix
        $colorFilterId = "colorfilter-{$hashPrefix}";
        $satMatrix = $this->generateSaturationMatrix($this->saturation);

        /**
         * Calculate contrast intercept: intercept = (1 - contrast) / 2
         * This ensures the contrast pivots around middle gray (0.5)
         */
        $contrastSlope = $this->contrast;
        $contrastIntercept = (1 - $this->contrast) / 2;

        $gradientDefs[] = <<<SVG
            <filter id="{$colorFilterId}" x="0" y="0" width="100%" height="100%">
                {$satMatrix}
                <feComponentTransfer>
                    <feFuncR type="linear" slope="{$contrastSlope}" intercept="{$contrastIntercept}"/>
                    <feFuncG type="linear" slope="{$contrastSlope}" intercept="{$contrastIntercept}"/>
                    <feFuncB type="linear" slope="{$contrastSlope}" intercept="{$contrastIntercept}"/>
                </feComponentTransfer>
            </filter>
        SVG;

        return implode("\n", $gradientDefs);
    }

    /**
     * Generate SVG feColorMatrix for saturation adjustment.
     */
    private function generateSaturationMatrix(float $saturation): string
    {
        /**
         * Standard saturation matrix formula
         * When saturation = 1, this is identity; < 1 desaturates, > 1 oversaturates
         */
        $s = $saturation;
        $sr = (1 - $s) * 0.2126;
        $sg = (1 - $s) * 0.7152;
        $sb = (1 - $s) * 0.0722;

        $m00 = $sr + $s;
        $m01 = $sg;
        $m02 = $sb;
        $m10 = $sr;
        $m11 = $sg + $s;
        $m12 = $sb;
        $m20 = $sr;
        $m21 = $sg;
        $m22 = $sb + $s;

        return <<<SVG
            <feColorMatrix type="matrix" values="{$m00} {$m01} {$m02} 0 0 {$m10} {$m11} {$m12} 0 0 {$m20} {$m21} {$m22} 0 0 0 0 0 1 0"/>
        SVG;
    }

    /**
     * Generate the background elements with gradients applied.
     */
    private function generateBackgroundElements(string $clipPathId, string $hashPrefix): string
    {
        $data = $this->meshGradientData();

        // Handle solid color case
        if ($data['solidColor'] !== null) {
            return <<<SVG
                <rect width="{$this->size}" height="{$this->size}" fill="{$data['solidColor']}" clip-path="url(#{$clipPathId})"/>
            SVG;
        }

        // Apply saturation to base color (guaranteed non-null when solidColor is null)
        assert($data['baseColor'] instanceof HslColor);
        $baseColorAdjusted = $this->adjustBaseColor($data['baseColor']);
        $colorFilterId = "colorfilter-{$hashPrefix}";
        $blurFilterId = "blur-{$hashPrefix}";

        // Base color layer (outside filter group for solid base)
        $elements = [];
        $elements[] = <<<SVG
            <rect width="{$this->size}" height="{$this->size}" fill="{$baseColorAdjusted}" clip-path="url(#{$clipPathId})"/>
        SVG;

        // Start a group for all gradient circles with color filter applied
        $elements[] = <<<SVG
            <g clip-path="url(#{$clipPathId})" filter="url(#{$colorFilterId})">
        SVG;

        /**
         * Add gradient layers in reverse order (back to front) for better blending
         * Larger circles first, smaller on top
         */
        $layerOrder = [6, 5, 4, 3, 2, 1, 0];

        foreach ($layerOrder as $i) {
            [$x, $y] = $data['positions'][$i];
            $dist = $data['fadeDistances'][$i];

            // Convert percentage to absolute coordinates
            $cx = ($x / 100) * $this->size;
            $cy = ($y / 100) * $this->size;

            // Increase radius for better overlap and blending (CSS gradients extend beyond visible area)
            $r = ($dist / 100) * $this->size * 1.2;

            $gradientId = "gradient-{$hashPrefix}-{$i}";

            /**
             * Apply blur filter to alternate circles for organic blending
             * This creates depth and prevents hard color boundaries
             */
            $circleFilter = ($i % 2 === 0) ? "filter=\"url(#{$blurFilterId})\"" : '';

            $elements[] = <<<SVG
                <circle cx="{$cx}" cy="{$cy}" r="{$r}" fill="url(#{$gradientId})" {$circleFilter}/>
            SVG;
        }

        // Close the filter group
        $elements[] = '</g>';

        return implode("\n", $elements);
    }

    /**
     * Calculate the X, Y coordinates for a gradient circle.
     *
     * @return array{int, int} - [X, Y] coordinates in percentage
     */
    private function resolvePosition(int $index, string $hash, int $offset): array
    {
        $zone = GradientZone::from($index);

        [$baseX, $baseY] = $zone->coordinates();

        $hashOffsetX = $offset + $index;
        $hashOffsetY = $offset + $index + 1;

        if ($zone === GradientZone::CenterBottom) {
            $posX = $baseX + ($this->getHashNumber($hash, $hashOffsetX, 1) % 30) - 15;
        } else {
            $posX = $baseX + ($this->getHashNumber($hash, $hashOffsetX, 1) % $zone->variation());
        }

        $posY = $baseY + ($this->getHashNumber($hash, $hashOffsetY, 1) % $zone->variation());

        return [$posX, $posY];
    }

    /**
     * Calculate a background base color by averaging two opposite gradients.
     *
     * @param  array<int, array{int, int, int}>  $colors
     */
    private function calculateBaseColor(array $colors): HslColor
    {
        [$h1, $s1, $l1] = $colors[0];
        [$h2, $s2, $l2] = $colors[4];

        $baseH = (int) (($h1 + $h2) / 2);
        $baseS = (int) (($s1 + $s2) / 2);
        $baseL = (int) max(30, (($l1 + $l2) / 2) - 10);

        return colority()->fromHsl([$baseH, $baseS, $baseL]);
    }

    /**
     * Adjust base color with saturation.
     */
    private function adjustBaseColor(HslColor $hslColor): string
    {
        /** @var array{int, int, int} $hsl */
        $hsl = $hslColor->getArrayValueColor();
        [$h, $s, $l] = $hsl;

        $adjustedS = min(100, $s * $this->saturation);

        return colority()->fromHsl([$h, $adjustedS, $l])->toHex()->getValueColor();
    }

    /**
     * Generate the clip path based on the selected shape.
     */
    private function generateClipPath(string $id): string
    {
        $size = $this->size;
        $center = (int) ($size / 2);
        $radius = (int) ($size * 0.2);

        return match ($this->shape) {
            AvatarShape::Circle => <<<SVG
                <clipPath id="{$id}"><circle cx="{$center}" cy="{$center}" r="{$center}"/></clipPath>
                SVG,
            AvatarShape::Squircle => <<<SVG
                <clipPath id="{$id}"><rect x="0" y="0" width="{$size}" height="{$size}" rx="{$radius}" ry="{$radius}"/></clipPath>
                SVG,
            default => <<<SVG
                <clipPath id="{$id}"><rect x="0" y="0" width="{$size}" height="{$size}"/></clipPath>
                SVG,
        };
    }

    /**
     * Extract a numeric value from a segment of the hash.
     */
    private function getHashNumber(string $hash, int $offset, int $length): int
    {
        return (int) hexdec(mb_substr($hash, $offset, $length));
    }
}
