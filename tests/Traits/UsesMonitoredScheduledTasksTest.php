<?php

use Spatie\ScheduleMonitor\Support\Concerns\UsesMonitoredScheduledTasks;
use Spatie\ScheduleMonitor\Support\ScheduledTasks\MonitoredScheduledTasks;

it('can resolve schedule monitoring configurations repository', function () {
    $concern = new class() {
        use UsesMonitoredScheduledTasks;
    };

    $repository = $concern->getScheduleMonitoringConfigurationsRepository();

    expect($repository)->toBeInstanceOf(MonitoredScheduledTasks::class);
});
