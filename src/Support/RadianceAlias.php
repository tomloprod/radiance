<?php

declare(strict_types=1);

use Tomloprod\Radiance\Services\RadianceManager;

if (! function_exists('radiance')) {
    function radiance(): RadianceManager
    {
        return RadianceManager::instance();
    }
}
