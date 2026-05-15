<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ServiceType;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ServiceCatalogUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_renders_only_services_from_the_active_tenant(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $userA = User::factory()->for($tenantA)->create();
        $serviceA = Service::factory()->for($tenantA)->create();
        Service::factory()->for($tenantB)->create();

        $this
            ->actingAs($userA)
            ->get('/services')
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('services/Index')
                ->has('services.data', 1)
                ->where('services.data.0.id', $serviceA->id));
    }

    public function test_user_can_store_service_with_price_in_cents(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->post('/services', [
                'name' => 'Proposal Workshop',
                'description' => 'Structured sales proposal planning.',
                'price' => 75_000,
                'type' => ServiceType::Fixed->value,
            ])
            ->assertRedirect('/services');

        $this->assertDatabaseHas('services', [
            'tenant_id' => $user->tenant_id,
            'name' => 'Proposal Workshop',
            'price' => 75_000,
            'type' => ServiceType::Fixed->value,
        ]);
    }

    public function test_user_cannot_open_edit_form_for_another_tenant_service(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $userA = User::factory()->for($tenantA)->create();
        $serviceB = Service::factory()->for($tenantB)->create();

        $this
            ->actingAs($userA)
            ->get("/services/{$serviceB->id}/edit")
            ->assertNotFound();
    }
}
