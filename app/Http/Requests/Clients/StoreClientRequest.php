<?php

declare(strict_types=1);

namespace App\Http\Requests\Clients;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates and authorizes creation of a tenant-owned client record.
 */
class StoreClientRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User && $user->can('create', Client::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
