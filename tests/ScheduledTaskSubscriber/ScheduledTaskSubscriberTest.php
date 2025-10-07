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

it('stores exception message in meta field for call() with exception', function () {
    TestKernel::replaceScheduledTasks(function (Schedule $schedule) {
        $schedule
            ->call(function () {
                throw new Exception('Call exception message');
            })
            ->everyMinute()
            ->monitorName('failing-call-task');
    });

    $this->artisan(SyncCommand::class)->assertExitCode(0);
    $this->artisan('schedule:run')->assertExitCode(0);

    $task = MonitoredScheduledTask::findByName('failing-call-task');
    $logItem = $task->logItems()->where('type', MonitoredScheduledTaskLogItem::TYPE_FAILED)->first();

    expect($logItem->meta)->toHaveKey('failure_message');
    expect($logItem->meta['failure_message'])->toContain('Call exception message');
    expect($logItem->meta)->toHaveKey('exit_code');
    expect($logItem->meta['exit_code'])->toBe(1);
    expect($logItem->meta)->toHaveKey('exception_class');
    expect($logItem->meta['exception_class'])->toBe(Exception::class);
});

it('stores exception message in meta field for command() with exception', function () {
    File::delete(base_path('artisan'));

    TestKernel::replaceScheduledTasks(function (Schedule $schedule) {
        $schedule->command(FailingCommand::class)->everyMinute();
    });

    $this->artisan(SyncCommand::class)->assertExitCode(0);
    $this->artisan('schedule:run')->assertExitCode(0);

    $task = MonitoredScheduledTask::findByName('failing-command');
    $logItem = $task->logItems()->where('type', MonitoredScheduledTaskLogItem::TYPE_FAILED)->first();

    expect($logItem->meta)->toHaveKey('failure_message');
    expect($logItem->meta['failure_message'])->toContain('failing');
    expect($logItem->meta)->toHaveKey('exit_code');
    expect($logItem->meta['exit_code'])->toBe(1);
    expect($logItem->meta)->toHaveKey('runtime');
    expect($logItem->meta['runtime'])->toBeGreaterThan(0);
});

it('handles ScheduledBackgroundTaskFinished for failed tasks', function () {
    $task = MonitoredScheduledTask::factory()->create(['name' => 'test-background-command']);

    $outputFile = storage_path('logs/test-background-failed.log');
    File::ensureDirectoryExists(dirname($outputFile));
    File::put($outputFile, "Starting failed command...\nException: Background task failed\nStack trace...");

    $schedule = app(\Illuminate\Console\Scheduling\Schedule::class);
    $scheduledEvent = $schedule->command('artisan test:command');
    $scheduledEvent->exitCode = 1;
    $scheduledEvent->output = $outputFile;

    $mockEvent = new \Illuminate\Console\Events\ScheduledBackgroundTaskFinished($scheduledEvent);

    $task->markAsBackgroundTaskFinished($mockEvent);

    $logItem = $task->logItems()->where('type', MonitoredScheduledTaskLogItem::TYPE_FAILED)->first();

    expect($logItem)->not->toBeNull();
    expect($logItem->meta)->toHaveKey('exit_code');
    expect($logItem->meta['exit_code'])->toBe(1);
    expect($logItem->meta)->toHaveKey('failure_message');
    expect($logItem->meta['failure_message'])->toContain('Background task failed');
    expect($task->last_failed_at)->not->toBeNull();

    File::delete($outputFile);
});

it('handles ScheduledBackgroundTaskFinished for successful tasks', function () {
    $task = MonitoredScheduledTask::factory()->create(['name' => 'test-background-success']);

    $outputFile = storage_path('logs/test-background-success.log');
    File::ensureDirectoryExists(dirname($outputFile));
    File::put($outputFile, "Command executed successfully\nAll done!");

    $schedule = app(\Illuminate\Console\Scheduling\Schedule::class);
    $scheduledEvent = $schedule->command('artisan test:command');
    $scheduledEvent->exitCode = 0;
    $scheduledEvent->output = $outputFile;

    $mockEvent = new \Illuminate\Console\Events\ScheduledBackgroundTaskFinished($scheduledEvent);

    $task->markAsBackgroundTaskFinished($mockEvent);

    $logItem = $task->logItems()->where('type', MonitoredScheduledTaskLogItem::TYPE_FINISHED)->first();

    expect($logItem)->not->toBeNull();
    expect($logItem->meta)->toHaveKey('exit_code');
    expect($logItem->meta['exit_code'])->toBe(0);
    expect($logItem->meta)->toHaveKey('output');
    expect($logItem->meta['output'])->toContain('Command executed successfully');
    expect($task->last_finished_at)->not->toBeNull();

    File::delete($outputFile);
});

it('does not create duplicate failed logs when both events fire', function () {
    File::delete(base_path('artisan'));

    TestKernel::replaceScheduledTasks(function (Schedule $schedule) {
        $schedule->command(FailingCommand::class)->everyMinute();
    });

    $this->artisan(SyncCommand::class)->assertExitCode(0);
    $this->artisan('schedule:run')->assertExitCode(0);

    $task = MonitoredScheduledTask::findByName('failing-command');

    // Should only have ONE failed log, not two
    $failedLogs = $task->logItems()->where('type', MonitoredScheduledTaskLogItem::TYPE_FAILED)->get();
    expect($failedLogs)->toHaveCount(1);

    // The single failed log should have metadata from BOTH events merged
    $failedLog = $failedLogs->first();
    expect($failedLog->meta)->toHaveKey('failure_message'); // From ScheduledTaskFailed
    expect($failedLog->meta)->toHaveKey('exception_class'); // From ScheduledTaskFailed
    expect($failedLog->meta)->toHaveKey('runtime'); // From ScheduledTaskFinished
    expect($failedLog->meta)->toHaveKey('exit_code');
    expect($failedLog->meta['exit_code'])->toBe(1);
});

it('extracts exception message from background task output', function () {
    $task = MonitoredScheduledTask::factory()->create(['name' => 'test-exception-parsing']);

    $outputFile = storage_path('logs/test-exception-parsing.log');
    File::ensureDirectoryExists(dirname($outputFile));

    // Simulate real Laravel exception output format
    File::put(
        $outputFile,
        <<<'OUTPUT'
Starting failed command...

   RuntimeException

  Something went terribly wrong in the background

  at app/Console/Commands/FailingCommand.php:42
OUTPUT
    );

    $schedule = app(\Illuminate\Console\Scheduling\Schedule::class);
    $scheduledEvent = $schedule->command('artisan test:command');
    $scheduledEvent->exitCode = 1;
    $scheduledEvent->output = $outputFile;

    $mockEvent = new \Illuminate\Console\Events\ScheduledBackgroundTaskFinished($scheduledEvent);

    $task->markAsBackgroundTaskFinished($mockEvent);

    $logItem = $task->logItems()->where('type', MonitoredScheduledTaskLogItem::TYPE_FAILED)->first();

    expect($logItem)->not->toBeNull();
    expect($logItem->meta)->toHaveKey('failure_message');
    // The regex will pick up text from the output, even if not in perfect Exception: format
    expect($logItem->meta['failure_message'])->not->toBeEmpty();

    File::delete($outputFile);
});
