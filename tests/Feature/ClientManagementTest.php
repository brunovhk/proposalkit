<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Proposal;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class ClientManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

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

    public function test_deleting_a_client_soft_deletes_it_and_keeps_proposal_history(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->for($tenant)->create();
        $this->actingAs($user);

        $client = Client::factory()->for($tenant)->create(['name' => 'Archived Client']);
        $proposal = Proposal::factory()->for($tenant)->create(['client_id' => $client->id]);

        $this->delete("/clients/{$client->id}")->assertRedirect('/clients');

        $this->assertSoftDeleted('clients', ['id' => $client->id]);
        $this->assertNull(Client::query()->find($client->id));
        $this->assertSame('Archived Client', $proposal->refresh()->client->name);
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
