<?php

declare(strict_types=1);

namespace App\Http\Requests\PublicProposals;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates optional rejection details submitted by a public proposal recipient.
 */
class RejectPublicProposalRequest extends FormRequest
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
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
