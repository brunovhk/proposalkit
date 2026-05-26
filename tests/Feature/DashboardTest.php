<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Services\ProposalAnalyticsService;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\Repository as CacheRepositoryContract;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->instance(ProposalAnalyticsService::class, new ProposalAnalyticsService($this->arrayCacheFactory()));
    }

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
        $response->assertInertia(
            fn (AssertableInertia $page): AssertableInertia => $page
                ->component('dashboard/Index')
                ->has('metrics')
                ->has('filters'),
        );
    }

    public function test_authenticated_users_can_filter_the_dashboard_by_date_range(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard', [
            'start_date' => '2026-01-01',
            'end_date' => '2026-05-21',
        ]));

        $response->assertOk();
        $response->assertInertia(
            fn (AssertableInertia $page): AssertableInertia => $page
                ->where('filters.start_date', '2026-01-01')
                ->where('filters.end_date', '2026-05-21'),
        );
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
