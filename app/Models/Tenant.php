<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Represents a company workspace that owns users and tenant-scoped records.
 *
 * @property int $id
 * @property string $name
 * @property string|null $slug
 */
#[Fillable(['name', 'slug'])]
class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory;

    /**
     * Register model event hooks.
     */
    #[\Override]
    protected static function booted(): void
    {
        static::creating(static function (Tenant $tenant): void {
            $slug = $tenant->getAttribute('slug');

            if (is_string($slug) && $slug !== '') {
                return;
            }

            $tenant->setAttribute('slug', self::uniqueSlug((string) $tenant->getAttribute('name')));
        });
    }

    /**
     * Get the users that belong to the tenant.
     *
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Build a tenant slug that remains stable for public proposal URLs.
     */
    private static function uniqueSlug(string $name): string
    {
        $baseSlug = Str::slug($name);

        if ($baseSlug === '') {
            $baseSlug = 'tenant';
        }

        $slug = $baseSlug;
        $suffix = 2;

        while (static::query()->where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
