<?php

declare(strict_types=1);

namespace App\Actions\Clients;

use App\Models\Client;

/**
 * Creates a tenant-scoped client record from validated request data.
 */
class CreateClient
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(array $attributes): Client
    {
        return Client::query()->create($attributes);
    }
}
