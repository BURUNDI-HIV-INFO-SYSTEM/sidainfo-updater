<?php

namespace Tests\Feature\Api;

use App\Models\Release;
use App\Models\Site;
use App\Models\SiteUpdateEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManifestTest extends TestCase
{
    use RefreshDatabase;

    private function createActiveRelease(array $overrides = []): Release
    {
        return Release::create(array_merge([
            'version'       => '1.2.41',
            'archive_name'  => 'RELEASE-1.2.41.zip',
            'file_path'     => 'releases/RELEASE-1.2.41.zip',
            'sha256'        => str_repeat('a', 64),
            'size_bytes'    => 1024 * 1024 * 50,
            'is_active'     => true,
            'published_at'  => now(),
        ], $overrides));
    }

    public function test_returns_404_when_no_active_release(): void
    {
        $response = $this->getJson('/laraupdater.json');
        $response->assertStatus(404);
    }

    public function test_returns_manifest_for_active_release(): void
    {
        $this->createActiveRelease();

        $response = $this->getJson('/laraupdater.json');

        $response->assertOk()
                 ->assertJsonStructure(['version', 'archive', 'description'])
                 ->assertJsonFragment(['version' => '1.2.41'])
                 ->assertJsonFragment(['archive' => 'RELEASE-1.2.41.zip']);
    }

    public function test_manifest_includes_sha256_when_present(): void
    {
        $this->createActiveRelease(['sha256' => str_repeat('b', 64)]);

        $response = $this->getJson('/laraupdater.json');
        $response->assertOk()->assertJsonStructure(['sha256']);
    }

    public function test_manifest_check_logs_event_for_known_site(): void
    {
        $this->createActiveRelease();
        Site::create([
            'siteid'    => '01010103',
            'site_name' => 'Test Site',
        ]);

        $this->getJson('/laraupdater.json?siteid=01010103&current_version=1.2.40');

        $this->assertDatabaseHas('site_update_events', [
            'siteid'     => '01010103',
            'event_type' => 'manifest_check',
            'status'     => 'checked',
        ]);
    }

    public function test_manifest_check_does_not_log_event_for_unknown_site(): void
    {
        $this->createActiveRelease();

        $this->getJson('/laraupdater.json?siteid=99999999');

        $this->assertDatabaseMissing('site_update_events', [
            'siteid' => '99999999',
        ]);
    }

    public function test_manifest_updates_site_last_checked_at(): void
    {
        $this->createActiveRelease();
        Site::create([
            'siteid'    => '01010103',
            'site_name' => 'Test Site',
        ]);

        $this->getJson('/laraupdater.json?siteid=01010103');

        $site = Site::find('01010103');
        $this->assertNotNull($site->last_checked_at);
        $this->assertEquals('checked', $site->last_status);
    }

    public function test_inactive_release_is_not_served(): void
    {
        Release::create([
            'version'      => '1.2.40',
            'archive_name' => 'RELEASE-1.2.40.zip',
            'file_path'    => 'releases/RELEASE-1.2.40.zip',
            'is_active'    => false,
            'published_at' => now(),
        ]);

        $response = $this->getJson('/laraupdater.json');
        $response->assertStatus(404);
    }
}
