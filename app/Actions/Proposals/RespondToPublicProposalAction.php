<?php

declare(strict_types=1);

namespace App\Actions\Proposals;

use App\Models\Proposal;
use App\States\Proposal\Accepted;
use App\States\Proposal\Declined;
use App\States\Proposal\ProposalState;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

/**
 * Applies public acceptance or rejection decisions and records recipient evidence.
 */
class RespondToPublicProposalAction
{
    public function __construct(
        private readonly TransitionProposalAction $transitionProposal,
    ) {}

    /**
     * Accept the proposal and record typed-name evidence.
     *
     * @param  array{typed_name: string, acceptance_confirmed: bool}  $evidence
     */
    public function accept(Proposal $proposal, array $evidence, Request $request): Proposal
    {
        return $this->respond(
            proposal: $proposal,
            targetStateClass: Accepted::class,
            eventType: 'accepted',
            payload: [
                'typed_name' => $evidence['typed_name'],
                'acceptance_confirmed' => $evidence['acceptance_confirmed'],
            ],
            request: $request,
        );
    }

    /**
     * Reject the proposal and record the optional rejection reason.
     */
    public function reject(Proposal $proposal, ?string $reason, Request $request): Proposal
    {
        return $this->respond(
            proposal: $proposal,
            targetStateClass: Declined::class,
            eventType: 'rejected',
            payload: [
                'reason' => $reason,
            ],
            request: $request,
        );
    }

    /**
     * Transition the proposal and write an immutable recipient evidence event.
     *
     * @param  class-string<ProposalState>  $targetStateClass
     * @param  array<string, mixed>  $payload
     */
    private function respond(
        Proposal $proposal,
        string $targetStateClass,
        string $eventType,
        array $payload,
        Request $request,
    ): Proposal {
        $occurredAt = Date::now();

        return DB::transaction(function () use ($proposal, $targetStateClass, $request, $eventType, $payload, $occurredAt): Proposal {
            $transitionedProposal = $this->transitionProposal->execute($proposal, $targetStateClass);

            $transitionedProposal->events()->create([
                'type' => $eventType,
                'payload' => [
                    ...$payload,
                    'occurred_at' => $occurredAt->toAtomString(),
                    'referrer' => $request->headers->get('referer'),
                ],
                'actor_type' => null,
                'actor_id' => null,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'occurred_at' => $occurredAt,
            ]);

            return $transitionedProposal;
        });
    }
}
