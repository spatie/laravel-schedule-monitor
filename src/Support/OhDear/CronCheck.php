<?php

namespace Spatie\ScheduleMonitor\Support\OhDear;

class CronCheck
{
    public int $id;
    public string $name;
    public string $uuid;
    public string $type;
    public string $pingUrl;
    public int $graceTimeInMinutes;
    public ?string $cronExpression;
    public ?string $description;
    public ?string $serverTimezone;

    protected OhDear $ohDear;
    protected array $attributes;

    public function __construct(array $attributes, OhDear $ohDear)
    {
        $this->attributes = $attributes;
        $this->ohDear = $ohDear;
        
        $this->id = $attributes['id'] ?? 0;
        $this->name = $attributes['name'] ?? '';
        $this->uuid = $attributes['uuid'] ?? '';
        $this->type = $attributes['type'] ?? 'cron';
        $this->pingUrl = $attributes['ping_url'] ?? '';
        $this->graceTimeInMinutes = $attributes['grace_time_in_minutes'] ?? 0;
        $this->cronExpression = $attributes['cron_expression'] ?? null;
        $this->description = $attributes['description'] ?? null;
        $this->serverTimezone = $attributes['server_timezone'] ?? null;
    }

    public function __get(string $name)
    {
        return $this->attributes[$name] ?? null;
    }
}
