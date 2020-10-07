<?php

namespace Spatie\ScheduleMonitor;

use Illuminate\Console\Scheduling\Schedule;

class ScheduleWithCronBetween extends Schedule
{
    public function exec($command, array $parameters = [])
    {
        if (count($parameters)) {
            $command .= ' '.$this->compileParameters($parameters);
        }

        $this->events[] = $event = new EventWithCronBetween($this->eventMutex, $command, $this->timezone);

        return $event;
    }
}
