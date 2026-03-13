<?php

namespace Tests\Feature\Admin;

use App\Models\Release;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReleaseTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'name'     => 'Admin',
            'email'    => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);
    }

    private function createRelease(array $overrides = []): Release
    {
        return Release::create(array_merge([
            'version'      => '1.2.40',
            'archive_name' => 'RELEASE-1.2.40.zip',
            'file_path'    => 'releases/RELEASE-1.2.40.zip',
            'is_active'    => false,
            'published_at' => now(),
        ], $overrides));
    }

    public function test_releases_index_is_accessible(): void
    {
        $this->actingAs($this->admin())
             ->get('/releases')
             ->assertOk();
    }

    public function test_release_create_form_is_accessible(): void
    {
        $this->actingAs($this->admin())
             ->get('/releases/new')
             ->assertOk();
    }

    public function test_release_can_be_uploaded(): void
    {
        Storage::fake('local');

        $this->actingAs($this->admin())
             ->post('/releases', [
                 'version'      => '1.2.41',
                 'zip_file'     => UploadedFile::fake()->create('RELEASE-1.2.41.zip', 1024),
                 'notes'        => 'Test release',
                 'published_at' => now()->format('Y-m-d\TH:i'),
             ])
             ->assertRedirect('/releases');

        $this->assertDatabaseHas('releases', ['version' => '1.2.41']);
        Storage::assertExists('releases/RELEASE-1.2.41.zip');
    }

    public function test_duplicate_version_is_rejected(): void
    {
        Storage::fake('local');
        $this->createRelease(['version' => '1.2.41']);

        $this->actingAs($this->admin())
             ->post('/releases', [
                 'version'  => '1.2.41',
                 'zip_file' => UploadedFile::fake()->create('file.zip', 100),
             ])
             ->assertSessionHasErrors('version');
    }

    public function test_release_can_be_activated(): void
    {
        $r1 = $this->createRelease(['version' => '1.2.40', 'is_active' => true]);
        $r2 = $this->createRelease(['version' => '1.2.41']);

        $this->actingAs($this->admin())
             ->post("/releases/{$r2->id}/activate")
             ->assertRedirect('/releases');

        $this->assertFalse(Release::find($r1->id)->is_active);
        $this->assertTrue(Release::find($r2->id)->is_active);
    }

    public function test_active_release_cannot_be_deleted(): void
    {
        $release = $this->createRelease(['is_active' => true]);

        $this->actingAs($this->admin())
             ->delete("/releases/{$release->id}")
             ->assertRedirect();

        $this->assertDatabaseHas('releases', ['id' => $release->id]);
    }

    public function test_inactive_release_can_be_deleted(): void
    {
        Storage::fake('local');
        $release = $this->createRelease(['is_active' => false]);

        $this->actingAs($this->admin())
             ->delete("/releases/{$release->id}")
             ->assertRedirect('/releases');

        $this->assertDatabaseMissing('releases', ['id' => $release->id]);
    }

    public function test_release_show_page_displays_adoption(): void
    {
        $release = $this->createRelease(['version' => '1.2.40']);

        $this->actingAs($this->admin())
             ->get("/releases/{$release->id}")
             ->assertOk()
             ->assertSee('1.2.40');
    }
}
