<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ServiceType;
use Database\Factories\ProposalItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Stores an immutable service snapshot captured when a proposal is assembled.
 *
 * @property int $id
 * @property int $proposal_id
 * @property string $name
 * @property int $price
 * @property ServiceType $type
 * @property int $quantity
 */
#[Fillable([
    'proposal_id',
    'name',
    'price',
    'type',
    'quantity',
])]
class ProposalItem extends Model
{
    /** @use HasFactory<ProposalItemFactory> */
    use HasFactory;

    /**
     * Get the proposal that owns this item snapshot.
     *
     * @return BelongsTo<Proposal, $this>
     */
    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    #[\Override]
    protected function casts(): array
    {
        return [
            'proposal_id' => 'integer',
            'price' => 'integer',
            'type' => ServiceType::class,
            'quantity' => 'integer',
        ];
    }
}
