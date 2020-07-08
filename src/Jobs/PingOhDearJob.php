<?php

namespace Spatie\ScheduleMonitor\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Spatie\ScheduleMonitor\Models\MonitoredScheduledTaskLogItem;
use Spatie\ScheduleMonitor\Support\OhDearPayload\OhDearPayloadFactory;

class PingOhDearJob implements ShouldQueue
{
    public $deleteWhenMissingModels = true;

    use Dispatchable, SerializesModels, InteractsWithQueue, Queueable;

    public MonitoredScheduledTaskLogItem $logItem;

    public function __construct(MonitoredScheduledTaskLogItem $logItem)
    {
        $this->logItem = $logItem;

        if ($queue = config('schedule-monitor.oh_dear.queue')) {
            $this->onQueue($queue);
        }
    }

    public function handle()
    {
        if (! $payload = OhDearPayloadFactory::createForLogItem($this->logItem)) {
            return;
        }

        Http::post($payload->url(), $payload->data());

        $this->logItem->monitoredScheduledTask->update(['last_pinged_at' => now()]);
    }
}
