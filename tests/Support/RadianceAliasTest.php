<?php

declare(strict_types=1);

use Tomloprod\Radiance\Services\RadianceManager;

test('radiance alias return instance of radiance', function (): void {
    expect(radiance())->toBeInstanceOf(RadianceManager::class);
});
