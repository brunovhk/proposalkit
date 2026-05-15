<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ServiceType;
use App\Models\Service;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Service>
     */
    protected $model = Service::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->words(3, true),
            'description' => fake()->paragraph(),
            'price' => fake()->numberBetween(5_000, 250_000),
            'type' => fake()->randomElement(ServiceType::cases()),
        ];
    }

    /**
     * Indicate that the service is billed hourly.
     */
    public function hourly(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => ServiceType::Hourly,
        ]);
    }

    /**
     * Indicate that the service has a fixed price.
     */
    public function fixed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => ServiceType::Fixed,
        ]);
    }
}
