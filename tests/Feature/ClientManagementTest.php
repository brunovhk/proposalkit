<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class ClientManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/__tests/clients', function (): JsonResponse {
            Gate::authorize('viewAny', Client::class);

            return response()->json([
                'client_ids' => $this->getVisibleClientIds(),
            ]);
        });

        Route::get('/__tests/clients/{client}', function (Client $client): Response {
            Gate::authorize('view', $client);

            return response()->noContent();
        });

        Route::put('/__tests/clients/{client}', function (Client $client): Response {
            Gate::authorize('update', $client);

            $client->update(['name' => 'Updated Client']);

            return response()->noContent();
        });

        Route::delete('/__tests/clients/{client}', function (Client $client): Response {
            Gate::authorize('delete', $client);

            $client->delete();

            return response()->noContent();
        });
    }

    public function test_user_can_create_client_assigned_to_their_tenant(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $client = Client::query()->create([
            'name' => 'Acme Example Ltda',
            'email' => 'finance@example.test',
            'tax_id' => '12.345.678/0001-90',
            'address' => 'Av. Example, 1000',
        ]);

        $this->assertSame($user->tenant_id, $client->tenant_id);
        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'tenant_id' => $user->tenant_id,
            'name' => 'Acme Example Ltda',
            'email' => 'finance@example.test',
            'tax_id' => '12.345.678/0001-90',
        ]);
    }

    public function test_index_only_returns_clients_from_the_active_tenant(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $userA = User::factory()->for($tenantA)->create();
        $clientA = Client::factory()->for($tenantA)->create();
        $clientB = Client::factory()->for($tenantB)->create();

        $this->actingAs($userA);

        $this
            ->get('/__tests/clients')
            ->assertOk()
            ->assertJsonPath('client_ids', [$clientA->id]);

        $this->assertNotContains($clientB->id, $this->getVisibleClientIds());
        $this->assertTrue(Gate::forUser($userA)->allows('view', $clientA));
    }

    public function test_user_cannot_view_update_or_delete_client_from_another_tenant(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $userA = User::factory()->for($tenantA)->create();
        $clientB = Client::factory()->for($tenantB)->create([
            'name' => 'Protected Client',
        ]);

        $this->assertFalse(Gate::forUser($userA)->allows('view', $clientB));
        $this->assertFalse(Gate::forUser($userA)->allows('update', $clientB));
        $this->assertFalse(Gate::forUser($userA)->allows('delete', $clientB));

        $this->actingAs($userA);

        $this->get("/__tests/clients/{$clientB->id}")->assertForbidden();
        $this->put("/__tests/clients/{$clientB->id}", [
            'name' => 'Compromised Client',
        ])->assertForbidden();
        $this->delete("/__tests/clients/{$clientB->id}")->assertForbidden();

        $this->assertDatabaseHas('clients', [
            'id' => $clientB->id,
            'name' => 'Protected Client',
        ]);
    }

    /**
     * Get the client IDs visible to the currently authenticated user.
     *
     * @return list<int>
     */
    private function getVisibleClientIds(): array
    {
        return array_values(array_map(
            static fn (mixed $id): int => (int) $id,
            Client::query()
                ->orderBy('id')
                ->pluck('id')
                ->all(),
        ));
    }
}
