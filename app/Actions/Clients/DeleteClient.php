<?php

declare(strict_types=1);

namespace App\Actions\Clients;

use App\Models\Client;

/**
 * Deletes a tenant-scoped client record after policy authorization.
 */
class DeleteClient
{
    public function execute(Client $client): void
    {
        $client->delete();
    }
}
