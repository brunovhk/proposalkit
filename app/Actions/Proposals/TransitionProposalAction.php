<?php

declare(strict_types=1);

namespace App\Actions\Proposals;

use App\Enums\ProposalStatus;
use App\Mail\ProposalAccepted;
use App\Mail\ProposalDeclined;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
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

            $this->queueFinalizedNotification($lockedProposal, $targetStatus);

            return $lockedProposal;
        });
    }

    /**
     * Queue the tenant-owner notification for client final decisions.
     */
    private function queueFinalizedNotification(Proposal $proposal, ProposalStatus $targetStatus): void
    {
        $mailable = match ($targetStatus) {
            ProposalStatus::Accepted => new ProposalAccepted($proposal),
            ProposalStatus::Declined => new ProposalDeclined($proposal),
            default => null,
        };

        if ($mailable === null) {
            return;
        }

        $proposal->loadMissing(['client', 'items']);

        $tenant = $proposal->tenant()->first();

        if ($tenant === null) {
            return;
        }

        $owner = $tenant
            ->users()
            ->oldest('id')
            ->first();

        if (! $owner instanceof User) {
            return;
        }

        Mail::to($owner)->queue($mailable->afterCommit());
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
