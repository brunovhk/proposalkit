<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Proposal;
use Illuminate\Support\Facades\URL;
use LogicException;

/**
 * Generates HMAC-signed public URLs for proposal viewing and response actions.
 */
class PublicProposalUrlGenerator
{
    /**
     * Generate the signed public viewing URL for a proposal.
     */
    public function show(Proposal $proposal): string
    {
        return $this->signedRoute('public.proposals.show', $proposal);
    }

    /**
     * Generate the signed public acceptance URL for a proposal.
     */
    public function accept(Proposal $proposal): string
    {
        return $this->signedRoute('public.proposals.accept', $proposal);
    }

    /**
     * Generate the signed public rejection URL for a proposal.
     */
    public function reject(Proposal $proposal): string
    {
        return $this->signedRoute('public.proposals.reject', $proposal);
    }

    /**
     * Generate the signed public PDF download URL for a proposal.
     */
    public function pdf(Proposal $proposal): string
    {
        return $this->signedRoute('public.proposals.pdf', $proposal);
    }

    /**
     * Generate a signed route whose expiration follows the proposal validity date.
     */
    private function signedRoute(string $routeName, Proposal $proposal): string
    {
        $proposal->loadMissing('tenant');

        if (! is_string($proposal->tenant->slug) || $proposal->tenant->slug === '') {
            throw new LogicException('Cannot generate a public proposal URL without a tenant slug.');
        }

        if (! is_string($proposal->secure_token) || $proposal->secure_token === '') {
            throw new LogicException('Cannot generate a public proposal URL without a secure token.');
        }

        $parameters = [
            'tenant_slug' => $proposal->tenant->slug,
            'secure_token' => $proposal->secure_token,
        ];

        if ($proposal->valid_until !== null) {
            return URL::temporarySignedRoute(
                $routeName,
                $proposal->valid_until->copy()->endOfDay(),
                $parameters,
            );
        }

        return URL::signedRoute($routeName, $parameters);
    }
}
