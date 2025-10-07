<?php

namespace Spatie\ScheduleMonitor\Tests\TestClasses;

use Illuminate\Console\Command;

class SuccessCommand extends Command
{
    public $signature = 'success-command';

    public function handle()
    {
        $this->info('Command executed successfully');

        return 0;
    }
}
