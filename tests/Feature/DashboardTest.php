<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_spa_shell_is_served_for_guests(): void
    {
        $path = '/'.config('services.dashboard.prefix').'/dashboard';

        $this->get($path)
            ->assertOk()
            ->assertViewIs('app');
    }
}
