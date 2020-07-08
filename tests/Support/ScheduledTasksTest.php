<?php

namespace Spatie\ScheduleMonitor\Tests\Support;

use Illuminate\Console\Scheduling\Schedule;
use Spatie\ScheduleMonitor\Support\ScheduledTasks\ScheduledTasks;
use Spatie\ScheduleMonitor\Support\ScheduledTasks\Tasks\Task;
use Spatie\ScheduleMonitor\Tests\TestCase;
use Spatie\ScheduleMonitor\Tests\TestClasses\TestKernel;

class ScheduledTasksTest extends TestCase
{
    /** @test */
    public function it_can_get_the_unique_and_duplicate_tasks_from_the_schedule()
    {
        TestKernel::registerScheduledTasks(function (Schedule $schedule) {
            $schedule->command('dummy')->everyMinute();
            $schedule->call(fn () => 1 + 1)->hourly()->monitorName('dummy');
            $schedule->command('other-dummy')->everyMinute();
        });

        $scheduledTasks = ScheduledTasks::createForSchedule();

        $uniqueTasks = $scheduledTasks->uniqueTasks()
            ->map(fn (Task $task) => "{$task->name()}-{$task->type()}")
            ->toArray();

        $this->assertEquals([
            'dummy-command',
            'other-dummy-command',
        ], $uniqueTasks);

        $duplicateTasks = $scheduledTasks->duplicateTasks()
            ->map(fn (Task $task) => "{$task->name()}-{$task->type()}")
            ->toArray();

        $this->assertEquals([
            'dummy-closure',
        ], $duplicateTasks);
    }
}
