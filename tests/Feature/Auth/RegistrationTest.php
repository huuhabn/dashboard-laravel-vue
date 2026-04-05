<?php

namespace Tests\Feature\Auth;

use App\Modules\Api\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_spa_shell_is_served(): void
    {
        $this->get('/register')
            ->assertOk()
            ->assertViewIs('app');
    }

    public function test_new_users_can_register_via_api(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'device_name' => 'phpunit',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['token', 'user']);

        $this->assertInstanceOf(User::class, User::query()->where('email', 'test@example.com')->first());
    }
}
