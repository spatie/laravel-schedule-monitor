<?php

namespace Spatie\ScheduleMonitor;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Support\Carbon;

class EventWithCronBetween extends Event
{
    /**
     * Schedule the event between start and end, including updating the Cron expression.
     *
     * @param  string  $startTime
     * @param  string  $endTime
     * @return $this
     */
    public function between($startTime, $endTime)
    {
        // Cron often supports hourly ranges so include `between` hours, even
        // if their minutes cannot not be expressed.
        $oldHours = explode(' ', $this->expression)[1] ?? null;
        // Preserve existing 'every' like "*/5" but not earlier range "1-2".
        $oldHoursEvery = ($slash = strpos($oldHours, '/'))
            ? substr($oldHours, $slash)
            : '';
        $startHour = Carbon::parse($startTime, $this->timezone)->hour;
        $endHour = Carbon::parse($endTime, $this->timezone)->hour;
        // Don't bother unless range is more than the same hour.
        if ($endHour > $startHour) {
            $this->spliceIntoPosition(
                2,
                "$startHour-$endHour$oldHoursEvery"
            );
        } else {
            $this->spliceIntoPosition(2, "*$oldHoursEvery");
        }

        return parent::between($startTime, $endTime);
    }
}
