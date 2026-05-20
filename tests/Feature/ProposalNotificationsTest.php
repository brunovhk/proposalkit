<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Proposals\TransitionProposalAction;
use App\Mail\ProposalAccepted;
use App\Mail\ProposalDeclined;
use App\Mail\ProposalSent;
use App\Models\Client;
use App\Models\Proposal;
use App\Models\ProposalEvent;
use App\Models\Tenant;
use App\Models\User;
use App\States\Proposal\Accepted;
use App\States\Proposal\Declined;
use App\States\Proposal\Draft;
use App\States\Proposal\Sent;
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
                'status' => Draft::class,
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

        $event = ProposalEvent::query()
            ->where('proposal_id', $proposal->id)
            ->where('type', 'transitioned')
            ->sole();
        $payload = $event->payload;

        $this->assertIsArray($payload);
        $this->assertSame($user->id, $event->actor_id);
        $this->assertSame(User::class, $event->actor_type);
        $this->assertTrue($event->actor?->is($user));
        $this->assertSame(Draft::$name, $payload['from']);
        $this->assertSame(Sent::$name, $payload['to']);
    }

    public function test_proposal_sent_mail_uses_signed_tenant_slug_and_secure_token_url(): void
    {
        $tenant = Tenant::factory()->create([
            'slug' => 'acme-studio',
        ]);
        $client = Client::factory()->for($tenant)->create([
            'email' => 'buyer@example.com',
        ]);
        $proposal = Proposal::factory()
            ->sent()
            ->for($tenant)
            ->for($client)
            ->create([
                'valid_until' => now()->addDays(7),
            ]);

        $html = new ProposalSent($proposal)->render();

        $this->assertStringContainsString("/p/{$tenant->slug}/{$proposal->secure_token}", $html);
        $this->assertStringContainsString('signature=', $html);
        $this->assertStringNotContainsString("/p/{$proposal->uuid}", $html);
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

        resolve(TransitionProposalAction::class)->execute($proposal, Accepted::class);

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

        resolve(TransitionProposalAction::class)->execute($proposal, Declined::class);

        Mail::assertQueued(
            ProposalDeclined::class,
            static fn (ProposalDeclined $mail): bool => $mail->hasTo($owner->email)
                && $mail->proposal->is($proposal),
        );
    }
}
