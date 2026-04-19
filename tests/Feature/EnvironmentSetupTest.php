<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class EnvironmentSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_connection_is_alive(): void
    {
        $this->assertNotNull(DB::connection()->getPdo());
    }

    public function test_welcome_page_loads(): void
    {
        $this->get('/')->assertStatus(200);
    }
}
