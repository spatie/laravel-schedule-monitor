<?php

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Spatie\ScheduleMonitor\Models\MonitoredScheduledTask;
use Spatie\ScheduleMonitor\Support\ScheduledTasks\ScheduledTaskFactory;
use Spatie\ScheduleMonitor\Support\ScheduledTasks\Tasks\Task;
use Spatie\ScheduleMonitor\Tests\TestCase;
use Spatie\TestTime\TestTime;

uses(TestCase::class);

beforeEach(function () {
    TestTime::freeze();

    $this->event = app()->make(Schedule::class)->command('foo:bar');

    $this->monitoredScheduledTask = MonitoredScheduledTask::factory()->create([
        'name' => 'foo:bar',
    ]);
});

it('will return false if it didnt start or fail yet', function () {
    $this->assertFalse(task()->lastRunFailed());
});

it('will return false if it did start but not fail yet', function () {
    $this->monitoredScheduledTask->update(['last_started_at' => now()]);

    $this->assertFalse(task()->lastRunFailed());
});

it('will return true if it failed after it started', function () {
    $this->monitoredScheduledTask->update(['last_started_at' => now()]);

    TestTime::addMinute();

    $this->monitoredScheduledTask->update(['last_failed_at' => now()]);

    $this->assertTrue(task()->lastRunFailed());
});

it('will return false if it started after it failed', function () {
    $this->monitoredScheduledTask->update(['last_failed_at' => now()]);

    TestTime::addMinute();

    $this->monitoredScheduledTask->update(['last_started_at' => now()]);

    $this->assertFalse(task()->lastRunFailed());
});

// Helpers
function task(): Task
{
    return ScheduledTaskFactory::createForEvent(test()->event);
}
