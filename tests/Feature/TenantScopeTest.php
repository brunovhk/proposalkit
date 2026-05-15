<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_only_queries_users_from_their_tenant(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $userA = User::factory()->for($tenantA)->create();
        $userB = User::factory()->for($tenantB)->create();

        $visibleUserIds = $this
            ->actingAs($userA)
            ->getVisibleUserIds();

        $this->assertContains($userA->id, $visibleUserIds);
        $this->assertNotContains($userB->id, $visibleUserIds);
    }

    public function test_authenticated_user_cannot_find_user_from_another_tenant(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $userA = User::factory()->for($tenantA)->create();
        $userB = User::factory()->for($tenantB)->create();

        $this->actingAs($userA);

        $this->assertNull(User::query()->find($userB->id));
    }

    public function test_authenticated_user_cannot_update_user_from_another_tenant(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $userA = User::factory()->for($tenantA)->create();
        $userB = User::factory()->for($tenantB)->create([
            'name' => 'Original Name',
        ]);

        $this->actingAs($userA);

        $updatedRows = User::query()
            ->whereKey($userB->id)
            ->update(['name' => 'Compromised Name']);

        $this->assertSame(0, $updatedRows);
        $this->assertDatabaseHas('users', [
            'id' => $userB->id,
            'name' => 'Original Name',
        ]);
    }

    /**
     * Get the user IDs visible to the currently authenticated user.
     *
     * @return list<int>
     */
    private function getVisibleUserIds(): array
    {
        return array_values(array_map(
            static fn (mixed $id): int => (int) $id,
            User::query()
                ->pluck('id')
                ->all(),
        ));
    }
}
