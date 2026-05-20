<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents a tenant-owned customer record used when preparing commercial proposals.
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $name
 * @property string $email
 * @property string|null $tax_id
 * @property string|null $address
 */
#[Fillable([
    'tenant_id',
    'name',
    'email',
    'tax_id',
    'address',
])]
class Client extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<ClientFactory> */
    use HasFactory;

    /**
     * Get the tenant that owns the client.
     *
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
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
        ];
    }
}
