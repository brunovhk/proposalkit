<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ServiceType;
use App\Models\Client;
use App\Models\Proposal;
use App\Models\ProposalItem;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ProposalAnalyticsService;
use App\States\Proposal\Accepted;
use App\States\Proposal\Draft;
use App\States\Proposal\Sent;
use Carbon\CarbonImmutable;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\Repository as CacheRepositoryContract;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

final class ProposalAnalyticsServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function tearDown(): void
    {
        Date::setTestNow();

        parent::tearDown();
    }

    public function test_it_builds_cached_tenant_pipeline_metrics(): void
    {
        Date::setTestNow(CarbonImmutable::parse('2026-05-21 12:00:00'));

        $tenant = Tenant::factory()->create();
        $user = User::factory()->for($tenant)->create();
        $client = Client::factory()->for($tenant)->create(['name' => 'Alpha Co']);

        $this->actingAs($user);

        $acceptedProposal = $this->createProposalWithValue(
            tenant: $tenant,
            client: $client,
            status: Accepted::class,
            valueCents: 10_000,
            createdAt: CarbonImmutable::parse('2026-05-10 10:00:00'),
        );
        $acceptedProposal->events()->create([
            'type' => 'created',
            'occurred_at' => CarbonImmutable::parse('2026-05-10 10:00:00'),
        ]);
        $acceptedProposal->events()->create([
            'type' => 'transitioned',
            'payload' => ['from' => Draft::$name, 'to' => Sent::$name],
            'occurred_at' => CarbonImmutable::parse('2026-05-11 10:00:00'),
        ]);
        $acceptedProposal->events()->create([
            'type' => 'accepted',
            'occurred_at' => CarbonImmutable::parse('2026-05-15 10:00:00'),
        ]);

        $sentProposal = $this->createProposalWithValue(
            tenant: $tenant,
            client: $client,
            status: Sent::class,
            valueCents: 5_000,
            createdAt: CarbonImmutable::parse('2026-05-12 10:00:00'),
        );
        $sentProposal->events()->create([
            'type' => 'transitioned',
            'payload' => ['from' => Draft::$name, 'to' => Sent::$name],
            'occurred_at' => CarbonImmutable::parse('2026-05-13 10:00:00'),
        ]);

        $this->createProposalWithValue(
            tenant: $tenant,
            client: $client,
            status: Draft::class,
            valueCents: 2_500,
            createdAt: CarbonImmutable::parse('2026-05-14 10:00:00'),
        );

        $service = new ProposalAnalyticsService($this->arrayCacheFactory());
        $metrics = $service->dashboard(
            tenant: $tenant,
            from: CarbonImmutable::parse('2026-02-01 00:00:00'),
            to: CarbonImmutable::parse('2026-05-21 23:59:59'),
        );

        $this->assertSame(['from' => '2026-02-01', 'to' => '2026-05-21'], $metrics['range']);
        $this->assertSame(['count' => 2, 'value_cents' => 7_500], $metrics['active']);
        $this->assertSame(['sent' => 2, 'accepted' => 1, 'rate' => 50.0], $metrics['conversion']['rolling_30_days']);
        $this->assertSame(5.0, $metrics['average_time_to_acceptance_days']);
        $this->assertSame(10_000, $metrics['revenue_won']['month_cents']);
        $this->assertSame(10_000, $metrics['revenue_won']['range_cents']);
        $this->assertSame('accepted', $metrics['funnel'][3]['status']);
        $this->assertSame(1, $metrics['funnel'][3]['count']);
        $this->assertSame(['client_id' => $client->id, 'client_name' => 'Alpha Co', 'accepted_count' => 1, 'revenue_cents' => 10_000], $metrics['top_clients'][0]);

        $this->createProposalWithValue(
            tenant: $tenant,
            client: $client,
            status: Draft::class,
            valueCents: 99_000,
            createdAt: CarbonImmutable::parse('2026-05-20 10:00:00'),
        );

        $cachedMetrics = $service->dashboard(
            tenant: $tenant,
            from: CarbonImmutable::parse('2026-02-01 00:00:00'),
            to: CarbonImmutable::parse('2026-05-21 23:59:59'),
        );

        $this->assertSame($metrics['active'], $cachedMetrics['active']);
    }

    /**
     * @param  class-string  $status
     */
    private function createProposalWithValue(
        Tenant $tenant,
        Client $client,
        string $status,
        int $valueCents,
        CarbonImmutable $createdAt,
    ): Proposal {
        $proposal = Proposal::factory()
            ->for($tenant)
            ->for($client)
            ->create([
                'status' => $status,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

        ProposalItem::factory()
            ->for($proposal)
            ->create([
                'name' => 'Strategy',
                'price' => $valueCents,
                'type' => ServiceType::Fixed,
                'quantity' => 1,
            ]);

        return $proposal;
    }

    private function arrayCacheFactory(): CacheFactory
    {
        return new readonly class implements CacheFactory
        {
            private CacheRepository $repository;

            public function __construct()
            {
                $this->repository = new CacheRepository(new ArrayStore);
            }

            #[\Override]
            public function store($name = null): CacheRepositoryContract
            {
                return $this->repository;
            }
        };
    }
}
