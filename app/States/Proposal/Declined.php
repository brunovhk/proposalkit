<?php

declare(strict_types=1);

namespace App\States\Proposal;

/**
 * Proposal has been declined by the client.
 */
class Declined extends ProposalState
{
    public static string $name = 'declined';
}
