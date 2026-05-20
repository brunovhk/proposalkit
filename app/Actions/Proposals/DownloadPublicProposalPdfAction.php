<?php

declare(strict_types=1);

namespace App\Actions\Proposals;

use App\Models\Proposal;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams a generated proposal PDF from private storage for a valid signed public request.
 */
class DownloadPublicProposalPdfAction
{
    /**
     * Stream the generated PDF or return a not-found response when it has not been created yet.
     */
    public function execute(Proposal $proposal): StreamedResponse
    {
        $path = $this->resolvePdfPath($proposal);

        if ($path === null) {
            abort(404);
        }

        return Storage::disk('proposals')->response(
            $path,
            "{$proposal->uuid}.pdf",
            ['Content-Type' => 'application/pdf'],
        );
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
}
