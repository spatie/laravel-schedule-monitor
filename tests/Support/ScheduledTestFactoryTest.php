<?php

use Illuminate\Console\Scheduling\Schedule;
use Spatie\ScheduleMonitor\Support\ScheduledTasks\ScheduledTaskFactory;
use Spatie\ScheduleMonitor\Support\ScheduledTasks\Tasks\ClosureTask;
use Spatie\ScheduleMonitor\Support\ScheduledTasks\Tasks\CommandTask;
use Spatie\ScheduleMonitor\Support\ScheduledTasks\Tasks\JobTask;
use Spatie\ScheduleMonitor\Support\ScheduledTasks\Tasks\ShellTask;
use Spatie\ScheduleMonitor\Tests\TestCase;
use Spatie\ScheduleMonitor\Tests\TestClasses\TestJob;
use Spatie\TestTime\TestTime;

uses(TestCase::class);

it('will return a command task for a scheduled command task', function () {
    $event = app()->make(Schedule::class)->command('foo:bar');

    $task = ScheduledTaskFactory::createForEvent($event);

    $this->assertInstanceOf(CommandTask::class, $task);
    $this->assertEquals('foo:bar', $task->name());
});

it('will return a shell task for a scheduled shell task', function () {
    $event = app()->make(Schedule::class)->exec('a bash command');

    $task = ScheduledTaskFactory::createForEvent($event);

    $this->assertInstanceOf(ShellTask::class, $task);
    $this->assertEquals('a bash command', $task->name());
});

it('will return a job task for a scheduled job task', function () {
    $event = app()->make(Schedule::class)->job(new TestJob());

    $task = ScheduledTaskFactory::createForEvent($event);

    $this->assertInstanceOf(JobTask::class, $task);
    $this->assertEquals(TestJob::class, $task->name());
});

it('will return a closure task for a scheduled closure task', function () {
    $event = app()->make(Schedule::class)->call(function () {
        $i = 1;
    });

    $task = ScheduledTaskFactory::createForEvent($event);

    $this->assertInstanceOf(ClosureTask::class, $task);
    $this->assertNull($task->name());
});

test('the task name can be manually set', function () {
    $event = app()->make(Schedule::class)->command('foo:bar')->monitorName('my-custom-name');

    $task = ScheduledTaskFactory::createForEvent($event);

    $this->assertEquals('my-custom-name', $task->name());
});

test('a task can be marked as not to be monitored', function () {
    $event = app()->make(Schedule::class)->command('foo:bar');
    $this->assertTrue(ScheduledTaskFactory::createForEvent($event)->shouldMonitor());

    $event = app()->make(Schedule::class)->command('foo:bar')->doNotMonitor();
    $this->assertFalse(ScheduledTaskFactory::createForEvent($event)->shouldMonitor());
});

it('can handle timezones', function () {
    TestTime::freeze('Y-m-d H:i:s', '2020-02-01 00:00:00');

    $schedule = app()->make(Schedule::class);

    $appTimezoneEvent = $schedule->command('foo:bar')->daily();
    $appTimezoneTask = ScheduledTaskFactory::createForEvent($appTimezoneEvent);
    $this->assertEquals('UTC', $appTimezoneTask->timezone());
    $this->assertEquals('2020-02-02 00:00:00', $appTimezoneTask->nextRunAt()->format('Y-m-d H:i:s'));

    $otherTimezoneEvent = $schedule->command('foo:bar')->daily()->timezone('Asia/Kolkata');
    $otherTimezoneTask = ScheduledTaskFactory::createForEvent($otherTimezoneEvent);
    $this->assertEquals('Asia/Kolkata', $otherTimezoneTask->timezone());
    $this->assertEquals('2020-02-01 18:30:00', $otherTimezoneTask->nextRunAt()->format('Y-m-d H:i:s'));
});
