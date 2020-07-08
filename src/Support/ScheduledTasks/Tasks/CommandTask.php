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

        return Str::contains($event->command, self::artisanString());
    }

    public function defaultName(): ?string
    {
        return Str::after($this->event->command, self::artisanString() . ' ');
    }

    public function type(): string
    {
        return 'command';
    }

    public static function artisanString(): string
    {
        $baseString = 'artisan';

        $quote = self::isRunningWindows()
            ? '"'
            : "'";

        return "{$quote}{$baseString}{$quote}";
    }

    protected static function isRunningWindows()
    {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }
}
