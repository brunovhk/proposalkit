<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Defines reserved first-party account identities used by platform workflows.
 */
enum SystemUserEmail: string
{
    case Demo = 'demo@proposalkit.test';
}
