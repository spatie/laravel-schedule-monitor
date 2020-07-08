<?php

namespace Spatie\ScheduleMonitor\Commands\Tables;

use Spatie\ScheduleMonitor\Support\ScheduledTasks\ScheduledTasks;
use Spatie\ScheduleMonitor\Support\ScheduledTasks\Tasks\Task;

class UnnamedTasksTable extends ScheduledTasksTable
{
    public function render(): void
    {
        $unnamedTasks = ScheduledTasks::createForSchedule()->unnamedTasks();

        if ($unnamedTasks->isEmpty()) {
            return;
        }

        $this->command->line('');
        $this->command->line('Unnamed tasks');
        $this->command->line('-------------');
        $this->command->line('These tasks cannot be monitored because no name could be determined for them.');
        $this->command->line('');


        $headers = ['Type', 'Frequency'];
        $rows = $unnamedTasks->map(function (Task $task) {
            return [
                'type' => ucfirst($task->type()),
                'cron_expression' => $task->humanReadableCron(),
            ];
        });

        $this->command->table($headers, $rows);

        $this->command->line('');
        $this->command->line('To monitor these tasks you should add `->monitorName()` in the schedule to manually specify a name.');
    }
}
