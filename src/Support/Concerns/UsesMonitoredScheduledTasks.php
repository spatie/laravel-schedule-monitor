<?php

namespace Spatie\ScheduleMonitor\Support\Concerns;

use Spatie\ScheduleMonitor\Support\ScheduledTasks\MonitoredScheduledTasks;

trait UsesMonitoredScheduledTasks
{
    public function getMonitoredScheduledTasks(): MonitoredScheduledTasks
    {
        return app(MonitoredScheduledTasks::class);
    }
}
