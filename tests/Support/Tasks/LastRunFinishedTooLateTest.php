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
    TestTime::freeze('H:i:s', '00:00:00');

    $this->event = app()->make(Schedule::class)
        ->command('foo:bar')
        ->hourly()
        ->graceTimeInMinutes(5);

    $this->monitoredScheduledTask = MonitoredScheduledTask::factory()->create([
        'name' => 'foo:bar',
    ]);
});

test('a task will be consider too late if does not finish within the grace period', function () {
    $this->assertFalse(task()->lastRunFinishedTooLate());

    TestTime::addMinutes(5);
    $this->assertFalse(task()->lastRunFinishedTooLate());

    TestTime::addSecond();
    $this->assertTrue(task()->lastRunFinishedTooLate());
});

it('will reset the period', function () {
    $this->assertFalse(task()->lastRunFinishedTooLate());

    TestTime::addMinutes(4);
    $this->assertFalse(task()->lastRunFinishedTooLate());

    $this->monitoredScheduledTask->update(['last_finished_at' => now()]);

    TestTime::addMinutes(10);
    $this->assertFalse(task()->lastRunFinishedTooLate());

    TestTime::addMinutes(46); // now at 1:00;
    $this->assertFalse(task()->lastRunFinishedTooLate());

    TestTime::addMinutes(5);
    $this->assertFalse(task()->lastRunFinishedTooLate());

    TestTime::addMinute();
    $this->assertTrue(task()->lastRunFinishedTooLate());
});

// Helpers
function task(): Task
{
    return ScheduledTaskFactory::createForEvent(test()->event);
}
