<?php

namespace Spatie\ScheduleMonitor\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Spatie\ScheduleMonitor\Models\MonitoredScheduledTaskLogItem;

class CleanLogCommand extends Command
{
    public $signature = 'schedule-monitor:clean';

    public $description = 'Display monitored scheduled tasks';

    public function handle()
    {
        $cutOffInDays = config('schedule-monitor.delete_log_items_older_than_days');

        $this->comment('Deleting all log items older than ' . $cutOffInDays .' '. Str::plural('day', $cutOffInDays) . '...');

        $cutOff = now()->subDays(config('schedule-monitor.delete_log_items_older_than_days'));

        $numberOfRecordsDeleted = MonitoredScheduledTaskLogItem::query()
            ->where('created_at', '<', $cutOff->toDateTimeString())
            ->delete();

        $this->info('Deleted ' . $numberOfRecordsDeleted . ' '. Str::plural('log item', $numberOfRecordsDeleted) . '!');
    }
}
