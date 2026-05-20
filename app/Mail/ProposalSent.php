<?php

declare(strict_types=1);

namespace App\Mail;

use App\Mail\Concerns\BuildsProposalMailContent;
use App\Models\Proposal;
use App\Services\PublicProposalUrlGenerator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

/**
 * Notifies a client that a sent proposal is ready for review.
 */
class ProposalSent extends Mailable implements ShouldQueue
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
            subject: __('ui.proposals.emails.sent.subject', [
                'title' => $this->proposal->title,
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
            markdown: 'mail.proposals.sent',
            with: [
                'proposal' => $this->proposal,
                'publicUrl' => $this->publicProposalUrl(),
                'total' => $this->formattedProposalTotal($this->proposal),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return list<Attachment>
     */
    public function attachments(): array
    {
        $path = $this->pdfAttachmentPath();

        if ($path === null) {
            return [];
        }

        return [
            Attachment::fromStorageDisk('proposals', $path)
                ->as("{$this->proposal->uuid}.pdf")
                ->withMime('application/pdf'),
        ];
    }

    /**
     * Build the signed public URL for this proposal.
     */
    private function publicProposalUrl(): string
    {
        return resolve(PublicProposalUrlGenerator::class)->show($this->proposal);
    }

    /**
     * Resolve the generated PDF path relative to the private proposals disk.
     */
    private function pdfAttachmentPath(): ?string
    {
        $disk = Storage::disk('proposals');
        $recordedPath = $this->proposal->pdf_path;
        $candidatePaths = array_filter([
            $recordedPath,
            is_string($recordedPath) ? basename($recordedPath) : null,
            "{$this->proposal->uuid}.pdf",
        ]);

        foreach (array_unique($candidatePaths) as $path) {
            if ($disk->exists($path)) {
                return $path;
            }
        }

        return null;
    }
}
