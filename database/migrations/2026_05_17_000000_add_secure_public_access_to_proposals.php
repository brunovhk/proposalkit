<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            if (! Schema::hasColumn('tenants', 'slug')) {
                $table->string('slug')->nullable()->unique()->after('name');
            }
        });

        Schema::table('proposals', function (Blueprint $table): void {
            if (! Schema::hasColumn('proposals', 'secure_token')) {
                $table->uuid('secure_token')->nullable()->unique()->after('uuid');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proposals', function (Blueprint $table): void {
            if (Schema::hasColumn('proposals', 'secure_token')) {
                $table->dropUnique(['secure_token']);
                $table->dropColumn('secure_token');
            }
        });

        Schema::table('tenants', function (Blueprint $table): void {
            if (Schema::hasColumn('tenants', 'slug')) {
                $table->dropUnique(['slug']);
                $table->dropColumn('slug');
            }
        });
    }
};
