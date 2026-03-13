<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        return User::create([
            'name'     => 'Admin',
            'email'    => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);
    }

    public function test_login_page_is_accessible(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_dashboard_redirects_unauthenticated_user(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_releases_redirects_unauthenticated_user(): void
    {
        $this->get('/releases')->assertRedirect('/login');
    }

    public function test_sites_redirects_unauthenticated_user(): void
    {
        $this->get('/sites')->assertRedirect('/login');
    }

    public function test_admin_can_login(): void
    {
        $this->createAdmin();

        $response = $this->post('/login', [
            'email'    => 'admin@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/');
        $this->assertAuthenticated();
    }

    public function test_invalid_credentials_rejected(): void
    {
        $this->createAdmin();

        $response = $this->post('/login', [
            'email'    => 'admin@example.com',
            'password' => 'wrong',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_authenticated_user_can_access_dashboard(): void
    {
        $this->actingAs($this->createAdmin())
             ->get('/')
             ->assertOk();
    }

    public function test_logout_works(): void
    {
        $this->actingAs($this->createAdmin())
             ->post('/logout')
             ->assertRedirect('/login');

        $this->assertGuest();
    }
}
