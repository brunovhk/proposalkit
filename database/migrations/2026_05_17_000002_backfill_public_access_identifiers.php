<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $usedSlugs = [];

        DB::table('tenants')
            ->orderBy('id')
            ->get(['id', 'name', 'slug'])
            ->each(function (object $tenant) use (&$usedSlugs): void {
                $existingSlug = is_string($tenant->slug) ? $tenant->slug : '';

                if ($existingSlug !== '') {
                    $usedSlugs[$existingSlug] = true;

                    return;
                }

                $baseSlug = Str::slug((string) $tenant->name);

                if ($baseSlug === '') {
                    $baseSlug = "tenant-{$tenant->id}";
                }

                $slug = $baseSlug;
                $suffix = 2;

                while (isset($usedSlugs[$slug])) {
                    $slug = "{$baseSlug}-{$suffix}";
                    $suffix++;
                }

                DB::table('tenants')
                    ->where('id', $tenant->id)
                    ->update(['slug' => $slug]);

                $usedSlugs[$slug] = true;
            });

        DB::table('proposals')
            ->whereNull('secure_token')
            ->orderBy('id')
            ->get(['id'])
            ->each(static function (object $proposal): void {
                DB::table('proposals')
                    ->where('id', $proposal->id)
                    ->update(['secure_token' => (string) Str::uuid7()]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('tenants')->update(['slug' => null]);
        DB::table('proposals')->update(['secure_token' => null]);
    }
};
