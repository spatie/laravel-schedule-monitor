<?php

namespace Spatie\ScheduleMonitor;

use Illuminate\Support\ServiceProvider;

class EventWithCronBetweenServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(
            'Illuminate\Console\Event',
            'Spatie\ScheduleMonitor\EventWithCronBetween'
        );
    }

    public function provides()
    {
        return ['events'];
    }
}
