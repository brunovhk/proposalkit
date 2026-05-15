<?php

declare(strict_types=1);

namespace App\Actions\Clients;

use App\Models\Client;

/**
 * Updates a tenant-scoped client record from validated request data.
 */
class UpdateClient
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(Client $client, array $attributes): Client
    {
        $client->update($attributes);

        return $client;
    }
}
