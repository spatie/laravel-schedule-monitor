<?php

namespace Spatie\ScheduleMonitor\Tests\Support\Tasks;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Spatie\ScheduleMonitor\Models\MonitoredScheduledTask;
use Spatie\ScheduleMonitor\Support\ScheduledTasks\ScheduledTaskFactory;
use Spatie\ScheduleMonitor\Support\ScheduledTasks\Tasks\Task;
use Spatie\ScheduleMonitor\Tests\TestCase;
use Spatie\TestTime\TestTime;

class LastRunFailedTest extends TestCase
{
    private Event $event;

    private MonitoredScheduledTask $monitoredScheduledTask;

    public function setUp(): void
    {
        parent::setUp();

        TestTime::freeze();

        $this->event = $this->app->make(Schedule::class)->command('foo:bar');

        $this->monitoredScheduledTask = factory(MonitoredScheduledTask::class)->create([
            'name' => 'foo:bar',
        ]);
    }

    /** @test */
    public function it_will_return_false_if_it_didnt_start_or_fail_yet()
    {
        $this->assertFalse($this->task()->lastRunFailed());
    }

    /** @test */
    public function it_will_return_false_if_it_did_start_but_not_fail_yet()
    {
        $this->monitoredScheduledTask->update(['last_started_at' => now()]);

        $this->assertFalse($this->task()->lastRunFailed());
    }

    /** @test */
    public function it_will_return_true_if_it_failed_after_it_started()
    {
        $this->monitoredScheduledTask->update(['last_started_at' => now()]);

        TestTime::addMinute();

        $this->monitoredScheduledTask->update(['last_failed_at' => now()]);

        $this->assertTrue($this->task()->lastRunFailed());
    }

    /** @test */
    public function it_will_return_false_if_it_started_after_it_failed()
    {
        $this->monitoredScheduledTask->update(['last_failed_at' => now()]);

        TestTime::addMinute();

        $this->monitoredScheduledTask->update(['last_started_at' => now()]);

        $this->assertFalse($this->task()->lastRunFailed());
    }

    protected function task(): Task
    {
        return ScheduledTaskFactory::createForEvent($this->event);
    }
}
