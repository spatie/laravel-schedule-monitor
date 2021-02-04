<?php

namespace Spatie\ScheduleMonitor;

use Illuminate\Console\Events\CommandStarting;
use Illuminate\Console\Scheduling\Event as SchedulerEvent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use OhDear\PhpSdk\OhDear;
use Spatie\ScheduleMonitor\Commands\CleanLogCommand;
use Spatie\ScheduleMonitor\Commands\CreateMigrationCommand;
use Spatie\ScheduleMonitor\Commands\ListCommand;
use Spatie\ScheduleMonitor\Commands\SyncCommand;
use Spatie\ScheduleMonitor\Commands\VerifyCommand;
use Spatie\ScheduleMonitor\EventHandlers\BackgroundCommandListener;
use Spatie\ScheduleMonitor\EventHandlers\ScheduledTaskEventSubscriber;

class ScheduleMonitorServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this
            ->registerPublishables()
            ->registerCommands()
            ->configureOhDearApi()
            ->registerEventHandlers()
            ->registerSchedulerEventMacros();
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/schedule-monitor.php', 'schedule-monitor');
    }

    protected function registerPublishables(): self
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/schedule-monitor.php' => config_path('schedule-monitor.php'),
            ], 'config');
        }

        return $this;
    }

    protected function registerCommands(): self
    {
        $this->commands([
            CleanLogCommand::class,
            ListCommand::class,
            SyncCommand::class,
            VerifyCommand::class,
            CreateMigrationCommand::class
        ]);

        return $this;
    }

    protected function configureOhDearApi(): self
    {
        if (! class_exists(OhDear::class)) {
            return $this;
        }

        $this->app->bind(OhDear::class, function () {
            $apiToken = config('schedule-monitor.oh_dear.api_token');

            return new OhDear($apiToken, 'https://ohdear.app/api/');
        });

        return $this;
    }

    protected function registerEventHandlers(): self
    {
        Event::subscribe(ScheduledTaskEventSubscriber::class);
        Event::listen(CommandStarting::class, BackgroundCommandListener::class);

        return $this;
    }

    protected function registerSchedulerEventMacros(): self
    {
        SchedulerEvent::macro('monitorName', function (string $monitorName) {
            $this->monitorName = $monitorName;

            return $this;
        });

        SchedulerEvent::macro('graceTimeInMinutes', function (int $graceTimeInMinutes) {
            $this->graceTimeInMinutes = $graceTimeInMinutes;

            return $this;
        });

        SchedulerEvent::macro('doNotMonitor', function () {
            $this->doNotMonitor = true;

            return $this;
        });

        return $this;
    }
}
