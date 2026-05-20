<?php

declare(strict_types=1);

namespace App\Http\Requests\PublicProposals;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates typed-name evidence submitted by a public proposal recipient.
 */
class AcceptPublicProposalRequest extends FormRequest
{
    /**
     * Public signed URLs authorize access through their HMAC signature.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'typed_name' => ['required', 'string', 'max:255'],
            'acceptance_confirmed' => ['accepted'],
        ];
    }
}
