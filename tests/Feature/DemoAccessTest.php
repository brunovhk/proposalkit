<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\SystemUserEmail;
use App\Models\Client;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

final class DemoAccessTest extends TestCase
{
    use LazilyRefreshDatabase;

    private const string DEMO_WARNING_MESSAGE = 'Create, Edit, and Delete operations are disabled in the Live Demo.';

    public function test_public_demo_login_authenticates_seeded_demo_user(): void
    {
        $demoUser = User::factory()->create([
            'email' => SystemUserEmail::Demo->value,
        ]);

        $this
            ->post('/demo/login')
            ->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($demoUser);
    }

    public function test_demo_user_can_modify_workspace_data_when_live_demo_is_disabled(): void
    {
        config(['app.live_demo_enabled' => false]);

        $demoUser = User::factory()->create([
            'email' => SystemUserEmail::Demo->value,
        ]);

        $this
            ->actingAs($demoUser)
            ->post('/clients', [
                'name' => 'Local Demo Mutation',
                'email' => 'local-demo@example.test',
            ])
            ->assertRedirect('/clients');

        $this->assertDatabaseHas('clients', [
            'name' => 'Local Demo Mutation',
            'email' => 'local-demo@example.test',
        ]);
    }

    public function test_demo_user_cannot_modify_workspace_data_when_live_demo_is_enabled(): void
    {
        config(['app.live_demo_enabled' => true]);

        $demoUser = User::factory()->create([
            'email' => SystemUserEmail::Demo->value,
        ]);

        $this
            ->from('/clients')
            ->actingAs($demoUser)
            ->post('/clients', [
                'name' => 'Sandbox Mutation',
                'email' => 'sandbox@example.test',
            ])
            ->assertRedirect('/clients')
            ->assertSessionHas('warning', self::DEMO_WARNING_MESSAGE);

        $this->assertDatabaseMissing('clients', [
            'name' => 'Sandbox Mutation',
            'email' => 'sandbox@example.test',
        ]);
    }

    public function test_profile_management_writes_are_blocked_when_live_demo_is_enabled(): void
    {
        config(['app.live_demo_enabled' => true]);

        $demoUser = User::factory()->create([
            'name' => 'Demo Profile',
            'email' => SystemUserEmail::Demo->value,
        ]);

        $this
            ->from('/settings/profile')
            ->actingAs($demoUser)
            ->patch(route('profile.update'), [
                'name' => 'Changed Demo Profile',
            ])
            ->assertRedirect('/settings/profile')
            ->assertSessionHas('warning', self::DEMO_WARNING_MESSAGE);

        $demoUser->refresh();

        $this->assertSame('Demo Profile', $demoUser->name);

        $this
            ->from('/settings/security')
            ->actingAs($demoUser)
            ->put(route('user-password.update'), [
                'current_password' => 'password',
                'password' => 'changed-password',
                'password_confirmation' => 'changed-password',
            ])
            ->assertRedirect('/settings/security')
            ->assertSessionHas('warning', self::DEMO_WARNING_MESSAGE);

        $demoUser->refresh();

        $this->assertTrue(Hash::check('password', $demoUser->password));

        $this
            ->from('/settings/profile')
            ->actingAs($demoUser)
            ->delete(route('profile.destroy'), [
                'password' => 'password',
            ])
            ->assertRedirect('/settings/profile')
            ->assertSessionHas('warning', self::DEMO_WARNING_MESSAGE);

        $this->assertDatabaseHas('users', [
            'id' => $demoUser->id,
            'email' => SystemUserEmail::Demo->value,
        ]);
    }

    public function test_inertia_flash_props_expose_supported_session_messages(): void
    {
        $demoUser = User::factory()->create([
            'email' => SystemUserEmail::Demo->value,
        ]);

        $this
            ->actingAs($demoUser)
            ->withSession([
                'success' => 'Saved.',
                'warning' => self::DEMO_WARNING_MESSAGE,
                'error' => 'Unable to save.',
            ])
            ->get('/clients')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->where('flash.success', 'Saved.')
                ->where('flash.warning', self::DEMO_WARNING_MESSAGE)
                ->where('flash.error', 'Unable to save.'));
    }

    public function test_demo_user_can_read_workspace_data(): void
    {
        $demoUser = User::factory()->create([
            'email' => SystemUserEmail::Demo->value,
        ]);

        $tenant = $demoUser->tenant;
        assert($tenant instanceof Tenant);

        Client::factory()->for($tenant)->create([
            'name' => 'Readable Sandbox Client',
        ]);

        $this
            ->actingAs($demoUser)
            ->get('/clients')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->component('clients/Index')
                ->has('clients.data', 1)
                ->where('clients.data.0.name', 'Readable Sandbox Client'));
    }
}
