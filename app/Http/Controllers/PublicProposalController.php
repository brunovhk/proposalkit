<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Proposals\TransitionProposalAction;
use App\Enums\ProposalStatus;
use App\Http\Resources\ProposalResource;
use App\Models\Proposal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Renders and processes public proposal access by UUID.
 */
class PublicProposalController extends Controller
{
    /**
     * Display the public proposal view.
     */
    public function show(string $uuid): Response
    {
        $proposal = Proposal::query()
            ->with(['client', 'items'])
            ->where('uuid', $uuid)
            ->firstOrFail();

        return Inertia::render('proposals/PublicView', [
            'proposal' => ProposalResource::make($proposal),
            'transitionUrl' => route('public.proposals.transition', $proposal->uuid),
        ]);
    }

    /**
     * Transition the public proposal based on the recipient's decision.
     */
    public function transition(
        string $uuid,
        Request $request,
        TransitionProposalAction $transitionProposal,
    ): RedirectResponse {
        $validated = $request->validate([
            'action' => [
                'required',
                'string',
                Rule::in([
                    ProposalStatus::Accepted->value,
                    ProposalStatus::Declined->value,
                ]),
            ],
        ]);

        $proposal = Proposal::query()
            ->where('uuid', $uuid)
            ->firstOrFail();

        $targetStatus = ProposalStatus::from((string) $validated['action']);

        $transitionProposal->execute($proposal, $targetStatus);

        return to_route('public.proposals.show', $uuid)
            ->with('success', __("ui.proposals.public.{$targetStatus->value}_message"));
    }
}
