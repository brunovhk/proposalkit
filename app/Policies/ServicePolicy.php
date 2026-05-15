<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Service;
use App\Models\User;

/**
 * Authorizes access to tenant-owned service catalog records.
 */
class ServicePolicy
{
    /**
     * Determine whether the user can view service listings.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the service.
     */
    public function view(User $user, Service $service): bool
    {
        return $this->belongsToUsersTenant($user, $service);
    }

    /**
     * Determine whether the user can create services.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the service.
     */
    public function update(User $user, Service $service): bool
    {
        return $this->belongsToUsersTenant($user, $service);
    }

    /**
     * Determine whether the user can delete the service.
     */
    public function delete(User $user, Service $service): bool
    {
        return $this->belongsToUsersTenant($user, $service);
    }

    /**
     * Determine whether the service belongs to the user's tenant.
     */
    private function belongsToUsersTenant(User $user, Service $service): bool
    {
        return $service->tenant_id === $user->tenant_id;
    }
}
