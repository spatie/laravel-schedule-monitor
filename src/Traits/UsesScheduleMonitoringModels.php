<?php

namespace Spatie\ScheduleMonitor\Traits;

use Spatie\ScheduleMonitor\Models\MonitoredScheduledTask;
use Spatie\ScheduleMonitor\Models\MonitoredScheduledTaskLogItem;

trait UsesScheduleMonitoringModels
{
    public function getMonitoredScheduleTaskModel()
    {
        return app(MonitoredScheduledTask::class);
    }

    public function getMonitoredScheduleTaskLogItemModel()
    {
        return app(MonitoredScheduledTaskLogItem::class);
    }
}
