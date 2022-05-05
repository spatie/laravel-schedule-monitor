<?php

namespace Spatie\ScheduleMonitor\Commands;

use Illuminate\Console\Command;
use Spatie\ScheduleMonitor\Support\ScheduledTasks\ScheduledTasks;
use function Termwind\render;
use function Termwind\style;

class ListCommand extends Command
{
    public $signature = 'schedule-monitor:list';

    public $description = 'Display monitored scheduled tasks';

    public function handle()
    {
        $dateFormat = config('schedule-monitor.date_format');
        style('date-width')->apply('w-' . strlen(date($dateFormat)));

        render(view('schedule-monitor::list', [
            'monitoredTasks' => ScheduledTasks::createForSchedule()->monitoredTasks(),
            'readyForMonitoringTasks' => ScheduledTasks::createForSchedule()->readyForMonitoringTasks(),
            'unnamedTasks' => ScheduledTasks::createForSchedule()->unnamedTasks(),
            'duplicateTasks' => ScheduledTasks::createForSchedule()->duplicateTasks(),
            'usingOhDear' => $this->usingOhDear(),
            'dateFormat' => $dateFormat,
        ]));
    }

    protected function usingOhDear(): bool
    {
        if (! class_exists(OhDear::class)) {
            return false;
        }

        if (empty(config('schedule-monitor.oh_dear.api_token'))) {
            return false;
        }

        if (empty(config('schedule-monitor.oh_dear.site_id'))) {
            return false;
        }

        return true;
    }
}
