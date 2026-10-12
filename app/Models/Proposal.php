<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\States\Proposal\ProposalState;
use Database\Factories\ProposalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Spatie\ModelStates\HasStates;

/**
 * Represents a tenant-owned commercial proposal prepared for a client.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $client_id
 * @property string $uuid
 * @property string|null $secure_token
 * @property string $title
 * @property ProposalState $status
 * @property Carbon|null $valid_until
 * @property string|null $pdf_path
 * @property-read Client $client
 * @property-read Collection<int, ProposalEvent> $events
 * @property-read Collection<int, ProposalItem> $items
 * @property-read Tenant $tenant
 */
#[Fillable([
    'tenant_id',
    'client_id',
    'uuid',
    'secure_token',
    'title',
    'status',
    'valid_until',
    'pdf_path',
])]
class Proposal extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<ProposalFactory> */
    use HasFactory;

    use HasStates;

    /**
     * Register model event hooks.
     */
    #[\Override]
    protected static function booted(): void
    {
        static::creating(static function (Proposal $proposal): void {
            $uuid = $proposal->getAttribute('uuid');

            if (! is_string($uuid) || $uuid === '') {
                $proposal->setAttribute('uuid', (string) Str::uuid());
            }

            $secureToken = $proposal->getAttribute('secure_token');

            if (is_string($secureToken) && $secureToken !== '') {
                return;
            }

            $proposal->setAttribute('secure_token', (string) Str::uuid7());
        });
    }

    /**
     * Get the client this proposal was prepared for, including archived clients.
     *
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class)->withTrashed();
    }

    /**
     * Get the tenant that owns the proposal.
     *
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the immutable line-item snapshots attached to this proposal.
     *
     * @return HasMany<ProposalItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(ProposalItem::class);
    }

    /**
     * Get the immutable audit events recorded for this proposal.
     *
     * @return HasMany<ProposalEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(ProposalEvent::class);
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
            'tenant_id' => 'integer',
            'client_id' => 'integer',
            'status' => ProposalState::class,
            'valid_until' => 'date',
        ];
    }
}
