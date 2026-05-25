<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Client;
use App\Models\Proposal;
use App\Models\Tenant;
use App\States\Proposal\Accepted;
use App\States\Proposal\Declined;
use App\States\Proposal\Draft;
use App\States\Proposal\Sent;
use App\States\Proposal\Viewed;
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
        $status = fake()->randomElement(['draft', 'sent', 'viewed', 'accepted', 'declined']);
        $createdAt = now()
            ->subDays(random_int(0, 365))
            ->subHours(random_int(0, 23))
            ->subMinutes(random_int(0, 59));

        return [
            'tenant_id' => Tenant::factory(),
            'client_id' => static fn (array $attributes): int => Client::factory()
                ->create(['tenant_id' => (int) $attributes['tenant_id']])
                ->id,
            'title' => fake()->sentence(4),
            'status' => match ($status) {
                'sent' => Sent::class,
                'viewed' => Viewed::class,
                'accepted' => Accepted::class,
                'declined' => Declined::class,
                default => Draft::class,
            },
            'valid_until' => $createdAt->copy()->addDays(30),
            'created_at' => $createdAt,
            'updated_at' => match ($status) {
                'sent' => $createdAt->copy()->addDays(1),
                'viewed' => $createdAt->copy()->addDays(2),
                'accepted', 'declined' => $createdAt->copy()->addDays(random_int(3, 10)),
                default => $createdAt->copy()->addHours(random_int(1, 12)),
            },
        ];
    }

    /**
     * Indicate that the proposal has been sent to a client.
     */
    public function sent(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => Sent::class,
        ]);
    }

    /**
     * Indicate that the proposal has been accepted.
     */
    public function accepted(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => Accepted::class,
        ]);
    }

    /**
     * Indicate that the proposal has been declined.
     */
    public function declined(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => Declined::class,
        ]);
    }
}
