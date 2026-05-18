<?php

namespace App\Jobs;

use App\Models\Monitor;
use App\Models\MonitorCheck;
use Illuminate\Support\Facades\DB;
use App\Services\UptimeCheckerService;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Notification;
use App\Notifications\MonitorStatusChangedNotification;

class CheckMonitorJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */

    public int $tries = 1;

    public function __construct(public readonly int $monitorId) {}

    /**
     * Execute the job.
     */
    public function handle(UptimeCheckerService $checker): void
    {
        $monitor = Monitor::find($this->monitorId);

        if (!$monitor) {
            return;
        }

        $result = $checker->check($monitor->url);

        $statusChanged = false;
        $previousStatus = $monitor->status;

        DB::transaction(function () use ($monitor, $result, &$statusChanged) {
            // check record
            MonitorCheck::create([
                'monitor_id' => $monitor->id,
                'status_code' => $result['status_code'],
                'response_time_ms' => $result['response_time_ms'],
                'is_up' => $result['is_up'],
                'checked_at' => now(),
            ]);

            // Recompute consecutive failure count and new status
            $newConsecutiveFailures = $result['is_up']
                ? 0
                : $monitor->consecutive_failures + 1;

            $newStatus = $this->resolveStatus(
                monitor: $monitor,
                isUp: $result['is_up'],
                consecutiveFailures: $newConsecutiveFailures,
            );

            $statusChanged = $newStatus !== $monitor->status;

            $monitor->update([
                'status' => $newStatus,
                'consecutive_failures' => $newConsecutiveFailures,
                'last_checked_at' => now(),
            ]);
        });

        // Send notification
        if ($statusChanged) {
            $this->sendStatusNotification($monitor->fresh(), $previousStatus);
        }
    }


    /**
     * Determine the new monitor status based on the check result.
     */
    private function resolveStatus(
        Monitor $monitor,
        bool $isUp,
        int $consecutiveFailures,
    ): string {
        if ($isUp) {
            return 'up';
        }

        if ($consecutiveFailures >= $monitor->threshold) {
            return 'down';
        }

        // preserve existing status unless it's already "up"
        return $monitor->isUp() ? 'up' : $monitor->status;
    }

    /**
     * Deliver an email notification to the address configured in
     * config/uptime.php when a monitor's status changes.
     */
    private function sendStatusNotification(Monitor $monitor, string $previousStatus): void
    {
        $email = config('uptime.alert_email');

        if (!$email) {
            return;
        }

        Notification::route('mail', $email)
            ->notify(new MonitorStatusChangedNotification($monitor, $previousStatus));
    }
}
