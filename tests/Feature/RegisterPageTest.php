<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_page_is_available(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
        $response->assertSee('Join OpenShelf');
    }

    public function test_a_new_user_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Aisha Rahman',
            'email' => 'aisha@example.com',
            'department' => 'CSE',
            'session' => '2021-22',
            'phone' => '01712345678',
            'roomNumber' => 'A-101',
            'hall' => '1',
            'password' => 'StrongPass123',
            'password_confirmation' => 'StrongPass123',
            'terms' => 'on',
        ]);

        $response->assertRedirect('/register/verify');
        $this->assertDatabaseHas('users', [
            'email' => 'aisha@example.com',
            'department' => 'CSE',
            'status' => 'unverified',
        ]);
    }
}
