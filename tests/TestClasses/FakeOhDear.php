<?php

namespace Spatie\ScheduleMonitor\Tests\TestClasses;

use Illuminate\Support\Str;
use OhDear\PhpSdk\OhDear;
use OhDear\PhpSdk\Resources\CronCheck;
use OhDear\PhpSdk\Resources\Site;

class FakeOhDear extends OhDear
{
    protected array $syncedCronCheckAttributes = [];

    public function __construct()
    {
    }

    public function site(int $siteId): Site
    {
        return new FakeSite($this);
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
        int $siteId,
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

class FakeSite extends Site
{
    public FakeOhDear $fakeOhDear;

    public function __construct(FakeOhDear $fakeOhDear)
    {
        $this->fakeOhDear = $fakeOhDear;

        parent::__construct([
            'sort_url' => 'example.com',
            'checks' => [],
        ]);
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
