<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds the default tenant-backed development account.
 */
final class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->seedDemoAccount();

        if ($this->isProductionEnvironment()) {
            $this->call([
                DemoLiveSeeder::class,
            ]);

            return;
        }

        if ($this->isDevelopmentEnvironment()) {
            $this->call([
                DemoSeeder::class,
            ]);
        }
    }

    private function seedDemoAccount(): void
    {
        $tenant = Tenant::query()->updateOrCreate(
            ['slug' => 'acme-studio'],
            ['name' => 'Acme Studio'],
        );

        User::query()->updateOrCreate(
            ['email' => 'demo@proposalkit.test'],
            [
                'tenant_id' => $tenant->id,
                'name' => 'Acme Demo',
                'password' => Hash::make('demo'),
            ],
        );
    }

    private function isProductionEnvironment(): bool
    {
        return config('app.env') === 'production';
    }

    private function isDevelopmentEnvironment(): bool
    {
        return in_array(config('app.env'), ['local', 'testing'], true);
    }
}
