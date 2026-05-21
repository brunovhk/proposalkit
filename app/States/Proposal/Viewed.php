<?php

declare(strict_types=1);

namespace App\States\Proposal;

/**
 * Proposal has been opened by the client at least once.
 */
class Viewed extends ProposalState
{
    public static string $name = 'viewed';
}
