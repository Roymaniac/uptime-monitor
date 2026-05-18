<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Monitor;
use App\Models\MonitorCheck;
use App\Jobs\CheckMonitorJob;
use Illuminate\Support\Facades\Queue;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MonitorControllerTest extends TestCase
{
    use RefreshDatabase;

    // -----------------------------------------------------------------------
    // POST /api/monitors
    // -----------------------------------------------------------------------

    public function test_can_create_a_monitor(): void
    {
        Queue::fake();

        $response = $this->postJson('/api/monitors', [
            'url'            => 'https://example.com',
            'check_interval' => 5,
            'threshold'      => 3,
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'url',
                    'check_interval',
                    'threshold',
                    'status',
                    'last_checked_at',
                    'uptime_percentage',
                    'created_at',
                ],
            ])
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.url', 'https://example.com');

        $this->assertDatabaseHas('monitors', ['url' => 'https://example.com']);

        Queue::assertPushed(CheckMonitorJob::class);
    }

    public function test_monitor_uses_defaults_when_optional_fields_omitted(): void
    {
        Queue::fake();

        $response = $this->postJson('/api/monitors', ['url' => 'https://example.com']);

        $response->assertCreated()
            ->assertJsonPath('data.check_interval', 5)
            ->assertJsonPath('data.threshold', 3);
    }

    public function test_rejects_duplicate_url_with_422(): void
    {
        Queue::fake();

        Monitor::factory()->create(['url' => 'https://example.com']);

        $response = $this->postJson('/api/monitors', ['url' => 'https://example.com']);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['url']);
    }

    public function test_rejects_invalid_url_with_422(): void
    {
        Queue::fake();

        $response = $this->postJson('/api/monitors', ['url' => 'not-a-url']);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['url']);
    }

    public function test_rejects_missing_url_with_422(): void
    {
        Queue::fake();

        $response = $this->postJson('/api/monitors', []);

        $response->assertUnprocessable()
            ->assertJsonPath('message', 'The url field is required.')
            ->assertJsonValidationErrors(['url']);
    }

    public function test_rejects_check_interval_below_minimum(): void
    {
        Queue::fake();

        $response = $this->postJson('/api/monitors', [
            'url' => 'https://example.com',
            'check_interval' => 0,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['check_interval']);
    }

    public function test_rejects_check_interval_above_maximum(): void
    {
        Queue::fake();

        $response = $this->postJson('/api/monitors', [
            'url' => 'https://example.com',
            'check_interval' => 61,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['check_interval']);
    }

    // -----------------------------------------------------------------------
    // GET /api/monitors
    // -----------------------------------------------------------------------

    public function test_can_list_monitors(): void
    {
        Monitor::factory()->count(3)->create();

        $response = $this->getJson('/api/monitors');

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'url',
                        'check_interval',
                        'threshold',
                        'status',
                        'last_checked_at',
                        'uptime_percentage',
                        'created_at'
                    ],
                ],
            ]);
    }

    public function test_list_returns_empty_array_when_no_monitors(): void
    {
        $response = $this->getJson('/api/monitors');

        $response->assertOk()->assertJsonPath('data', []);
    }

    // -----------------------------------------------------------------------
    // GET /api/monitors/{id}/history
    // -----------------------------------------------------------------------

    public function test_can_fetch_check_history(): void
    {
        $monitor = Monitor::factory()->create();
        MonitorCheck::factory()->count(5)->for($monitor)->create();

        $response = $this->getJson("/api/monitors/{$monitor->id}/history");

        $response->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'monitor_id',
                        'status_code',
                        'response_time_ms',
                        'is_up',
                        'checked_at'
                    ],
                ],
                'meta' => ['current_page', 'per_page', 'total'],
            ]);
    }

    public function test_history_is_ordered_by_checked_at_descending(): void
    {
        $monitor = Monitor::factory()->create();

        $old = MonitorCheck::factory()->for($monitor)->create([
            'checked_at' => now()->subHour(),
        ]);
        $new = MonitorCheck::factory()->for($monitor)->create([
            'checked_at' => now(),
        ]);

        $response = $this->getJson("/api/monitors/{$monitor->id}/history");

        $ids = collect($response->json('data'))->pluck('id');
        $this->assertEquals($new->id, $ids->first());
        $this->assertEquals($old->id, $ids->last());
    }

    public function test_history_respects_per_page_parameter(): void
    {
        $monitor = Monitor::factory()->create();
        MonitorCheck::factory()->count(20)->for($monitor)->create();

        $response = $this->getJson("/api/monitors/{$monitor->id}/history?per_page=5");

        $response->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.per_page', 5)
            ->assertJsonPath('meta.total', 20);
    }

    public function test_history_caps_per_page_at_100(): void
    {
        $monitor = Monitor::factory()->create();
        MonitorCheck::factory()->count(10)->for($monitor)->create();

        $response = $this->getJson("/api/monitors/{$monitor->id}/history?per_page=500");

        $response->assertOk()->assertJsonPath('meta.per_page', 100);
    }

    public function test_history_returns_404_for_unknown_monitor(): void
    {
        $response = $this->getJson('/api/monitors/9999/history');

        $response->assertNotFound()
            ->assertJsonPath('message', 'Monitor not found.');
    }
}
