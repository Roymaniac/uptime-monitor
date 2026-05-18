<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\Monitor;
use App\Jobs\CheckMonitorJob;
use Illuminate\Console\Command;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Attributes\Description;

#[Signature('monitors:check')]
#[Description('Dispatch check jobs for all monitors that are due for a check.')]
class ScheduleMonitorChecksCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now();

        Monitor::query()
            ->whereNull('last_checked_at')
            ->orWhereRaw(
                'DATE_ADD(last_checked_at, INTERVAL check_interval MINUTE) <= ?',
                [$now]
            )
            ->each(function (Monitor $monitor) {
                CheckMonitorJob::dispatch($monitor->id);
                $this->line("Dispatched check for monitor #{$monitor->id}: {$monitor->url}");
            });

        return self::SUCCESS;
    }
}
