<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
        ]);
    }

    public function test_users_page_is_accessible(): void
    {
        $this->actingAs($this->admin())
            ->get('/users')
            ->assertOk()
            ->assertSee('Add User');
    }

    public function test_admin_can_create_user(): void
    {
        $this->actingAs($this->admin())
            ->post('/users', [
                'name' => 'Backup User',
                'email' => 'backup@example.com',
                'password' => 'secret123',
                'password_confirmation' => 'secret123',
            ])
            ->assertRedirect('/users');

        $this->assertDatabaseHas('users', [
            'email' => 'backup@example.com',
            'name' => 'Backup User',
        ]);
    }

    public function test_admin_can_update_user_and_password(): void
    {
        $admin = $this->admin();
        $user = User::create([
            'name' => 'User One',
            'email' => 'user1@example.com',
            'password' => Hash::make('password123'),
        ]);

        $this->actingAs($admin)
            ->put("/users/{$user->id}", [
                'name' => 'User Two',
                'email' => 'user2@example.com',
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ])
            ->assertRedirect("/users/{$user->id}/edit");

        $user->refresh();

        $this->assertSame('User Two', $user->name);
        $this->assertSame('user2@example.com', $user->email);
        $this->assertTrue(Hash::check('newpassword123', $user->password));
    }

    public function test_user_can_update_profile_and_password(): void
    {
        $user = $this->admin();

        $this->actingAs($user)
            ->put('/profile', [
                'name' => 'Admin Updated',
                'email' => 'admin-updated@example.com',
            ])
            ->assertRedirect('/profile');

        $this->actingAs($user->fresh())
            ->put('/profile/password', [
                'current_password' => 'password123',
                'password' => 'freshpassword123',
                'password_confirmation' => 'freshpassword123',
            ])
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Admin Updated', $user->name);
        $this->assertSame('admin-updated@example.com', $user->email);
        $this->assertTrue(Hash::check('freshpassword123', $user->password));
    }
}
