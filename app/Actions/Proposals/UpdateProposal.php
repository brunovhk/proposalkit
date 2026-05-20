<?php

declare(strict_types=1);

namespace App\Actions\Proposals;

use App\Actions\Concerns\ResolvesAuthenticatedActor;
use App\Models\Proposal;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

/**
 * Updates proposal metadata and replaces item snapshots when item data is provided.
 */
class UpdateProposal
{
    use ResolvesAuthenticatedActor;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(Proposal $proposal, array $attributes): Proposal
    {
        return DB::transaction(function () use ($proposal, $attributes): Proposal {
            $items = $attributes['items'] ?? null;
            unset($attributes['items']);

            $proposal->update($attributes);

            if (is_array($items)) {
                $proposal->items()->delete();

                if ($items !== []) {
                    $proposal->items()->createMany($items);
                }
            }

            $proposal->events()->create([
                'type' => 'updated',
                'payload' => [
                    'changed' => array_keys($attributes),
                    'items_replaced' => is_array($items),
                    'item_count' => is_array($items) ? count($items) : null,
                ],
                ...$this->authenticatedActor(),
                'occurred_at' => Date::now(),
            ]);

            return $proposal;
        });
    }
}
