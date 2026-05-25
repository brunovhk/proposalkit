<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Proposals\TransitionProposalAction;
use App\Enums\ServiceType;
use App\Models\Client;
use App\Models\Proposal;
use App\Models\ProposalEvent;
use App\Models\ProposalItem;
use App\Models\Tenant;
use App\Models\User;
use App\Services\PublicProposalUrlGenerator;
use App\States\Proposal\Accepted;
use App\States\Proposal\Declined;
use App\States\Proposal\Draft;
use App\States\Proposal\Sent;
use App\States\Proposal\Viewed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicProposalSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_signed_public_proposal_can_be_viewed_without_authentication(): void
    {
        $tenant = Tenant::factory()->create([
            'name' => 'ProposalKit Studio',
            'slug' => 'proposalkit-studio',
        ]);
        $client = Client::factory()->for($tenant)->create([
            'name' => 'Acme Buyer',
        ]);
        $proposal = Proposal::factory()
            ->sent()
            ->for($tenant)
            ->for($client)
            ->create([
                'title' => 'Public Proposal',
                'valid_until' => now()->addDays(10),
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
            ->get($this->publicUrl($proposal))
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('proposals/PublicView')
                ->where('proposal.data.title', 'Public Proposal')
                ->where('proposal.data.client.name', 'Acme Buyer')
                ->where('proposal.data.items.0.name', 'Implementation')
                ->where('proposal.data.total.cents', 50_000)
                ->has('pdfUrl'));
    }

    public function test_draft_proposal_returns_not_found_for_public_visitors(): void
    {
        $proposal = Proposal::factory()
            ->create([
                'status' => Draft::class,
                'valid_until' => now()->addDays(10),
            ]);

        $this
            ->get($this->publicUrl($proposal))
            ->assertNotFound();
    }

    public function test_tampered_signature_returns_forbidden(): void
    {
        $proposal = Proposal::factory()
            ->sent()
            ->create([
                'valid_until' => now()->addDays(10),
            ]);

        $tamperedUrl = str_replace(
            (string) $proposal->secure_token,
            (string) Str::uuid7(),
            $this->publicUrl($proposal),
        );

        $this
            ->get($tamperedUrl)
            ->assertForbidden();
    }

    public function test_signed_invalid_token_returns_forbidden(): void
    {
        $proposal = Proposal::factory()
            ->sent()
            ->create([
                'valid_until' => now()->addDays(10),
            ]);
        $proposal->load('tenant');

        $url = URL::temporarySignedRoute(
            'public.proposals.show',
            now()->addDay(),
            [
                'tenant_slug' => $proposal->tenant->slug,
                'secure_token' => (string) Str::uuid7(),
            ],
        );

        $this
            ->get($url)
            ->assertForbidden();
    }

    public function test_expired_signature_returns_forbidden(): void
    {
        $proposal = Proposal::factory()
            ->sent()
            ->create([
                'valid_until' => now()->subDay(),
            ]);

        $this
            ->get($this->publicUrl($proposal))
            ->assertForbidden();
    }

    public function test_first_public_open_transitions_to_viewed_exactly_once(): void
    {
        $proposal = Proposal::factory()
            ->sent()
            ->create([
                'valid_until' => now()->addDays(10),
            ]);
        $url = $this->publicUrl($proposal);

        $this
            ->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
            ->withHeader('User-Agent', 'ProposalKit Test Browser')
            ->get($url)
            ->assertOk();

        $this->assertDatabaseHas('proposals', [
            'id' => $proposal->id,
            'status' => Viewed::$name,
        ]);

        $this
            ->withServerVariables(['REMOTE_ADDR' => '203.0.113.11'])
            ->withHeader('User-Agent', 'ProposalKit Test Browser Again')
            ->get($url)
            ->assertOk();

        $this->assertSame(
            1,
            ProposalEvent::query()
                ->where('proposal_id', $proposal->id)
                ->where('type', 'viewed')
                ->get()
                ->filter(static fn (ProposalEvent $event): bool => is_array($event->payload)
                    && ($event->payload['transitioned'] ?? false) === true)
                ->count(),
        );

        $this->assertSame(
            2,
            ProposalEvent::query()
                ->where('proposal_id', $proposal->id)
                ->where('type', 'viewed')
                ->count(),
        );
    }

    public function test_authenticated_users_do_not_trigger_view_tracking(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->for($tenant)->create();
        $proposal = Proposal::factory()
            ->sent()
            ->for($tenant)
            ->create([
                'valid_until' => now()->addDays(10),
            ]);

        $this
            ->actingAs($user)
            ->get($this->publicUrl($proposal))
            ->assertOk();

        $this->assertDatabaseHas('proposals', [
            'id' => $proposal->id,
            'status' => Sent::$name,
        ]);

        $this->assertDatabaseMissing('proposal_events', [
            'proposal_id' => $proposal->id,
            'type' => 'viewed',
        ]);
    }

    public function test_signed_pdf_download_streams_generated_file(): void
    {
        Storage::fake('proposals');

        $proposal = Proposal::factory()
            ->sent()
            ->create([
                'valid_until' => now()->addDays(10),
            ]);
        $fileName = "{$proposal->uuid}.pdf";

        Storage::disk('proposals')->put($fileName, '%PDF-1.4 test content');
        $proposal->forceFill(['pdf_path' => "proposals/{$fileName}"])->save();

        $response = $this
            ->get($this->pdfUrl($proposal))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', "inline; filename=\"{$proposal->uuid}.pdf\"");

        $this->assertStringContainsString('%PDF-1.4 test content', (string) $response->getContent());
    }

    public function test_signed_pdf_download_returns_not_found_when_file_is_missing(): void
    {
        Storage::fake('proposals');

        $proposal = Proposal::factory()
            ->sent()
            ->create([
                'valid_until' => now()->addDays(10),
            ]);

        $this
            ->get($this->pdfUrl($proposal))
            ->assertNotFound();
    }

    public function test_signed_pdf_download_generates_missing_file_on_live_demo(): void
    {
        Storage::fake('proposals');
        config(['app.live_demo_enabled' => true]);

        $proposal = Proposal::factory()
            ->sent()
            ->create([
                'valid_until' => now()->addDays(10),
            ]);

        $response = $this
            ->get($this->pdfUrl($proposal))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', "inline; filename=\"{$proposal->uuid}.pdf\"");

        $this->assertStringStartsWith('%PDF-', (string) $response->getContent());
    }

    public function test_pdf_download_blocks_tampered_signed_requests(): void
    {
        Storage::fake('proposals');

        $proposal = Proposal::factory()
            ->sent()
            ->create([
                'valid_until' => now()->addDays(10),
            ]);
        $fileName = "{$proposal->uuid}.pdf";

        Storage::disk('proposals')->put($fileName, '%PDF-1.4 test content');
        $proposal->forceFill(['pdf_path' => "proposals/{$fileName}"])->save();

        $tamperedUrl = str_replace(
            (string) $proposal->secure_token,
            (string) Str::uuid7(),
            $this->pdfUrl($proposal),
        );

        $this
            ->get($tamperedUrl)
            ->assertForbidden();
    }

    public function test_sent_proposal_can_be_accepted_publicly_with_evidence(): void
    {
        $proposal = Proposal::factory()
            ->sent()
            ->create([
                'valid_until' => now()->addDays(10),
            ]);

        $this
            ->withServerVariables(['REMOTE_ADDR' => '203.0.113.20'])
            ->withHeader('User-Agent', 'Evidence Browser')
            ->post($this->acceptUrl($proposal), [
                'typed_name' => 'Jordan Buyer',
                'acceptance_confirmed' => true,
            ])
            ->assertRedirect(route('home'))
            ->assertSessionHas('success', __('ui.proposals.public.accepted_message'));

        $this->assertDatabaseHas('proposals', [
            'id' => $proposal->id,
            'status' => Accepted::$name,
        ]);

        $event = ProposalEvent::query()
            ->where('proposal_id', $proposal->id)
            ->where('type', 'accepted')
            ->sole();
        $payload = $event->payload;

        $this->assertIsArray($payload);

        $this->assertNull($event->actor_id);
        $this->assertNull($event->actor_type);
        $this->assertSame('203.0.113.20', $event->ip);
        $this->assertSame('Evidence Browser', $event->user_agent);
        $this->assertSame('Jordan Buyer', $payload['typed_name']);
        $this->assertTrue($payload['acceptance_confirmed']);
        $this->assertArrayHasKey('occurred_at', $payload);
    }

    public function test_sent_proposal_can_be_rejected_publicly_with_reason(): void
    {
        $proposal = Proposal::factory()
            ->sent()
            ->create([
                'valid_until' => now()->addDays(10),
            ]);

        $this
            ->withServerVariables(['REMOTE_ADDR' => '203.0.113.30'])
            ->withHeader('User-Agent', 'Rejection Browser')
            ->post($this->rejectUrl($proposal), [
                'reason' => 'Timeline does not fit.',
            ])
            ->assertRedirect(route('home'))
            ->assertSessionHas('success', __('ui.proposals.public.declined_message'));

        $this->assertDatabaseHas('proposals', [
            'id' => $proposal->id,
            'status' => Declined::$name,
        ]);

        $event = ProposalEvent::query()
            ->where('proposal_id', $proposal->id)
            ->where('type', 'rejected')
            ->sole();
        $payload = $event->payload;

        $this->assertIsArray($payload);

        $this->assertNull($event->actor_id);
        $this->assertNull($event->actor_type);
        $this->assertSame('203.0.113.30', $event->ip);
        $this->assertSame('Rejection Browser', $event->user_agent);
        $this->assertSame('Timeline does not fit.', $payload['reason']);
    }

    public function test_finalized_proposal_public_access_returns_gone(): void
    {
        $proposal = Proposal::factory()
            ->accepted()
            ->create([
                'valid_until' => now()->addDays(10),
            ]);

        $this
            ->get($this->publicUrl($proposal))
            ->assertGone();
    }

    public function test_attempting_to_modify_already_accepted_document_returns_gone(): void
    {
        $proposal = Proposal::factory()
            ->accepted()
            ->create([
                'valid_until' => now()->addDays(10),
            ]);

        $this
            ->post($this->rejectUrl($proposal), [
                'reason' => 'Changed my mind.',
            ])
            ->assertGone();

        $this->assertDatabaseHas('proposals', [
            'id' => $proposal->id,
            'status' => Accepted::$name,
        ]);
    }

    public function test_invalid_state_transitions_throw_validation_exception(): void
    {
        $proposal = Proposal::factory()
            ->create([
                'status' => Draft::class,
            ]);

        $this->expectException(ValidationException::class);

        resolve(TransitionProposalAction::class)->execute($proposal, Accepted::class);
    }

    private function publicUrl(Proposal $proposal): string
    {
        return resolve(PublicProposalUrlGenerator::class)->show($proposal);
    }

    private function acceptUrl(Proposal $proposal): string
    {
        return resolve(PublicProposalUrlGenerator::class)->accept($proposal);
    }

    private function rejectUrl(Proposal $proposal): string
    {
        return resolve(PublicProposalUrlGenerator::class)->reject($proposal);
    }

    private function pdfUrl(Proposal $proposal): string
    {
        return resolve(PublicProposalUrlGenerator::class)->pdf($proposal);
    }
}
