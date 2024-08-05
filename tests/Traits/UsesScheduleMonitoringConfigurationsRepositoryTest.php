<?php

use Spatie\ScheduleMonitor\Support\Concerns\UsesScheduleMonitoringConfigurationsRepository;
use Spatie\ScheduleMonitor\Support\ScheduledTasks\ScheduleMonitoringConfigurationsRepository;

it('can resolve schedule monitoring configurations repository', function () {
    $concern = new class() {
        use UsesScheduleMonitoringConfigurationsRepository;
    };

    $repository = $concern->getScheduleMonitoringConfigurationsRepository();

    expect($repository)->toBeInstanceOf(ScheduleMonitoringConfigurationsRepository::class);
});
