<?php

namespace Spatie\ScheduleMonitor\Jobs;

use DateTime;
use GuzzleHttp\TransferStats;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Spatie\ScheduleMonitor\Events\OhDearPingFailed;
use Spatie\ScheduleMonitor\Models\MonitoredScheduledTaskLogItem;
use Spatie\ScheduleMonitor\Support\OhDearPayload\OhDearPayloadFactory;
use Spatie\ScheduleMonitor\Support\OhDearPayload\Payloads\Payload;
use Throwable;

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

    public function handle(): void
    {
        if (! $payload = OhDearPayloadFactory::createForLogItem($this->logItem)) {
            return;
        }

        $transferStats = null;
        $debugLogging = config('schedule-monitor.oh_dear.debug_logging');

        $pendingRequest = Http::retry(
            times: 3,
            sleepMilliseconds: config('schedule-monitor.oh_dear.retry_delay_ms', 10_000),
        )->when($debugLogging, fn ($request) => $request->withOptions([
            'on_stats' => function (TransferStats $stats) use (&$transferStats) {
                $transferStats = $stats;
            },
        ]));

        try {
            $response = $pendingRequest->post($payload->url(), $payload->data());
            $response->throw();
        } catch (Throwable $exception) {
            event(new OhDearPingFailed($this->logItem, $payload, $exception, $transferStats));

            if ($debugLogging) {
                $this->logFailedPing($payload, $transferStats, $exception);
            }

            throw $exception;
        }

        $this->logItem->monitoredScheduledTask->update(['last_pinged_at' => now()]);
    }

    public function retryUntil(): DateTime
    {
        return now()->addMinutes(config('schedule-monitor.oh_dear.retry_job_for_minutes', 10))->toDateTime();
    }

    protected function logFailedPing(Payload $payload, ?TransferStats $transferStats, Throwable $exception): void
    {
        $context = [
            'request' => [
                'url' => $payload->url(),
                'data' => $payload->data(),
            ],
            'exception' => $exception->getMessage(),
        ];

        if ($transferStats) {
            $stats = $transferStats->getHandlerStats();

            $context['timing'] = [
                'namelookup_time_s' => $stats['namelookup_time'] ?? null,
                'connect_time_s' => $stats['connect_time'] ?? null,
                'appconnect_time_s' => $stats['appconnect_time'] ?? null,
                'starttransfer_time_s' => $stats['starttransfer_time'] ?? null,
                'total_time_s' => $stats['total_time'] ?? null,
            ];

            $context['connection'] = [
                'primary_ip' => $stats['primary_ip'] ?? null,
                'primary_port' => $stats['primary_port'] ?? null,
                'local_ip' => $stats['local_ip'] ?? null,
            ];

            if ($transferStats->hasResponse()) {
                $response = $transferStats->getResponse();
                $body = (string) $response->getBody();

                $context['response'] = [
                    'status' => $response->getStatusCode(),
                    'headers' => $response->getHeaders(),
                    'body' => strlen($body) > 1024 ? substr($body, 0, 1024) . '... (truncated)' : $body,
                ];
            }

            if ($request = $transferStats->getRequest()) {
                $context['request']['headers'] = $request->getHeaders();
            }
        }

        Log::warning('PingOhDearJob failed: ' . $exception->getMessage(), $context);
    }
}
