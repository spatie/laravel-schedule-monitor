<?php

namespace Spatie\ScheduleMonitor\Commands\Tables;

use Spatie\ScheduleMonitor\Support\ScheduledTasks\ScheduledTasks;
use Spatie\ScheduleMonitor\Support\ScheduledTasks\Tasks\Task;

class ReadyForMonitoringTasksTable extends ScheduledTasksTable
{
    public function render(): void
    {
        $tasks = ScheduledTasks::createForSchedule()
            ->uniqueTasks()
            ->reject(fn (Task $task) => $task->isBeingMonitored());

        if ($tasks->isEmpty()) {
            return;
        }

        $this->command->line('');
        $this->command->line('Run sync to start monitoring');
        $this->command->line('----------------------------');
        $this->command->line('');
        $this->command->line('These tasks will be monitored after running `php artisan schedule-monitor:sync`');
        $this->command->line('');

        $tasks = ScheduledTasks::createForSchedule()
            ->uniqueTasks()
            ->reject(fn (Task $task) => $task->isBeingMonitored());

        $headers = ['Name', 'Type', 'Frequency'];
        $rows = $tasks->map(function (Task $task) {
            return [
                'name' => $task->name(),
                'type' => ucfirst($task->type()),
                'cron_expression' => $task->humanReadableCron(),
            ];
        });
        $this->command->table($headers, $rows);
    }
}
