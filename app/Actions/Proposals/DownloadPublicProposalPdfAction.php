<?php

declare(strict_types=1);

namespace App\Actions\Proposals;

use App\Models\Proposal;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

/**
 * Streams a generated proposal PDF from private storage for a valid signed public request.
 */
class DownloadPublicProposalPdfAction
{
    /**
     * Stream the generated PDF or return a not-found response when it has not been created yet.
     */
    public function execute(Proposal $proposal): Response
    {
        $path = $this->resolvePdfPath($proposal);
        $filename = "{$proposal->uuid}.pdf";

        if ($path === null) {
            if (config('app.live_demo_enabled') === true) {
                return $this->inlinePdfResponse(
                    $this->generatePdfContents($proposal),
                    $filename,
                );
            }

            abort(404);
        }

        $pdfContents = Storage::disk('proposals')->get($path);

        return $this->inlinePdfResponse((string) $pdfContents, $filename);
    }

    /**
     * Resolve legacy and current PDF path formats on the private proposals disk.
     */
    private function resolvePdfPath(Proposal $proposal): ?string
    {
        $disk = Storage::disk('proposals');
        $recordedPath = $proposal->pdf_path;
        $candidatePaths = array_filter([
            $recordedPath,
            is_string($recordedPath) ? basename($recordedPath) : null,
            "{$proposal->uuid}.pdf",
        ]);

        foreach (array_unique($candidatePaths) as $path) {
            if ($disk->exists($path)) {
                return $path;
            }
        }

        return null;
    }

    private function generatePdfContents(Proposal $proposal): string
    {
        $proposal->loadMissing(['tenant', 'client', 'items']);

        return Pdf::loadView('proposals.pdf', [
            'proposal' => $proposal,
        ])
            ->setPaper('a4')
            ->output();
    }

    private function inlinePdfResponse(string $pdfContents, string $filename): Response
    {
        return response($pdfContents, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('inline; filename="%s"', $filename),
        ]);
    }
}
