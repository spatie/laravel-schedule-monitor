<?php

use Illuminate\Console\Scheduling\Schedule;
use Spatie\ScheduleMonitor\Support\ScheduledTasks\ScheduledTasks;
use Spatie\ScheduleMonitor\Support\ScheduledTasks\Tasks\Task;
use Spatie\ScheduleMonitor\Tests\TestClasses\TestKernel;

it('can get the unique and duplicate tasks from the schedule', function () {
    TestKernel::registerScheduledTasks(function (Schedule $schedule) {
        $schedule->command('dummy')->everyMinute();
        $schedule->call(fn () => 1 + 1)->hourly()->monitorName('dummy');
        $schedule->command('other-dummy')->everyMinute();
    });

    $scheduledTasks = ScheduledTasks::createForSchedule();

    $uniqueTasks = $scheduledTasks->uniqueTasks()
        ->map(fn (Task $task) => "{$task->name()}-{$task->type()}")
        ->toArray();

    $this->assertEquals([
        'dummy-command',
        'other-dummy-command',
    ], $uniqueTasks);

    $duplicateTasks = $scheduledTasks->duplicateTasks()
        ->map(fn (Task $task) => "{$task->name()}-{$task->type()}")
        ->toArray();

    $this->assertEquals([
        'dummy-closure',
    ], $duplicateTasks);
});

it('can get only the tasks that run in the current environment from the schedule', function () {
    TestKernel::registerScheduledTasks(function (Schedule $schedule) {
        $schedule->command('dummy')->environments(config('app.env', 'testing')); // current
        $schedule->command('other-dummy')->environments('production');
    });

    $scheduledTasks = ScheduledTasks::createForSchedule();

    $uniqueTasks = $scheduledTasks->uniqueTasks()
        ->map(fn (Task $task) => "{$task->name()}-{$task->type()}")
        ->toArray();

    $this->assertEquals([
        'dummy-command',
    ], $uniqueTasks);
});
