<?php

namespace Spatie\ScheduleMonitor\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

interface MonitoredScheduledLogItem
{
    public function monitoredScheduledTask(): BelongsTo;
    public function updateMeta(array $values): self;
}
