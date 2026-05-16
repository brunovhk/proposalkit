<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ProposalStatus;
use App\Models\Client;
use App\Models\Proposal;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Proposal>
 */
class ProposalFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Proposal>
     */
    protected $model = Proposal::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'client_id' => static fn (array $attributes): int => Client::factory()
                ->create(['tenant_id' => (int) $attributes['tenant_id']])
                ->id,
            'title' => fake()->sentence(4),
            'status' => ProposalStatus::Draft,
            'valid_until' => fake()->optional()->dateTimeBetween('now', '+60 days'),
        ];
    }

    /**
     * Indicate that the proposal has been sent to a client.
     */
    public function sent(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ProposalStatus::Sent,
        ]);
    }

    /**
     * Indicate that the proposal has been accepted.
     */
    public function accepted(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ProposalStatus::Accepted,
        ]);
    }

    /**
     * Indicate that the proposal has been declined.
     */
    public function declined(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ProposalStatus::Declined,
        ]);
    }
}
