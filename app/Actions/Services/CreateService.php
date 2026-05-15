<?php

declare(strict_types=1);

namespace App\Actions\Services;

use App\Models\Service;

/**
 * Creates a tenant-scoped service catalog item from validated request data.
 */
class CreateService
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(array $attributes): Service
    {
        return Service::query()->create($attributes);
    }
}
