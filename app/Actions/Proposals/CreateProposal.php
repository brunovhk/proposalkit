<?php

declare(strict_types=1);

namespace App\Actions\Proposals;

use App\Actions\Concerns\ResolvesAuthenticatedActor;
use App\Models\Proposal;
use App\States\Proposal\Draft;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

/**
 * Creates a tenant-scoped proposal and optional immutable item snapshots from validated data.
 */
class CreateProposal
{
    use ResolvesAuthenticatedActor;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(array $attributes): Proposal
    {
        return DB::transaction(function () use ($attributes): Proposal {
            $items = $attributes['items'] ?? [];
            unset($attributes['items']);

            $proposal = Proposal::query()->create([
                ...$attributes,
                'status' => Draft::class,
            ]);

            if (is_array($items) && $items !== []) {
                $proposal->items()->createMany($items);
            }

            $proposal->events()->create([
                'type' => 'created',
                'payload' => [
                    'title' => $proposal->title,
                    'client_id' => $proposal->client_id,
                    'item_count' => is_array($items) ? count($items) : 0,
                ],
                ...$this->authenticatedActor(),
                'occurred_at' => Date::now(),
            ]);

            return $proposal;
        });
    }
}
