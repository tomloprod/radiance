<?php

declare(strict_types=1);

namespace Tomloprod\Radiance\Support\Facades;

use Tomloprod\Radiance\Services\RadianceManager;

/**
 * @method static RadianceManager seed(string $seed)
 * @method static string toSvg()
 * @method static string toBase64()
 * @method static RadianceManager baseColor(string $color)
 * @method static RadianceManager solidColor(string $color)
 * @method static RadianceManager saturation(float $value)
 * @method static RadianceManager contrast(float $value)
 * @method static RadianceManager fadeDistance(int $value)
 * @method static RadianceManager size(int $sizeInPixels)
 * @method static RadianceManager circle()
 * @method static RadianceManager square()
 * @method static RadianceManager squircle()
 * @method static RadianceManager text(string $text)
 * @method static RadianceManager fontFamily(string $fontFamily)
 * @method static RadianceManager fontSizeRatio(float $ratio)
 * @method static RadianceManager textShadow(float $intensity)
 * @method static RadianceManager fontSizeRatioAuto(bool $enabled = true)
 * @method static RadianceManager enablePixelPattern(bool $enable = true)
 * @method static RadianceManager pixelGridSize(int $size)
 * @method static RadianceManager pixelOpacity(float $opacity)
 * @method static RadianceManager pixelDensity(float $density)
 * @method static RadianceManager pixelColorGradient()
 * @method static RadianceManager pixelColorMonochrome()
 * @method static RadianceManager pixelColorAccent()
 * @method static RadianceManager pixelShapeSquares()
 * @method static RadianceManager pixelShapeCircles()
 * @method static RadianceManager pixelShapeMix()
 *
 * @see RadianceManager
 */
final class Radiance
{
    /**
     * @param  array<mixed>  $args
     */
    public static function __callStatic(string $method, array $args): mixed
    {
        $instance = RadianceManager::instance();

        return $instance->$method(...$args);
    }
}
