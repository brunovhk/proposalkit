<?php

declare(strict_types=1);

namespace App\Actions\Proposals;

use App\Models\Proposal;
use Illuminate\Support\Facades\DB;

/**
 * Updates proposal metadata and replaces item snapshots when item data is provided.
 */
class UpdateProposal
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(Proposal $proposal, array $attributes): Proposal
    {
        return DB::transaction(static function () use ($proposal, $attributes): Proposal {
            $items = $attributes['items'] ?? null;
            unset($attributes['items']);

            $proposal->update($attributes);

            if (is_array($items)) {
                $proposal->items()->delete();

                if ($items !== []) {
                    $proposal->items()->createMany($items);
                }
            }

            return $proposal;
        });
    }
}
