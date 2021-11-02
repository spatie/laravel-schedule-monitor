<?php

namespace Spatie\ScheduleMonitor\Jobs;

use DateTime;
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

    use Dispatchable;
    use SerializesModels;
    use InteractsWithQueue;
    use Queueable;

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

        $response = Http::retry(3, 10 * 1000)->post($payload->url(), $payload->data());
        $response->throw();

        $this->logItem->monitoredScheduledTask->update(['last_pinged_at' => now()]);
    }

    public function retryUntil(): DateTime
    {
        return now()->addMinutes(config('schedule-monitor.oh_dear.retry_job_for_minutes', 10))->toDateTime();
    }
}
