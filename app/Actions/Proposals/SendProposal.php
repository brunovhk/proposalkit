<?php

declare(strict_types=1);

namespace App\Actions\Proposals;

use App\Jobs\GenerateProposalPdf;
use App\Mail\ProposalSent;
use App\Models\Proposal;
use App\States\Proposal\Sent;
use Illuminate\Support\Facades\Mail;

/**
 * Sends a draft proposal and queues the follow-up delivery side effects.
 */
class SendProposal
{
    public function __construct(
        private readonly TransitionProposalAction $transitionProposal,
    ) {}

    /**
     * Transition a draft proposal to sent and queue its outbound artifacts.
     */
    public function execute(Proposal $proposal): Proposal
    {
        $sentProposal = $this->transitionProposal->execute($proposal, Sent::class);

        dispatch(new GenerateProposalPdf($sentProposal->id));

        $sentProposal->loadMissing(['client', 'items', 'tenant']);

        Mail::to($sentProposal->client->email)
            ->queue(new ProposalSent($sentProposal));

        return $sentProposal;
    }
}
