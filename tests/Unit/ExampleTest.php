<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_starts_without_users(): void
    {
        $this->assertDatabaseCount('users', 0);
    }
}
