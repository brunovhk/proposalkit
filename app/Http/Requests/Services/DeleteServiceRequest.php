<?php

declare(strict_types=1);

namespace App\Http\Requests\Services;

use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Authorizes deletion of a tenant-owned service catalog item.
 */
class DeleteServiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $service = $this->route('service');

        return $user instanceof User
            && $service instanceof Service
            && $user->can('delete', $service);
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
