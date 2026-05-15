<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Scopes\TenantScope;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Marks an Eloquent model as tenant-owned and keeps its tenant key in sync on creation.
 *
 * @mixin Model
 */
trait BelongsToTenant
{
    /**
     * Register tenant scoping and automatic tenant assignment for the model.
     */
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(static function (Model $model): void {
            if ($model->getAttribute('tenant_id') !== null) {
                return;
            }

            $guard = Auth::guard();

            if (! $guard->hasUser()) {
                return;
            }

            $user = $guard->user();

            if (! $user instanceof User) {
                return;
            }

            $model->setAttribute('tenant_id', $user->tenant_id);
        });
    }
}
