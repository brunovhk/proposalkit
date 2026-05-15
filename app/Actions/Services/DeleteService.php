<?php

declare(strict_types=1);

namespace App\Actions\Services;

use App\Models\Service;

/**
 * Deletes a tenant-scoped service catalog item after policy authorization.
 */
class DeleteService
{
    public function execute(Service $service): void
    {
        $service->delete();
    }
}
