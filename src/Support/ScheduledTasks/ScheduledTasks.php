<?php

namespace Spatie\ScheduleMonitor\Support\ScheduledTasks;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Collection;
use Spatie\ScheduleMonitor\Support\ScheduledTasks\Tasks\Task;

class ScheduledTasks
{
    protected Schedule $schedule;

    protected Collection $tasks;

    public static function createForSchedule()
    {
        $schedule = app(Schedule::class);

        return new static($schedule);
    }

    public function __construct(Schedule $schedule)
    {
        $this->schedule = $schedule;

        $this->tasks = collect($this->schedule->events())
            ->filter(
                fn (Event $event): bool => $event->runsInEnvironment(config('app.env'))
            )
            ->map(
                fn (Event $event): Task => ScheduledTaskFactory::createForEvent($event)
            );
    }

    public function uniqueTasks(): Collection
    {
        return $this->tasks
            ->filter(fn (Task $task) => $task->shouldMonitor())
            ->reject(fn (Task $task) => empty($task->name()))
            ->unique(fn (Task $task) => $task->name())
            ->values();
    }

    public function monitoredAtOhDear()
    {
        return $this->uniqueTasks()
            ->filter(fn (Task $task) => $task->shouldMonitorAtOhDear());
    }

    public function duplicateTasks(): Collection
    {
        $uniqueTasksIds = $this->uniqueTasks()
            ->map(fn (Task $task) => $task->uniqueId())
            ->toArray();

        return $this->tasks
            ->filter(fn (Task $task) => $task->shouldMonitor())
            ->reject(fn (Task $task) => empty($task->name()))
            ->reject(fn (Task $task) => in_array($task->uniqueId(), $uniqueTasksIds))
            ->values();
    }

    public function readyForMonitoringTasks(): Collection
    {
        return $this->uniqueTasks()
            ->reject(fn (Task $task) => $task->isBeingMonitored());
    }

    public function monitoredTasks(): Collection
    {
        return $this->uniqueTasks()
            ->filter(fn (Task $task) => $task->isBeingMonitored());
    }

    public function unmonitoredTasks(): Collection
    {
        return $this->tasks->reject(fn (Task $task) => $task->shouldMonitor());
    }

    public function unnamedTasks(): Collection
    {
        return $this->tasks
            ->filter(fn (Task $task) => empty($task->name()))
            ->values();
    }
}
