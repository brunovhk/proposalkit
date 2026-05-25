<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ServiceType;
use App\Models\Client;
use App\Models\Proposal;
use App\Models\ProposalEvent;
use App\Models\ProposalItem;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use App\States\Proposal\Accepted;
use App\States\Proposal\Declined;
use App\States\Proposal\Draft;
use App\States\Proposal\ProposalState;
use App\States\Proposal\Sent;
use App\States\Proposal\Viewed;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Rebuilds a realistic Acme Studio sandbox tenant for analytics and public demo flows.
 */
final class DemoSeeder extends Seeder
{
    private const string DEMO_EMAIL = 'demo@proposalkit.test';

    private const int CLIENT_COUNT = 30;

    private const int PROPOSAL_COUNT = 200;

    /**
     * Seed the demo tenant, replacing only prior Acme Studio sandbox data.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $demoUser = User::query()
                ->where('email', self::DEMO_EMAIL)
                ->first();

            if (! $demoUser instanceof User) {
                throw new RuntimeException('The demo user [demo@proposalkit.test] must be seeded before DemoSeeder runs.');
            }

            $tenant = $demoUser->tenant;

            if (! $tenant instanceof Tenant) {
                throw new RuntimeException('The demo user [demo@proposalkit.test] is missing an associated tenant.');
            }

            $this->wipeTenantData($tenant);
            $services = $this->createServices($tenant);
            $clients = $this->createClients($tenant);
            $this->createProposals($tenant, $demoUser, $clients, $services);
        });
    }

    private function wipeTenantData(Tenant $tenant): void
    {
        $proposalIds = Proposal::query()
            ->whereBelongsTo($tenant)
            ->pluck('id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();

        ProposalEvent::query()
            ->whereIn('proposal_id', $proposalIds)
            ->delete();

        ProposalItem::query()
            ->whereIn('proposal_id', $proposalIds)
            ->delete();

        Proposal::query()
            ->whereBelongsTo($tenant)
            ->delete();

        Client::query()
            ->whereBelongsTo($tenant)
            ->delete();

        Service::query()
            ->whereBelongsTo($tenant)
            ->delete();
    }

    /**
     * @return list<Service>
     */
    private function createServices(Tenant $tenant): array
    {
        $serviceDefinitions = [
            ['Brand Strategy Sprint', 'Positioning, messaging, and creative direction.', 450_000, ServiceType::Fixed],
            ['Website UX Audit', 'Heuristic review with prioritized conversion recommendations.', 180_000, ServiceType::Fixed],
            ['Landing Page Design', 'High-converting responsive landing page design.', 320_000, ServiceType::Fixed],
            ['Webflow Implementation', 'Production build and CMS configuration.', 28_000, ServiceType::Hourly],
            ['Laravel Integration', 'Custom backend integration and API wiring.', 35_000, ServiceType::Hourly],
            ['Analytics Setup', 'Tracking plan, events, dashboards, and reporting handoff.', 220_000, ServiceType::Fixed],
            ['Campaign Creative', 'Ad concepts and channel-specific creative assets.', 260_000, ServiceType::Fixed],
            ['Retainer Support', 'Design and development support for ongoing launches.', 24_000, ServiceType::Hourly],
            ['Proposal Workshop', 'Discovery and scope planning workshop.', 120_000, ServiceType::Fixed],
            ['Conversion Copywriting', 'Landing page and email sequence copy.', 210_000, ServiceType::Fixed],
        ];

        $services = [];

        foreach ($serviceDefinitions as [$name, $description, $price, $type]) {
            $services[] = Service::query()->create([
                'tenant_id' => $tenant->id,
                'name' => $name,
                'description' => $description,
                'price' => $price,
                'type' => $type,
            ]);
        }

        return $services;
    }

    /**
     * @return list<Client>
     */
    private function createClients(Tenant $tenant): array
    {
        $clients = [];

        for ($index = 0; $index < self::CLIENT_COUNT; $index++) {
            $clients[] = Client::query()->create([
                'tenant_id' => $tenant->id,
                'name' => fake()->company(),
                'email' => fake()->unique()->companyEmail(),
                'tax_id' => fake()->regexify('[A-Z]{2}[0-9]{9}'),
                'address' => fake()->address(),
            ]);
        }

        return $clients;
    }

    /**
     * @param  list<Client>  $clients
     * @param  list<Service>  $services
     */
    private function createProposals(Tenant $tenant, User $demoUser, array $clients, array $services): void
    {
        for ($index = 0; $index < self::PROPOSAL_COUNT; $index++) {
            $client = $clients[$index % self::CLIENT_COUNT];

            $proposal = Proposal::factory()
                ->for($tenant)
                ->for($client)
                ->create([
                    'title' => sprintf('%s growth proposal', $client->name),
                ]);

            /** @var class-string<ProposalState> $statusClass */
            $statusClass = $proposal->status::class;
            $createdAt = CarbonImmutable::parse((string) $proposal->created_at);

            $this->createProposalItems($proposal, $services, $index);
            $this->createProposalEvents($proposal, $demoUser, $statusClass, $createdAt, $index);
        }
    }

    /**
     * @param  list<Service>  $services
     */
    private function createProposalItems(Proposal $proposal, array $services, int $proposalIndex): void
    {
        $itemCount = 2 + ($proposalIndex % 3);

        for ($itemIndex = 0; $itemIndex < $itemCount; $itemIndex++) {
            $service = $services[($proposalIndex + $itemIndex) % count($services)];
            $quantity = $service->type === ServiceType::Hourly
                ? 8 + (($proposalIndex + $itemIndex) % 32)
                : 1;

            ProposalItem::query()->create([
                'proposal_id' => $proposal->id,
                'name' => $service->name,
                'price' => $service->price,
                'type' => $service->type,
                'quantity' => $quantity,
            ]);
        }
    }

    /**
     * @param  class-string<ProposalState>  $statusClass
     */
    private function createProposalEvents(
        Proposal $proposal,
        User $demoUser,
        string $statusClass,
        CarbonImmutable $createdAt,
        int $proposalIndex,
    ): void {
        $this->createEvent(
            proposal: $proposal,
            type: 'created',
            occurredAt: $createdAt,
            payload: [
                'title' => $proposal->title,
                'client_id' => $proposal->client_id,
                'item_count' => $proposal->items()->count(),
            ],
            actor: $demoUser,
        );

        if ($statusClass === Draft::class) {
            return;
        }

        $sentAt = $createdAt->addHours(2 + ($proposalIndex % 6));
        $this->createTransitionEvent($proposal, Draft::$name, Sent::$name, $sentAt, $demoUser);

        if ($statusClass === Sent::class) {
            return;
        }

        $viewedAt = $sentAt->addHours(2 + ($proposalIndex % 6));
        $this->createTransitionEvent($proposal, Sent::$name, Viewed::$name, $viewedAt, $demoUser);
        $this->createEvent(
            proposal: $proposal,
            type: 'viewed',
            occurredAt: $viewedAt->addMinutes(3),
            payload: [
                'transitioned' => true,
                'referrer' => 'https://mail.proposalkit.test/inbox',
            ],
        );

        if ($statusClass === Viewed::class) {
            return;
        }

        $closedAt = $viewedAt->addHours(2 + ($proposalIndex % 18));

        if ($statusClass === Accepted::class) {
            $this->createTransitionEvent($proposal, Viewed::$name, Accepted::$name, $closedAt, $demoUser);
            $this->createEvent(
                proposal: $proposal,
                type: 'accepted',
                occurredAt: $closedAt->addMinutes(5),
                payload: [
                    'typed_name' => $proposal->client->name,
                    'acceptance_confirmed' => true,
                    'occurred_at' => $closedAt->addMinutes(5)->toAtomString(),
                    'referrer' => 'https://mail.proposalkit.test/inbox',
                ],
            );

            return;
        }

        if ($statusClass === Declined::class) {
            $this->createTransitionEvent($proposal, Viewed::$name, Declined::$name, $closedAt, $demoUser);
            $this->createEvent(
                proposal: $proposal,
                type: 'rejected',
                occurredAt: $closedAt->addMinutes(5),
                payload: [
                    'reason' => 'Timing was not aligned with this quarter.',
                    'occurred_at' => $closedAt->addMinutes(5)->toAtomString(),
                    'referrer' => 'https://mail.proposalkit.test/inbox',
                ],
            );
        }
    }

    private function createTransitionEvent(
        Proposal $proposal,
        string $from,
        string $to,
        CarbonImmutable $occurredAt,
        User $actor,
    ): void {
        $this->createEvent(
            proposal: $proposal,
            type: 'transitioned',
            occurredAt: $occurredAt,
            payload: [
                'from' => $from,
                'to' => $to,
            ],
            actor: $actor,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function createEvent(
        Proposal $proposal,
        string $type,
        CarbonImmutable $occurredAt,
        array $payload,
        ?User $actor = null,
    ): void {
        ProposalEvent::query()->create([
            'proposal_id' => $proposal->id,
            'type' => $type,
            'payload' => $payload,
            'actor_type' => $actor instanceof User ? User::class : null,
            'actor_id' => $actor?->id,
            'ip' => $actor instanceof User ? null : sprintf('198.51.100.%d', 10 + ($proposal->id % 100)),
            'user_agent' => $actor instanceof User ? null : 'Mozilla/5.0 ProposalKit Demo Browser',
            'occurred_at' => $occurredAt,
        ]);
    }
}
