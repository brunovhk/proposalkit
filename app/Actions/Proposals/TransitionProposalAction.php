<?php

declare(strict_types=1);

namespace App\Actions\Proposals;

use App\Actions\Concerns\ResolvesAuthenticatedActor;
use App\Mail\ProposalAccepted;
use App\Mail\ProposalDeclined;
use App\Models\Proposal;
use App\Models\User;
use App\States\Proposal\Accepted;
use App\States\Proposal\Declined;
use App\States\Proposal\ProposalState;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Spatie\ModelStates\Exceptions\CouldNotPerformTransition;

/**
 * Transitions a proposal status inside a locked transaction to prevent double-submit races.
 */
class TransitionProposalAction
{
    use ResolvesAuthenticatedActor;

    /**
     * Transition the proposal to the requested target status.
     *
     * @param  class-string<ProposalState>  $targetStateClass
     *
     * @throws ValidationException
     */
    public function execute(Proposal $proposal, string $targetStateClass): Proposal
    {
        return DB::transaction(function () use ($proposal, $targetStateClass): Proposal {
            $lockedProposal = Proposal::query()
                ->whereKey($proposal->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $previousStatus = $lockedProposal->status;

            try {
                $lockedProposal = $lockedProposal->status->transitionTo($targetStateClass);
            } catch (CouldNotPerformTransition) {
                throw ValidationException::withMessages([
                    'status' => __('ui.proposals.transitions.invalid'),
                ]);
            }

            $lockedProposal->events()->create([
                'type' => 'transitioned',
                'payload' => [
                    'from' => $previousStatus->getValue(),
                    'to' => $targetStateClass::getMorphClass(),
                ],
                ...$this->authenticatedActor(),
                'occurred_at' => Date::now(),
            ]);

            $this->queueFinalizedNotification($lockedProposal, $targetStateClass);

            return $lockedProposal;
        });
    }

    /**
     * @param  class-string<ProposalState>  $targetStateClass
     */
    private function queueFinalizedNotification(Proposal $proposal, string $targetStateClass): void
    {
        $mailable = match ($targetStateClass) {
            Accepted::class => new ProposalAccepted($proposal),
            Declined::class => new ProposalDeclined($proposal),
            default => null,
        };

        if ($mailable === null) {
            return;
        }

        $proposal->loadMissing(['client', 'items', 'tenant']);

        $tenant = $proposal->tenant;

        $owner = $tenant
            ->users()
            ->oldest('id')
            ->first();

        if (! $owner instanceof User) {
            return;
        }

        Mail::to($owner)->queue($mailable->afterCommit());
    }
}
