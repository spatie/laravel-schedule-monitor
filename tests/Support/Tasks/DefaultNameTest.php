<?php

use Illuminate\Console\Scheduling\Schedule;
use Spatie\ScheduleMonitor\Support\ScheduledTasks\ScheduledTasks;
use Spatie\ScheduleMonitor\Support\ScheduledTasks\Tasks\Task;
use Spatie\ScheduleMonitor\Tests\TestClasses\TestJob;
use Spatie\ScheduleMonitor\Tests\TestClasses\TestKernel;

it('truncates a long command task name so it fits the name column', function () {
    TestKernel::replaceScheduledTasks(function (Schedule $schedule) {
        $schedule->command('model:prune', [
            '--model' => array_map(
                fn(int $i) => 'App\\Very\\Long\\Namespace\\Model' . $i . 'WithAnExtremelyLongClassName',
                range(1, 20),
            ),
        ])->daily();
    });

    $task = ScheduledTasks::createForSchedule()
        ->uniqueTasks()
        ->first(fn(Task $task) => $task->type() === 'command');

    expect($task->name())->toStartWith('model:prune')
        ->and(mb_strlen($task->name()))->toBeLessThanOrEqual(255);
});

it('truncates a long shell task name so it fits the name column', function () {
    TestKernel::replaceScheduledTasks(function (Schedule $schedule) {
        $schedule->exec(str_repeat('a', 300))->daily();
    });

    $task = ScheduledTasks::createForSchedule()
        ->uniqueTasks()
        ->first(fn(Task $task) => $task->type() === 'shell');

    expect(mb_strlen($task->name()))->toBeLessThanOrEqual(255);
});

it('truncates a long job task name so it fits the name column', function () {
    $longJobName = 'LongJob' . str_repeat('X', 300);

    if (!class_exists($longJobName, false)) {
        class_alias(TestJob::class, $longJobName);
    }

    TestKernel::replaceScheduledTasks(function (Schedule $schedule) use ($longJobName) {
        $schedule->job($longJobName)->daily();
    });

    $task = ScheduledTasks::createForSchedule()
        ->uniqueTasks()
        ->first(fn(Task $task) => $task->type() === 'job');

    expect($task->name())->toStartWith('LongJob')
        ->and(mb_strlen($task->name()))->toBeLessThanOrEqual(255);
});
