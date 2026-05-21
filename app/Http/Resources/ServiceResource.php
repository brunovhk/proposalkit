<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Number;

/**
 * Transforms service catalog items into the shape consumed by the Inertia frontend.
 *
 * @mixin Service
 */
class ServiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function toArray(Request $request): array
    {
        $service = $this->resource;
        assert($service instanceof Service);

        return [
            'id' => $service->id,
            'name' => $service->name,
            'description' => $service->description,
            'price' => [
                'cents' => $service->price,
                'formatted' => Number::currency($service->price / 100, 'USD', app()->getLocale()),
            ],
            'type' => [
                'value' => $service->type->value,
                'label' => __("ui.services.types.{$service->type->value}"),
            ],
            'urls' => [
                'edit' => route('services.edit', $service),
            ],
        ];
    }
}
