<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProposalStatus;
use App\Models\Concerns\BelongsToTenant;
use Database\Factories\ProposalFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Represents a tenant-owned commercial proposal prepared for a client.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $client_id
 * @property string $uuid
 * @property string $title
 * @property ProposalStatus $status
 * @property Carbon|null $valid_until
 * @property string|null $pdf_path
 * @property-read Client $client
 * @property-read Collection<int, ProposalItem> $items
 */
class Proposal extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<ProposalFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'client_id',
        'uuid',
        'title',
        'status',
        'valid_until',
        'pdf_path',
    ];

    /**
     * Register model event hooks.
     */
    protected static function booted(): void
    {
        static::creating(static function (Proposal $proposal): void {
            $uuid = $proposal->getAttribute('uuid');

            if (is_string($uuid) && $uuid !== '') {
                return;
            }

            $proposal->setAttribute('uuid', (string) Str::uuid());
        });
    }

    /**
     * Get the client this proposal was prepared for.
     *
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
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
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tenant_id' => 'integer',
            'client_id' => 'integer',
            'status' => ProposalStatus::class,
            'valid_until' => 'date',
        ];
    }
}
