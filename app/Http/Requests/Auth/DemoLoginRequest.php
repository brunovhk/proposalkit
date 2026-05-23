<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Authorizes the public POST endpoint that starts a live demo session.
 */
final class DemoLoginRequest extends FormRequest
{
    /**
     * Determine if the visitor may start a demo session.
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
        return [];
    }
}
