<?php

namespace Tests\Feature\Admin;

use App\Models\BackupDestination;
use App\Models\User;
use App\Services\Backups\BackupManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BackupTest extends TestCase
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

    public function test_backups_page_is_accessible(): void
    {
        $this->actingAs($this->admin())
            ->get('/backups')
            ->assertOk()
            ->assertSee('Backup scope');
    }

    public function test_admin_can_create_gmail_backup_destination(): void
    {
        $this->actingAs($this->admin())
            ->post('/backups', [
                'name' => 'Main Gmail',
                'driver' => 'gmail',
                'recipient_email' => 'archive@gmail.com',
                'is_active' => '1',
            ])
            ->assertRedirect('/backups');

        $destination = BackupDestination::first();

        $this->assertNotNull($destination);
        $this->assertSame('gmail', $destination->driver);
        $this->assertSame('archive@gmail.com', $destination->config['recipient_email']);
    }

    public function test_admin_can_update_onedrive_destination(): void
    {
        $destination = BackupDestination::create([
            'name' => 'Cloud Backup',
            'driver' => 'onedrive',
            'is_active' => true,
            'config' => [
                'tenant_id' => 'common',
                'client_id' => 'old-client',
                'client_secret' => 'old-secret',
                'refresh_token' => 'old-refresh',
                'folder_path' => 'Backups',
            ],
        ]);

        $this->actingAs($this->admin())
            ->put("/backups/{$destination->id}", [
                'name' => 'Cloud Backup Updated',
                'driver' => 'onedrive',
                'tenant_id' => 'tenant-123',
                'client_id' => 'client-123',
                'client_secret' => '',
                'refresh_token' => '',
                'folder_path' => 'Backups/SIDAInfo',
                'is_active' => '1',
            ])
            ->assertRedirect("/backups/{$destination->id}/edit");

        $destination->refresh();

        $this->assertSame('Cloud Backup Updated', $destination->name);
        $this->assertSame('tenant-123', $destination->config['tenant_id']);
        $this->assertSame('client-123', $destination->config['client_id']);
        $this->assertSame('old-secret', $destination->config['client_secret']);
        $this->assertSame('old-refresh', $destination->config['refresh_token']);
        $this->assertSame('Backups/SIDAInfo', $destination->config['folder_path']);
    }

    public function test_running_backup_updates_destination_status(): void
    {
        $destination = BackupDestination::create([
            'name' => 'Main Gmail',
            'driver' => 'gmail',
            'is_active' => true,
            'config' => [
                'recipient_email' => 'archive@gmail.com',
            ],
        ]);

        $this->mock(BackupManager::class, function ($mock) use ($destination) {
            $mock->shouldReceive('run')
                ->once()
                ->withArgs(fn (BackupDestination $given) => $given->is($destination))
                ->andReturn([
                    'filename' => 'backup.zip',
                    'size_bytes' => 1024,
                    'message' => 'Backup emailed successfully.',
                ]);
        });

        $this->actingAs($this->admin())
            ->post("/backups/{$destination->id}/run")
            ->assertRedirect('/backups');

        $destination->refresh();

        $this->assertSame('success', $destination->last_status);
        $this->assertSame('backup.zip', $destination->last_backup_filename);
        $this->assertSame('Backup emailed successfully.', $destination->last_message);
    }
}
