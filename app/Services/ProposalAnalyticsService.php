<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Proposal;
use App\Models\ProposalEvent;
use App\Models\ProposalItem;
use App\Models\Tenant;
use App\States\Proposal\Accepted;
use App\States\Proposal\Declined;
use App\States\Proposal\Draft;
use App\States\Proposal\Sent;
use App\States\Proposal\Viewed;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Date;

/**
 * Builds tenant-scoped proposal pipeline read models for dashboard analytics.
 *
 * @phpstan-type MoneyMetric array{cents: int}
 * @phpstan-type ConversionMetric array{sent: int, accepted: int, rate: float}
 * @phpstan-type FunnelMetric array{status: string, count: int, value_cents: int}
 * @phpstan-type MonthlyRevenueMetric array{month: string, revenue_cents: int}
 * @phpstan-type TopClientMetric array{client_id: int, client_name: string, accepted_count: int, revenue_cents: int}
 * @phpstan-type DashboardMetrics array{
 *     range: array{from: string, to: string},
 *     active: array{count: int, value_cents: int},
 *     conversion: array{rolling_30_days: ConversionMetric, rolling_90_days: ConversionMetric},
 *     average_time_to_acceptance_days: float|null,
 *     revenue_won: array{month_cents: int, quarter_cents: int, year_cents: int, range_cents: int},
 *     funnel: list<FunnelMetric>,
 *     monthly_revenue: list<MonthlyRevenueMetric>,
 *     top_clients: list<TopClientMetric>
 * }
 */
final readonly class ProposalAnalyticsService
{
    private const string CACHE_STORE = 'redis';

    private const int CACHE_TTL_SECONDS = 300;

    public function __construct(
        private CacheFactory $cache,
    ) {}

    /**
     * Return all dashboard metrics for a tenant and date range.
     *
     * @return DashboardMetrics
     */
    public function dashboard(Tenant $tenant, ?CarbonInterface $from = null, ?CarbonInterface $to = null): array
    {
        [$rangeStart, $rangeEnd] = $this->resolveRange($from, $to);
        $cacheKey = $this->dashboardCacheKey($tenant, $rangeStart, $rangeEnd);

        /** @var DashboardMetrics $metrics */
        $metrics = $this->cache
            ->store(self::CACHE_STORE)
            ->remember(
                $cacheKey,
                self::CACHE_TTL_SECONDS,
                fn (): array => $this->buildDashboard($tenant, $rangeStart, $rangeEnd),
            );

        return $metrics;
    }

    /**
     * Forget the cached dashboard read model for a tenant/date range.
     */
    public function forgetDashboard(Tenant $tenant, CarbonInterface $from, CarbonInterface $to): void
    {
        $this->cache
            ->store(self::CACHE_STORE)
            ->forget($this->dashboardCacheKey(
                $tenant,
                $this->toImmutable($from)->startOfDay(),
                $this->toImmutable($to)->endOfDay(),
            ));
    }

    /**
     * @return DashboardMetrics
     */
    private function buildDashboard(Tenant $tenant, CarbonImmutable $rangeStart, CarbonImmutable $rangeEnd): array
    {
        $acceptedProposals = $this->acceptedProposalsInRange($tenant, $rangeStart, $rangeEnd);

        return [
            'range' => [
                'from' => $rangeStart->toDateString(),
                'to' => $rangeEnd->toDateString(),
            ],
            'active' => $this->activePipeline($tenant),
            'conversion' => [
                'rolling_30_days' => $this->conversionRateForWindow(
                    $tenant,
                    $rangeEnd->subDays(29)->startOfDay(),
                    $rangeEnd,
                ),
                'rolling_90_days' => $this->conversionRateForWindow(
                    $tenant,
                    $rangeEnd->subDays(89)->startOfDay(),
                    $rangeEnd,
                ),
            ],
            'average_time_to_acceptance_days' => $this->averageTimeToAcceptanceDays($acceptedProposals),
            'revenue_won' => [
                'month_cents' => $this->wonRevenueCents($tenant, $rangeEnd->startOfMonth(), $rangeEnd),
                'quarter_cents' => $this->wonRevenueCents($tenant, $rangeEnd->startOfQuarter(), $rangeEnd),
                'year_cents' => $this->wonRevenueCents($tenant, $rangeEnd->startOfYear(), $rangeEnd),
                'range_cents' => $this->sumProposalValues($acceptedProposals),
            ],
            'funnel' => $this->funnel($tenant, $rangeStart, $rangeEnd),
            'monthly_revenue' => $this->monthlyRevenue($acceptedProposals, $rangeStart, $rangeEnd),
            'top_clients' => $this->topClients($acceptedProposals),
        ];
    }

    /**
     * @return array{count: int, value_cents: int}
     */
    private function activePipeline(Tenant $tenant): array
    {
        $proposals = Proposal::query()
            ->with('items')
            ->whereBelongsTo($tenant)
            ->whereIn('status', [Draft::$name, Sent::$name, Viewed::$name])
            ->get();

        return [
            'count' => $proposals->count(),
            'value_cents' => $this->sumProposalValues($proposals),
        ];
    }

    /**
     * @return ConversionMetric
     */
    private function conversionRateForWindow(
        Tenant $tenant,
        CarbonImmutable $windowStart,
        CarbonImmutable $windowEnd,
    ): array {
        $sentCount = ProposalEvent::query()
            ->where('type', 'transitioned')
            ->where('payload->to', Sent::$name)
            ->whereBetween('occurred_at', [$windowStart, $windowEnd])
            ->whereHas(
                'proposal',
                static fn ($query) => $query->whereBelongsTo($tenant),
            )
            ->count();

        $acceptedCount = ProposalEvent::query()
            ->where('type', 'accepted')
            ->whereBetween('occurred_at', [$windowStart, $windowEnd])
            ->whereHas(
                'proposal',
                static fn ($query) => $query->whereBelongsTo($tenant),
            )
            ->count();

        return [
            'sent' => $sentCount,
            'accepted' => $acceptedCount,
            'rate' => $sentCount === 0 ? 0.0 : round(($acceptedCount / $sentCount) * 100, 2),
        ];
    }

    private function wonRevenueCents(Tenant $tenant, CarbonImmutable $rangeStart, CarbonImmutable $rangeEnd): int
    {
        return $this->sumProposalValues($this->acceptedProposalsInRange($tenant, $rangeStart, $rangeEnd));
    }

    /**
     * @return list<FunnelMetric>
     */
    private function funnel(Tenant $tenant, CarbonImmutable $rangeStart, CarbonImmutable $rangeEnd): array
    {
        $proposals = Proposal::query()
            ->with('items')
            ->whereBelongsTo($tenant)
            ->whereBetween('created_at', [$rangeStart, $rangeEnd])
            ->get();

        $metrics = [];

        foreach ([Draft::$name, Sent::$name, Viewed::$name, Accepted::$name, Declined::$name] as $status) {
            $statusProposals = $proposals->filter(
                static fn (Proposal $proposal): bool => $proposal->status->value === $status,
            );

            $metrics[] = [
                'status' => $status,
                'count' => $statusProposals->count(),
                'value_cents' => $this->sumProposalValues($statusProposals),
            ];
        }

        return $metrics;
    }

    /**
     * @param  EloquentCollection<int, Proposal>  $acceptedProposals
     * @return list<MonthlyRevenueMetric>
     */
    private function monthlyRevenue(
        EloquentCollection $acceptedProposals,
        CarbonImmutable $rangeStart,
        CarbonImmutable $rangeEnd,
    ): array {
        $months = [];
        $cursor = $rangeStart->startOfMonth();
        $lastMonth = $rangeEnd->startOfMonth();

        while ($cursor->lessThanOrEqualTo($lastMonth)) {
            $months[$cursor->format('Y-m')] = 0;
            $cursor = $cursor->addMonthNoOverflow();
        }

        foreach ($acceptedProposals as $proposal) {
            $acceptedAt = $this->acceptedAt($proposal);

            if (! $acceptedAt instanceof CarbonInterface) {
                continue;
            }

            $monthKey = $acceptedAt->format('Y-m');

            if (! array_key_exists($monthKey, $months)) {
                continue;
            }

            $months[$monthKey] += $this->proposalValueCents($proposal);
        }

        $revenue = [];

        foreach ($months as $month => $revenueCents) {
            $revenue[] = [
                'month' => $month,
                'revenue_cents' => $revenueCents,
            ];
        }

        return $revenue;
    }

    /**
     * @param  EloquentCollection<int, Proposal>  $acceptedProposals
     * @return list<TopClientMetric>
     */
    private function topClients(EloquentCollection $acceptedProposals): array
    {
        $clients = [];

        foreach ($acceptedProposals as $proposal) {
            $client = $proposal->client;
            $clientId = $client->id;

            if (! isset($clients[$clientId])) {
                $clients[$clientId] = [
                    'client_id' => $clientId,
                    'client_name' => $client->name,
                    'accepted_count' => 0,
                    'revenue_cents' => 0,
                ];
            }

            $clients[$clientId]['accepted_count']++;
            $clients[$clientId]['revenue_cents'] += $this->proposalValueCents($proposal);
        }

        usort(
            $clients,
            static fn (array $left, array $right): int => $right['revenue_cents'] <=> $left['revenue_cents'],
        );

        return array_slice($clients, 0, 5);
    }

    /**
     * @param  EloquentCollection<int, Proposal>  $acceptedProposals
     */
    private function averageTimeToAcceptanceDays(EloquentCollection $acceptedProposals): ?float
    {
        $durations = [];

        foreach ($acceptedProposals as $proposal) {
            $createdAt = $this->createdAt($proposal);
            $acceptedAt = $this->acceptedAt($proposal);

            if (! $createdAt instanceof CarbonInterface || ! $acceptedAt instanceof CarbonInterface) {
                continue;
            }

            $durations[] = $createdAt->diffInSeconds($acceptedAt);
        }

        if ($durations === []) {
            return null;
        }

        return round((array_sum($durations) / count($durations)) / 86_400, 2);
    }

    /**
     * @return EloquentCollection<int, Proposal>
     */
    private function acceptedProposalsInRange(
        Tenant $tenant,
        CarbonImmutable $rangeStart,
        CarbonImmutable $rangeEnd,
    ): EloquentCollection {
        return Proposal::query()
            ->with([
                'client',
                'items',
                'events' => static fn ($query) => $query
                    ->whereIn('type', ['created', 'accepted'])
                    ->orderBy('occurred_at'),
            ])
            ->whereBelongsTo($tenant)
            ->where('status', Accepted::$name)
            ->whereHas(
                'events',
                static fn ($query) => $query
                    ->where('type', 'accepted')
                    ->whereBetween('occurred_at', [$rangeStart, $rangeEnd]),
            )
            ->get();
    }

    /**
     * @param  EloquentCollection<int, Proposal>  $proposals
     */
    private function sumProposalValues(EloquentCollection $proposals): int
    {
        return (int) $proposals->sum(
            fn (Proposal $proposal): int => $this->proposalValueCents($proposal),
        );
    }

    private function proposalValueCents(Proposal $proposal): int
    {
        if (! $proposal->relationLoaded('items')) {
            $proposal->load('items');
        }

        return (int) $proposal->items->sum(
            static fn (ProposalItem $item): int => $item->price * $item->quantity,
        );
    }

    private function createdAt(Proposal $proposal): ?CarbonInterface
    {
        /** @var EloquentCollection<int, ProposalEvent> $events */
        $events = $proposal->getRelation('events');

        $event = $events->first(
            static fn (ProposalEvent $event): bool => $event->type === 'created',
        );

        if (! $event instanceof ProposalEvent) {
            return $proposal->created_at;
        }

        return $event->occurred_at;
    }

    private function acceptedAt(Proposal $proposal): ?CarbonInterface
    {
        /** @var EloquentCollection<int, ProposalEvent> $events */
        $events = $proposal->getRelation('events');

        $event = $events->first(
            static fn (ProposalEvent $event): bool => $event->type === 'accepted',
        );

        return $event?->occurred_at;
    }

    /**
     * @return array{CarbonImmutable, CarbonImmutable}
     */
    private function resolveRange(?CarbonInterface $from, ?CarbonInterface $to): array
    {
        $rangeEnd = $to instanceof CarbonInterface
            ? $this->toImmutable($to)->endOfDay()
            : $this->toImmutable(Date::now())->endOfDay();

        $rangeStart = $from instanceof CarbonInterface
            ? $this->toImmutable($from)->startOfDay()
            : $rangeEnd->subMonthsNoOverflow(5)->startOfMonth();

        return [$rangeStart, $rangeEnd];
    }

    private function toImmutable(CarbonInterface $date): CarbonImmutable
    {
        return CarbonImmutable::parse($date->toDateTimeString(), $date->getTimezone());
    }

    private function dashboardCacheKey(Tenant $tenant, CarbonImmutable $rangeStart, CarbonImmutable $rangeEnd): string
    {
        return sprintf(
            'tenant:%d:proposal-analytics:dashboard:%s:%s',
            $tenant->id,
            $rangeStart->toDateString(),
            $rangeEnd->toDateString(),
        );
    }
}
