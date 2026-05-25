<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Proposals\DownloadPublicProposalPdfAction;
use App\Actions\Proposals\RecordProposalPublicViewAction;
use App\Actions\Proposals\ResolvePublicProposalAction;
use App\Actions\Proposals\RespondToPublicProposalAction;
use App\Http\Requests\PublicProposals\AcceptPublicProposalRequest;
use App\Http\Requests\PublicProposals\RejectPublicProposalRequest;
use App\Http\Resources\PublicProposalResource;
use App\Models\Proposal;
use App\Services\PublicProposalUrlGenerator;
use App\States\Proposal\Accepted;
use App\States\Proposal\Declined;
use App\States\Proposal\Draft;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Renders and processes signed public proposal access by tenant slug and secure token.
 */
class PublicProposalController extends Controller
{
    /**
     * Display the public proposal view.
     */
    public function show(
        string $tenant_slug,
        string $secure_token,
        Request $request,
        ResolvePublicProposalAction $resolvePublicProposal,
        RecordProposalPublicViewAction $recordProposalPublicView,
        PublicProposalUrlGenerator $urlGenerator,
    ): Response {
        $proposal = $resolvePublicProposal->execute($tenant_slug, $secure_token);

        $this->abortUnlessPubliclyOpen($proposal);

        $proposal = $recordProposalPublicView->execute($proposal, $request);

        return Inertia::render('proposals/PublicView', [
            'proposal' => PublicProposalResource::make($proposal),
            'acceptUrl' => $urlGenerator->accept($proposal),
            'pdfUrl' => $urlGenerator->pdf($proposal),
            'rejectUrl' => $urlGenerator->reject($proposal),
        ]);
    }

    /**
     * Stream the generated PDF for a valid signed public proposal URL.
     */
    public function pdf(
        string $tenant_slug,
        string $secure_token,
        ResolvePublicProposalAction $resolvePublicProposal,
        DownloadPublicProposalPdfAction $downloadPublicProposalPdf,
    ): SymfonyResponse {
        $proposal = $resolvePublicProposal->execute($tenant_slug, $secure_token);

        if ($proposal->status->equals(Draft::class)) {
            abort(404);
        }

        return $downloadPublicProposalPdf->execute($proposal);
    }

    /**
     * Accept the public proposal with typed-name evidence.
     */
    public function accept(
        string $tenant_slug,
        string $secure_token,
        AcceptPublicProposalRequest $request,
        ResolvePublicProposalAction $resolvePublicProposal,
        RespondToPublicProposalAction $respondToPublicProposal,
    ): RedirectResponse {
        $proposal = $resolvePublicProposal->execute($tenant_slug, $secure_token);

        $this->abortUnlessMutable($proposal);

        $respondToPublicProposal->accept($proposal, [
            'typed_name' => (string) $request->validated('typed_name'),
            'acceptance_confirmed' => true,
        ], $request);

        return to_route('home')
            ->with('success', __('ui.proposals.public.accepted_message'));
    }

    /**
     * Reject the public proposal with optional recipient context.
     */
    public function reject(
        string $tenant_slug,
        string $secure_token,
        RejectPublicProposalRequest $request,
        ResolvePublicProposalAction $resolvePublicProposal,
        RespondToPublicProposalAction $respondToPublicProposal,
    ): RedirectResponse {
        $proposal = $resolvePublicProposal->execute($tenant_slug, $secure_token);

        $this->abortUnlessMutable($proposal);

        $reason = $request->validated('reason');

        $respondToPublicProposal->reject(
            $proposal,
            is_string($reason) ? $reason : null,
            $request,
        );

        return to_route('home')
            ->with('success', __('ui.proposals.public.declined_message'));
    }

    /**
     * Stop public viewing of drafts and already-finalized proposals.
     */
    private function abortUnlessPubliclyOpen(Proposal $proposal): void
    {
        if ($proposal->status->equals(Draft::class)) {
            abort(404);
        }

        if ($proposal->status->equals(Accepted::class, Declined::class)) {
            abort(410);
        }
    }

    /**
     * Stop replay attempts against proposals that can no longer receive a response.
     */
    private function abortUnlessMutable(Proposal $proposal): void
    {
        if ($proposal->status->equals(Accepted::class, Declined::class)) {
            abort(410);
        }
    }
}
