<?php

declare(strict_types=1);

namespace App\Http\Requests\Proposals;

use App\Models\Proposal;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Authorizes deletion of a tenant-owned proposal.
 */
class DeleteProposalRequest extends FormRequest
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
            && $user->can('delete', $proposal);
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
