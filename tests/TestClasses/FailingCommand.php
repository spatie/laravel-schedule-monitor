<?php

namespace Spatie\ScheduleMonitor\Tests\TestClasses;

use Illuminate\Console\Command;

class FailingCommand extends Command
{
    public static bool $executed = false;

    public $signature = 'failing-command';

    public function handle()
    {
        throw new \Exception('failing');
    }
}
