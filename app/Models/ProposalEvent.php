<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * Stores immutable audit and analytics events for proposal lifecycle activity.
 *
 * @property int $id
 * @property int $proposal_id
 * @property string $type
 * @property array<string, mixed>|null $payload
 * @property string|null $actor_type
 * @property int|null $actor_id
 * @property string|null $ip
 * @property string|null $user_agent
 * @property Carbon $occurred_at
 * @property-read Model|null $actor
 * @property-read Proposal $proposal
 */
#[Fillable([
    'proposal_id',
    'type',
    'payload',
    'actor_type',
    'actor_id',
    'ip',
    'user_agent',
    'occurred_at',
])]
#[WithoutTimestamps]
class ProposalEvent extends Model
{
    /**
     * Get the proposal this event belongs to.
     *
     * @return BelongsTo<Proposal, $this>
     */
    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    /**
     * Get the authenticated model that caused this event when one exists.
     *
     * @return MorphTo<Model, $this>
     */
    public function actor(): MorphTo
    {
        return $this->morphTo();
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
            'actor_id' => 'integer',
            'payload' => 'array',
            'occurred_at' => 'datetime',
        ];
    }
}
