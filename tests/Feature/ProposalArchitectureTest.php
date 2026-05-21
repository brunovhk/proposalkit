<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ServiceType;
use App\Models\Client;
use App\Models\Proposal;
use App\Models\ProposalItem;
use App\Models\Tenant;
use App\Models\User;
use App\States\Proposal\Draft;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class ProposalArchitectureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/__tests/proposals', function (): JsonResponse {
            Gate::authorize('viewAny', Proposal::class);

            return response()->json([
                'proposal_ids' => $this->getVisibleProposalIds(),
            ]);
        });

        Route::get('/__tests/proposals/{proposal}', function (Proposal $proposal): Response {
            Gate::authorize('view', $proposal);

            return response()->noContent();
        });

        Route::put('/__tests/proposals/{proposal}', function (Proposal $proposal): Response {
            Gate::authorize('update', $proposal);

            $proposal->update(['title' => 'Updated Proposal']);

            return response()->noContent();
        });

        Route::delete('/__tests/proposals/{proposal}', function (Proposal $proposal): Response {
            Gate::authorize('delete', $proposal);

            $proposal->delete();

            return response()->noContent();
        });
    }

    public function test_proposal_uuid_is_generated_on_creation(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->for($tenant)->create();
        $client = Client::factory()->for($tenant)->create();

        $this->actingAs($user);

        $proposal = Proposal::query()->create([
            'client_id' => $client->id,
            'title' => 'Website Redesign Proposal',
            'status' => Draft::class,
            'valid_until' => now()->addDays(30)->toDateString(),
        ]);

        $this->assertSame($user->tenant_id, $proposal->tenant_id);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $proposal->uuid,
        );
        $this->assertDatabaseHas('proposals', [
            'id' => $proposal->id,
            'tenant_id' => $user->tenant_id,
            'client_id' => $client->id,
            'title' => 'Website Redesign Proposal',
            'status' => Draft::$name,
        ]);
    }

    public function test_user_cannot_view_or_manipulate_proposals_from_another_tenant(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $userA = User::factory()->for($tenantA)->create();
        $proposalA = Proposal::factory()->for($tenantA)->create();
        $proposalB = Proposal::factory()->for($tenantB)->create([
            'title' => 'Protected Proposal',
        ]);

        $this->assertTrue(Gate::forUser($userA)->allows('view', $proposalA));
        $this->assertFalse(Gate::forUser($userA)->allows('view', $proposalB));
        $this->assertFalse(Gate::forUser($userA)->allows('update', $proposalB));
        $this->assertFalse(Gate::forUser($userA)->allows('delete', $proposalB));

        $this->actingAs($userA);

        $this
            ->get('/__tests/proposals')
            ->assertOk()
            ->assertJsonPath('proposal_ids', [$proposalA->id]);

        $this->assertNull(Proposal::query()->find($proposalB->id));

        $updatedRows = Proposal::query()
            ->whereKey($proposalB->id)
            ->update(['title' => 'Compromised Proposal']);

        $deletedRows = Proposal::query()
            ->whereKey($proposalB->id)
            ->delete();

        $this->assertSame(0, $updatedRows);
        $this->assertSame(0, $deletedRows);
        $this->assertDatabaseHas('proposals', [
            'id' => $proposalB->id,
            'title' => 'Protected Proposal',
        ]);
    }

    public function test_proposal_items_load_through_relationships_and_cascade_on_delete(): void
    {
        $tenant = Tenant::factory()->create();
        $client = Client::factory()->for($tenant)->create();
        $proposal = Proposal::factory()
            ->for($tenant)
            ->for($client)
            ->create();

        $fixedItem = ProposalItem::factory()
            ->for($proposal)
            ->create([
                'name' => 'Discovery Workshop',
                'price' => 15_000,
                'type' => ServiceType::Fixed,
                'quantity' => 1,
            ]);
        $hourlyItem = ProposalItem::factory()
            ->for($proposal)
            ->create([
                'name' => 'Implementation Support',
                'price' => 25_000,
                'type' => ServiceType::Hourly,
                'quantity' => 3,
            ]);

        $loadedProposal = Proposal::query()
            ->with(['client', 'items'])
            ->findOrFail($proposal->id);

        $this->assertTrue($loadedProposal->client->is($client));
        $this->assertCount(2, $loadedProposal->items);
        $this->assertTrue($loadedProposal->items->contains($fixedItem));
        $this->assertTrue($loadedProposal->items->contains($hourlyItem));
        $this->assertSame(15_000, $fixedItem->price);
        $this->assertSame(ServiceType::Fixed, $fixedItem->type);

        $proposal->delete();

        $this->assertDatabaseMissing('proposal_items', [
            'id' => $fixedItem->id,
        ]);
        $this->assertDatabaseMissing('proposal_items', [
            'id' => $hourlyItem->id,
        ]);
    }

    /**
     * Get the proposal IDs visible to the currently authenticated user.
     *
     * @return list<int>
     */
    private function getVisibleProposalIds(): array
    {
        return array_values(array_map(
            static fn (mixed $id): int => (int) $id,
            Proposal::query()
                ->orderBy('id')
                ->pluck('id')
                ->all(),
        ));
    }
}
