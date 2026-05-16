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
        Schema::create('proposals', function (Blueprint $table): void {
            $table->id();
            $table
                ->foreignId('tenant_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table
                ->foreignId('client_id')
                ->constrained()
                ->restrictOnDelete();
            $table->string('title');
            $table->string('status');
            $table->date('valid_until')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proposals');
    }
};
