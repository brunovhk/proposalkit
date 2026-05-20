<?php

declare(strict_types=1);

namespace App\States\Proposal;

/**
 * Proposal has been accepted by the client.
 */
class Accepted extends ProposalState
{
    public static string $name = 'accepted';
}
