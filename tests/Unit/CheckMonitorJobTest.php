<?php

namespace Tests\Unit;

use Mockery;
use Tests\TestCase;
use App\Models\Monitor;
use App\Jobs\CheckMonitorJob;
use App\Services\UptimeCheckerService;
use Illuminate\Support\Facades\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Notifications\MonitorStatusChangedNotification;

class CheckMonitorJobTest extends TestCase
{
    use RefreshDatabase;

    private function makeChecker(array $result): UptimeCheckerService
    {
        $mock = Mockery::mock(UptimeCheckerService::class);
        $mock->shouldReceive('check')->once()->andReturn($result);
        return $mock;
    }

    public function test_records_check_and_marks_monitor_up_on_success(): void
    {
        Notification::fake();
        $monitor = Monitor::factory()->create(['status' => 'pending']);

        $checker = $this->makeChecker([
            'status_code' => 200,
            'response_time_ms' => 120,
            'is_up' => true,
        ]);

        (new CheckMonitorJob($monitor->id))->handle($checker);

        $this->assertDatabaseHas('monitor_checks', [
            'monitor_id' => $monitor->id,
            'status_code' => 200,
            'is_up' => true,
        ]);

        $this->assertDatabaseHas('monitors', [
            'id' => $monitor->id,
            'status' => 'up',
        ]);
    }

    public function test_does_not_mark_down_until_threshold_reached(): void
    {
        Notification::fake();

        // threshold = 3; first failure should NOT flip status to "down"
        $monitor = Monitor::factory()->create([
            'status' => 'up',
            'threshold' => 3,
            'consecutive_failures' => 0,
        ]);

        $checker = $this->makeChecker([
            'status_code' => 500,
            'response_time_ms' => 800,
            'is_up' => false,
        ]);

        (new CheckMonitorJob($monitor->id))->handle($checker);

        $this->assertDatabaseHas('monitors', [
            'id' => $monitor->id,
            'status' => 'up',
            'consecutive_failures' => 1,
        ]);

        Notification::assertNothingSent();
    }

    public function test_marks_down_when_threshold_reached(): void
    {
        Notification::fake();

        $monitor = Monitor::factory()->create([
            'status' => 'up',
            'threshold' => 3,
            'consecutive_failures' => 2,
        ]);

        $checker = $this->makeChecker([
            'status_code' => 0,
            'response_time_ms' => null,
            'is_up' => false,
        ]);

        (new CheckMonitorJob($monitor->id))->handle($checker);

        $this->assertDatabaseHas('monitors', [
            'id' => $monitor->id,
            'status' => 'down',
            'consecutive_failures' => 3,
        ]);

        Notification::assertSentOnDemand(MonitorStatusChangedNotification::class);
    }

    public function test_marks_up_and_resets_failures_on_recovery(): void
    {
        Notification::fake();

        $monitor = Monitor::factory()->create([
            'status' => 'down',
            'consecutive_failures' => 5,
        ]);

        $checker = $this->makeChecker([
            'status_code' => 200,
            'response_time_ms' => 90,
            'is_up' => true,
        ]);

        (new CheckMonitorJob($monitor->id))->handle($checker);

        $this->assertDatabaseHas('monitors', [
            'id' => $monitor->id,
            'status' => 'up',
            'consecutive_failures' => 0,
        ]);

        Notification::assertSentOnDemand(MonitorStatusChangedNotification::class);
    }

    public function test_records_timeout_with_zero_status_code_and_null_response_time(): void
    {
        Notification::fake();

        $monitor = Monitor::factory()->create(['status' => 'up', 'threshold' => 1]);

        $checker = $this->makeChecker([
            'status_code' => 0,
            'response_time_ms' => null,
            'is_up' => false,
        ]);

        (new CheckMonitorJob($monitor->id))->handle($checker);

        $this->assertDatabaseHas('monitor_checks', [
            'monitor_id' => $monitor->id,
            'status_code' => 0,
            'response_time_ms' => null,
            'is_up' => false,
        ]);
    }

    public function test_does_nothing_when_monitor_does_not_exist(): void
    {
        $checker = Mockery::mock(UptimeCheckerService::class);
        $checker->shouldNotReceive('check');

        (new CheckMonitorJob(99999))->handle($checker);

        $this->assertDatabaseCount('monitor_checks', 0);
    }
}
