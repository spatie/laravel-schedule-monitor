<?php

namespace Spatie\ScheduleMonitor\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\ScheduleMonitor\Support\Concerns\UsesScheduleMonitoringModels;

class MonitoredScheduledTaskLogItem extends Model
{
    use UsesScheduleMonitoringModels;
    use HasFactory;

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
        return $this->belongsTo($this->getMonitoredScheduleTaskModel(), 'monitored_scheduled_task_id');
    }

    public function updateMeta(array $values): self
    {
        $this->update(['meta' => $values]);

        return $this;
    }
}
