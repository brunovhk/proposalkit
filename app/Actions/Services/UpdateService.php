<?php

declare(strict_types=1);

namespace App\Actions\Services;

use App\Models\Service;

/**
 * Updates a tenant-scoped service catalog item from validated request data.
 */
class UpdateService
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(Service $service, array $attributes): Service
    {
        $service->update($attributes);

        return $service;
    }
}
