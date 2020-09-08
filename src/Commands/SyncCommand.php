<?php

namespace Spatie\ScheduleMonitor\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use OhDear\PhpSdk\OhDear;
use OhDear\PhpSdk\Resources\CronCheck;
use Spatie\ScheduleMonitor\Models\MonitoredScheduledTask;
use Spatie\ScheduleMonitor\Support\ScheduledTasks\ScheduledTasks;
use Spatie\ScheduleMonitor\Support\ScheduledTasks\Tasks\Task;

class SyncCommand extends Command
{
    public $signature = 'schedule-monitor:sync';

    public $description = 'Sync the schedule of the app with the schedule monitor';

    public function handle()
    {
        $this->info('Start syncing schedule...' . PHP_EOL);

        $this
            ->syncScheduledTasksWithDatabase()
            ->syncMonitoredScheduledTaskWithOhDear();

        $monitoredScheduledTasksCount = MonitoredScheduledTask::count();
        $this->info('');
        $this->info('All done! Now monitoring ' . $monitoredScheduledTasksCount . ' ' . Str::plural('scheduled task', $monitoredScheduledTasksCount) . '.');
        $this->info('');
        $this->info('Run `php artisan schedule-monitor:list` to see which jobs are now monitored.');
    }

    protected function syncScheduledTasksWithDatabase(): self
    {
        $this->comment('Start syncing schedule with database...');

        $monitoredScheduledTasks = ScheduledTasks::createForSchedule()
            ->uniqueTasks()
            ->map(function (Task $task) {
                return MonitoredScheduledTask::updateOrCreate(
                    ['name' => $task->name()],
                    [
                        'type' => $task->type(),
                        'cron_expression' => $task->cronExpression(),
                        'grace_time_in_minutes' => $task->graceTimeInMinutes(),
                    ]
                );
            });

        MonitoredScheduledTask::query()
            ->whereNotIn('id', $monitoredScheduledTasks->pluck('id'))
            ->delete();

        return $this;
    }

    protected function syncMonitoredScheduledTaskWithOhDear(): self
    {
        if (! class_exists(OhDear::class)) {
            return $this;
        }

        $siteId = config('schedule-monitor.oh_dear.site_id');

        if (! $siteId) {
            $this->warn('Not syncing schedule with Oh Dear because not `site_id` is not set in the `oh-dear` config file. Learn how to set this up at https://ohdear.app/TODO-add-link.');

            return $this;
        }

        $this->comment('Start syncing schedule with Oh Dear...');

        $monitoredScheduledTasks = MonitoredScheduledTask::get();

        $cronChecks = $monitoredScheduledTasks
            ->map(function (MonitoredScheduledTask $monitoredScheduledTask) {
                return [
                    'name' => $monitoredScheduledTask->name,
                    'type' => 'cron',
                    'cron_expression' => $monitoredScheduledTask->cron_expression,
                    'grace_time_in_minutes' => $monitoredScheduledTask->grace_time_in_minutes,
                    'server_timezone' => config('app.timezone'),
                    'description' => '',
                ];
            })
            ->toArray();

        $cronChecks = app(OhDear::class)->site($siteId)->syncCronChecks($cronChecks);
        $this->comment('Successfully synced schedule with Oh Dear!');

        collect($cronChecks)
            ->each(
                function (CronCheck $cronCheck) {
                    if (! $monitoredScheduledTask = MonitoredScheduledTask::findForCronCheck($cronCheck)) {
                        return;
                    }

                    $monitoredScheduledTask->update(['ping_url' => $cronCheck->pingUrl]);
                    $monitoredScheduledTask->markAsRegisteredOnOhDear();
                }
            );

        return $this;
    }
}
