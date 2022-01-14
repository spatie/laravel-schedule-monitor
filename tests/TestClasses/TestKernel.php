<?php

namespace Spatie\ScheduleMonitor\Tests\TestClasses;

use Closure;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as IlluminateKernel;

class TestKernel extends IlluminateKernel
{
    protected static array $registeredScheduleCommands = [];

    public function commands()
    {
        return [
            FailingCommand::class,
        ];
    }

    public function schedule(Schedule $schedule)
    {
        collect(static::$registeredScheduleCommands)->each(
            fn (Closure $closure) => $closure($schedule)
        );
    }

    public static function registerScheduledTasks(Closure $closure)
    {
        static::$registeredScheduleCommands[] = $closure;
    }

    public static function replaceScheduledTasks(Closure $closure)
    {
        static::clearScheduledCommands();
        static::$registeredScheduleCommands[] = $closure;
    }

    public static function clearScheduledCommands()
    {
        static::$registeredScheduleCommands = [];
    }
}
