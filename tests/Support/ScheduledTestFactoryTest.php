<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Once;
use Spatie\ScheduleMonitor\Models\MonitoredScheduledTask;
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

it('loads monitoring state on first read and keeps a snapshot for that task', function () {
    $event = app(Schedule::class)->command('foo:bar')->hourly();

    DB::enableQueryLog();
    DB::flushQueryLog();

    $task = ScheduledTaskFactory::createForEvent($event);

    expect($task->name())->toBe('foo:bar')
        ->and($task->cronExpression())->toBe('0 * * * *')
        ->and($task->shouldMonitor())->toBeTrue()
        ->and(DB::getQueryLog())->toBeEmpty();

    $model = MonitoredScheduledTask::factory()->create([
        'name' => 'foo:bar',
        'last_started_at' => '2026-01-02 03:04:05',
    ]);

    DB::flushQueryLog();

    expect($task->isBeingMonitored())->toBeTrue()
        ->and($task->lastRunStartedAt()->format('Y-m-d H:i:s'))->toBe('2026-01-02 03:04:05')
        ->and(DB::getQueryLog())->toHaveCount(1);

    $model->update(['last_started_at' => '2026-02-03 04:05:06']);

    Once::flush();
    DB::flushQueryLog();

    expect($task->lastRunStartedAt()->format('Y-m-d H:i:s'))->toBe('2026-01-02 03:04:05')
        ->and(DB::getQueryLog())->toBeEmpty()
        ->and(ScheduledTaskFactory::createForEvent($event)->lastRunStartedAt()->format('Y-m-d H:i:s'))
        ->toBe('2026-02-03 04:05:06');
});

it('remembers a missing row without sharing that result with new tasks', function () {
    $event = app(Schedule::class)->command('not-yet-monitored');
    $task = ScheduledTaskFactory::createForEvent($event);

    expect($task->isBeingMonitored())->toBeFalse();

    MonitoredScheduledTask::factory()->create(['name' => 'not-yet-monitored']);

    Once::flush();
    DB::enableQueryLog();
    DB::flushQueryLog();

    expect($task->isBeingMonitored())->toBeFalse()
        ->and($task->lastRunStartedAt())->toBeNull()
        ->and($task->lastRunFinishedAt())->toBeNull()
        ->and($task->lastRunFailedAt())->toBeNull()
        ->and($task->lastRunSkippedAt())->toBeNull()
        ->and($task->isBeingMonitoredAtOhDear())->toBeFalse()
        ->and($task->lastRunFailed())->toBeFalse()
        ->and($task->lastRunFinishedTooLate())->toBeFalse()
        ->and(DB::getQueryLog())->toBeEmpty()
        ->and(ScheduledTaskFactory::createForEvent($event)->isBeingMonitored())->toBeTrue();
});

it('keeps monitoring snapshots independent of the global once cache', function () {
    $event = app(Schedule::class)->command('foo:bar');
    $model = MonitoredScheduledTask::factory()->create([
        'name' => 'foo:bar',
        'ping_url' => 'https://ping.ohdear.app/example',
    ]);

    Once::disable();

    try {
        $task = ScheduledTaskFactory::createForEvent($event);

        expect($task->isBeingMonitoredAtOhDear())->toBeTrue();

        $model->delete();

        expect($task->isBeingMonitoredAtOhDear())->toBeTrue()
            ->and(ScheduledTaskFactory::createForEvent($event)->isBeingMonitored())->toBeFalse();
    } finally {
        Once::enable();
        Once::flush();
    }
});

it('does not query monitoring state for unnamed tasks', function () {
    $event = app(Schedule::class)->call(fn () => null);

    DB::enableQueryLog();
    DB::flushQueryLog();

    $task = ScheduledTaskFactory::createForEvent($event);

    expect($task->name())->toBeNull()
        ->and($task->isBeingMonitored())->toBeFalse()
        ->and($task->lastRunStartedAt())->toBeNull()
        ->and($task->lastRunFinishedTooLate())->toBeFalse()
        ->and(DB::getQueryLog())->toBeEmpty();
});
