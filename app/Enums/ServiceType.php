<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Defines the supported pricing strategies for catalog services.
 */
enum ServiceType: string
{
    case Hourly = 'hourly';
    case Fixed = 'fixed';
}
