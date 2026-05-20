<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Proposal;
use App\Models\ProposalItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;

/**
 * Transforms a proposal for unauthenticated recipients without exposing internal identifiers.
 *
 * @mixin Proposal
 */
class PublicProposalResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function toArray(Request $request): array
    {
        $proposal = $this->resource;
        assert($proposal instanceof Proposal);

        $totalCents = $this->calculateTotalCents($proposal);

        return [
            'title' => $proposal->title,
            'status' => [
                'value' => $proposal->status->value,
                'label' => __("ui.proposals.statuses.{$proposal->status->value}"),
            ],
            'valid_until' => [
                'value' => $proposal->valid_until?->toDateString(),
                'formatted' => $proposal->valid_until?->format('M j, Y'),
            ],
            'total' => [
                'cents' => $totalCents,
                'formatted' => Number::currency($totalCents / 100, 'USD', app()->getLocale()),
            ],
            'client' => [
                'name' => $proposal->client->name,
                'email' => $proposal->client->email,
                'tax_id' => $proposal->client->tax_id,
                'address' => $proposal->client->address,
            ],
            'items' => $proposal->items->map(static fn (ProposalItem $item): array => [
                'id' => $item->id,
                'name' => $item->name,
                'price' => [
                    'cents' => $item->price,
                    'formatted' => Number::currency($item->price / 100, 'USD', app()->getLocale()),
                ],
                'type' => [
                    'value' => $item->type->value,
                    'label' => __("ui.services.types.{$item->type->value}"),
                ],
                'quantity' => $item->quantity,
                'line_total' => [
                    'cents' => $item->price * $item->quantity,
                    'formatted' => Number::currency(
                        ($item->price * $item->quantity) / 100,
                        'USD',
                        app()->getLocale(),
                    ),
                ],
            ])->all(),
        ];
    }

    /**
     * Calculate the current proposal total from item snapshots.
     */
    private function calculateTotalCents(Proposal $proposal): int
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
