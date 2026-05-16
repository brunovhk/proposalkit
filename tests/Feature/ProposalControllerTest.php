<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ProposalStatus;
use App\Enums\ServiceType;
use App\Models\Client;
use App\Models\Proposal;
use App\Models\ProposalItem;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProposalControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lists_only_proposals_from_the_active_tenant(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $userA = User::factory()->for($tenantA)->create();
        $clientA = Client::factory()->for($tenantA)->create();
        $proposalA = Proposal::factory()
            ->for($tenantA)
            ->for($clientA)
            ->create([
                'title' => 'Visible Proposal',
            ]);
        ProposalItem::factory()
            ->for($proposalA)
            ->create([
                'price' => 25_000,
                'quantity' => 2,
            ]);

        Proposal::factory()->for($tenantB)->create([
            'title' => 'Hidden Proposal',
        ]);

        $this
            ->actingAs($userA)
            ->get('/proposals')
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('proposals/Index')
                ->has('proposals.data', 1)
                ->where('proposals.data.0.id', $proposalA->id)
                ->where('proposals.data.0.title', 'Visible Proposal')
                ->where('proposals.data.0.client.id', $clientA->id)
                ->where('proposals.data.0.total.cents', 50_000));
    }

    public function test_user_can_store_proposal_with_valid_item_snapshots(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->for($tenant)->create();
        $client = Client::factory()->for($tenant)->create();

        $this
            ->actingAs($user)
            ->post('/proposals', [
                'title' => 'Website Redesign',
                'client_id' => $client->id,
                'valid_until' => now()->addDays(30)->toDateString(),
                'items' => [
                    [
                        'name' => 'Discovery Workshop',
                        'price' => 15_000,
                        'type' => ServiceType::Fixed->value,
                        'quantity' => 1,
                    ],
                    [
                        'name' => 'Implementation Support',
                        'price' => 25_000,
                        'type' => ServiceType::Hourly->value,
                        'quantity' => 2,
                    ],
                ],
            ])
            ->assertRedirect('/proposals')
            ->assertSessionHas('success', __('ui.proposals.created'));

        $this->assertDatabaseHas('proposals', [
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
            'title' => 'Website Redesign',
            'status' => ProposalStatus::Draft->value,
        ]);
        $this->assertDatabaseHas('proposal_items', [
            'name' => 'Discovery Workshop',
            'price' => 15_000,
            'type' => ServiceType::Fixed->value,
            'quantity' => 1,
        ]);
        $this->assertDatabaseHas('proposal_items', [
            'name' => 'Implementation Support',
            'price' => 25_000,
            'type' => ServiceType::Hourly->value,
            'quantity' => 2,
        ]);
    }

    public function test_create_and_edit_forms_receive_catalog_and_item_data(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->for($tenant)->create();
        $client = Client::factory()->for($tenant)->create();
        $service = Service::factory()->for($tenant)->create([
            'name' => 'Proposal Strategy',
        ]);
        $proposal = Proposal::factory()
            ->for($tenant)
            ->for($client)
            ->create([
                'title' => 'Editable Proposal',
            ]);
        ProposalItem::factory()
            ->for($proposal)
            ->create([
                'name' => 'Snapshot Item',
                'price' => 12_500,
                'type' => ServiceType::Fixed,
                'quantity' => 2,
            ]);

        $this
            ->actingAs($user)
            ->get('/proposals/create')
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('proposals/Form')
                ->where('proposal', null)
                ->has('clients.data', 1)
                ->where('services.data.0.id', $service->id)
                ->where('services.data.0.name', 'Proposal Strategy'));

        $this
            ->actingAs($user)
            ->get("/proposals/{$proposal->id}/edit")
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('proposals/Form')
                ->where('proposal.data.id', $proposal->id)
                ->where('proposal.data.items.0.name', 'Snapshot Item')
                ->where('proposal.data.items.0.price.cents', 12_500)
                ->where('proposal.data.items.0.quantity', 2)
                ->where('services.data.0.id', $service->id));
    }

    public function test_authenticated_user_can_mark_own_draft_proposal_as_sent(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->for($tenant)->create();
        $proposal = Proposal::factory()
            ->for($tenant)
            ->create([
                'status' => ProposalStatus::Draft,
            ]);

        $this
            ->actingAs($user)
            ->post(route('proposals.send', $proposal))
            ->assertRedirect('/proposals')
            ->assertSessionHas('success', __('ui.proposals.sent'));

        $this->assertDatabaseHas('proposals', [
            'id' => $proposal->id,
            'status' => ProposalStatus::Sent->value,
        ]);
    }

    public function test_authenticated_user_cannot_send_another_tenants_proposal(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $userA = User::factory()->for($tenantA)->create();
        $proposalB = Proposal::factory()
            ->for($tenantB)
            ->create([
                'status' => ProposalStatus::Draft,
            ]);

        $this
            ->actingAs($userA)
            ->post(route('proposals.send', $proposalB))
            ->assertNotFound();

        $this->assertDatabaseHas('proposals', [
            'id' => $proposalB->id,
            'status' => ProposalStatus::Draft->value,
        ]);
    }

    public function test_non_draft_proposals_cannot_be_edited_updated_or_deleted(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->for($tenant)->create();
        $client = Client::factory()->for($tenant)->create();

        foreach ([ProposalStatus::Sent, ProposalStatus::Accepted, ProposalStatus::Declined] as $status) {
            $proposal = Proposal::factory()
                ->for($tenant)
                ->for($client)
                ->create([
                    'status' => $status,
                    'title' => "Locked {$status->value} proposal",
                ]);

            $this
                ->actingAs($user)
                ->get("/proposals/{$proposal->id}/edit")
                ->assertForbidden();

            $this
                ->actingAs($user)
                ->put("/proposals/{$proposal->id}", [
                    'title' => 'Mutated proposal',
                    'client_id' => $client->id,
                    'valid_until' => null,
                    'items' => [],
                ])
                ->assertForbidden();

            $this
                ->actingAs($user)
                ->delete("/proposals/{$proposal->id}")
                ->assertForbidden();

            $this->assertDatabaseHas('proposals', [
                'id' => $proposal->id,
                'status' => $status->value,
                'title' => "Locked {$status->value} proposal",
            ]);
        }
    }

    public function test_creation_validation_rejects_invalid_payloads(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->for($tenant)->create();
        $client = Client::factory()->for($tenant)->create();

        $this
            ->actingAs($user)
            ->from('/proposals/create')
            ->post('/proposals', [
                'title' => '',
                'client_id' => $client->id,
                'valid_until' => 'not-a-date',
                'items' => [
                    [
                        'name' => '',
                        'price' => '125.00',
                        'type' => 'retainer',
                        'quantity' => 0,
                    ],
                ],
            ])
            ->assertRedirect('/proposals/create')
            ->assertSessionHasErrors([
                'title',
                'valid_until',
                'items.0.name',
                'items.0.price',
                'items.0.type',
                'items.0.quantity',
            ]);

        $this->assertDatabaseCount('proposals', 0);
        $this->assertDatabaseCount('proposal_items', 0);
    }

    public function test_user_cannot_forge_proposal_with_client_from_another_tenant(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $userA = User::factory()->for($tenantA)->create();
        $clientB = Client::factory()->for($tenantB)->create();

        $this
            ->actingAs($userA)
            ->from('/proposals/create')
            ->post('/proposals', [
                'title' => 'Forged Proposal',
                'client_id' => $clientB->id,
                'valid_until' => null,
                'items' => [
                    [
                        'name' => 'Unauthorized Line Item',
                        'price' => 50_000,
                        'type' => ServiceType::Fixed->value,
                        'quantity' => 1,
                    ],
                ],
            ])
            ->assertRedirect('/proposals/create')
            ->assertSessionHasErrors('client_id');

        $this->assertDatabaseMissing('proposals', [
            'title' => 'Forged Proposal',
        ]);
        $this->assertDatabaseMissing('proposal_items', [
            'name' => 'Unauthorized Line Item',
        ]);
    }
}
