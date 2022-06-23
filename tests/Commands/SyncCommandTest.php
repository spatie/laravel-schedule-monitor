<?php

use Illuminate\Console\Scheduling\Schedule;
use Spatie\ScheduleMonitor\Commands\SyncCommand;
use Spatie\ScheduleMonitor\Models\MonitoredScheduledTask;
use Spatie\ScheduleMonitor\Tests\TestClasses\TestJob;
use Spatie\ScheduleMonitor\Tests\TestClasses\TestKernel;
use Spatie\TestTime\TestTime;

beforeEach(function () {
    TestTime::freeze('Y-m-d H:i:s', '2020-01-01 00:00:00');
});

it('can sync the schedule with the db and oh dear', function () {
    TestKernel::registerScheduledTasks(function (Schedule $schedule) {
        $schedule->command('dummy')->everyMinute();
        $schedule->exec('execute')->everyFifteenMinutes();
        $schedule->call(fn () => 1 + 1)->hourly()->monitorName('my-closure');
        $schedule->job(new TestJob())->daily()->timezone('Asia/Kolkata');
    });

    $this->artisan(SyncCommand::class);

    $monitoredScheduledTasks = MonitoredScheduledTask::get();
    expect($monitoredScheduledTasks)->toHaveCount(4);

    $this->assertDatabaseHas('monitored_scheduled_tasks', [
        'name' => 'dummy',
        'type' => 'command',
        'cron_expression' => '* * * * *',
        'ping_url' => 'https://ping.ohdear.app/test-ping-url-dummy',
        'registered_on_oh_dear_at' => now()->format('Y-m-d H:i:s'),
        'grace_time_in_minutes' => 5,
        'last_pinged_at' => null,
        'last_started_at' => null,
        'last_finished_at' => null,
        'timezone' => 'UTC',
    ]);

    $this->assertDatabaseHas('monitored_scheduled_tasks', [
        'name' => 'execute',
        'type' => 'shell',
        'cron_expression' => '*/15 * * * *',
        'ping_url' => 'https://ping.ohdear.app/test-ping-url-execute',
        'registered_on_oh_dear_at' => now()->format('Y-m-d H:i:s'),
        'grace_time_in_minutes' => 5,
        'last_pinged_at' => null,
        'last_started_at' => null,
        'last_finished_at' => null,
        'timezone' => 'UTC',
    ]);

    $this->assertDatabaseHas('monitored_scheduled_tasks', [
        'name' => 'my-closure',
        'type' => 'closure',
        'cron_expression' => '0 * * * *',
        'ping_url' => 'https://ping.ohdear.app/test-ping-url-my-closure',
        'registered_on_oh_dear_at' => now()->format('Y-m-d H:i:s'),
        'grace_time_in_minutes' => 5,
        'last_pinged_at' => null,
        'last_started_at' => null,
        'last_finished_at' => null,
        'timezone' => 'UTC',
    ]);

    $this->assertDatabaseHas('monitored_scheduled_tasks', [
        'name' => TestJob::class,
        'type' => 'job',
        'cron_expression' => '0 0 * * *',
        'ping_url' => 'https://ping.ohdear.app/test-ping-url-' . urlencode(TestJob::class),
        'registered_on_oh_dear_at' => now()->format('Y-m-d H:i:s'),
        'grace_time_in_minutes' => 5,
        'last_pinged_at' => null,
        'last_started_at' => null,
        'last_finished_at' => null,
        'timezone' => 'Asia/Kolkata',
    ]);

    $this->assertMatchesSnapshot($this->ohDear->getSyncedCronCheckAttributes());
});

it('will not monitor commands without a name', function () {
    TestKernel::registerScheduledTasks(function (Schedule $schedule) {
        $schedule->call(fn () => 'a closure has no name')->hourly();
    });

    $this->artisan(SyncCommand::class);

    $monitoredScheduledTasks = MonitoredScheduledTask::get();
    expect($monitoredScheduledTasks)->toHaveCount(0);

    expect($this->ohDear->getSyncedCronCheckAttributes())->toEqual([]);
});

it('will remove old tasks from the database', function () {
    MonitoredScheduledTask::factory()->create(['name' => 'old-task']);
    expect(MonitoredScheduledTask::get())->toHaveCount(1);

    TestKernel::registerScheduledTasks(function (Schedule $schedule) {
        $schedule->command('new')->everyMinute();
    });

    $this->artisan(SyncCommand::class);

    expect(MonitoredScheduledTask::get())->toHaveCount(1);

    expect(MonitoredScheduledTask::first()->name)->toEqual('new');
});

it('can use custom grace time', function () {
    TestKernel::registerScheduledTasks(function (Schedule $schedule) {
        $schedule->command('dummy')->everyMinute()->graceTimeInMinutes(15);
    });

    $this->artisan(SyncCommand::class);

    $this->assertDatabaseHas('monitored_scheduled_tasks', [
        'grace_time_in_minutes' => 15,
    ]);

    $syncedCronChecks = $this->ohDear->getSyncedCronCheckAttributes();

    expect($syncedCronChecks[0]['grace_time_in_minutes'])->toEqual(15);
});

it('will not monitor tasks that should not be monitored', function () {
    TestKernel::registerScheduledTasks(function (Schedule $schedule) {
        $schedule->command('dummy')->everyMinute()->doNotMonitor();
    });

    $this->artisan(SyncCommand::class);

    expect(MonitoredScheduledTask::get())->toHaveCount(0);

    expect($this->ohDear->getSyncedCronCheckAttributes())->toEqual([]);
});

it('will remove tasks from the db that should not be monitored anymore', function () {
    MonitoredScheduledTask::factory()->create(['name' => 'not-monitored']);
    expect(MonitoredScheduledTask::get())->toHaveCount(1);

    TestKernel::registerScheduledTasks(function (Schedule $schedule) {
        $schedule->command('not-monitored')->everyMinute()->doNotMonitor();
    });
    $this->artisan(SyncCommand::class);

    expect(MonitoredScheduledTask::get())->toHaveCount(0);
});

it('will update tasks that have their schedule updated', function () {
    $monitoredScheduledTask = MonitoredScheduledTask::factory()->create([
        'name' => 'dummy',
        'cron_expression' => '* * * * *',
    ]);

    TestKernel::registerScheduledTasks(function (Schedule $schedule) {
        $schedule->command('dummy')->daily();
    });
    $this->artisan(SyncCommand::class);

    expect(MonitoredScheduledTask::get())->toHaveCount(1);
    expect($monitoredScheduledTask->refresh()->cron_expression)->toEqual('0 0 * * *');
});

it('will not sync with oh dear when no site id is set', function () {
    config()->set('schedule-monitor.oh_dear.site_id', null);

    TestKernel::registerScheduledTasks(function (Schedule $schedule) {
        $schedule->command('dummy')->daily();
    });
    $this->artisan(SyncCommand::class);
    expect(MonitoredScheduledTask::get())->toHaveCount(1);
    expect($this->ohDear->getSyncedCronCheckAttributes())->toEqual([]);
});
