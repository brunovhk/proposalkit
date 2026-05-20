<?php

declare(strict_types=1);

namespace App\Actions\Proposals;

use App\Models\Proposal;
use App\States\Proposal\Sent;
use App\States\Proposal\Viewed;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

/**
 * Records public proposal reads and promotes the first sent proposal view to Viewed.
 */
class RecordProposalPublicViewAction
{
    public function __construct(
        private readonly TransitionProposalAction $transitionProposal,
    ) {}

    /**
     * Persist the view event and run the first-view status transition once.
     */
    public function execute(Proposal $proposal, Request $request): Proposal
    {
        if (Auth::check()) {
            return $proposal->load(['client', 'items', 'tenant']);
        }

        return DB::transaction(function () use ($proposal, $request): Proposal {
            $lockedProposal = Proposal::query()
                ->whereKey($proposal->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $transitioned = false;

            if ($lockedProposal->status->equals(Sent::class)) {
                $lockedProposal = $this->transitionProposal->execute($lockedProposal, Viewed::class);
                $transitioned = true;
            }

            $lockedProposal->events()->create([
                'type' => 'viewed',
                'payload' => [
                    'transitioned' => $transitioned,
                    'referrer' => $request->headers->get('referer'),
                ],
                'actor_type' => null,
                'actor_id' => null,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'occurred_at' => Date::now(),
            ]);

            return $lockedProposal->load(['client', 'items', 'tenant']);
        });
    }
}
