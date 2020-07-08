<?php

namespace Spatie\ScheduleMonitor\Support\ScheduledTasks\Tasks;

use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Support\Str;

class CommandTask extends Task
{
    public static function canHandleEvent(Event $event): bool
    {
        if ($event instanceof CallbackEvent) {
            return false;
        }

        return str_contains($event->command, "'artisan'");
    }

    public function defaultName(): ?string
    {
        return Str::after($this->event->command, "'artisan' ");
    }

    public function type(): string
    {
        return 'command';
    }
}
