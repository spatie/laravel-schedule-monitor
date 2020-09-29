<?php

namespace Spatie\ScheduleMonitor\Tests\Support;

use Illuminate\Console\Scheduling\Schedule;
use Spatie\ScheduleMonitor\Support\ScheduledTasks\ScheduledTaskFactory;
use Spatie\ScheduleMonitor\Support\ScheduledTasks\Tasks\ClosureTask;
use Spatie\ScheduleMonitor\Support\ScheduledTasks\Tasks\CommandTask;
use Spatie\ScheduleMonitor\Support\ScheduledTasks\Tasks\JobTask;
use Spatie\ScheduleMonitor\Support\ScheduledTasks\Tasks\ShellTask;
use Spatie\ScheduleMonitor\Tests\TestCase;
use Spatie\ScheduleMonitor\Tests\TestClasses\TestJob;
use Spatie\TestTime\TestTime;

class ScheduledTestFactoryTest extends TestCase
{
    /** @test */
    public function it_will_return_a_command_task_for_a_scheduled_command_task()
    {
        $event = $this->app->make(Schedule::class)->command('foo:bar');

        $task = ScheduledTaskFactory::createForEvent($event);

        $this->assertInstanceOf(CommandTask::class, $task);
        $this->assertEquals('foo:bar', $task->name());
    }

    /** @test */
    public function it_will_return_a_shell_task_for_a_scheduled_shell_task()
    {
        $event = $this->app->make(Schedule::class)->exec('a bash command');

        $task = ScheduledTaskFactory::createForEvent($event);

        $this->assertInstanceOf(ShellTask::class, $task);
        $this->assertEquals('a bash command', $task->name());
    }

    /** @test */
    public function it_will_return_a_job_task_for_a_scheduled_job_task()
    {
        $event = $this->app->make(Schedule::class)->job(new TestJob());

        $task = ScheduledTaskFactory::createForEvent($event);

        $this->assertInstanceOf(JobTask::class, $task);
        $this->assertEquals(TestJob::class, $task->name());
    }

    /** @test */
    public function it_will_return_a_closure_task_for_a_scheduled_closure_task()
    {
        $event = $this->app->make(Schedule::class)->call(function () {
            $i = 1;
        });

        $task = ScheduledTaskFactory::createForEvent($event);

        $this->assertInstanceOf(ClosureTask::class, $task);
        $this->assertNull($task->name());
    }

    /** @test */
    public function the_task_name_can_be_manually_set()
    {
        $event = $this->app->make(Schedule::class)->command('foo:bar')->monitorName('my-custom-name');

        $task = ScheduledTaskFactory::createForEvent($event);

        $this->assertEquals('my-custom-name', $task->name());
    }

    /** @test */
    public function a_task_can_be_marked_as_not_to_be_monitored()
    {
        $event = $this->app->make(Schedule::class)->command('foo:bar');
        $this->assertTrue(ScheduledTaskFactory::createForEvent($event)->shouldMonitor());

        $event = $this->app->make(Schedule::class)->command('foo:bar')->doNotMonitor();
        $this->assertFalse(ScheduledTaskFactory::createForEvent($event)->shouldMonitor());
    }

    /** @test */
    public function it_can_handle_timezones()
    {
        TestTime::freeze('Y-m-d H:i:s', '2020-02-01 00:00:00');

        $schedule = $this->app->make(Schedule::class);

        $appTimezoneEvent = $schedule->command('foo:bar')->daily();
        $appTimezoneTask = ScheduledTaskFactory::createForEvent($appTimezoneEvent);
        $this->assertEquals('UTC', $appTimezoneTask->timezone());
        $this->assertEquals('2020-02-02 00:00:00', $appTimezoneTask->nextRunAt()->format('Y-m-d H:i:s'));

        $otherTimezoneEvent = $schedule->command('foo:bar')->daily()->timezone('Asia/Kolkata');
        $otherTimezoneTask = ScheduledTaskFactory::createForEvent($otherTimezoneEvent);
        $this->assertEquals('Asia/Kolkata', $otherTimezoneTask->timezone());
        $this->assertEquals('2020-02-01 18:30:00', $otherTimezoneTask->nextRunAt()->format('Y-m-d H:i:s'));
    }
}
