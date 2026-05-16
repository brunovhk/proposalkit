<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Proposal;
use App\Models\User;

/**
 * Authorizes access to tenant-owned proposal records.
 */
class ProposalPolicy
{
    /**
     * Determine whether the user can view proposal listings.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the proposal.
     */
    public function view(User $user, Proposal $proposal): bool
    {
        return $this->belongsToUsersTenant($user, $proposal);
    }

    /**
     * Determine whether the user can create proposals.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the proposal.
     */
    public function update(User $user, Proposal $proposal): bool
    {
        return $this->belongsToUsersTenant($user, $proposal);
    }

    /**
     * Determine whether the user can delete the proposal.
     */
    public function delete(User $user, Proposal $proposal): bool
    {
        return $this->belongsToUsersTenant($user, $proposal);
    }

    /**
     * Determine whether the proposal belongs to the user's tenant.
     */
    private function belongsToUsersTenant(User $user, Proposal $proposal): bool
    {
        return $proposal->tenant_id === $user->tenant_id;
    }
}
