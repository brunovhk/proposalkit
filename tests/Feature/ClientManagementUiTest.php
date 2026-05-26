<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ClientManagementUiTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_index_renders_only_clients_from_the_active_tenant(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $userA = User::factory()->for($tenantA)->create();
        $clientA = Client::factory()->for($tenantA)->create();
        Client::factory()->for($tenantB)->create();

        $this
            ->actingAs($userA)
            ->get('/clients')
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('clients/Index')
                ->has('clients.data', 1)
                ->where('clients.data.0.id', $clientA->id));
    }

    public function test_user_can_store_client_with_success_feedback(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->post('/clients', [
                'name' => 'Acme Corporation',
                'email' => 'finance@example.test',
                'tax_id' => '12.345.678/0001-90',
                'address' => 'Av. Paulista, 1000',
            ])
            ->assertRedirect('/clients')
            ->assertSessionHas('success', __('ui.clients.created'));

        $this->assertDatabaseHas('clients', [
            'tenant_id' => $user->tenant_id,
            'name' => 'Acme Corporation',
            'email' => 'finance@example.test',
            'tax_id' => '12.345.678/0001-90',
        ]);
    }

    public function test_create_page_renders_client_form(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->get('/clients/create')
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('clients/ClientForm')
                ->where('client', null));
    }

    public function test_edit_page_renders_client_form_for_active_tenant_client(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->for($tenant)->create();
        $client = Client::factory()->for($tenant)->create();

        $this
            ->actingAs($user)
            ->get("/clients/{$client->id}/edit")
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('clients/ClientForm')
                ->where('client.data.id', $client->id));
    }

    public function test_user_can_update_client_with_success_feedback(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->for($tenant)->create();
        $client = Client::factory()->for($tenant)->create([
            'name' => 'Original Client',
        ]);

        $this
            ->actingAs($user)
            ->put("/clients/{$client->id}", [
                'name' => 'Updated Client',
                'email' => 'updated@example.test',
                'tax_id' => null,
                'address' => 'Updated address',
            ])
            ->assertRedirect('/clients')
            ->assertSessionHas('success', __('ui.clients.updated'));

        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'name' => 'Updated Client',
            'email' => 'updated@example.test',
            'address' => 'Updated address',
        ]);
    }

    public function test_user_can_delete_client_with_success_feedback(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->for($tenant)->create();
        $client = Client::factory()->for($tenant)->create();

        $this
            ->actingAs($user)
            ->delete("/clients/{$client->id}")
            ->assertRedirect('/clients')
            ->assertSessionHas('success', __('ui.clients.deleted'));

        $this->assertDatabaseMissing('clients', [
            'id' => $client->id,
        ]);
    }

    public function test_user_cannot_update_or_delete_client_from_another_tenant(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $userA = User::factory()->for($tenantA)->create();
        $clientB = Client::factory()->for($tenantB)->create([
            'name' => 'Protected Client',
        ]);

        $this
            ->actingAs($userA)
            ->put("/clients/{$clientB->id}", [
                'name' => 'Compromised Client',
                'email' => 'compromised@example.test',
                'tax_id' => null,
                'address' => null,
            ])
            ->assertNotFound();

        $this
            ->actingAs($userA)
            ->delete("/clients/{$clientB->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('clients', [
            'id' => $clientB->id,
            'name' => 'Protected Client',
        ]);
    }

    public function test_user_cannot_open_edit_form_for_another_tenant_client(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $userA = User::factory()->for($tenantA)->create();
        $clientB = Client::factory()->for($tenantB)->create();

        $this
            ->actingAs($userA)
            ->get("/clients/{$clientB->id}/edit")
            ->assertNotFound();
    }
}
