<?php

use Spatie\ScheduleMonitor\Support\Concerns\UsesMonitoredScheduledTasks;
use Spatie\ScheduleMonitor\Support\ScheduledTasks\MonitoredScheduledTasks;

it('can resolve schedule monitoring configurations repository', function () {
    $concern = new class() {
        use UsesMonitoredScheduledTasks;
    };

    $repository = $concern->getMonitoredScheduledTasks();

    expect($repository)->toBeInstanceOf(MonitoredScheduledTasks::class);
});
