<?php

namespace Spatie\ScheduleMonitor\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\ScheduleMonitor\Contracts\MonitoredScheduledTask as MonitoredScheduledTaskContract;
use Spatie\ScheduleMonitor\Contracts\MonitoredScheduledLogItem as MonitoredScheduledLogItemContract;

class MonitoredScheduledTaskLogItem extends Model implements MonitoredScheduledLogItemContract
{
    public $guarded = [];

    public const TYPE_STARTING = 'starting';
    public const TYPE_FINISHED = 'finished';
    public const TYPE_FAILED = 'failed';
    public const TYPE_SKIPPED = 'skipped';

    public $casts = [
        'meta' => 'array',
    ];

    public function monitoredScheduledTask(): BelongsTo
    {
        return $this->belongsTo(app(MonitoredScheduledTaskContract::class));
    }

    public function updateMeta(array $values): self
    {
        $this->update(['meta' => $values]);

        return $this;
    }
}
