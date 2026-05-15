<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Transforms client records into the shape consumed by the Inertia frontend.
 *
 * @mixin Client
 */
class ClientResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $client = $this->resource;
        assert($client instanceof Client);

        return [
            'id' => $client->id,
            'name' => $client->name,
            'email' => $client->email,
            'tax_id' => $client->tax_id,
            'address' => $client->address,
            'urls' => [
                'edit' => route('clients.edit', $client),
            ],
        ];
    }
}
