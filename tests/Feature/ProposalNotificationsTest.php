<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Proposals\TransitionProposalAction;
use App\Enums\ProposalStatus;
use App\Mail\ProposalAccepted;
use App\Mail\ProposalDeclined;
use App\Mail\ProposalSent;
use App\Models\Client;
use App\Models\Proposal;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ProposalNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_proposal_sent_mail_is_queued_when_proposal_is_marked_as_sent(): void
    {
        Mail::fake();
        Queue::fake();

        $tenant = Tenant::factory()->create();
        $user = User::factory()->for($tenant)->create();
        $client = Client::factory()->for($tenant)->create([
            'email' => 'buyer@example.com',
        ]);
        $proposal = Proposal::factory()
            ->for($tenant)
            ->for($client)
            ->create([
                'status' => ProposalStatus::Draft,
                'title' => 'Website Redesign',
            ]);

        $this
            ->actingAs($user)
            ->post(route('proposals.send', $proposal))
            ->assertRedirect('/proposals');

        Mail::assertQueued(
            ProposalSent::class,
            static fn (ProposalSent $mail): bool => $mail->hasTo('buyer@example.com')
                && $mail->proposal->is($proposal),
        );
    }

    public function test_proposal_accepted_mail_is_queued_during_successful_acceptance_transition(): void
    {
        Mail::fake();

        $tenant = Tenant::factory()->create();
        $owner = User::factory()->for($tenant)->create([
            'email' => 'owner@example.com',
        ]);
        $client = Client::factory()->for($tenant)->create();
        $proposal = Proposal::factory()
            ->sent()
            ->for($tenant)
            ->for($client)
            ->create();

        app(TransitionProposalAction::class)->execute($proposal, ProposalStatus::Accepted);

        Mail::assertQueued(
            ProposalAccepted::class,
            static fn (ProposalAccepted $mail): bool => $mail->hasTo($owner->email)
                && $mail->proposal->is($proposal),
        );
    }

    public function test_proposal_declined_mail_is_queued_during_successful_decline_transition(): void
    {
        Mail::fake();

        $tenant = Tenant::factory()->create();
        $owner = User::factory()->for($tenant)->create([
            'email' => 'owner@example.com',
        ]);
        $client = Client::factory()->for($tenant)->create();
        $proposal = Proposal::factory()
            ->sent()
            ->for($tenant)
            ->for($client)
            ->create();

        app(TransitionProposalAction::class)->execute($proposal, ProposalStatus::Declined);

        Mail::assertQueued(
            ProposalDeclined::class,
            static fn (ProposalDeclined $mail): bool => $mail->hasTo($owner->email)
                && $mail->proposal->is($proposal),
        );
    }
}
