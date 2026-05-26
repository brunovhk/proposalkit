<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ServiceType;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class ServiceCatalogTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/__tests/services/{service}', function (Service $service): Response {
            Gate::authorize('view', $service);

            return response()->noContent();
        });

        Route::put('/__tests/services/{service}', function (Service $service): Response {
            Gate::authorize('update', $service);

            $service->update(['name' => 'Updated Strategy Session']);

            return response()->noContent();
        });

        Route::delete('/__tests/services/{service}', function (Service $service): Response {
            Gate::authorize('delete', $service);

            $service->delete();

            return response()->noContent();
        });
    }

    public function test_user_can_create_service_assigned_to_their_tenant(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $service = Service::query()->create([
            'name' => 'Discovery Workshop',
            'description' => 'Initial commercial discovery and scope definition.',
            'price' => 15_000,
            'type' => ServiceType::Fixed,
        ]);

        $this->assertSame($user->tenant_id, $service->tenant_id);
        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'tenant_id' => $user->tenant_id,
            'name' => 'Discovery Workshop',
            'price' => 15_000,
            'type' => ServiceType::Fixed->value,
        ]);
    }

    public function test_user_can_view_their_own_tenant_services(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $userA = User::factory()->for($tenantA)->create();
        $serviceA = Service::factory()->for($tenantA)->create();
        $serviceB = Service::factory()->for($tenantB)->create();

        $this->actingAs($userA);

        $visibleServiceIds = $this->getVisibleServiceIds();

        $this->assertContains($serviceA->id, $visibleServiceIds);
        $this->assertNotContains($serviceB->id, $visibleServiceIds);
        $this->assertTrue(Gate::forUser($userA)->allows('view', $serviceA));
    }

    public function test_user_cannot_view_update_or_delete_service_from_another_tenant(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $userA = User::factory()->for($tenantA)->create();
        $serviceB = Service::factory()->for($tenantB)->create([
            'name' => 'Protected Service',
        ]);

        $this->assertFalse(Gate::forUser($userA)->allows('view', $serviceB));
        $this->assertFalse(Gate::forUser($userA)->allows('update', $serviceB));
        $this->assertFalse(Gate::forUser($userA)->allows('delete', $serviceB));

        $this->actingAs($userA);

        $this->get("/__tests/services/{$serviceB->id}")->assertForbidden();
        $this->put("/__tests/services/{$serviceB->id}", [
            'name' => 'Compromised Service',
        ])->assertForbidden();
        $this->delete("/__tests/services/{$serviceB->id}")->assertForbidden();

        $this->assertDatabaseHas('services', [
            'id' => $serviceB->id,
            'name' => 'Protected Service',
        ]);
    }

    /**
     * Get the service IDs visible to the currently authenticated user.
     *
     * @return list<int>
     */
    private function getVisibleServiceIds(): array
    {
        return array_values(array_map(
            static fn (mixed $id): int => (int) $id,
            Service::query()
                ->pluck('id')
                ->all(),
        ));
    }
}
