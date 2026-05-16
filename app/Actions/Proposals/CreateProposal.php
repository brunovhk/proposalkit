<?php

declare(strict_types=1);

namespace App\Actions\Proposals;

use App\Enums\ProposalStatus;
use App\Models\Proposal;
use Illuminate\Support\Facades\DB;

/**
 * Creates a tenant-scoped proposal and optional immutable item snapshots from validated data.
 */
class CreateProposal
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(array $attributes): Proposal
    {
        return DB::transaction(static function () use ($attributes): Proposal {
            $items = $attributes['items'] ?? [];
            unset($attributes['items']);

            $proposal = Proposal::query()->create([
                ...$attributes,
                'status' => ProposalStatus::Draft,
            ]);

            if (is_array($items) && $items !== []) {
                $proposal->items()->createMany($items);
            }

            return $proposal;
        });
    }
}
