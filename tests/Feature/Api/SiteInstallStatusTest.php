<?php

namespace Tests\Feature\Api;

use App\Models\Site;
use App\Models\SiteUpdateEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteInstallStatusTest extends TestCase
{
    use RefreshDatabase;

    private function makeSite(string $siteid = '01010103'): Site
    {
        return Site::create([
            'siteid'    => $siteid,
            'site_name' => 'Test Site',
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'siteid'          => '01010103',
            'status'          => 'installed',
            'current_version' => '1.2.41',
            'target_version'  => '1.2.41',
            'archive'         => 'RELEASE-1.2.41.zip',
            'message'         => 'Installation terminee',
        ], $overrides);
    }

    public function test_returns_201_for_valid_install_report(): void
    {
        $this->makeSite();

        $response = $this->postJson('/api/site-install-status', $this->payload());

        $response->assertStatus(201)->assertJson(['ok' => true]);
    }

    public function test_creates_install_event(): void
    {
        $this->makeSite();

        $this->postJson('/api/site-install-status', $this->payload());

        $this->assertDatabaseHas('site_update_events', [
            'siteid'         => '01010103',
            'event_type'     => 'install_report',
            'status'         => 'installed',
            'target_version' => '1.2.41',
        ]);
    }

    public function test_updates_site_version_on_install(): void
    {
        $this->makeSite();

        $this->postJson('/api/site-install-status', $this->payload([
            'status'          => 'installed',
            'current_version' => '1.2.41',
        ]));

        $site = Site::find('01010103');
        $this->assertEquals('1.2.41', $site->current_version);
        $this->assertEquals('installed', $site->last_status);
        $this->assertNotNull($site->last_installed_at);
    }

    public function test_records_failed_status(): void
    {
        $this->makeSite();

        $this->postJson('/api/site-install-status', $this->payload([
            'status'  => 'failed',
            'message' => 'SQL error on migration',
        ]));

        $site = Site::find('01010103');
        $this->assertEquals('failed', $site->last_status);

        $this->assertDatabaseHas('site_update_events', [
            'siteid'  => '01010103',
            'status'  => 'failed',
            'message' => 'SQL error on migration',
        ]);
    }

    public function test_returns_422_for_unknown_site(): void
    {
        $response = $this->postJson('/api/site-install-status', $this->payload([
            'siteid' => '99999999',
        ]));

        $response->assertStatus(422);
    }

    public function test_returns_401_when_token_required_but_missing(): void
    {
        config(['laraupdater.status_report_token' => 'secret123']);
        $this->makeSite();

        $response = $this->postJson('/api/site-install-status', $this->payload());
        $response->assertStatus(401);
    }

    public function test_accepts_correct_bearer_token(): void
    {
        config(['laraupdater.status_report_token' => 'secret123']);
        $this->makeSite();

        $response = $this->withToken('secret123')
                         ->postJson('/api/site-install-status', $this->payload());

        $response->assertStatus(201);
    }

    public function test_validation_requires_siteid_and_status(): void
    {
        $response = $this->postJson('/api/site-install-status', []);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['siteid', 'status']);
    }

    public function test_status_must_be_valid_enum_value(): void
    {
        $this->makeSite();

        $response = $this->postJson('/api/site-install-status', $this->payload([
            'status' => 'bad_status',
        ]));

        $response->assertStatus(422)->assertJsonValidationErrors(['status']);
    }
}
