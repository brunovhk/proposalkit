<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Proposals\CreateProposal;
use App\Actions\Proposals\DeleteProposal;
use App\Actions\Proposals\TransitionProposalAction;
use App\Actions\Proposals\UpdateProposal;
use App\Http\Requests\Proposals\DeleteProposalRequest;
use App\Http\Requests\Proposals\SendProposalRequest;
use App\Http\Requests\Proposals\StoreProposalRequest;
use App\Http\Requests\Proposals\UpdateProposalRequest;
use App\Http\Resources\ClientResource;
use App\Http\Resources\ProposalResource;
use App\Http\Resources\ServiceResource;
use App\Jobs\GenerateProposalPdfJob;
use App\Mail\ProposalSent;
use App\Models\Client;
use App\Models\Proposal;
use App\Models\Service;
use App\States\Proposal\Sent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Renders and processes tenant-scoped proposal management screens.
 */
class ProposalController extends Controller
{
    /**
     * Display the tenant-scoped proposal list.
     */
    public function index(): Response
    {
        Gate::authorize('viewAny', Proposal::class);

        return Inertia::render('proposals/Index', [
            'proposals' => ProposalResource::collection(
                Proposal::query()
                    ->with(['client', 'items'])
                    ->latest()
                    ->get(),
            ),
        ]);
    }

    /**
     * Display the proposal creation form.
     */
    public function create(): Response
    {
        Gate::authorize('create', Proposal::class);

        return Inertia::render('proposals/Form', [
            'proposal' => null,
            'clients' => ClientResource::collection(
                Client::query()
                    ->orderBy('name')
                    ->get(),
            ),
            'services' => ServiceResource::collection(
                Service::query()
                    ->orderBy('name')
                    ->get(),
            ),
        ]);
    }

    /**
     * Persist a newly created proposal.
     */
    public function store(StoreProposalRequest $request, CreateProposal $createProposal): RedirectResponse
    {
        $createProposal->execute($request->validated());

        return to_route('proposals.index')
            ->with('success', __('ui.proposals.created'));
    }

    /**
     * Display the proposal editing form.
     */
    public function edit(Proposal $proposal): Response
    {
        Gate::authorize('update', $proposal);

        return Inertia::render('proposals/Form', [
            'proposal' => ProposalResource::make($proposal->load(['client', 'items'])),
            'clients' => ClientResource::collection(
                Client::query()
                    ->orderBy('name')
                    ->get(),
            ),
            'services' => ServiceResource::collection(
                Service::query()
                    ->orderBy('name')
                    ->get(),
            ),
        ]);
    }

    /**
     * Persist updates to an existing proposal.
     */
    public function update(
        UpdateProposalRequest $request,
        Proposal $proposal,
        UpdateProposal $updateProposal,
    ): RedirectResponse {
        $updateProposal->execute($proposal, $request->validated());

        return to_route('proposals.index')
            ->with('success', __('ui.proposals.updated'));
    }

    /**
     * Mark a draft proposal as sent.
     */
    public function markAsSent(
        SendProposalRequest $request,
        Proposal $proposal,
        TransitionProposalAction $transitionProposal,
    ): RedirectResponse {
        $request->validated();

        $sentProposal = $transitionProposal->execute($proposal, Sent::class);

        dispatch(new GenerateProposalPdfJob($sentProposal->id));
        $sentProposal->loadMissing(['client', 'items', 'tenant']);

        Mail::to($sentProposal->client->email)
            ->queue(new ProposalSent($sentProposal));

        return to_route('proposals.index')
            ->with('success', __('ui.proposals.sent'));
    }

    /**
     * Delete an existing proposal.
     */
    public function destroy(
        DeleteProposalRequest $request,
        Proposal $proposal,
        DeleteProposal $deleteProposal,
    ): RedirectResponse {
        $deleteProposal->execute($proposal);

        return to_route('proposals.index')
            ->with('success', __('ui.proposals.deleted'));
    }
}
