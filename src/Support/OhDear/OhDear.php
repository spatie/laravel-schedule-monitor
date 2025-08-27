<?php

namespace Spatie\ScheduleMonitor\Support\OhDear;

use Exception;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class OhDear
{
    protected string $apiToken;

    protected string $baseUri;

    public function __construct(string $apiToken, string $baseUri = 'https://ohdear.app/api/')
    {
        $this->apiToken = $apiToken;

        $this->baseUri = rtrim($baseUri, '/');
    }

    public function monitor(int $monitorId): ?array
    {
        return $this->get("monitors/{$monitorId}");
    }

    public function createCronCheck(
        int $monitorId,
        string $name,
        string $cronExpression,
        int $graceTimeInMinutes,
        $description,
        string $serverTimezone
    ): CronCheck {
        $attributes = $this->post("monitors/{$monitorId}/cron-checks", [
            'name' => $name,
            'type' => 'cron',
            'cron_expression' => $cronExpression,
            'grace_time_in_minutes' => $graceTimeInMinutes,
            'description' => $description ?? '',
            'server_timezone' => $serverTimezone,
        ]);

        return new CronCheck($attributes, $this);
    }

    public function syncCronChecks(int $monitorId, array $cronCheckAttributes): array
    {
        $response = $this->post("monitors/{$monitorId}/cron-checks/sync", ['cron_checks' => $cronCheckAttributes]);

        return collect($response)->map(function ($attributes) {
            return new CronCheck($attributes, $this);
        })->toArray();
    }

    protected function get(string $endpoint): array
    {
        try {
            $response = Http::withToken($this->apiToken)
                ->get("{$this->baseUri}/{$endpoint}");

            $response->throw();

            return $response->json();
        } catch (RequestException $e) {
            throw new Exception("Failed to GET {$endpoint}: " . $e->getMessage());
        }
    }

    protected function post(string $endpoint, array $data): array
    {
        try {
            $response = Http::withToken($this->apiToken)
                ->acceptJson()
                ->post("{$this->baseUri}/{$endpoint}", $data);

            $response->throw();


            return $response->json();
        } catch (RequestException $e) {
            ;

            throw new Exception("Failed to POST {$endpoint}: " . $e->getMessage());
        }
    }
}
