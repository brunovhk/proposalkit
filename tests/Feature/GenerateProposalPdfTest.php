<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ServiceType;
use App\Jobs\GenerateProposalPdf;
use App\Models\Client;
use App\Models\Proposal;
use App\Models\ProposalItem;
use App\Models\Tenant;
use App\Models\User;
use App\States\Proposal\Draft;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Mockery;
use ReflectionProperty;
use Tests\TestCase;

class GenerateProposalPdfTest extends TestCase
{
    use LazilyRefreshDatabase;

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

        Queue::assertPushed(
            GenerateProposalPdf::class,
            static function (GenerateProposalPdf $job) use ($proposal): bool {
                $proposalId = new ReflectionProperty($job, 'proposalId');

                return $proposalId->getValue($job) === $proposal->id;
            },
        );

        $this->assertDatabaseHas('proposals', [
            'id' => $proposal->id,
            'status' => 'sent',
        ]);
    }

    public function test_pdf_generation_job_writes_private_pdf_snapshot(): void
    {
        Storage::fake('proposals');
        $pdf = new class extends \Barryvdh\DomPDF\PDF
        {
            /**
             * @var string|array<int, float>|null
             */
            public string|array|null $paper = null;

            public function __construct() {}

            /**
             * @param  string|array<int, float>  $paper
             */
            public function setPaper(string|array $paper, string $orientation = 'portrait'): self
            {
                $this->paper = $paper;

                return $this;
            }

            /**
             * @param  array<string, int>  $options
             */
            public function output(array $options = []): string
            {
                return '%PDF-1.4 fake test content';
            }
        };

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

        Pdf::shouldReceive('loadView')
            ->once()
            ->with('proposals.pdf', Mockery::on(
                static fn (array $data): bool => ($data['proposal'] ?? null) instanceof Proposal
                    && $data['proposal']->is($proposal),
            ))
            ->andReturn($pdf);

        new GenerateProposalPdf($proposal->id)->handle();

        $this->assertSame('a4', $pdf->paper);
        Storage::disk('proposals')->assertExists("{$proposal->uuid}.pdf");

        $this->assertDatabaseHas('proposals', [
            'id' => $proposal->id,
            'pdf_path' => "proposals/{$proposal->uuid}.pdf",
        ]);
    }
}
