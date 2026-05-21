<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Proposal;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

/**
 * Renders and stores the immutable PDF snapshot for a sent proposal.
 */
class GenerateProposalPdfJob implements ShouldQueue
{
    use Queueable;

    /**
     * @var positive-int
     */
    public int $tries = 3;

    /**
     * @var list<int>
     */
    public array $backoff = [10, 30, 90];

    private readonly int $proposalId;

    /**
     * Create a new job instance.
     */
    public function __construct(Proposal|int $proposal)
    {
        $this->proposalId = $proposal instanceof Proposal
            ? (int) $proposal->getKey()
            : $proposal;
    }

    /**
     * Generate the PDF and record its private storage path.
     */
    public function handle(): void
    {
        $proposal = Proposal::query()
            ->with(['tenant', 'client', 'items'])
            ->findOrFail($this->proposalId);

        $fileName = "{$proposal->uuid}.pdf";

        $pdfContents = Pdf::loadView('proposals.pdf', [
            'proposal' => $proposal,
        ])
            ->setPaper('a4')
            ->output();

        Storage::disk('proposals')->put($fileName, $pdfContents);

        $proposal->forceFill([
            'pdf_path' => "proposals/{$fileName}",
        ])->save();
    }
}
