<?php

namespace Spatie\ScheduleMonitor\Commands;

use Illuminate\Console\Command;
use OhDear\PhpSdk\OhDear;
use OhDear\PhpSdk\Resources\CronCheck;
use Spatie\ScheduleMonitor\Models\MonitoredScheduledTask;
use Spatie\ScheduleMonitor\Support\Concerns\UsesScheduleMonitoringModels;
use Spatie\ScheduleMonitor\Support\ScheduledTasks\ScheduledTasks;
use Spatie\ScheduleMonitor\Support\ScheduledTasks\Tasks\Task;
use function Termwind\render;

class SyncCommand extends Command
{
    use UsesScheduleMonitoringModels;

    public $signature = 'schedule-monitor:sync {--keep-old}';

    public $description = 'Sync the schedule of the app with the schedule monitor';

    public function handle()
    {
        render(view('schedule-monitor::alert', [
            'message' => 'Start syncing schedule...',
            'class' => 'text-green',
        ]));

        $this
            ->storeScheduledTasksInDatabase()
            ->storeMonitoredScheduledTasksInOhDear();

        $monitoredScheduledTasksCount = $this->getMonitoredScheduleTaskModel()->count();

        render(view('schedule-monitor::sync', [
            'monitoredScheduledTasksCount' => $monitoredScheduledTasksCount,
        ]));
    }

    protected function storeScheduledTasksInDatabase(): self
    {
        render(view('schedule-monitor::alert', [
            'message' => 'Start syncing schedule with database...',
        ]));

        $monitoredScheduledTasks = ScheduledTasks::createForSchedule()
            ->uniqueTasks()
            ->map(function (Task $task) {
                return $this->getMonitoredScheduleTaskModel()->updateOrCreate(
                    ['name' => $task->name()],
                    array_merge([
                        'type' => $task->type(),
                        'cron_expression' => $task->cronExpression(),
                        'timezone' => $task->timezone(),
                        'grace_time_in_minutes' => $task->graceTimeInMinutes(),
                    ], $task->shouldMonitorAtOhDear() ? [] : ['ping_url' => null])
                );
            });

        if (! $this->option('keep-old')) {
            $this->getMonitoredScheduleTaskModel()->query()
                ->whereNotIn('id', $monitoredScheduledTasks->pluck('id'))
                ->delete();
        }

        return $this;
    }

    protected function storeMonitoredScheduledTasksInOhDear(): self
    {
        if (! class_exists(OhDear::class)) {
            return $this;
        }

        $siteId = config('schedule-monitor.oh_dear.site_id');

        if (! $siteId) {
            render(view('schedule-monitor::alert', [
                'message' => <<<HTML
                    <div>
                        Not syncing schedule with <b class="bg-red-700 text-white px-1">oh dear</b> because not <b class="bg-gray-500 px-1 text-white">site_id</b>
                        is not set in the <b class="bg-gray-500 px-1 text-white">oh-dear</b> config file.
                    </div>
                    <div>
                        Learn how to set this up at <a href="https://ohdear.app/docs/general/cron-job-monitoring/php#cron-monitoring-in-laravel-php">https://ohdear.app/docs/general/cron-job-monitoring/php#cron-monitoring-in-laravel-php</a>.
                    </div>
                HTML,
                'class' => 'text-yellow',
            ]));

            return $this;
        }

        render(view('schedule-monitor::alert', [
            'message' => 'Start syncing schedule with Oh Dear...',
        ]));

        $cronChecks = $this->option('keep-old')
            ? $this->pushMonitoredScheduledTaskToOhDear($siteId)
            : $this->syncMonitoredScheduledTaskWithOhDear($siteId);

        render(view('schedule-monitor::alert', [
            'message' => 'Successfully synced schedule with Oh Dear!',
            'class' => 'text-green',
        ]));

        collect($cronChecks)
            ->each(
                function (CronCheck $cronCheck) {
                    if (! $monitoredScheduledTask = $this->getMonitoredScheduleTaskModel()->findForCronCheck($cronCheck)) {
                        return;
                    }

                    $monitoredScheduledTask->update(['ping_url' => $this->pingUrl($cronCheck)]);
                    $monitoredScheduledTask->markAsRegisteredOnOhDear();
                }
            );

        return $this;
    }

    protected function pingUrl(CronCheck $cronCheck): string
    {
        if ($userDefinedEndpoint = config('schedule-monitor.oh_dear.endpoint_url')) {
            return rtrim($userDefinedEndpoint, '/') . '/' . $cronCheck->uuid;
        }

        return $cronCheck->pingUrl;
    }

    protected function syncMonitoredScheduledTaskWithOhDear(int $siteId): array
    {
        $monitoredScheduledTasks = $this->getMonitoredScheduleTaskModel()
            ->whereIn(
                'name',
                ScheduledTasks::createForSchedule()
                    ->monitoredAtOhDear()
                    ->map->name()
            )
            ->get();

        $cronChecks = $monitoredScheduledTasks
            ->map(function (MonitoredScheduledTask $monitoredScheduledTask) {
                return [
                    'name' => $monitoredScheduledTask->name,
                    'type' => 'cron',
                    'cron_expression' => $monitoredScheduledTask->cron_expression,
                    'grace_time_in_minutes' => $monitoredScheduledTask->grace_time_in_minutes,
                    'server_timezone' => $monitoredScheduledTask->timezone,
                    'description' => '',
                ];
            })
            ->toArray();

        $cronChecks = app(OhDear::class)->site($siteId)->syncCronChecks($cronChecks);

        return $cronChecks;
    }

    protected function pushMonitoredScheduledTaskToOhDear(int $siteId): array
    {
        $tasksToRegister = $this->getMonitoredScheduleTaskModel()
            ->whereNull('registered_on_oh_dear_at')
            ->whereIn(
                'name',
                ScheduledTasks::createForSchedule()
                    ->monitoredAtOhDear()
                    ->map->name()
            )
            ->get();

        $cronChecks = [];
        foreach ($tasksToRegister as $taskToRegister) {
            $cronChecks[] = app(OhDear::class)->createCronCheck(
                siteId: $siteId,
                name: $taskToRegister->name,
                cronExpression: $taskToRegister->cron_expression,
                graceTimeInMinutes: $taskToRegister->grace_time_in_minutes,
                description: '',
                serverTimezone: $taskToRegister->timezone,
            );
        }

        return $cronChecks;
    }
}
