<?php

namespace Spatie\ScheduleMonitor\Commands\Tables;

use OhDear\PhpSdk\OhDear;
use Spatie\ScheduleMonitor\Support\ScheduledTasks\ScheduledTasks;
use Spatie\ScheduleMonitor\Support\ScheduledTasks\Tasks\Task;

class MonitoredTasksTable extends ScheduledTasksTable
{
    public function render(): void
    {
        $this->command->line('');
        $this->command->line('Monitored tasks');
        $this->command->line('---------------');

        $tasks = ScheduledTasks::createForSchedule()
            ->uniqueTasks()
            ->filter(fn (Task $task) => $task->isBeingMonitored());


        if ($tasks->isEmpty()) {
            $this->command->line('');
            $this->command->warn('There currently are no tasks being monitored!');

            return;
        }

        $headers = [
            'Name',
            'Type',
            'Frequency',
            'Last started at',
            'Last finished at',
            'Last failed at',
            'Next run date',
            'Grace time',
        ];

        if ($this->usingOhDear()) {
            $headers = array_merge($headers, [
                'Registered at Oh Dear',
            ]);
        }

        $rows = $tasks->map(function (Task $task) {
            $row = [
                'name' => $task->name(),
                'type' => ucfirst($task->type()),
                'cron_expression' => $task->humanReadableCron(),
                'started_at' => optional($task->lastRunStartedAt())->format('Y-m-d H:i:s') ?? 'Did not start yet',
                'finished_at' => $this->getLastRunFinishedAt($task),
                'failed_at' => $this->getLastRunFailedAt($task),
                'next_run' => $task->nextRunAt()->format('Y-m-d H:i:s'),
                'grace_time' => $task->graceTimeInMinutes(),
            ];

            if ($this->usingOhDear()) {
                $row = array_merge($row, [
                    'registered_at_oh_dear' => $task->isBeingMonitoredAtOhDear() ? '✅' : '❌',
                ]);
            }

            return $row;
        });

        $this->command->table($headers, $rows);

        if ($this->usingOhDear()) {
            if ($tasks->contains(fn (Task $task) => ! $task->isBeingMonitoredAtOhDear())) {
                $this->command->line('');
                $this->command->line('Some tasks are not registered on Oh Dear. You will not be notified when they do not run on time.');
                $this->command->line('Run `php artisan schedule-monitor:sync` to register them and receive notifications.');
            }
        }
    }

    private function getLastRunFinishedAt(Task $task)
    {
        $formattedLastRunFinishedAt = optional($task->lastRunFinishedAt())->format('Y-m-d H:i:s') ?? '';

        if ($task->lastRunFinishedTooLate()) {
            $formattedLastRunFinishedAt = "<bg=red>{$formattedLastRunFinishedAt}</>";
        }

        return $formattedLastRunFinishedAt;
    }

    public function getLastRunFailedAt(Task $task): string
    {
        if (! $lastRunFailedAt = $task->lastRunFailedAt()) {
            return '';
        }

        $formattedLastFailedAt = $lastRunFailedAt->format('Y-m-d H:i:s');

        if ($task->lastRunFailed()) {
            $formattedLastFailedAt = "<bg=red>{$formattedLastFailedAt}</>";
        }

        return $formattedLastFailedAt;
    }

    protected function usingOhDear(): bool
    {
        if (! class_exists(OhDear::class)) {
            return false;
        }

        if (empty(config('schedule-monitor.oh_dear.api_token'))) {
            return false;
        }

        if (empty(config('schedule-monitor.oh_dear.site_id'))) {
            return false;
        }

        return true;
    }
}
