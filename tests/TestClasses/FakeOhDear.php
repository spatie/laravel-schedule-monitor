<?php

namespace Spatie\ScheduleMonitor\Tests\TestClasses;

use Illuminate\Support\Str;
use Spatie\ScheduleMonitor\Support\OhDear\CronCheck;
use Spatie\ScheduleMonitor\Support\OhDear\OhDear;

class FakeOhDear extends OhDear
{
    protected array $syncedCronCheckAttributes = [];

    public function __construct()
    {
        // Skip parent constructor for fake implementation
        $this->apiToken = 'fake-token';
        $this->baseUri = 'https://fake.ohdear.app/api';
    }

    public function monitor(int $monitorId): ?array
    {
        // Return fake monitor data for testing
        return [
            'sort_url' => 'example.com',
            'checks' => [],
        ];
    }

    public function setSyncedCronCheckAttributes(array $cronCheckAttributes)
    {
        $this->syncedCronCheckAttributes = $cronCheckAttributes;
    }

    public function getSyncedCronCheckAttributes(): array
    {
        return $this->syncedCronCheckAttributes;
    }

    public function syncCronChecks(int $monitorId, array $cronCheckAttributes): array
    {
        $cronCheckAttributes = collect($cronCheckAttributes)
            ->map(function (array $singleCronCheckAttributes) {
                $singleCronCheckAttributes['uuid'] = (string) Str::uuid();
                $singleCronCheckAttributes['ping_url'] = config('schedule-monitor.oh_dear.endpoint_url', 'https://ping.ohdear.app') . '/' . $singleCronCheckAttributes['uuid'];

                return $singleCronCheckAttributes;
            });

        $this->setSyncedCronCheckAttributes($cronCheckAttributes->all());

        return $cronCheckAttributes
            ->map(function (array $singleCronCheckAttributes) {
                return new CronCheck($singleCronCheckAttributes, $this);
            })
            ->toArray();
    }

    public function createCronCheck(
        int $monitorId,
        string $name,
        string $cronExpression,
        int $graceTimeInMinutes,
        $description,
        string $serverTimezone
    ): CronCheck {
        $attributes = [
            'name' => $name,
            'type' => 'cron',
            'cron_expression' => $cronExpression,
            'grace_time_in_minutes' => $graceTimeInMinutes,
            'description' => $description ?? '',
            'server_timezone' => $serverTimezone,
        ];

        $attributes['uuid'] = (string) Str::uuid();
        $attributes['ping_url'] = config('schedule-monitor.oh_dear.endpoint_url', 'https://ping.ohdear.app') . '/' . $attributes['uuid'];

        $this->syncedCronCheckAttributes[] = $attributes;

        return new CronCheck($attributes, $this);
    }
}
