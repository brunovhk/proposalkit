<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Proposal;
use App\Models\ProposalEvent;
use App\Models\ProposalItem;
use App\Services\PublicProposalUrlGenerator;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;

/**
 * Transforms proposals into the management payload consumed by the Inertia frontend.
 *
 * @mixin Proposal
 */
class ProposalResource extends JsonResource
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
        $urlGenerator = resolve(PublicProposalUrlGenerator::class);

        return [
            'id' => $proposal->id,
            'uuid' => $proposal->uuid,
            'tenant_id' => $proposal->tenant_id,
            'client_id' => $proposal->client_id,
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
            'client' => ClientResource::make($this->whenLoaded('client')),
            'items' => ProposalItemResource::collection($this->whenLoaded('items')),
            'finalized_metadata' => $this->finalizedMetadata($proposal),
            'created_at' => $proposal->created_at?->toAtomString(),
            'updated_at' => $proposal->updated_at?->toAtomString(),
            'urls' => [
                'edit' => route('proposals.edit', $proposal),
                'destroy' => route('proposals.destroy', $proposal),
                'send' => route('proposals.send', $proposal),
                'public' => $urlGenerator->show($proposal),
            ],
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

    /**
     * Return lifecycle metadata displayed by the finalized proposal detail modal.
     *
     * @return array{accepted_by_name: string|null, refusal_reason: string|null}
     */
    private function finalizedMetadata(Proposal $proposal): array
    {
        /** @var Collection<int, ProposalEvent> $events */
        $events = $proposal->relationLoaded('events')
            ? $proposal->getRelation('events')
            : collect();

        $acceptedEvent = $events->first(
            static fn (ProposalEvent $event): bool => $event->type === 'accepted',
        );
        $rejectedEvent = $events->first(
            static fn (ProposalEvent $event): bool => $event->type === 'rejected',
        );

        return [
            'accepted_by_name' => $this->stringPayloadValue($acceptedEvent, 'typed_name'),
            'refusal_reason' => $this->stringPayloadValue($rejectedEvent, 'reason'),
        ];
    }

    private function stringPayloadValue(?ProposalEvent $event, string $key): ?string
    {
        $payload = $event?->payload;

        if (! is_array($payload)) {
            return null;
        }

        $value = $payload[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }
}
