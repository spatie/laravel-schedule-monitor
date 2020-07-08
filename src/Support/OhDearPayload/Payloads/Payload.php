<?php

namespace Spatie\ScheduleMonitor\Support\OhDearPayload\Payloads;

use Spatie\ScheduleMonitor\Models\MonitoredScheduledTaskLogItem;

abstract class Payload
{
    protected MonitoredScheduledTaskLogItem $logItem;

    abstract public static function canHandle(MonitoredScheduledTaskLogItem $logItem): bool;

    public function __construct(MonitoredScheduledTaskLogItem $logItem)
    {
        $this->logItem = $logItem;
    }

    abstract public function url();

    abstract public function data();

    protected function baseUrl(): string
    {
        return $this->logItem->monitoredScheduledTask->ping_url;
    }
}
