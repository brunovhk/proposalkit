<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Client;
use App\Models\User;

/**
 * Authorizes access to tenant-owned client records.
 */
class ClientPolicy
{
    /**
     * Determine whether the user can view client listings.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the client.
     */
    public function view(User $user, Client $client): bool
    {
        return $this->belongsToUsersTenant($user, $client);
    }

    /**
     * Determine whether the user can create clients.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the client.
     */
    public function update(User $user, Client $client): bool
    {
        return $this->belongsToUsersTenant($user, $client);
    }

    /**
     * Determine whether the user can delete the client.
     */
    public function delete(User $user, Client $client): bool
    {
        return $this->belongsToUsersTenant($user, $client);
    }

    /**
     * Determine whether the client belongs to the user's tenant.
     */
    private function belongsToUsersTenant(User $user, Client $client): bool
    {
        return $client->tenant_id === $user->tenant_id;
    }
}
