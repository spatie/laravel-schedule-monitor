<?php

namespace Spatie\ScheduleMonitor\Support\OhDear;

class Monitor
{
    public string $sortUrl;
    public int $id;

    protected OhDear $ohDear;
    protected array $attributes;

    public function __construct(array $attributes, OhDear $ohDear)
    {
        $this->attributes = $attributes;
        $this->ohDear = $ohDear;
        $this->id = $attributes['id'] ?? 0;
        $this->sortUrl = $attributes['sort_url'] ?? $attributes['url'] ?? '';
    }

    public function syncCronChecks(array $cronCheckAttributes): array
    {
        return $this->ohDear->syncCronChecks($this->id, $cronCheckAttributes);
    }

    public function __get(string $name)
    {
        return $this->attributes[$name] ?? null;
    }
}
