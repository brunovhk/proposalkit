<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ServiceType;
use App\Models\Proposal;
use App\Models\ProposalItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProposalItem>
 */
class ProposalItemFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<ProposalItem>
     */
    protected $model = ProposalItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'proposal_id' => Proposal::factory(),
            'name' => fake()->words(3, true),
            'price' => fake()->numberBetween(5_000, 250_000),
            'type' => fake()->randomElement(ServiceType::cases()),
            'quantity' => fake()->numberBetween(1, 5),
        ];
    }
}
