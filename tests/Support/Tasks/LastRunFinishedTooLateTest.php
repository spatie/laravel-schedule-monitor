<?php

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Spatie\ScheduleMonitor\Models\MonitoredScheduledTask;
use Spatie\ScheduleMonitor\Support\ScheduledTasks\ScheduledTaskFactory;
use Spatie\ScheduleMonitor\Support\ScheduledTasks\Tasks\Task;
use Spatie\ScheduleMonitor\Tests\TestCase;
use Spatie\TestTime\TestTime;


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
    expect(task()->lastRunFinishedTooLate())->toBeFalse();

    TestTime::addMinutes(5);
    expect(task()->lastRunFinishedTooLate())->toBeFalse();

    TestTime::addSecond();
    expect(task()->lastRunFinishedTooLate())->toBeTrue();
});

it('will reset the period', function () {
    expect(task()->lastRunFinishedTooLate())->toBeFalse();

    TestTime::addMinutes(4);
    expect(task()->lastRunFinishedTooLate())->toBeFalse();

    $this->monitoredScheduledTask->update(['last_finished_at' => now()]);

    TestTime::addMinutes(10);
    expect(task()->lastRunFinishedTooLate())->toBeFalse();

    TestTime::addMinutes(46); // now at 1:00;
    expect(task()->lastRunFinishedTooLate())->toBeFalse();

    TestTime::addMinutes(5);
    expect(task()->lastRunFinishedTooLate())->toBeFalse();

    TestTime::addMinute();
    expect(task()->lastRunFinishedTooLate())->toBeTrue();
});

// Helpers
function task(): Task
{
    return ScheduledTaskFactory::createForEvent(test()->event);
}
