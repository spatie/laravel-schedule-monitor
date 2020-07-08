<?php

namespace Spatie\ScheduleMonitor\Support\ScheduledTasks;

use Illuminate\Console\Scheduling\Event;
use Spatie\ScheduleMonitor\Support\ScheduledTasks\Tasks\ClosureTask;
use Spatie\ScheduleMonitor\Support\ScheduledTasks\Tasks\CommandTask;
use Spatie\ScheduleMonitor\Support\ScheduledTasks\Tasks\JobTask;
use Spatie\ScheduleMonitor\Support\ScheduledTasks\Tasks\ShellTask;
use Spatie\ScheduleMonitor\Support\ScheduledTasks\Tasks\Task;

class ScheduledTaskFactory
{
    public static function createForEvent(Event $task): Task
    {
        $taskClass = collect([
            ClosureTask::class,
            JobTask::class,
            CommandTask::class,
            ShellTask::class,
        ])
            ->first(fn (string $taskClass) => $taskClass::canHandleEvent($task));

        return new $taskClass($task);
    }
}
