<?php

namespace Spatie\ScheduleMonitor\Support\OhDearPayload\Payloads;

use Illuminate\Support\Arr;
use Spatie\ScheduleMonitor\Models\MonitoredScheduledTaskLogItem;

class FailedPayload extends Payload
{
    public static function canHandle(MonitoredScheduledTaskLogItem $logItem): bool
    {
        return $logItem->type === MonitoredScheduledTaskLogItem::TYPE_FAILED;
    }

    public function url()
    {
        return "{$this->baseUrl()}/failed";
    }

    public function data(): array
    {
        return Arr::only($this->logItem->meta ?? [], [
            'failure_message',
        ]);
    }
}
