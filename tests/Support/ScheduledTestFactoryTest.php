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
}
