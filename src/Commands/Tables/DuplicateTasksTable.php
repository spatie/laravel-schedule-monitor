<?php

namespace Spatie\ScheduleMonitor\Commands\Tables;

use Spatie\ScheduleMonitor\Support\ScheduledTasks\ScheduledTasks;
use Spatie\ScheduleMonitor\Support\ScheduledTasks\Tasks\Task;

class DuplicateTasksTable extends ScheduledTasksTable
{
    public function render(): void
    {
        $duplicateTasks = ScheduledTasks::createForSchedule()->duplicateTasks();

        if ($duplicateTasks->isEmpty()) {
            return;
        }

        $this->command->line('');
        $this->command->line('Duplicate tasks');
        $this->command->line('---------------');
        $this->command->line('These tasks could not be monitored because they have a duplicate name.');
        $this->command->line('');

        $headers = ['Type', 'Frequency'];
        $rows = $duplicateTasks->map(function (Task $task) {
            return [
                'name' => $task->name(),
                'type' => ucfirst($task->type()),
                'cron_expression' => $task->humanReadableCron(),
            ];
        });

        $this->command->table($headers, $rows);

        $this->command->line('');
        $this->command->line('To monitor these tasks you should add `->monitorName()` in the schedule to manually specify a unique name.');
    }
}
