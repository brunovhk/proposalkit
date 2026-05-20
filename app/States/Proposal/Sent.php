<?php

declare(strict_types=1);

namespace App\States\Proposal;

/**
 * Proposal has been sent to the client and is awaiting review.
 */
class Sent extends ProposalState
{
    public static string $name = 'sent';
}
