<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_login_and_logout_flow(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test Patient',
            'email' => 'patient@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(200);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'patient@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)->assertJsonStructure([
            'data' => ['token'],
        ]);

        $token = $response->json('data.token');

        $logout = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/auth/logout');

        $logout->assertStatus(200);
    }
}

