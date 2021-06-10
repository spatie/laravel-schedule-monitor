<?php

namespace Spatie\ScheduleMonitor\Support\Concerns;

use Spatie\ScheduleMonitor\Models\MonitoredScheduledTask;
use Spatie\ScheduleMonitor\Models\MonitoredScheduledTaskLogItem;
use function app;

trait UsesScheduleMonitoringModels
{
    public function getMonitoredScheduleTaskModel(): MonitoredScheduledTask
    {
        return app(MonitoredScheduledTask::class);
    }

    public function getMonitoredScheduleTaskLogItemModel(): MonitoredScheduledTaskLogItem
    {
        return app(MonitoredScheduledTaskLogItem::class);
    }
}
