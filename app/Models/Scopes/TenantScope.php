<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * Applies the authenticated user's tenant boundary to tenant-owned Eloquent queries.
 *
 * @implements Scope<Model>
 */
final class TenantScope implements Scope
{
    /**
     * Apply the tenant constraint to the given query builder.
     *
     * @param  Builder<covariant Model>  $builder
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
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

        $builder->where($model->qualifyColumn('tenant_id'), $user->tenant_id);
    }
}
