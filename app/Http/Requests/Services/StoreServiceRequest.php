<?php

declare(strict_types=1);

namespace App\Http\Requests\Services;

use App\Enums\ServiceType;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

/**
 * Validates and authorizes creation of a tenant-owned service catalog item.
 */
class StoreServiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User && $user->can('create', Service::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, list<Enum|string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'price' => ['required', 'integer', 'min:0'],
            'type' => ['required', new Enum(ServiceType::class)],
        ];
    }
}
