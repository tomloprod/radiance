<?php

declare(strict_types=1);

use Tomloprod\Radiance\Services\RadianceManager;
use Tomloprod\Radiance\Support\Facades\Radiance;

test('facade returns the same instance', function (): void {
    $instance1 = RadianceManager::instance();
    $instance2 = Radiance::instance();

    expect($instance1)->toBe($instance2);
});
