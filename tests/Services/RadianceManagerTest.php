<?php

declare(strict_types=1);

use Tomloprod\Radiance\Services\RadianceManager;

beforeEach(function (): void {
    RadianceManager::flush();
});

it('throws exception on clone', function (): void {
    $instance = RadianceManager::instance();

    $closure = fn (): mixed => clone $instance;

    expect($closure)->toThrow(Exception::class, 'Cannot clone singleton');
});

it('throws exception on unserialize', function (): void {
    $instance = RadianceManager::instance();

    $closure = fn (): mixed => unserialize(serialize($instance));

    expect($closure)->toThrow(Exception::class, 'Cannot unserialize singleton');
});

it('returns the same instance', function (): void {
    $instance1 = RadianceManager::instance();
    $instance2 = RadianceManager::instance();

    expect($instance1)->toBe($instance2);
});

it('supports method chaining', function (): void {
    $builder = RadianceManager::instance();

    $result = $builder
        ->seed('test')
        ->size(512)
        ->circle();

    expect($result)->toBeInstanceOf(RadianceManager::class);
});

it('generates valid SVG output with toSvg()', function (): void {
    $svg = RadianceManager::instance()
        ->seed('test-seed')
        ->toSvg();

    expect($svg)->toBeString();
    expect($svg)->toContain('<svg');
    expect($svg)->toContain('</svg>');
    expect($svg)->toContain('xmlns="http://www.w3.org/2000/svg"');
});

it('is deterministic - same seed produces same output', function (): void {
    $radiance = RadianceManager::instance();

    $result1 = $radiance->seed('unique-seed')->toSvg();
    $result2 = $radiance->seed('unique-seed')->toSvg();

    expect($result1)->toBe($result2);
});

it('different seeds produce different outputs', function (): void {
    $radiance = RadianceManager::instance();

    $result1 = $radiance->seed('seed-one')->toSvg();
    $result2 = $radiance->seed('seed-two')->toSvg();

    expect($result1)->not()->toBe($result2);
});

it('respects custom size parameter', function (): void {
    $radiance = RadianceManager::instance();

    $small = $radiance->seed('test')->size(64)->toSvg();
    $large = $radiance->seed('test')->size(512)->toSvg();

    expect($small)->toContain('width="64"');
    expect($small)->toContain('height="64"');
    expect($large)->toContain('width="512"');
    expect($large)->toContain('height="512"');
});

it('applies circle shape with clipPath', function (): void {
    $svg = RadianceManager::instance()
        ->seed('test')
        ->circle()
        ->toSvg();

    expect($svg)->toContain('<circle cx=');
    expect($svg)->toContain('<clipPath');
});

it('applies square shape', function (): void {
    $svg = RadianceManager::instance()
        ->seed('test')
        ->square()
        ->toSvg();

    expect($svg)->toContain('<clipPath');
    expect($svg)->toContain('<rect');
});

it('applies squircle shape with rounded corners', function (): void {
    $svg = RadianceManager::instance()
        ->seed('test')
        ->squircle()
        ->toSvg();

    expect($svg)->toContain('<clipPath');
    expect($svg)->toContain('rx=');
    expect($svg)->toContain('ry=');
});

it('toBase64() outputs base64 data URI format', function (): void {
    $base64 = RadianceManager::instance()
        ->seed('test')
        ->toBase64();

    expect($base64)->toStartWith('data:image/svg+xml;base64,');

    // Verify it's valid base64
    $encoded = str_replace('data:image/svg+xml;base64,', '', $base64);
    expect(base64_decode($encoded, true))->not()->toBeFalse();
});

it('works with the full fluent chain example', function (): void {
    $result = RadianceManager::instance()
        ->seed('Semilla')
        ->size(512)
        ->toSvg();

    expect($result)->toBeString();
    expect($result)->toContain('<svg');
    expect($result)->toContain('width="512"');
});

it('allows shape to be changed multiple times', function (): void {
    $radiance = RadianceManager::instance();

    $circle = $radiance->seed('test')->circle()->toSvg();
    $square = $radiance->seed('test')->square()->toSvg();

    expect($circle)->not()->toBe($square);
});

it('generates valid SVG with native elements', function (): void {
    $svg = RadianceManager::instance()
        ->seed('test-seed')
        ->size(256)
        ->toSvg();

    expect($svg)->toBeString();
    expect($svg)->toContain('width="256"');
    expect($svg)->toContain('height="256"');
    expect($svg)->toContain('<radialGradient');
    expect($svg)->toContain('<circle');
});

it('generates random seed when no seed is provided', function (): void {
    $radiance = RadianceManager::instance();

    $result1 = $radiance->toSvg();
    $result2 = $radiance->toSvg();

    // Without seed, each call should generate different output (random seed)
    expect($result1)->not()->toBe($result2);
});

it('generates random seed when empty string seed is provided', function (): void {
    $radiance = RadianceManager::instance();

    $result1 = $radiance->seed('')->toSvg();
    $result2 = $radiance->seed('')->toSvg();

    // Empty string should trigger random seed generation
    expect($result1)->not()->toBe($result2);
});

it('allows customizing saturation', function (): void {
    $svg = RadianceManager::instance()
        ->seed('test')
        ->saturation(1.5)
        ->toSvg();

    // Saturation is applied via feColorMatrix filter
    expect($svg)->toContain('<radialGradient');
    expect($svg)->toContain('<circle');
    expect($svg)->toContain('<feColorMatrix');
});

it('allows customizing contrast', function (): void {
    $svg = RadianceManager::instance()
        ->seed('test')
        ->contrast(1.2)
        ->toSvg();

    // Contrast is applied via feComponentTransfer filter
    expect($svg)->toContain('<radialGradient');
    expect($svg)->toContain('<feComponentTransfer');
    expect($svg)->toContain('slope="1.2"');
});

it('displays text in SVG output with shadow filter', function (): void {
    $svg = RadianceManager::instance()
        ->seed('test')
        ->text('AB')
        ->toSvg();

    expect($svg)->toContain('<text');
    expect($svg)->toContain('>AB</text>');
    expect($svg)->toContain('sans-serif');
    expect($svg)->toContain('<filter id="shadow-');
    expect($svg)->toContain('feGaussianBlur');
});

it('allows customizing font family', function (): void {
    $svg = RadianceManager::instance()
        ->seed('test')
        ->text('XY')
        ->fontFamily('Arial, sans-serif')
        ->toSvg();

    expect($svg)->toContain('font-family="Arial, sans-serif');
});

it('calculates font size from fontSizeRatio', function (): void {
    $svg = RadianceManager::instance()
        ->seed('test')
        ->size(200)
        ->text('RT')
        ->fontSizeRatio(0.5)
        ->toSvg();

    // 200 * 0.5 = 100
    expect($svg)->toContain('font-size="100"');
});

it('uses auto fontSizeRatio by default', function (): void {
    $svg = RadianceManager::instance()
        ->seed('test')
        ->size(100)
        ->text('DF')
        ->toSvg();

    // 2 chars = 0.45 ratio, so 100 * 0.45 = 45
    expect($svg)->toContain('font-size="45"');
});

it('text method with empty string behaves like no text', function (): void {
    $svg = RadianceManager::instance()
        ->seed('test')
        ->text('')
        ->toSvg();

    expect($svg)->not()->toContain('<text');
});

it('fontSizeRatio is clamped to valid range', function (): void {
    $svgTooSmall = RadianceManager::instance()
        ->seed('test')
        ->size(100)
        ->text('SM')
        ->fontSizeRatio(0.001)
        ->toSvg();

    // Should be clamped to 0.01, so 100 * 0.01 = 1
    expect($svgTooSmall)->toContain('font-size="1"');

    $svgTooBig = RadianceManager::instance()
        ->seed('test')
        ->size(100)
        ->text('BG')
        ->fontSizeRatio(1.5)
        ->toSvg();

    // Should be clamped to 1.0, so 100 * 1.0 = 100
    expect($svgTooBig)->toContain('font-size="100"');
});

it('allows customizing fade distance', function (): void {
    $svg = RadianceManager::instance()
        ->seed('test')
        ->fadeDistance(80)
        ->toSvg();

    expect($svg)->toContain('<radialGradient');
});

it('allows customizing text shadow intensity', function (): void {
    $svg = RadianceManager::instance()
        ->seed('test')
        ->text('AB')
        ->textShadow(2.0)
        ->toSvg();

    // Shadow values should be doubled (dy=4, stdDeviation=5, opacity=0.8)
    expect($svg)->toContain('dy="4"');
    expect($svg)->toContain('stdDeviation="5"');
});

it('allows toggling fontSizeRatioAuto', function (): void {
    $svg = RadianceManager::instance()
        ->seed('test')
        ->size(100)
        ->text('AB')
        ->fontSizeRatioAuto(false)
        ->toSvg();

    // When auto is disabled, uses default fontSizeRatio of 0.5
    expect($svg)->toContain('font-size="50"');
});

it('auto sizes font for longer text', function (): void {
    $svg = RadianceManager::instance()
        ->seed('test')
        ->size(100)
        ->text('Hello World')
        ->toSvg();

    // 11 chars = 1.2/11 = 0.109, so 100 * 0.109 = 10
    expect($svg)->toContain('font-size="10"');
});

it('allows setting a base color', function (): void {
    $svg = RadianceManager::instance()
        ->seed('test')
        ->baseColor('#FF0000')
        ->toSvg();

    expect($svg)->toContain('<radialGradient');
});

it('activates PHP easter egg correctly', function (): void {
    $svg = RadianceManager::instance()
        ->seed('PHP')
        ->toSvg();

    expect($svg)->toContain('#4F5B93');
});

it('activates Laravel easter egg correctly', function (): void {
    $svg = RadianceManager::instance()
        ->seed('Laravel')
        ->toSvg();

    expect($svg)->toContain('#F05340');
});

it('generates a solid background when solidColor is set with HexColor', function (): void {
    $svg = RadianceManager::instance()->seed('test')
        ->solidColor('#ff0000')
        ->toSvg();

    expect($svg)->toContain('fill="#FF0000"');
});

it('generates a solid background when solidColor is set with RgbColor', function (): void {
    $svg = RadianceManager::instance()->seed('test')
        ->solidColor('rgb(0, 255, 0)')
        ->toSvg();

    expect($svg)->toContain('fill="#00FF00"');
});

it('generates a solid background when solidColor is set with HslColor', function (): void {
    $svg = RadianceManager::instance()->seed('test')
        ->solidColor('hsl(240, 100%, 50%)')
        ->toSvg();

    expect($svg)->toContain('fill="#0000FF"');
});

it('pixel pattern is enabled by default', function (): void {
    $svg = RadianceManager::instance()
        ->seed('default-test')
        ->toSvg();

    // When pixel pattern is enabled, it creates a group with opacity and rect elements
    expect($svg)->toMatch('/<g[^>]+opacity="[^"]+">/');
    expect($svg)->toContain('<rect x="');
});

it('generates same pixel pattern for same seed', function (): void {
    $svg1 = RadianceManager::instance()
        ->seed('test-pattern')
        ->enablePixelPattern()
        ->toSvg();

    $svg2 = RadianceManager::instance()
        ->seed('test-pattern')
        ->enablePixelPattern()
        ->toSvg();

    expect($svg1)->toBe($svg2);
});

it('generates different pixel patterns for different seeds', function (): void {
    $svg1 = RadianceManager::instance()
        ->seed('pattern-one')
        ->enablePixelPattern()
        ->toSvg();

    $svg2 = RadianceManager::instance()
        ->seed('pattern-two')
        ->enablePixelPattern()
        ->toSvg();

    expect($svg1)->not()->toBe($svg2);
});

it('pixel pattern includes rect elements when enabled', function (): void {
    $svg = RadianceManager::instance()
        ->seed('pattern-test')
        ->enablePixelPattern()
        ->toSvg();

    expect($svg)->toContain('<rect');
    expect($svg)->toContain('opacity="0.3"');
});

it('supports different pixel grid sizes', function (): void {
    $svg3x3 = RadianceManager::instance()
        ->seed('grid-test')
        ->enablePixelPattern()
        ->pixelGridSize(3)
        ->toSvg();

    $svg7x7 = RadianceManager::instance()
        ->seed('grid-test')
        ->enablePixelPattern()
        ->pixelGridSize(7)
        ->toSvg();

    expect($svg3x3)->toContain('<rect');
    expect($svg7x7)->toContain('<rect');
    expect($svg3x3)->not()->toBe($svg7x7);
});

it('supports gradient pixel color mode', function (): void {
    $svg = RadianceManager::instance()
        ->seed('color-mode-gradient')
        ->enablePixelPattern()
        ->pixelColorGradient()
        ->toSvg();

    expect($svg)->toContain('<rect');
    expect($svg)->toContain('fill="#');
});

it('supports monochrome pixel color mode', function (): void {
    $svg = RadianceManager::instance()
        ->seed('color-mode-monochrome')
        ->enablePixelPattern()
        ->pixelColorMonochrome()
        ->toSvg();

    expect($svg)->toContain('<rect');
    expect($svg)->toContain('fill="#ffffff"');
});

it('supports accent pixel color mode', function (): void {
    $svg = RadianceManager::instance()
        ->seed('color-mode-accent')
        ->enablePixelPattern()
        ->pixelColorAccent()
        ->toSvg();

    expect($svg)->toContain('<rect');
    expect($svg)->toContain('fill="#');
});

it('uses gradient as default pixel color mode', function (): void {
    $svg = RadianceManager::instance()
        ->seed('default-mode')
        ->enablePixelPattern()
        ->toSvg();

    // Should use gradient mode by default
    expect($svg)->toContain('<rect');
    expect($svg)->toContain('fill="#');
});

it('allows customizing pixel pattern opacity', function (): void {
    $svg = RadianceManager::instance()
        ->seed('opacity-test')
        ->enablePixelPattern()
        ->pixelOpacity(0.5)
        ->toSvg();

    expect($svg)->toContain('opacity="0.5"');
});

it('clamps pixel opacity to valid range', function (): void {
    $svgTooLow = RadianceManager::instance()
        ->seed('opacity-low')
        ->enablePixelPattern()
        ->pixelOpacity(-0.5)
        ->toSvg();

    expect($svgTooLow)->toContain('opacity="0"');

    $svgTooHigh = RadianceManager::instance()
        ->seed('opacity-high')
        ->enablePixelPattern()
        ->pixelOpacity(1.5)
        ->toSvg();

    expect($svgTooHigh)->toContain('opacity="1"');
});

it('pixel pattern can be disabled after being enabled', function (): void {
    $svg = RadianceManager::instance()
        ->seed('toggle-test')
        ->enablePixelPattern()
        ->enablePixelPattern(false)
        ->toSvg();

    // Should not contain pixel pattern elements
    expect($svg)->not()->toContain('opacity="0.3"');
});

it('pixel grid size is clamped to minimum of 3', function (): void {
    $svg = RadianceManager::instance()
        ->seed('grid-minimum')
        ->enablePixelPattern()
        ->pixelGridSize(1)
        ->toSvg();

    // Even with size 1, it should be clamped to 3
    expect($svg)->toContain('<rect');
});

it('pixel grid size is always odd for symmetry', function (): void {
    $svgEven = RadianceManager::instance()
        ->seed('grid-even')
        ->enablePixelPattern()
        ->pixelGridSize(6)
        ->toSvg();

    // Size 6 should become 7 (next odd number)
    expect($svgEven)->toContain('<rect');

    $svgOdd = RadianceManager::instance()
        ->seed('grid-odd')
        ->enablePixelPattern()
        ->pixelGridSize(5)
        ->toSvg();

    expect($svgOdd)->toContain('<rect');
});

it('pixel pattern works with base color in accent mode', function (): void {
    $svg = RadianceManager::instance()
        ->seed('accent-with-base')
        ->baseColor('#FF0000')
        ->enablePixelPattern()
        ->pixelColorAccent()
        ->toSvg();

    expect($svg)->toContain('<rect');
    // Accent should be complementary to red (180° from red's hue)
    expect($svg)->toContain('fill="#');
});

it('allows customizing pixel density', function (): void {
    $lowDensity = RadianceManager::instance()
        ->seed('density-test')
        ->enablePixelPattern()
        ->pixelDensity(0.1)
        ->toSvg();

    $highDensity = RadianceManager::instance()
        ->seed('density-test')
        ->enablePixelPattern()
        ->pixelDensity(0.9)
        ->toSvg();

    expect($lowDensity)->toContain('<rect');
    expect($highDensity)->toContain('<rect');

    // High density should have more pixels (more rect elements)
    $lowCount = substr_count($lowDensity, '<rect');
    $highCount = substr_count($highDensity, '<rect');
    expect($highCount)->toBeGreaterThan($lowCount);
});

it('clamps pixel density to valid range', function (): void {
    $svgLow = RadianceManager::instance()
        ->seed('density-clamp')
        ->enablePixelPattern()
        ->pixelDensity(-0.5)
        ->toSvg();

    $svgHigh = RadianceManager::instance()
        ->seed('density-clamp')
        ->enablePixelPattern()
        ->pixelDensity(1.5)
        ->toSvg();

    // Both should generate valid SVG
    expect($svgLow)->toContain('<svg');
    expect($svgHigh)->toContain('<svg');
});

it('supports square pixel shape', function (): void {
    $svg = RadianceManager::instance()
        ->seed('shape-squares')
        ->enablePixelPattern()
        ->pixelShapeSquares()
        ->toSvg();

    // Square shape uses rect elements
    expect($svg)->toMatch('/<rect x="\d+\.\d{2}"/');
});

it('supports circle pixel shape', function (): void {
    $svg = RadianceManager::instance()
        ->seed('shape-circles')
        ->enablePixelPattern()
        ->pixelShapeCircles()
        ->toSvg();

    // Circle shape uses circle elements with cx, cy, r
    expect($svg)->toMatch('/<circle cx="\d+\.\d{2}"/');
});

it('uses squares as default pixel shape', function (): void {
    $svg = RadianceManager::instance()
        ->seed('shape-default')
        ->enablePixelPattern()
        ->toSvg();

    // Should use squares by default (rect elements)
    expect($svg)->toMatch('/<rect x="\d+\.\d{2}"/');
});

it('supports mix pixel shape with both squares and circles', function (): void {
    $svg = RadianceManager::instance()
        ->seed('shape-mix')
        ->enablePixelPattern()
        ->pixelShapeMix()
        ->toSvg();

    // Mix mode should contain both rect and circle elements
    expect($svg)->toMatch('/<rect x="\d+\.\d{2}"/');
    expect($svg)->toMatch('/<circle cx="\d+\.\d{2}"/');
});

it('mix pixel shape is deterministic', function (): void {
    $svg1 = RadianceManager::instance()
        ->seed('mix-deterministic')
        ->enablePixelPattern()
        ->pixelShapeMix()
        ->toSvg();

    $svg2 = RadianceManager::instance()
        ->seed('mix-deterministic')
        ->enablePixelPattern()
        ->pixelShapeMix()
        ->toSvg();

    expect($svg1)->toBe($svg2);
});
