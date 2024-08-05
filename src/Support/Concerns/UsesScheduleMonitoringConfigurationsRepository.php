<?php

namespace Spatie\ScheduleMonitor\Support\Concerns;

use Spatie\ScheduleMonitor\Support\ScheduledTasks\ScheduleMonitoringConfigurationsRepository;

trait UsesScheduleMonitoringConfigurationsRepository
{
    public function getScheduleMonitoringConfigurationsRepository(): ScheduleMonitoringConfigurationsRepository
    {
        return app(ScheduleMonitoringConfigurationsRepository::class);
    }
}
