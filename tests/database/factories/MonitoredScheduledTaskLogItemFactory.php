<?php

use \Faker\Generator;
use Spatie\ScheduleMonitor\Models\MonitoredScheduledTask;
use Spatie\ScheduleMonitor\Models\MonitoredScheduledTaskLogItem;

/* @var Illuminate\Database\Eloquent\Factory $factory */
$factory->define(MonitoredScheduledTaskLogItem::class, function (Generator $faker) {
    return [
        'monitored_scheduled_task_id' => factory(MonitoredScheduledTask::class),
        'type' => $faker->randomElement([
            MonitoredScheduledTaskLogItem::TYPE_STARTING,
            MonitoredScheduledTaskLogItem::TYPE_FINISHED,
            MonitoredScheduledTaskLogItem::TYPE_SKIPPED,
        ]),
        'meta' => [],
    ];
});


$factory->afterMaking(MonitoredScheduledTaskLogItem::class, function (MonitoredScheduledTaskLogItem $model) {
    $scheduledTask = $model->monitoredScheduledTask;
    $scheduledTask->ping_url = 'https://ping.ohdear.app';
    $scheduledTask->save();
});
