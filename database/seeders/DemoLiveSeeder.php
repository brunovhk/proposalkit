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
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Rebuilds a realistic Acme Studio live demo tenant without requiring development factories.
 */
final class DemoLiveSeeder extends Seeder
{
    private const string DEMO_EMAIL = 'demo@proposalkit.test';

    private const int CLIENT_COUNT = 30;

    private const int PROPOSAL_COUNT = 200;

    private const array CLIENT_NAMES = [
        'AeroSpace Digital',
        'Apex Logistics',
        'ByteCraft Software',
        'Northstar Analytics',
        'Summit Retail Group',
        'Vector Health Systems',
        'Brightline Energy',
        'Cobalt Finance Partners',
        'Vertex Mobility',
        'Pioneer Food Labs',
        'Atlas Property Group',
        'Nova Education Network',
        'Clearwater Manufacturing',
        'SignalWorks Media',
        'Evergreen Hospitality',
        'Quantum Legal Services',
        'Harbor Freight Solutions',
        'BluePeak Insurance',
        'UrbanGrid Construction',
        'Luma Beauty Collective',
        'Ironwood Cybersecurity',
        'Meridian Travel Co',
        'Greenfield Agriculture',
        'Silverline Automotive',
        'CloudNine Consulting',
        'Horizon Event Group',
        'Catalyst Pharma',
        'Redwood Capital',
        'OpenWave Telecom',
        'Keystone Robotics',
    ];

    private const array CLIENT_ADDRESSES = [
        '100 Innovation Drive, Austin, TX 78701',
        '2400 Harbor Avenue, Seattle, WA 98101',
        '18 Market Street, San Francisco, CA 94105',
        '455 Peachtree Center Avenue, Atlanta, GA 30303',
        '901 Brickell Plaza, Miami, FL 33131',
        '77 Medical Parkway, Boston, MA 02115',
        '620 Energy Corridor, Houston, TX 77079',
        '12 Federal Street, New York, NY 10005',
        '300 Mobility Way, Detroit, MI 48226',
        '88 Research Boulevard, Raleigh, NC 27601',
        '501 Civic Center Drive, Denver, CO 80202',
        '44 Campus Circle, Madison, WI 53703',
        '1700 Industrial Road, Columbus, OH 43215',
        '725 Media Row, Nashville, TN 37203',
        '250 Hospitality Lane, Orlando, FL 32801',
        '33 Justice Avenue, Washington, DC 20001',
        '812 Freight Terminal Road, Memphis, TN 38103',
        '1100 Assurance Plaza, Hartford, CT 06103',
        '940 Builder Street, Phoenix, AZ 85004',
        '222 Magnolia Avenue, Charleston, SC 29401',
        '650 Security Park, Arlington, VA 22201',
        '410 Meridian Boulevard, Portland, OR 97204',
        '95 Harvest Road, Des Moines, IA 50309',
        '1200 Assembly Drive, Chicago, IL 60606',
        '711 Strategy Lane, Minneapolis, MN 55402',
        '35 Convention Center Way, Las Vegas, NV 89109',
        '19 Discovery Drive, Princeton, NJ 08540',
        '888 Redwood Street, Palo Alto, CA 94301',
        '460 Signal Parkway, Dallas, TX 75201',
        '5 Robotics Court, Pittsburgh, PA 15222',
    ];

    /**
     * Seed the live demo tenant, replacing only prior Acme Studio sandbox data.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $demoUser = User::query()
                ->where('email', self::DEMO_EMAIL)
                ->first();

            if (! $demoUser instanceof User) {
                throw new RuntimeException('The demo user [demo@proposalkit.test] must be seeded before DemoLiveSeeder runs.');
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

        for ($index = 0; $index < $this->clientCount(); $index++) {
            $name = self::CLIENT_NAMES[$index % count(self::CLIENT_NAMES)];
            $address = self::CLIENT_ADDRESSES[$index % count(self::CLIENT_ADDRESSES)];

            $clients[] = Client::query()->create([
                'tenant_id' => $tenant->id,
                'name' => $name,
                'email' => sprintf('contact-%02d@%s.test', $index + 1, Str::slug($name)),
                'tax_id' => sprintf('US%09d', $index + 1),
                'address' => $address,
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
        $clientCount = count($clients);
        $itemsToInsert = [];
        $eventsToInsert = [];
        $proposalCount = $this->proposalCount();

        for ($index = 0; $index < $proposalCount; $index++) {
            $client = $clients[$index % $clientCount];
            $statusClass = $this->proposalStatusForIndex($index);
            $createdAt = CarbonImmutable::now()
                ->subDays($proposalCount - $index)
                ->setTime(9 + ($index % 8), ($index * 7) % 60);

            /** @var Proposal $proposal */
            $proposal = Proposal::query()->create([
                'tenant_id' => $tenant->id,
                'client_id' => $client->id,
                'title' => sprintf('%s growth proposal', $client->name),
                'status' => $statusClass,
                'valid_until' => $createdAt->addDays(30),
            ]);

            $proposal->forceFill([
                'created_at' => $createdAt,
                'updated_at' => $this->updatedAtForStatus($createdAt, $statusClass, $index),
            ])->saveQuietly();

            $proposal->setRelation('client', $client);

            $itemCount = $this->createProposalItems($proposal, $services, $index, $itemsToInsert);
            $this->createProposalEvents($proposal, $demoUser, $statusClass, $createdAt, $index, $itemCount, $eventsToInsert);
        }

        if ($itemsToInsert !== []) {
            ProposalItem::query()->insert($itemsToInsert);
        }

        if ($eventsToInsert !== []) {
            ProposalEvent::query()->insert($eventsToInsert);
        }
    }

    private function clientCount(): int
    {
        return max(1, (int) config('demo.client_count', self::CLIENT_COUNT));
    }

    private function proposalCount(): int
    {
        return max(1, (int) config('demo.proposal_count', self::PROPOSAL_COUNT));
    }

    /**
     * @return class-string<ProposalState>
     */
    private function proposalStatusForIndex(int $index): string
    {
        return match ($index % 5) {
            1 => Sent::class,
            2 => Viewed::class,
            3 => Accepted::class,
            4 => Declined::class,
            default => Draft::class,
        };
    }

    /**
     * @param  class-string<ProposalState>  $statusClass
     */
    private function updatedAtForStatus(CarbonImmutable $createdAt, string $statusClass, int $proposalIndex): CarbonImmutable
    {
        return match ($statusClass) {
            Sent::class => $createdAt->addDays(1),
            Viewed::class => $createdAt->addDays(2),
            Accepted::class, Declined::class => $createdAt->addDays(3 + ($proposalIndex % 8)),
            default => $createdAt->addHours(1 + ($proposalIndex % 12)),
        };
    }

    /**
     * @param  list<Service>  $services
     * @param  list<array{proposal_id: int, name: string, price: int, type: string, quantity: int, created_at: string, updated_at: string}>  $itemsToInsert
     */
    private function createProposalItems(Proposal $proposal, array $services, int $proposalIndex, array &$itemsToInsert): int
    {
        $itemCount = 2 + ($proposalIndex % 3);
        $timestamp = CarbonImmutable::parse((string) $proposal->created_at)->toDateTimeString();

        for ($itemIndex = 0; $itemIndex < $itemCount; $itemIndex++) {
            $service = $services[($proposalIndex + $itemIndex) % count($services)];
            $quantity = $service->type === ServiceType::Hourly
                ? 8 + (($proposalIndex + $itemIndex) % 32)
                : 1;

            $itemsToInsert[] = [
                'proposal_id' => $proposal->id,
                'name' => $service->name,
                'price' => $service->price,
                'type' => $service->type->value,
                'quantity' => $quantity,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        return $itemCount;
    }

    /**
     * @param  class-string<ProposalState>  $statusClass
     * @param  list<array{proposal_id: int, type: string, payload: string, actor_type: class-string<User>|null, actor_id: int|null, ip: string|null, user_agent: string|null, occurred_at: string}>  $eventsToInsert
     *
     * @throws \JsonException
     */
    private function createProposalEvents(
        Proposal $proposal,
        User $demoUser,
        string $statusClass,
        CarbonImmutable $createdAt,
        int $proposalIndex,
        int $itemCount,
        array &$eventsToInsert,
    ): void {
        $this->createEvent(
            eventsToInsert: $eventsToInsert,
            proposal: $proposal,
            type: 'created',
            occurredAt: $createdAt,
            payload: [
                'title' => $proposal->title,
                'client_id' => $proposal->client_id,
                'item_count' => $itemCount,
            ],
            actor: $demoUser,
        );

        if ($statusClass === Draft::class) {
            return;
        }

        $sentAt = $createdAt->addHours(2 + ($proposalIndex % 6));
        $this->createTransitionEvent($eventsToInsert, $proposal, Draft::$name, Sent::$name, $sentAt, $demoUser);

        if ($statusClass === Sent::class) {
            return;
        }

        $viewedAt = $sentAt->addHours(2 + ($proposalIndex % 6));
        $this->createTransitionEvent($eventsToInsert, $proposal, Sent::$name, Viewed::$name, $viewedAt, $demoUser);
        $this->createEvent(
            eventsToInsert: $eventsToInsert,
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
            $this->createTransitionEvent($eventsToInsert, $proposal, Viewed::$name, Accepted::$name, $closedAt, $demoUser);
            $this->createEvent(
                eventsToInsert: $eventsToInsert,
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
            $this->createTransitionEvent($eventsToInsert, $proposal, Viewed::$name, Declined::$name, $closedAt, $demoUser);
            $this->createEvent(
                eventsToInsert: $eventsToInsert,
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

    /**
     * @param  list<array{proposal_id: int, type: string, payload: string, actor_type: class-string<User>|null, actor_id: int|null, ip: string|null, user_agent: string|null, occurred_at: string}>  $eventsToInsert
     *
     * @throws \JsonException
     */
    private function createTransitionEvent(
        array &$eventsToInsert,
        Proposal $proposal,
        string $from,
        string $to,
        CarbonImmutable $occurredAt,
        User $actor,
    ): void {
        $this->createEvent(
            eventsToInsert: $eventsToInsert,
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
     * @param  list<array{proposal_id: int, type: string, payload: string, actor_type: class-string<User>|null, actor_id: int|null, ip: string|null, user_agent: string|null, occurred_at: string}>  $eventsToInsert
     * @param  array<string, mixed>  $payload
     *
     * @throws \JsonException
     */
    private function createEvent(
        array &$eventsToInsert,
        Proposal $proposal,
        string $type,
        CarbonImmutable $occurredAt,
        array $payload,
        ?User $actor = null,
    ): void {
        $eventsToInsert[] = [
            'proposal_id' => $proposal->id,
            'type' => $type,
            'payload' => json_encode($payload, JSON_THROW_ON_ERROR),
            'actor_type' => $actor instanceof User ? User::class : null,
            'actor_id' => $actor?->id,
            'ip' => $actor instanceof User ? null : sprintf('198.51.100.%d', 10 + ($proposal->id % 100)),
            'user_agent' => $actor instanceof User ? null : 'Mozilla/5.0 ProposalKit Demo Browser',
            'occurred_at' => $occurredAt->toDateTimeString(),
        ];
    }
}
