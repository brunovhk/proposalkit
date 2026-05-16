<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Proposals\TransitionProposalAction;
use App\Enums\ProposalStatus;
use App\Enums\ServiceType;
use App\Models\Client;
use App\Models\Proposal;
use App\Models\ProposalItem;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicProposalTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_uuid_proposal_can_be_viewed_without_authentication(): void
    {
        $tenant = Tenant::factory()->create();
        $client = Client::factory()->for($tenant)->create([
            'name' => 'Acme Buyer',
        ]);
        $proposal = Proposal::factory()
            ->sent()
            ->for($tenant)
            ->for($client)
            ->create([
                'title' => 'Public Proposal',
            ]);
        ProposalItem::factory()
            ->for($proposal)
            ->create([
                'name' => 'Implementation',
                'price' => 25_000,
                'type' => ServiceType::Fixed,
                'quantity' => 2,
            ]);

        $this
            ->get(route('public.proposals.show', $proposal->uuid))
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('proposals/PublicView')
                ->where('proposal.data.title', 'Public Proposal')
                ->where('proposal.data.client.name', 'Acme Buyer')
                ->where('proposal.data.items.0.name', 'Implementation')
                ->where('proposal.data.total.cents', 50_000));
    }

    public function test_draft_proposal_uuid_returns_not_found_for_public_visitors(): void
    {
        $proposal = Proposal::factory()
            ->create([
                'status' => ProposalStatus::Draft,
            ]);

        $this
            ->get(route('public.proposals.show', $proposal->uuid))
            ->assertNotFound();
    }

    public function test_sent_proposal_can_be_accepted_publicly(): void
    {
        $proposal = Proposal::factory()
            ->sent()
            ->create();

        $this
            ->post(route('public.proposals.transition', $proposal->uuid), [
                'action' => ProposalStatus::Accepted->value,
            ])
            ->assertRedirect(route('public.proposals.show', $proposal->uuid))
            ->assertSessionHas('success', __('ui.proposals.public.accepted_message'));

        $this->assertDatabaseHas('proposals', [
            'id' => $proposal->id,
            'status' => ProposalStatus::Accepted->value,
        ]);
    }

    public function test_invalid_state_transitions_throw_validation_exception(): void
    {
        $proposal = Proposal::factory()
            ->create([
                'status' => ProposalStatus::Draft,
            ]);

        $this->expectException(ValidationException::class);

        app(TransitionProposalAction::class)->execute($proposal, ProposalStatus::Accepted);
    }

    public function test_finalized_proposal_cannot_be_transitioned_again(): void
    {
        $proposal = Proposal::factory()
            ->accepted()
            ->create();

        try {
            app(TransitionProposalAction::class)->execute($proposal, ProposalStatus::Declined);
        } catch (ValidationException) {
            $this->assertDatabaseHas('proposals', [
                'id' => $proposal->id,
                'status' => ProposalStatus::Accepted->value,
            ]);

            return;
        }

        $this->fail('Expected finalized proposal transition to throw a validation exception.');
    }
}
