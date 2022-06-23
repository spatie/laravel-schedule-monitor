<?php

namespace Spatie\ScheduleMonitor\Tests\Commands;

use Illuminate\Console\Scheduling\Schedule;
use function Pest\Laravel\artisan;
use Spatie\ScheduleMonitor\Commands\ListCommand;
use Spatie\ScheduleMonitor\Tests\TestClasses\TestJob;
use Spatie\ScheduleMonitor\Tests\TestClasses\TestKernel;

it('can list scheduled tasks', function () {
    TestKernel::registerScheduledTasks(function (Schedule $schedule) {
        $schedule->command('dummy')->everyMinute();
        $schedule->exec('execute')->everyFifteenMinutes();
        $schedule->call(fn () => 1 + 1)->hourly();
        $schedule->job(new TestJob())->daily();
        $schedule->job(new TestJob())->daily();
    });

    artisan(ListCommand::class)->assertSuccessful();
});
