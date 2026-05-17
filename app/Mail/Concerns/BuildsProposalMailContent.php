<?php

declare(strict_types=1);

namespace App\Mail\Concerns;

use App\Models\Proposal;
use App\Models\ProposalItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;

/**
 * Builds common display values used by proposal notification mailables.
 */
trait BuildsProposalMailContent
{
    /**
     * Return the current formatted total from immutable proposal item snapshots.
     */
    private function formattedProposalTotal(Proposal $proposal): string
    {
        $totalCents = $this->proposalTotalCents($proposal);
        $formatted = Number::currency($totalCents / 100, 'USD', app()->getLocale());

        return is_string($formatted)
            ? $formatted
            : number_format($totalCents / 100, 2);
    }

    /**
     * Calculate the current proposal total in cents.
     */
    private function proposalTotalCents(Proposal $proposal): int
    {
        /** @var Collection<int, ProposalItem> $items */
        $items = $proposal->relationLoaded('items')
            ? $proposal->items
            : $proposal->items()->get();

        return (int) $items->sum(
            static fn (ProposalItem $item): int => $item->price * $item->quantity,
        );
    }
}
