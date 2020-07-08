<?php

namespace Spatie\ScheduleMonitor\Support\ScheduledTasks\Tasks;

use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Support\Str;

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
        return Str::limit($this->event->command, 255);
    }

    public function type(): string
    {
        return 'shell';
    }
}
