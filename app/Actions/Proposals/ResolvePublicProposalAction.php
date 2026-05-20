<?php

declare(strict_types=1);

namespace App\Actions\Proposals;

use App\Models\Proposal;
use Illuminate\Database\Eloquent\Builder;

/**
 * Resolves a public proposal by tenant slug and secure token without exposing internal IDs.
 */
class ResolvePublicProposalAction
{
    /**
     * Resolve the proposal or fail with a forbidden response for invalid public tokens.
     */
    public function execute(string $tenantSlug, string $secureToken): Proposal
    {
        $proposal = Proposal::query()
            ->with(['client', 'items', 'tenant'])
            ->where('secure_token', $secureToken)
            ->whereHas('tenant', static fn (Builder $query): Builder => $query->where('slug', $tenantSlug))
            ->first();

        if (! $proposal instanceof Proposal) {
            abort(403);
        }

        return $proposal;
    }
}
