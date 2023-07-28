<?php

namespace Spatie\ScheduleMonitor\Tests\TestClasses;

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

        $attributes['ping_url'] = 'https://ping.ohdear.app/test-ping-url-' . urlencode($attributes['name']);

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
        $this->fakeOhDear->setSyncedCronCheckAttributes($cronCheckAttributes);

        return collect($cronCheckAttributes)
            ->map(function (array $singleCronCheckAttributes) {
                $singleCronCheckAttributes['ping_url'] = 'https://ping.ohdear.app/test-ping-url-' . urlencode($singleCronCheckAttributes['name']);

                return new CronCheck($singleCronCheckAttributes, $this->fakeOhDear);
            })
            ->toArray();
    }
}
