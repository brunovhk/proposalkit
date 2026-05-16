<?php

declare(strict_types=1);

namespace App\Actions\Proposals;

use App\Enums\ProposalStatus;
use App\Models\Proposal;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Transitions a proposal status inside a locked transaction to prevent double-submit races.
 */
class TransitionProposalAction
{
    /**
     * Transition the proposal to the requested target status.
     *
     * @throws ValidationException
     */
    public function execute(Proposal $proposal, ProposalStatus $targetStatus): Proposal
    {
        return DB::transaction(function () use ($proposal, $targetStatus): Proposal {
            $lockedProposal = Proposal::query()
                ->whereKey($proposal->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->ensureTransitionIsAllowed($lockedProposal->status, $targetStatus);

            $lockedProposal->status = $targetStatus;
            $lockedProposal->save();

            return $lockedProposal;
        });
    }

    /**
     * Ensure the requested state transition is part of the allowed lifecycle.
     *
     * @throws ValidationException
     */
    private function ensureTransitionIsAllowed(
        ProposalStatus $currentStatus,
        ProposalStatus $targetStatus,
    ): void {
        $allowedTargets = match ($currentStatus) {
            ProposalStatus::Draft => [ProposalStatus::Sent],
            ProposalStatus::Sent => [ProposalStatus::Accepted, ProposalStatus::Declined],
            ProposalStatus::Accepted, ProposalStatus::Declined => [],
        };

        if (in_array($targetStatus, $allowedTargets, true)) {
            return;
        }

        throw ValidationException::withMessages([
            'status' => __('ui.proposals.transitions.invalid'),
        ]);
    }
}
