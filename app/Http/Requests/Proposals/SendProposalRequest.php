<?php

declare(strict_types=1);

namespace App\Http\Requests\Proposals;

use App\Models\Proposal;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Authorizes the transition that sends a draft proposal to the client.
 */
class SendProposalRequest extends FormRequest
{
    /**
     * Determine whether the current user can send the proposal.
     */
    public function authorize(): bool
    {
        $proposal = $this->route('proposal');

        return $proposal instanceof Proposal
            && $this->user()?->can('send', $proposal) === true;
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
