<?php

namespace Spatie\ScheduleMonitor\Tests\TestClasses;

use Illuminate\Support\Str;
use Spatie\ScheduleMonitor\Support\OhDear\CronCheck;
use Spatie\ScheduleMonitor\Support\OhDear\Monitor;
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

    public function monitor(int $monitorId): Monitor
    {
        return new FakeMonitor($this);
    }

    public function setSyncedCronCheckAttributes(array $cronCheckAttributes)
    {
        $this->syncedCronCheckAttributes = $cronCheckAttributes;
    }

    public function getSyncedCronCheckAttributes(): array
    {
        return $this->syncedCronCheckAttributes;
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

class FakeMonitor extends Monitor
{
    public FakeOhDear $fakeOhDear;

    public function __construct(FakeOhDear $fakeOhDear)
    {
        $this->fakeOhDear = $fakeOhDear;

        parent::__construct([
            'sort_url' => 'example.com',
            'checks' => [],
        ], $fakeOhDear);
    }

    public function syncCronChecks(array $cronCheckAttributes): array
    {
        $cronCheckAttributes = collect($cronCheckAttributes)
            ->map(function (array $singleCronCheckAttributes) {
                $singleCronCheckAttributes['uuid'] = (string) Str::uuid();
                $singleCronCheckAttributes['ping_url'] = config('schedule-monitor.oh_dear.endpoint_url', 'https://ping.ohdear.app') . '/' . $singleCronCheckAttributes['uuid'];

                return $singleCronCheckAttributes;
            });

        $this->fakeOhDear->setSyncedCronCheckAttributes($cronCheckAttributes->all());

        return $cronCheckAttributes
            ->map(function (array $singleCronCheckAttributes) {
                return new CronCheck($singleCronCheckAttributes, $this->fakeOhDear);
            })
            ->toArray();
    }
}
