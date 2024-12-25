<?php

namespace Spatie\ScheduleMonitor\Support\Concerns;

use Spatie\ScheduleMonitor\Support\ScheduledTasks\MonitoredScheduledTasks;

trait UsesMonitoredScheduledTasks
{
    public function getScheduleMonitoringConfigurationsRepository(): MonitoredScheduledTasks
    {
        return app(MonitoredScheduledTasks::class);
    }
}
