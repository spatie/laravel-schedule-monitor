<?php

namespace Spatie\ScheduleMonitor\Commands;

use Illuminate\Console\Command;
use Spatie\ScheduleMonitor\Commands\Tables\DuplicateTasksTable;
use Spatie\ScheduleMonitor\Commands\Tables\MonitoredTasksTable;
use Spatie\ScheduleMonitor\Commands\Tables\ReadyForMonitoringTasksTable;
use Spatie\ScheduleMonitor\Commands\Tables\UnnamedTasksTable;

class ListCommand extends Command
{
    public $signature = 'schedule-monitor:list';

    public $description = 'Display monitored scheduled tasks';

    public function handle()
    {
        (new MonitoredTasksTable($this))->render();
        (new ReadyForMonitoringTasksTable($this))->render();
        (new UnnamedTasksTable($this))->render();
        (new DuplicateTasksTable($this))->render();

        $this->line('');
    }
}
