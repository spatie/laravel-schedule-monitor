<?php

namespace Spatie\ScheduleMonitor\Events;

use GuzzleHttp\TransferStats;
use Illuminate\Support\Str;
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
    ) {
    }

    public function context(): array
    {
        $context = [
            'request' => [
                'url' => $this->payload->url(),
                'data' => $this->payload->data(),
            ],
            'exception' => $this->exception->getMessage(),
        ];

        if (! $this->transferStats) {
            return $context;
        }

        $stats = $this->transferStats->getHandlerStats();

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

        if ($this->transferStats->hasResponse()) {
            $response = $this->transferStats->getResponse();

            $context['response'] = [
                'status' => $response->getStatusCode(),
                'headers' => $response->getHeaders(),
                'body' => Str::limit((string) $response->getBody(), 1024),
            ];
        }

        if ($request = $this->transferStats->getRequest()) {
            $context['request']['headers'] = $request->getHeaders();
        }

        return $context;
    }
}
