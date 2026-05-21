<?php

declare(strict_types=1);

namespace App\States\Proposal;

/**
 * Proposal is still editable by the tenant.
 */
class Draft extends ProposalState
{
    public static string $name = 'draft';
}
