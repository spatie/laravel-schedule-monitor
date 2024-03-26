<?php

use Illuminate\Console\Scheduling\Schedule;
use Spatie\ScheduleMonitor\Models\MonitoredScheduledTask;
use Spatie\ScheduleMonitor\Support\ScheduledTasks\ScheduledTaskFactory;
use Spatie\ScheduleMonitor\Support\ScheduledTasks\Tasks\Task;
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
    expect(createTask()->lastRunFinishedTooLate())->toBeFalse();

    TestTime::addMinutes(5);
    expect(createTask()->lastRunFinishedTooLate())->toBeFalse();

    TestTime::addSecond();
    expect(createTask()->lastRunFinishedTooLate())->toBeTrue();
});

test('a task will be not consider too late if the last start and finished date are the same because the task executes pretty fast', function () {
    expect(createTask()->lastRunFinishedTooLate())->toBeFalse();

    $this->monitoredScheduledTask->update(['last_started_at' => now(), 'last_finished_at' => now()]);
    TestTime::addMinutes(6);
    expect(createTask()->lastRunFinishedTooLate())->toBeFalse();
});

it('will reset the period', function () {
    expect(createTask()->lastRunFinishedTooLate())->toBeFalse();

    TestTime::addMinutes(4);
    expect(createTask()->lastRunFinishedTooLate())->toBeFalse();

    $this->monitoredScheduledTask->update(['last_finished_at' => now()]);

    TestTime::addMinutes(10);
    expect(createTask()->lastRunFinishedTooLate())->toBeFalse();

    TestTime::addMinutes(46); // now at 1:00;
    expect(createTask()->lastRunFinishedTooLate())->toBeFalse();

    TestTime::addMinutes(5);
    expect(createTask()->lastRunFinishedTooLate())->toBeFalse();

    TestTime::addMinute();
    expect(createTask()->lastRunFinishedTooLate())->toBeTrue();
});

function createTask(): Task
{
    return ScheduledTaskFactory::createForEvent(test()->event);
}
