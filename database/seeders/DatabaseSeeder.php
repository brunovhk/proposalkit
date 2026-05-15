<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Seeds the default tenant-backed development account.
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $tenant = Tenant::factory()->create([
            'name' => 'ProposalKit Demo Company',
        ]);

        User::factory()->for($tenant)->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}
