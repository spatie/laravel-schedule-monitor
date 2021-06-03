<?php

namespace Spatie\ScheduleMonitor\EventHandlers;

use Illuminate\Console\Events\CommandStarting;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Spatie\ScheduleMonitor\Contracts\MonitoredScheduledTask as MonitoredScheduledTaskContract;

class BackgroundCommandListener
{
    public function handle(CommandStarting $event)
    {
        if ($event->command !== 'schedule:finish') {
            return;
        }

        collect(app(Schedule::class)->events())
            ->filter(fn (Event $task) => $task->runInBackground)
            ->each(function (Event $task) {
                $task
                    ->then(
                        function () use ($task) {
                            if (! $monitoredTask = app(MonitoredScheduledTaskContract)::findForTask($task)) {
                                return;
                            }

                            $event = new ScheduledTaskFinished(
                                $task,
                                0
                            );

                            $monitoredTask->markAsFinished($event);
                        }
                    );
            });
    }
}
