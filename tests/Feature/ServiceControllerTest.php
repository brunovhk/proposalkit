<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ServiceType;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ServiceControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_create_form_exposes_service_type_options(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->get('/services/create')
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('services/Form')
                ->where('service', null)
                ->has('serviceTypes', count(ServiceType::cases()))
                ->where('serviceTypes.0.value', ServiceType::Hourly->value)
                ->where('serviceTypes.1.value', ServiceType::Fixed->value));
    }

    public function test_edit_form_exposes_service_and_type_options(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->for($tenant)->create();
        $service = Service::factory()->for($tenant)->fixed()->create([
            'name' => 'Retainer Planning',
            'price' => 125_000,
        ]);

        $this
            ->actingAs($user)
            ->get("/services/{$service->id}/edit")
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('services/Form')
                ->where('service.data.id', $service->id)
                ->where('service.data.name', 'Retainer Planning')
                ->where('service.data.price.cents', 125_000)
                ->has('serviceTypes', count(ServiceType::cases())));
    }

    public function test_store_validation_rejects_invalid_service_payload(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->from('/services/create')
            ->post('/services', [
                'name' => '',
                'description' => str_repeat('a', 5001),
                'price' => -1,
                'type' => 'retainer',
            ])
            ->assertRedirect('/services/create')
            ->assertSessionHasErrors(['name', 'description', 'price', 'type']);

        $this->assertDatabaseCount('services', 0);
    }

    public function test_update_validation_rejects_invalid_service_payload(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->for($tenant)->create();
        $service = Service::factory()->for($tenant)->create([
            'name' => 'Original Service',
            'price' => 50_000,
            'type' => ServiceType::Fixed,
        ]);

        $this
            ->actingAs($user)
            ->from("/services/{$service->id}/edit")
            ->put("/services/{$service->id}", [
                'name' => '',
                'description' => str_repeat('b', 5001),
                'price' => '125.00',
                'type' => 'retainer',
            ])
            ->assertRedirect("/services/{$service->id}/edit")
            ->assertSessionHasErrors(['name', 'description', 'price', 'type']);

        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'name' => 'Original Service',
            'price' => 50_000,
            'type' => ServiceType::Fixed->value,
        ]);
    }

    public function test_user_can_create_update_and_delete_service_item(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->for($tenant)->create();

        $this
            ->actingAs($user)
            ->post('/services', [
                'name' => 'Implementation Sprint',
                'description' => 'Delivery support for proposal commitments.',
                'price' => 150_000,
                'type' => ServiceType::Fixed->value,
            ])
            ->assertRedirect('/services')
            ->assertSessionHas('success', __('ui.services.created'));

        $service = Service::query()
            ->where('name', 'Implementation Sprint')
            ->sole();

        $this->assertSame($tenant->id, $service->tenant_id);

        $this
            ->actingAs($user)
            ->put("/services/{$service->id}", [
                'name' => 'Implementation Sprint Updated',
                'description' => null,
                'price' => 175_000,
                'type' => ServiceType::Hourly->value,
            ])
            ->assertRedirect('/services')
            ->assertSessionHas('success', __('ui.services.updated'));

        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'tenant_id' => $tenant->id,
            'name' => 'Implementation Sprint Updated',
            'description' => null,
            'price' => 175_000,
            'type' => ServiceType::Hourly->value,
        ]);

        $this
            ->actingAs($user)
            ->delete("/services/{$service->id}")
            ->assertRedirect('/services')
            ->assertSessionHas('success', __('ui.services.deleted'));

        $this->assertDatabaseMissing('services', [
            'id' => $service->id,
        ]);
    }

    public function test_service_routes_block_foreign_tenant_parameters(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $userA = User::factory()->for($tenantA)->create();
        $serviceB = Service::factory()->for($tenantB)->create([
            'name' => 'Protected Foreign Service',
        ]);

        $this
            ->actingAs($userA)
            ->get("/services/{$serviceB->id}/edit")
            ->assertNotFound();

        $this
            ->actingAs($userA)
            ->put("/services/{$serviceB->id}", [
                'name' => 'Compromised Service',
                'description' => null,
                'price' => 1,
                'type' => ServiceType::Fixed->value,
            ])
            ->assertNotFound();

        $this
            ->actingAs($userA)
            ->delete("/services/{$serviceB->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('services', [
            'id' => $serviceB->id,
            'name' => 'Protected Foreign Service',
        ]);
    }
}
