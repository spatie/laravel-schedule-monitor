<?php

use \Faker\Generator;
use Spatie\ScheduleMonitor\Models\MonitoredScheduledTask;

/* @var Illuminate\Database\Eloquent\Factory $factory */
$factory->define(MonitoredScheduledTask::class, function (Generator $faker) {
    return [
        'name' => $faker->name,
        'type' => $faker->randomElement(['command', 'shell', 'job', 'closure']),
        'cron_expression' => '* * * * *',
        'grace_time_in_minutes' => 5,
    ];
});
