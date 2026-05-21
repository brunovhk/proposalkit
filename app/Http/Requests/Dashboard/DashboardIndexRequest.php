<?php

declare(strict_types=1);

namespace App\Http\Requests\Dashboard;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates and normalizes dashboard analytics date filters.
 */
final class DashboardIndexRequest extends FormRequest
{
    /**
     * Every authenticated tenant user may view their own dashboard metrics.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
        ];
    }

    public function startDate(): ?CarbonImmutable
    {
        $value = $this->validated('start_date');

        if (! is_string($value) || $value === '') {
            return null;
        }

        return CarbonImmutable::parse($value)->startOfDay();
    }

    public function endDate(): ?CarbonImmutable
    {
        $value = $this->validated('end_date');

        if (! is_string($value) || $value === '') {
            return null;
        }

        return CarbonImmutable::parse($value)->endOfDay();
    }
}
