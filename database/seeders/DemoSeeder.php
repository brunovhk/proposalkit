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
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Rebuilds a realistic Acme Studio sandbox tenant for analytics and public demo flows.
 */
final class DemoSeeder extends Seeder
{
    private const string DEMO_EMAIL = 'demo@proposalkit.test';

    private const int CLIENT_COUNT = 30;

    private const int PROPOSAL_COUNT = 80;

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
        $companyNames = [
            'Northstar Clinics',
            'BrightLedger Finance',
            'Atlas Roasters',
            'Cobalt Labs',
            'Juniper Events',
            'Pioneer Legal',
            'Summit Travel Co',
            'UrbanNest Realty',
            'Nimbus Learning',
            'Harbor Dental Group',
            'Luma Fitness',
            'Evergreen Foods',
            'Vector Supply',
            'Oakline Architecture',
            'Beacon HR',
            'Quartz Security',
            'Fieldstone Builders',
            'Mosaic Apparel',
            'Silverline Logistics',
            'Bluebird Hospitality',
            'Signal Commerce',
            'Opal Wellness',
            'Meridian Solar',
            'Hearth & Table',
            'Pulse Recruiting',
            'Keystone Advisors',
            'Aperture Media',
            'Riverbend Education',
            'Forge Industrial',
            'Clearwater Insurance',
        ];

        $clients = [];

        foreach ($companyNames as $index => $name) {
            $clients[] = Client::query()->create([
                'tenant_id' => $tenant->id,
                'name' => $name,
                'email' => sprintf('contact+%02d@%s.test', $index + 1, Str::slug($name)),
                'tax_id' => sprintf('%02d.%03d.%03d/0001-%02d', 10 + $index, 100 + $index, 700 + $index, 20 + $index),
                'address' => sprintf('%d Market Street, Suite %d', 100 + ($index * 17), 200 + $index),
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
        $now = CarbonImmutable::parse(Date::now()->toDateTimeString(), Date::now()->getTimezone());

        for ($index = 0; $index < self::PROPOSAL_COUNT; $index++) {
            $statusClass = $this->statusForIndex($index);
            $createdAt = $now
                ->subDays(180 - (int) floor($index * 170 / (self::PROPOSAL_COUNT - 1)))
                ->subHours($index % 9);

            $client = $clients[$index % self::CLIENT_COUNT];

            $proposal = Proposal::query()->create([
                'tenant_id' => $tenant->id,
                'client_id' => $client->id,
                'title' => sprintf('%s growth proposal', $client->name),
                'status' => $statusClass,
                'valid_until' => $createdAt->addDays(45 + ($index % 20))->toDateString(),
            ]);

            $proposal->timestamps = false;
            $proposal->forceFill([
                'created_at' => $createdAt,
                'updated_at' => $this->updatedAtForStatus($statusClass, $createdAt, $index),
            ])->save();
            $proposal->timestamps = true;

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

        $sentAt = $createdAt->addDays(1 + ($proposalIndex % 5));
        $this->createTransitionEvent($proposal, Draft::$name, Sent::$name, $sentAt, $demoUser);

        if ($statusClass === Sent::class) {
            return;
        }

        $viewedAt = $sentAt->addDays(1 + ($proposalIndex % 4))->addHours($proposalIndex % 6);
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

        $closedAt = $viewedAt->addDays(2 + ($proposalIndex % 18))->addHours($proposalIndex % 5);

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

    /**
     * @return class-string<ProposalState>
     */
    private function statusForIndex(int $index): string
    {
        return match (true) {
            $index < 12 => Draft::class,
            $index < 30 => Sent::class,
            $index < 46 => Viewed::class,
            $index < 70 => Accepted::class,
            default => Declined::class,
        };
    }

    /**
     * @param  class-string<ProposalState>  $statusClass
     */
    private function updatedAtForStatus(string $statusClass, CarbonImmutable $createdAt, int $proposalIndex): CarbonImmutable
    {
        return match ($statusClass) {
            Draft::class => $createdAt->addHours(2 + ($proposalIndex % 12)),
            Sent::class => $createdAt->addDays(1 + ($proposalIndex % 5)),
            Viewed::class => $createdAt->addDays(3 + ($proposalIndex % 9)),
            Accepted::class, Declined::class => $createdAt->addDays(7 + ($proposalIndex % 18)),
            default => $createdAt,
        };
    }
}
