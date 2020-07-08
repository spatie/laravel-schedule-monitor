<?php

namespace Spatie\ScheduleMonitor\Tests\Commands;

use Illuminate\Console\Scheduling\Schedule;
use Spatie\ScheduleMonitor\Commands\ListCommand;
use Spatie\ScheduleMonitor\Tests\TestCase;
use Spatie\ScheduleMonitor\Tests\TestClasses\TestJob;
use Spatie\ScheduleMonitor\Tests\TestClasses\TestKernel;

class ListCommandTest extends TestCase
{
    /** @test */
    public function it_can_list_scheduled_tasks()
    {
        TestKernel::registerScheduledTasks(function (Schedule $schedule) {
            $schedule->command('dummy')->everyMinute();
            $schedule->exec('execute')->everyFifteenMinutes();
            $schedule->call(fn () => 1 + 1)->hourly();
            $schedule->job(new TestJob())->daily();
            $schedule->job(new TestJob())->daily();
        });

        $this->artisan(ListCommand::class)->assertExitCode(0);
    }
}
