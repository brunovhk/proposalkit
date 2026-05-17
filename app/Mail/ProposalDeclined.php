<?php

declare(strict_types=1);

namespace App\Mail;

use App\Mail\Concerns\BuildsProposalMailContent;
use App\Models\Proposal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Notifies the tenant owner that a client declined a proposal.
 */
class ProposalDeclined extends Mailable implements ShouldQueue
{
    use BuildsProposalMailContent;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(public Proposal $proposal) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('ui.proposals.emails.declined.subject', [
                'title' => $this->proposal->title,
                'client' => $this->proposal->client->name,
            ]),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $this->proposal->loadMissing(['client', 'items', 'tenant']);

        return new Content(
            markdown: 'mail.proposals.declined',
            with: [
                'proposal' => $this->proposal,
                'total' => $this->formattedProposalTotal($this->proposal),
            ],
        );
    }
}
