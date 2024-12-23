<?php

namespace Spatie\ScheduleMonitor;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Console\Scheduling\Event as SchedulerEvent;
use Illuminate\Support\Facades\Event;
use OhDear\PhpSdk\OhDear;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Spatie\ScheduleMonitor\Commands\ListCommand;
use Spatie\ScheduleMonitor\Commands\SyncCommand;
use Spatie\ScheduleMonitor\Commands\VerifyCommand;
use Spatie\ScheduleMonitor\EventHandlers\BackgroundCommandListener;
use Spatie\ScheduleMonitor\EventHandlers\ScheduledTaskEventSubscriber;
use Spatie\ScheduleMonitor\Exceptions\InvalidClassException;
use Spatie\ScheduleMonitor\Jobs\PingOhDearJob;
use Spatie\ScheduleMonitor\Models\MonitoredScheduledTask;
use Spatie\ScheduleMonitor\Models\MonitoredScheduledTaskLogItem;

class ScheduleMonitorServiceProvider extends PackageServiceProvider
{
    private string $monitorName;

    private int $graceTimeInMinutes;

    private bool $doNotMonitor;

    private bool $doNotMonitorAtOhDear;

    private bool $storeOutputInDb;

    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-schedule-monitor')
            ->hasViews()
            ->hasConfigFile()
            ->hasMigrations('create_schedule_monitor_tables')
            ->hasCommands([
                ListCommand::class,
                SyncCommand::class,
                VerifyCommand::class,
            ]);
    }

    public function packageBooted()
    {
        $this
            ->configureOhDearApi()
            ->silenceOhDearJob()
            ->registerEventHandlers()
            ->registerSchedulerEventMacros()
            ->registerModelBindings();
    }

    protected function registerModelBindings()
    {
        $config = config('schedule-monitor.models');

        $this->app->bind(MonitoredScheduledTask::class, $config['monitored_scheduled_task']);
        $this->app->bind(MonitoredScheduledTaskLogItem::class, $config['monitored_scheduled_log_item']);

        $this->protectAgainstInvalidClassDefinition(MonitoredScheduledTask::class, app($config['monitored_scheduled_task']));
        $this->protectAgainstInvalidClassDefinition(MonitoredScheduledTaskLogItem::class, app($config['monitored_scheduled_log_item']));

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

    protected function silenceOhDearJob(): self
    {
        if (! config('schedule-monitor.oh_dear.silence_ping_oh_dear_job_in_horizon', true)) {
            return $this;
        }

        $silencedJobs = config('horizon.silenced', []);

        if (in_array(PingOhDearJob::class, $silencedJobs)) {
            return $this;
        }

        $silencedJobs[] = PingOhDearJob::class;

        config()->set('horizon.silenced', $silencedJobs);

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
        $this->app->singleton(
            Support\ScheduledTasks\ScheduleMonitoringConfigurationsRepository::class,
            static fn () => new Support\ScheduledTasks\ScheduleMonitoringConfigurationsRepository(),
        );

        /** @var Support\ScheduledTasks\ScheduleMonitoringConfigurationsRepository $scheduleMonitoringConfigurationsRepository */
        $scheduleMonitoringConfigurationsRepository = $this->app->make(Support\ScheduledTasks\ScheduleMonitoringConfigurationsRepository::class);

        SchedulerEvent::macro('monitorName', function (string $monitorName) use ($scheduleMonitoringConfigurationsRepository) {
            $scheduleMonitoringConfigurationsRepository->setMonitorName($this, $monitorName);

            return $this;
        });

        SchedulerEvent::macro('graceTimeInMinutes', function (int $graceTimeInMinutes) use ($scheduleMonitoringConfigurationsRepository) {
            $scheduleMonitoringConfigurationsRepository->setGraceTimeInMinutes($this, $graceTimeInMinutes);

            return $this;
        });

        SchedulerEvent::macro('doNotMonitor', function (bool $bool = true) use ($scheduleMonitoringConfigurationsRepository) {
            $scheduleMonitoringConfigurationsRepository->setDoNotMonitor($this, $bool);

            return $this;
        });

        SchedulerEvent::macro('doNotMonitorAtOhDear', function (bool $bool = true) use ($scheduleMonitoringConfigurationsRepository) {
            $scheduleMonitoringConfigurationsRepository->setDoNotMonitorAtOhDear($this, $bool);

            return $this;
        });

        SchedulerEvent::macro('storeOutputInDb', function () use ($scheduleMonitoringConfigurationsRepository) {
            $scheduleMonitoringConfigurationsRepository->setStoreOutputInDb($this, true);
            /** @psalm-suppress UndefinedMethod */
            $this->ensureOutputIsBeingCaptured();

            return $this;
        });

        return $this;
    }

    protected function protectAgainstInvalidClassDefinition($packageClass, $providedModel): void
    {
        if (! ($providedModel instanceof $packageClass)) {
            $providedClass = get_class($providedModel);

            throw new InvalidClassException("The provided class name {$providedClass} does not extend the required package class {$packageClass}.");
        }
    }
}
