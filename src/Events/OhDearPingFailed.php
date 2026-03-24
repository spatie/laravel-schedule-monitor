<?php

namespace Spatie\ScheduleMonitor\Events;

use GuzzleHttp\TransferStats;
use Spatie\ScheduleMonitor\Models\MonitoredScheduledTaskLogItem;
use Spatie\ScheduleMonitor\Support\OhDearPayload\Payloads\Payload;
use Throwable;

class OhDearPingFailed
{
    public function __construct(
        public MonitoredScheduledTaskLogItem $logItem,
        public Payload $payload,
        public Throwable $exception,
        public ?TransferStats $transferStats = null,
    ) {}
}
