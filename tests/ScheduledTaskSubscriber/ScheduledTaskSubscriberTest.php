<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\File;
use Spatie\ScheduleMonitor\Commands\SyncCommand;
use Spatie\ScheduleMonitor\Jobs\PingOhDearJob;
use Spatie\ScheduleMonitor\Models\MonitoredScheduledTask;
use Spatie\ScheduleMonitor\Models\MonitoredScheduledTaskLogItem;
use Spatie\ScheduleMonitor\Tests\TestClasses\FailingCommand;
use Spatie\ScheduleMonitor\Tests\TestClasses\TestKernel;
use Spatie\TestTime\TestTime;

beforeEach(function () {
    Bus::fake();

    TestKernel::registerScheduledTasks(function (Schedule $schedule) {
        $schedule->call(fn () => 1 + 1)->everyMinute()->monitorName('dummy-task');
    });

    File::copy(__DIR__.'/../stubs/artisan', base_path('artisan'));
});

afterEach(function () {
    File::delete(base_path('artisan'));
});

it('will fire a job and create a log item when a monitored scheduled task finished', function () {
    config()->set('schedule-monitor.oh_dear.send_starting_ping', true);

    $this->artisan(SyncCommand::class)->assertExitCode(0);
    $this->artisan('schedule:run')->assertExitCode(0);

    Bus::assertDispatched(function (PingOhDearJob $job) {
        $monitoredScheduledTask = $job->logItem->monitoredScheduledTask;

        return $monitoredScheduledTask->name === 'dummy-task' && $job->logItem->type === MonitoredScheduledTaskLogItem::TYPE_STARTING;
    });

    Bus::assertDispatched(function (PingOhDearJob $job) {
        $monitoredScheduledTask = $job->logItem->monitoredScheduledTask;

        return $monitoredScheduledTask->name === 'dummy-task' && $job->logItem->type === MonitoredScheduledTaskLogItem::TYPE_FINISHED;
    });

    $logTypes = MonitoredScheduledTask::findByName('dummy-task')->logItems->pluck('type')->toArray();

    $this->assertEquals([
        MonitoredScheduledTaskLogItem::TYPE_FINISHED,
        MonitoredScheduledTaskLogItem::TYPE_STARTING,
    ], $logTypes);
});

it('will not not ping oh dear starting endpoint by default', function () {
    $this->artisan(SyncCommand::class)->assertExitCode(0);
    $this->artisan('schedule:run')->assertExitCode(0);

    Bus::assertNotDispatched(function (PingOhDearJob $job) {
        $monitoredScheduledTask = $job->logItem->monitoredScheduledTask;

        return $monitoredScheduledTask->name === 'dummy-task' && $job->logItem->type === MonitoredScheduledTaskLogItem::TYPE_STARTING;
    });
});

it('will log skipped scheduled tasks', function () {
    TestKernel::replaceScheduledTasks(function (Schedule $schedule) {
        $schedule
            ->call(fn () => TestTime::addSecond())
            ->everyMinute()->skip(fn () => true)
            ->monitorName('dummy-task');
    });
    $this->artisan(SyncCommand::class)->assertExitCode(0);

    $this->artisan('schedule:run')->assertExitCode(0);

    $logTypes = MonitoredScheduledTask::findByName('dummy-task')->logItems->pluck('type')->toArray();

    $this->assertEquals([
        MonitoredScheduledTaskLogItem::TYPE_SKIPPED,
    ], $logTypes);
});

it('will fire a job and log failures of scheduled tasks', function () {
    TestKernel::replaceScheduledTasks(function (Schedule $schedule) {
        $schedule
            ->call(function () {
                throw new Exception("exception");
            })
            ->everyMinute()
            ->monitorName('failing-task');
    });


    $this->artisan(SyncCommand::class)->assertExitCode(0);
    $this->artisan('schedule:run')->assertExitCode(0);

    Bus::assertDispatched(function (PingOhDearJob $job) {
        $monitoredScheduledTask = $job->logItem->monitoredScheduledTask;

        return $monitoredScheduledTask->name === 'failing-task' && $job->logItem->type === MonitoredScheduledTaskLogItem::TYPE_FAILED;
    });

    $logTypes = MonitoredScheduledTask::findByName('failing-task')
        ->logItems
        ->pluck('type')
        ->toArray();

    $this->assertEquals([
        MonitoredScheduledTaskLogItem::TYPE_FAILED,
        MonitoredScheduledTaskLogItem::TYPE_STARTING,
    ], $logTypes);
});

it('will mark a task as failed when it throws an exception', function () {
    File::delete(base_path('artisan'));

    TestKernel::replaceScheduledTasks(function (Schedule $schedule) {
        $schedule->command(FailingCommand::class)->everyMinute();
    });

    $this->artisan(SyncCommand::class)->assertExitCode(0);
    $this->artisan('schedule:run')->assertExitCode(0);

    $logTypes = MonitoredScheduledTask::findByName('failing-command')
        ->logItems
        ->pluck('type')
        ->values()
        ->toArray();

    $this->assertEquals([
        MonitoredScheduledTaskLogItem::TYPE_FAILED,
        MonitoredScheduledTaskLogItem::TYPE_STARTING,
    ], $logTypes);
});

it('will not fire a job when a scheduled task finished that is not monitored', function () {
    // running the schedule without syncing to oh dear
    $this->artisan('schedule:run')->assertExitCode(0);

    Bus::assertNotDispatched(PingOhDearJob::class);
});

it('can use a specific queue to ping oh dear', function () {
    Bus::fake();

    config()->set('schedule-monitor.oh_dear.queue', 'custom-queue');

    $this->artisan(SyncCommand::class)->assertExitCode(0);
    $this->artisan('schedule:run')->assertExitCode(0);

    Bus::assertDispatched(function (PingOhDearJob $job) {
        return $job->queue === 'custom-queue';
    });
});

it('stores the command output to db', function () {
    TestKernel::replaceScheduledTasks(function (Schedule $schedule) {
        $schedule
            ->command('help')
            ->everyMinute()
            ->storeOutputInDb()
            ->monitorName('dummy-task');
    });

    $this->artisan(SyncCommand::class)->assertExitCode(0);
    $this->artisan('schedule:run')->assertExitCode(0);

    $task = MonitoredScheduledTask::findByName('dummy-task');
    $logItem = $task->logItems()->where('type', MonitoredScheduledTaskLogItem::TYPE_FINISHED)->first();

    expect($logItem->meta['output'] ?? '')->toContain('help for a command');
});

it('does not store the command output to db', function () {
    TestKernel::replaceScheduledTasks(function (Schedule $schedule) {
        $schedule
            ->command('help')
            ->everyMinute()
            ->monitorName('dummy-task');
    });

    $this->artisan(SyncCommand::class)->assertExitCode(0);
    $this->artisan('schedule:run')->assertExitCode(0);

    $task = MonitoredScheduledTask::findByName('dummy-task');
    $logItem = $task->logItems()->where('type', MonitoredScheduledTaskLogItem::TYPE_FINISHED)->first();

    expect($logItem->meta['output'])->toBeNull();
});
