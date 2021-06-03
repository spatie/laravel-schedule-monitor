<?php

namespace Spatie\ScheduleMonitor\EventHandlers;

use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskSkipped;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Contracts\Events\Dispatcher;
use Spatie\ScheduleMonitor\Contracts\MonitoredScheduledTask as MonitoredScheduledTaskContract;

class ScheduledTaskEventSubscriber
{
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(
            ScheduledTaskStarting::class,
            fn (ScheduledTaskStarting $event) => optional(app(MonitoredScheduledTaskContract::class)::findForTask($event->task))->markAsStarting($event)
        );

        $events->listen(
            ScheduledTaskFinished::class,
            fn (ScheduledTaskFinished $event) => optional(app(MonitoredScheduledTaskContract::class)::findForTask($event->task))->markAsFinished($event)
        );

        $events->listen(
            ScheduledTaskFailed::class,
            fn (ScheduledTaskFailed $event) => optional(app(MonitoredScheduledTaskContract::class)::findForTask($event->task))->markAsFailed($event)
        );

        $events->listen(
            ScheduledTaskSkipped::class,
            fn (ScheduledTaskSkipped $event) => optional(app(MonitoredScheduledTaskContract::class)::findForTask($event->task))->markAsSkipped($event)
        );
    }
}
