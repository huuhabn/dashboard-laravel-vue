<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_spa_shell_is_served(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertViewIs('app');
    }

    public function test_home_spa_shell_is_served(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertViewIs('app');
    }
}
