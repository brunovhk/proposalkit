<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ProposalItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Number;

/**
 * Transforms proposal item snapshots into the shape consumed by the Inertia frontend.
 *
 * @mixin ProposalItem
 */
class ProposalItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function toArray(Request $request): array
    {
        $item = $this->resource;
        assert($item instanceof ProposalItem);

        $lineTotal = $item->price * $item->quantity;

        return [
            'id' => $item->id,
            'proposal_id' => $item->proposal_id,
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
                'cents' => $lineTotal,
                'formatted' => Number::currency($lineTotal / 100, 'USD', app()->getLocale()),
            ],
        ];
    }
}
