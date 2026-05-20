<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ServiceType;
use App\Jobs\GenerateProposalPdfJob;
use App\Models\Client;
use App\Models\Proposal;
use App\Models\ProposalItem;
use App\Models\Tenant;
use App\Models\User;
use App\States\Proposal\Draft;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GenerateProposalPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_pdf_generation_job_is_dispatched_when_proposal_is_sent(): void
    {
        Queue::fake();

        $tenant = Tenant::factory()->create();
        $user = User::factory()->for($tenant)->create();
        $proposal = Proposal::factory()
            ->for($tenant)
            ->create([
                'status' => Draft::class,
            ]);

        $this
            ->actingAs($user)
            ->post(route('proposals.send', $proposal))
            ->assertRedirect('/proposals');

        Queue::assertPushed(GenerateProposalPdfJob::class);
    }

    public function test_pdf_generation_job_writes_private_pdf_snapshot(): void
    {
        Storage::fake('proposals');

        $tenant = Tenant::factory()->create([
            'name' => 'ProposalKit Studio',
        ]);
        $client = Client::factory()->for($tenant)->create([
            'name' => 'Acme Corporation',
            'email' => 'finance@example.com',
            'tax_id' => 'VAT-123',
            'address' => '100 Market Street',
        ]);
        $proposal = Proposal::factory()
            ->for($tenant)
            ->for($client)
            ->sent()
            ->create([
                'title' => 'Website Redesign',
            ]);

        ProposalItem::factory()
            ->for($proposal)
            ->create([
                'name' => 'Discovery Workshop',
                'price' => 25_000,
                'type' => ServiceType::Fixed,
                'quantity' => 1,
            ]);
        ProposalItem::factory()
            ->for($proposal)
            ->create([
                'name' => 'Implementation Support',
                'price' => 12_500,
                'type' => ServiceType::Hourly,
                'quantity' => 4,
            ]);

        new GenerateProposalPdfJob($proposal->id)->handle();

        Storage::disk('proposals')->assertExists("{$proposal->uuid}.pdf");

        $this->assertDatabaseHas('proposals', [
            'id' => $proposal->id,
            'pdf_path' => "proposals/{$proposal->uuid}.pdf",
        ]);
    }
}
