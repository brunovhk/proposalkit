<?php

declare(strict_types=1);

namespace App\Http\Requests\Proposals;

use App\Enums\ServiceType;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Exists;

/**
 * Validates and authorizes updates to a tenant-owned proposal.
 */
class UpdateProposalRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $proposal = $this->route('proposal');

        return $user instanceof User
            && $proposal instanceof Proposal
            && $user->can('update', $proposal);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, list<Enum|Exists|string>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'valid_until' => ['nullable', 'date'],
            'client_id' => ['required', 'integer', $this->tenantClientExistsRule()],
            'items' => ['sometimes', 'array'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.price' => ['required', 'integer', 'min:0'],
            'items.*.type' => ['required', new Enum(ServiceType::class)],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * Build the tenant-bound client existence rule.
     */
    private function tenantClientExistsRule(): Exists
    {
        $user = $this->user();
        assert($user instanceof User);

        return Rule::exists('clients', 'id')
            ->where('tenant_id', $user->tenant_id);
    }
}
