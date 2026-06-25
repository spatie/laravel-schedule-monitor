<?php

namespace Spatie\ScheduleMonitor\Support\ScheduledTasks\Tasks;

use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Console\Scheduling\Event;

class JobTask extends Task
{
    protected Event $task;

    public static function canHandleEvent(Event $event): bool
    {
        if (! $event instanceof CallbackEvent) {
            return false;
        }

        if (! is_null($event->command)) {
            return false;
        }

        if (empty($event->description)) {
            return false;
        }

        return class_exists($event->description);
    }

    public function defaultName(): ?string
    {
        return $this->event->description;
    }

    public function type(): string
    {
        return 'job';
    }
}
