<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ServiceType;
use App\Models\Concerns\BelongsToTenant;
use Database\Factories\ServiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents a tenant-owned catalog item that can be priced hourly or as a fixed fee.
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $name
 * @property string|null $description
 * @property int $price
 * @property ServiceType $type
 */
#[Fillable([
    'tenant_id',
    'name',
    'description',
    'price',
    'type',
])]
class Service extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<ServiceFactory> */
    use HasFactory;

    /**
     * Get the tenant that owns the service.
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
            'price' => 'integer',
            'type' => ServiceType::class,
        ];
    }
}
