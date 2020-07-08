<?php

namespace Spatie\ScheduleMonitor;

use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskSkipped;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Contracts\Events\Dispatcher;
use Spatie\ScheduleMonitor\Models\MonitoredScheduledTask;

class ScheduledTaskEventSubscriber
{
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(
            ScheduledTaskStarting::class,
            fn (ScheduledTaskStarting $event) => optional(MonitoredScheduledTask::findForTask($event->task))->markAsStarting($event)
        );

        $events->listen(
            ScheduledTaskFinished::class,
            fn (ScheduledTaskFinished $event) => optional(MonitoredScheduledTask::findForTask($event->task))->markAsFinished($event)
        );

        $events->listen(
            ScheduledTaskFailed::class,
            fn (ScheduledTaskFailed $event) => optional(MonitoredScheduledTask::findForTask($event->task))->markAsFailed($event)
        );

        $events->listen(
            ScheduledTaskSkipped::class,
            fn (ScheduledTaskSkipped $event) => optional(MonitoredScheduledTask::findForTask($event->task))->markAsSkipped($event)
        );
    }
}
