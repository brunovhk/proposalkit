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
        Schema::create('proposal_events', function (Blueprint $table): void {
            $table->id();
            $table
                ->foreignId('proposal_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('type');
            $table->json('payload')->nullable();
            $table->nullableMorphs('actor');
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('occurred_at')->index();

            $table->index(['proposal_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proposal_events');
    }
};
