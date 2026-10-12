<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Proposal;
use App\Models\ProposalEvent;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

final class DemoSeederTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function tearDown(): void
    {
        Date::setTestNow();

        parent::tearDown();
    }

    public function test_demo_seeder_is_idempotent_and_rebuilds_only_the_demo_tenant(): void
    {
        Date::setTestNow('2026-05-21 12:00:00');
        config([
            'demo.client_count' => 3,
            'demo.proposal_count' => 12,
        ]);

        $otherTenant = Tenant::factory()->create(['name' => 'Other Tenant']);
        $otherUser = User::factory()->for($otherTenant)->create(['email' => 'owner@example.test']);

        $this->seed(DatabaseSeeder::class);

        $originalTenant = Tenant::query()
            ->where('slug', 'acme-studio')
            ->firstOrFail();

        $originalDemoUser = User::query()
            ->whereBelongsTo($originalTenant)
            ->where('email', 'demo@proposalkit.test')
            ->firstOrFail();

        $this->seed(DemoSeeder::class);
        $this->seed(DemoSeeder::class);

        $tenant = Tenant::query()
            ->where('slug', 'acme-studio')
            ->firstOrFail();

        $demoUser = User::query()
            ->whereBelongsTo($tenant)
            ->where('email', 'demo@proposalkit.test')
            ->firstOrFail();

        $proposalIds = Proposal::query()
            ->whereBelongsTo($tenant)
            ->pluck('id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();

        $this->assertSame('Acme Studio', $tenant->name);
        $this->assertSame($originalTenant->id, $tenant->id);
        $this->assertSame($originalDemoUser->id, $demoUser->id);
        $this->assertTrue(Hash::check('demo', $demoUser->password));
        $this->assertSame(1, User::query()->where('email', 'demo@proposalkit.test')->count());
        $this->assertSame(3, Client::query()->whereBelongsTo($tenant)->count());
        $this->assertSame(10, Service::query()->whereBelongsTo($tenant)->count());
        $this->assertSame(12, Proposal::query()->whereBelongsTo($tenant)->count());
        $this->assertGreaterThan(12, ProposalEvent::query()->whereIn('proposal_id', $proposalIds)->count());
        $this->assertDatabaseHas('users', [
            'id' => $otherUser->id,
            'tenant_id' => $otherTenant->id,
            'email' => 'owner@example.test',
        ]);
    }

    public function test_demo_seeder_requires_the_primary_demo_user(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The demo user [demo@proposalkit.test] must be seeded before DemoSeeder runs.');

        $this->seed(DemoSeeder::class);
    }

    public function test_database_seeder_uses_deterministic_live_demo_data_in_production(): void
    {
        Date::setTestNow('2026-05-21 12:00:00');
        config([
            'app.env' => 'production',
            'demo.client_count' => 3,
            'demo.proposal_count' => 12,
        ]);

        $this->seed(DatabaseSeeder::class);

        $tenant = Tenant::query()
            ->where('slug', 'acme-studio')
            ->firstOrFail();

        $proposalIds = Proposal::query()
            ->whereBelongsTo($tenant)
            ->pluck('id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();

        $this->assertSame(3, Client::query()->whereBelongsTo($tenant)->count());
        $this->assertSame(10, Service::query()->whereBelongsTo($tenant)->count());
        $this->assertSame(12, Proposal::query()->whereBelongsTo($tenant)->count());
        $this->assertGreaterThan(12, ProposalEvent::query()->whereIn('proposal_id', $proposalIds)->count());
        $this->assertDatabaseHas('clients', [
            'tenant_id' => $tenant->id,
            'name' => 'AeroSpace Digital',
            'email' => 'contact-01@aerospace-digital.test',
            'tax_id' => 'US000000001',
            'address' => '100 Innovation Drive, Austin, TX 78701',
        ]);
    }
}
