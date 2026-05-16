<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Defines the lifecycle states available to commercial proposals.
 */
enum ProposalStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Accepted = 'accepted';
    case Declined = 'declined';
}
