<?php

use Illuminate\Console\Scheduling\Schedule;
use Spatie\ScheduleMonitor\Support\ScheduledTasks\ScheduledTaskFactory;
use Spatie\ScheduleMonitor\Support\ScheduledTasks\Tasks\ClosureTask;
use Spatie\ScheduleMonitor\Support\ScheduledTasks\Tasks\CommandTask;
use Spatie\ScheduleMonitor\Support\ScheduledTasks\Tasks\JobTask;
use Spatie\ScheduleMonitor\Support\ScheduledTasks\Tasks\ShellTask;
use Spatie\ScheduleMonitor\Tests\TestClasses\TestJob;
use Spatie\TestTime\TestTime;

it('will return a command task for a scheduled command task', function () {
    $event = app()->make(Schedule::class)->command('foo:bar');

    $task = ScheduledTaskFactory::createForEvent($event);

    expect($task)->toBeInstanceOf(CommandTask::class);
    expect($task->name())->toEqual('foo:bar');
});

it('will return a shell task for a scheduled shell task', function () {
    $event = app()->make(Schedule::class)->exec('a bash command');

    $task = ScheduledTaskFactory::createForEvent($event);

    expect($task)->toBeInstanceOf(ShellTask::class);
    expect($task->name())->toEqual('a bash command');
});

it('will return a job task for a scheduled job task', function () {
    $event = app()->make(Schedule::class)->job(new TestJob());

    $task = ScheduledTaskFactory::createForEvent($event);

    expect($task)->toBeInstanceOf(JobTask::class);
    expect($task->name())->toEqual(TestJob::class);
});

it('will return a closure task for a scheduled closure task', function () {
    $event = app()->make(Schedule::class)->call(function () {
        $i = 1;
    });

    $task = ScheduledTaskFactory::createForEvent($event);

    expect($task)->toBeInstanceOf(ClosureTask::class);
    expect($task->name())->toBeNull();
});

test('the task name can be manually set', function () {
    $event = app()->make(Schedule::class)->command('foo:bar')->monitorName('my-custom-name');

    $task = ScheduledTaskFactory::createForEvent($event);

    expect($task->name())->toEqual('my-custom-name');
});

test('a task can be marked as not to be monitored', function () {
    $event = app()->make(Schedule::class)->command('foo:bar');
    expect(ScheduledTaskFactory::createForEvent($event)->shouldMonitor())->toBeTrue();

    $event = app()->make(Schedule::class)->command('foo:bar')->doNotMonitor();
    expect(ScheduledTaskFactory::createForEvent($event)->shouldMonitor())->toBeFalse();

    $event = app()->make(Schedule::class)->command('foo:bar')->doNotMonitor(true);
    expect(ScheduledTaskFactory::createForEvent($event)->shouldMonitor())->toBeFalse();

    $event = app()->make(Schedule::class)->command('foo:bar');
    expect(ScheduledTaskFactory::createForEvent($event)->shouldMonitor(false))->toBeTrue();
});

it('can handle timezones', function () {
    TestTime::freeze('Y-m-d H:i:s', '2020-02-01 00:00:00');

    $schedule = app()->make(Schedule::class);

    $appTimezoneEvent = $schedule->command('foo:bar')->daily();
    $appTimezoneTask = ScheduledTaskFactory::createForEvent($appTimezoneEvent);
    expect($appTimezoneTask->timezone())->toEqual('UTC');
    expect($appTimezoneTask->nextRunAt()->format('Y-m-d H:i:s'))->toEqual('2020-02-02 00:00:00');

    $otherTimezoneEvent = $schedule->command('foo:bar')->daily()->timezone('Asia/Kolkata');
    $otherTimezoneTask = ScheduledTaskFactory::createForEvent($otherTimezoneEvent);
    expect($otherTimezoneTask->timezone())->toEqual('Asia/Kolkata');
    expect($otherTimezoneTask->nextRunAt()->format('Y-m-d H:i:s'))->toEqual('2020-02-01 18:30:00');
});
