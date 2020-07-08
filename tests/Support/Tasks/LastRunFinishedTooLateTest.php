<?php

namespace Spatie\ScheduleMonitor\Tests\Support\Tasks;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Spatie\ScheduleMonitor\Models\MonitoredScheduledTask;
use Spatie\ScheduleMonitor\Support\ScheduledTasks\ScheduledTaskFactory;
use Spatie\ScheduleMonitor\Support\ScheduledTasks\Tasks\Task;
use Spatie\ScheduleMonitor\Tests\TestCase;
use Spatie\TestTime\TestTime;

class LastRunFinishedTooLateTest extends TestCase
{
    private Event $event;

    private MonitoredScheduledTask $monitoredScheduledTask;

    public function setUp(): void
    {
        parent::setUp();

        TestTime::freeze('H:i:s', '00:00:00');

        $this->event = $this->app->make(Schedule::class)
            ->command('foo:bar')
            ->hourly()
            ->graceTimeInMinutes(5);

        $this->monitoredScheduledTask = factory(MonitoredScheduledTask::class)->create([
            'name' => 'foo:bar',
        ]);
    }

    /** @test */
    public function a_task_will_be_consider_too_late_if_does_not_finish_within_the_grace_period()
    {
        $this->assertFalse($this->task()->lastRunFinishedTooLate());

        TestTime::addMinutes(5);
        $this->assertFalse($this->task()->lastRunFinishedTooLate());

        TestTime::addSecond();
        $this->assertTrue($this->task()->lastRunFinishedTooLate());
    }

    /** @test */
    public function it_will_reset_the_period()
    {
        $this->assertFalse($this->task()->lastRunFinishedTooLate());

        TestTime::addMinutes(4);
        $this->assertFalse($this->task()->lastRunFinishedTooLate());

        $this->monitoredScheduledTask->update(['last_finished_at' => now()]);

        TestTime::addMinutes(10);
        $this->assertFalse($this->task()->lastRunFinishedTooLate());

        TestTime::addMinutes(46); // now at 1:00;
        $this->assertFalse($this->task()->lastRunFinishedTooLate());

        TestTime::addMinutes(5);
        $this->assertFalse($this->task()->lastRunFinishedTooLate());

        TestTime::addMinute();
        $this->assertTrue($this->task()->lastRunFinishedTooLate());
    }

    protected function task(): Task
    {
        return ScheduledTaskFactory::createForEvent($this->event);
    }
}
