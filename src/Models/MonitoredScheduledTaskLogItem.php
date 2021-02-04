<?php

namespace Spatie\ScheduleMonitor\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonitoredScheduledTaskLogItem extends Model
{
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('schedule-monitor.tasks_log_items_db_table');
    }

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
        return $this->belongsTo(MonitoredScheduledTask::class);
    }

    public function updateMeta(array $values): self
    {
        $this->update(['meta' => $values]);

        return $this;
    }
}
