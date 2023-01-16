<?php

namespace Spatie\ScheduleMonitor\Support\OhDearPayload\Payloads;

use Spatie\ScheduleMonitor\Models\MonitoredScheduledTaskLogItem;

class StartingPayload extends Payload
{
    public static function canHandle(MonitoredScheduledTaskLogItem $logItem): bool
    {
        return $logItem->type === MonitoredScheduledTaskLogItem::TYPE_STARTING;
    }

    public function url()
    {
        return "{$this->baseUrl()}/starting";
    }

    public function data(): array
    {
        return [];
    }
}
