<?php

namespace Spatie\ScheduleMonitor\Tests\Commands;

use Illuminate\Console\Scheduling\Schedule;
use Spatie\ScheduleMonitor\Commands\SyncCommand;
use Spatie\ScheduleMonitor\Models\MonitoredScheduledTask;
use Spatie\ScheduleMonitor\Tests\TestCase;
use Spatie\ScheduleMonitor\Tests\TestClasses\TestJob;
use Spatie\ScheduleMonitor\Tests\TestClasses\TestKernel;
use Spatie\Snapshots\MatchesSnapshots;
use Spatie\TestTime\TestTime;

class SyncCommandTest extends TestCase
{
    use MatchesSnapshots;

    public function setUp(): void
    {
        parent::setUp();

        TestTime::freeze('Y-m-d H:i:s', '2020-01-01 00:00:00');
    }

    /** @test */
    public function it_can_sync_the_schedule_with_the_db_and_oh_dear()
    {
        TestKernel::registerScheduledTasks(function (Schedule $schedule) {
            $schedule->command('dummy')->everyMinute();
            $schedule->exec('execute')->everyFifteenMinutes();
            $schedule->call(fn () => 1 + 1)->hourly()->monitorName('my-closure');
            $schedule->job(new TestJob())->daily();
        });

        $this->artisan(SyncCommand::class);

        $monitoredScheduledTasks = MonitoredScheduledTask::get();
        $this->assertCount(4, $monitoredScheduledTasks);

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
        ]);

        $this->assertMatchesSnapshot($this->ohDear->getSyncedCronCheckAttributes());
    }

    /** @test */
    public function it_will_not_monitor_commands_without_a_name()
    {
        TestKernel::registerScheduledTasks(function (Schedule $schedule) {
            $schedule->call(fn () => 'a closure has no name')->hourly();
        });

        $this->artisan(SyncCommand::class);

        $monitoredScheduledTasks = MonitoredScheduledTask::get();
        $this->assertCount(0, $monitoredScheduledTasks);

        $this->assertEquals([], $this->ohDear->getSyncedCronCheckAttributes());
    }

    /** @test **/
    public function it_will_remove_old_tasks_from_the_database()
    {
        factory(MonitoredScheduledTask::class)->create(['name' => 'old-task']);
        $this->assertCount(1, MonitoredScheduledTask::get());

        TestKernel::registerScheduledTasks(function (Schedule $schedule) {
            $schedule->command('new')->everyMinute();
        });

        $this->artisan(SyncCommand::class);

        $this->assertCount(1, MonitoredScheduledTask::get());

        $this->assertEquals('new', MonitoredScheduledTask::first()->name);
    }

    /** @test */
    public function it_can_use_custom_grace_time()
    {
        TestKernel::registerScheduledTasks(function (Schedule $schedule) {
            $schedule->command('dummy')->everyMinute()->graceTimeInMinutes(15);
        });

        $this->artisan(SyncCommand::class);

        $this->assertDatabaseHas('monitored_scheduled_tasks', [
            'grace_time_in_minutes' => 15,
        ]);

        $syncedCronChecks = $this->ohDear->getSyncedCronCheckAttributes();

        $this->assertEquals(15, $syncedCronChecks[0]['grace_time_in_minutes']);
    }

    /** @test */
    public function it_will_not_monitor_tasks_that_should_not_be_monitored()
    {
        TestKernel::registerScheduledTasks(function (Schedule $schedule) {
            $schedule->command('dummy')->everyMinute()->doNotMonitor();
        });

        $this->artisan(SyncCommand::class);

        $this->assertCount(0, MonitoredScheduledTask::get());

        $this->assertEquals([], $this->ohDear->getSyncedCronCheckAttributes());
    }

    /** @test */
    public function it_will_remove_tasks_from_the_db_that_should_not_be_monitored_anymore()
    {
        factory(MonitoredScheduledTask::class)->create(['name' => 'not-monitored']);
        $this->assertCount(1, MonitoredScheduledTask::get());

        TestKernel::registerScheduledTasks(function (Schedule $schedule) {
            $schedule->command('not-monitored')->everyMinute()->doNotMonitor();
        });
        $this->artisan(SyncCommand::class);

        $this->assertCount(0, MonitoredScheduledTask::get());
    }

    /** @test */
    public function it_will_update_tasks_that_have_their_schedule_updated()
    {
        $monitoredScheduledTask = factory(MonitoredScheduledTask::class)->create([
            'name' => 'dummy',
            'cron_expression' => '* * * * *',
        ]);

        TestKernel::registerScheduledTasks(function (Schedule $schedule) {
            $schedule->command('dummy')->daily();
        });
        $this->artisan(SyncCommand::class);

        $this->assertCount(1, MonitoredScheduledTask::get());
        $this->assertEquals('0 0 * * *', $monitoredScheduledTask->refresh()->cron_expression);
    }

    /** @test */
    public function it_will_not_sync_with_oh_dear_when_no_site_id_is_set()
    {
        config()->set('schedule-monitor.oh_dear.site_id', null);

        TestKernel::registerScheduledTasks(function (Schedule $schedule) {
            $schedule->command('dummy')->daily();
        });
        $this->artisan(SyncCommand::class);
        $this->assertCount(1, MonitoredScheduledTask::get());
        $this->assertEquals([], $this->ohDear->getSyncedCronCheckAttributes());
    }
}
