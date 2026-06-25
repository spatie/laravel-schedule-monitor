<?php

namespace Spatie\ScheduleMonitor\Support\ScheduledTasks\Tasks;

use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Console\Scheduling\Event;

class ShellTask extends Task
{
    public static function canHandleEvent(Event $event): bool
    {
        if ($event instanceof CallbackEvent) {
            return true;
        }

        return true;
    }

    public function defaultName(): ?string
    {
        return $this->event->command;
    }

    public function type(): string
    {
        return 'shell';
    }
}
