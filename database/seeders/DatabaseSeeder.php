<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

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
        $tenant = Tenant::factory()->create([
            'name' => 'Acme Studio',
            'slug' => 'acme-studio',
        ]);

        User::factory()->for($tenant)->create([
            'name' => 'Acme Demo',
            'email' => 'demo@proposalkit.test',
            'password' => Hash::make('demo'),
        ]);

        $this->call([
            DemoSeeder::class,
        ]);
    }
}
