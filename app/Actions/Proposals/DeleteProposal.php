<?php

declare(strict_types=1);

namespace App\Actions\Proposals;

use App\Models\Proposal;

/**
 * Deletes a tenant-scoped proposal after policy authorization.
 */
class DeleteProposal
{
    public function execute(Proposal $proposal): void
    {
        $proposal->delete();
    }
}
