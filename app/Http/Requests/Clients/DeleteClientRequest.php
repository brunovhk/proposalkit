<?php

declare(strict_types=1);

namespace App\Http\Requests\Clients;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Authorizes deletion of a tenant-owned client record.
 */
class DeleteClientRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $client = $this->route('client');

        return $user instanceof User
            && $client instanceof Client
            && $user->can('delete', $client);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [];
    }
}
