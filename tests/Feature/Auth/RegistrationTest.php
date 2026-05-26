<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Inertia\Testing\AssertableInertia;
use Laravel\Fortify\Features;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('app.live_demo_enabled', false);

        $this->skipUnlessFortifyHas(Features::registration());
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get(route('register'));

        $response
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->component('auth/Register'));
    }

    public function test_registration_screen_returns_not_found_when_live_demo_is_enabled(): void
    {
        Config::set('app.live_demo_enabled', true);

        $this
            ->get(route('register'))
            ->assertNotFound();
    }

    public function test_login_screen_hides_registration_link_when_live_demo_is_enabled(): void
    {
        Config::set('app.live_demo_enabled', true);

        $this
            ->get(route('login'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->component('auth/Login')
                ->where('live_demo_enabled', true)
                ->where('canRegister', false));
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Acme Studio',
            'email' => 'demo@proposalkit.test',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        $user = Auth::user();

        assert($user instanceof User);

        $this->assertDatabaseHas('tenants', [
            'id' => $user->tenant_id,
            'name' => "Acme Studio's Company",
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'demo@proposalkit.test',
            'tenant_id' => $user->tenant_id,
        ]);
    }

    public function test_new_users_cannot_register_when_live_demo_is_enabled(): void
    {
        Config::set('app.live_demo_enabled', true);

        $this
            ->post(route('register.store'), [
                'name' => 'Blocked User',
                'email' => 'blocked@example.test',
                'password' => 'password',
                'password_confirmation' => 'password',
            ])
            ->assertNotFound();

        $this->assertGuest();

        $this->assertDatabaseMissing('users', [
            'email' => 'blocked@example.test',
        ]);
    }
}
