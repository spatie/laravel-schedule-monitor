<?php

namespace Spatie\ScheduleMonitor\Models;

use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskSkipped;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use OhDear\PhpSdk\Resources\CronCheck;
use Spatie\ScheduleMonitor\Jobs\PingOhDearJob;
use Spatie\ScheduleMonitor\Support\ScheduledTasks\ScheduledTaskFactory;

class MonitoredScheduledTask extends Model
{
    public $guarded = [];

    protected $casts = [
        'registered_on_oh_dear_at' => 'datetime',
        'last_pinged_at' => 'datetime',
        'last_started_at' => 'datetime',
        'last_finished_at' => 'datetime',
        'last_skipped_at' => 'datetime',
        'last_failed_at' => 'datetime',
        'grace_time_in_minutes' => 'integer',
    ];

    public function logItems(): HasMany
    {
        return $this->hasMany(MonitoredScheduledTaskLogItem::class)->orderByDesc('id');
    }

    public static function findByName(string $name): ?self
    {
        return MonitoredScheduledTask::where('name', $name)->first();
    }

    public static function findForTask(Event $event): ?self
    {
        $task = ScheduledTaskFactory::createForEvent($event);

        if (empty($task->name())) {
            return null;
        }

        return MonitoredScheduledTask::findByName($task->name());
    }

    public static function findForCronCheck(CronCheck $cronCheck): ?self
    {
        return MonitoredScheduledTask::findByName($cronCheck->name);
    }

    public function markAsRegisteredOnOhDear(): self
    {
        if (is_null($this->registered_on_oh_dear_at)) {
            $this->update(['registered_on_oh_dear_at' => now()]);
        }

        return $this;
    }

    public function markAsStarting(ScheduledTaskStarting $event): self
    {
        $logItem = $this->createLogItem(MonitoredScheduledTaskLogItem::TYPE_STARTING);

        $logItem->updateMeta([
            'memory' => memory_get_usage(true),
        ]);

        $this->update([
            'last_started_at' => now(),
        ]);

        return $this;
    }

    public function markAsFinished(ScheduledTaskFinished $event): self
    {
        if ($event->task->exitCode !== 0 && ! is_null($event->task->exitCode)) {
            return $this->markAsFailed($event);
        }

        $logItem = $this->createLogItem(MonitoredScheduledTaskLogItem::TYPE_FINISHED);

        $logItem->updateMeta([
            'runtime' => $event->runtime,
            'exit_code' => $event->task->exitCode,
            'memory' => memory_get_usage(true),
        ]);

        $this->update(['last_finished_at' => now()]);

        $this->pingOhDear($logItem);

        return $this;
    }

    /**
     * @param ScheduledTaskFailed|ScheduledTaskFinished $event
     *
     * @return $this
     */
    public function markAsFailed($event): self
    {
        $logItem = $this->createLogItem(MonitoredScheduledTaskLogItem::TYPE_FAILED);

        if ($event instanceof ScheduledTaskFailed) {
            $logItem->updateMeta([
                'failure_message' => Str::limit(optional($event->exception)->getMessage(), 255),
            ]);
        }

        if ($event instanceof ScheduledTaskFinished) {
            $logItem->updateMeta([
                'runtime' => $event->runtime,
                'exit_code' => $event->task->exitCode,
                'memory' => memory_get_usage(true),
            ]);
        }

        $this->update(['last_failed_at' => now()]);

        $this->pingOhDear($logItem);

        return $this;
    }

    public function markAsSkipped(ScheduledTaskSkipped $event): self
    {
        $this->createLogItem(MonitoredScheduledTaskLogItem::TYPE_SKIPPED);

        $this->update(['last_skipped_at' => now()]);

        return $this;
    }

    protected function pingOhDear(MonitoredScheduledTaskLogItem $logItem): self
    {
        if (empty($this->ping_url)) {
            return $this;
        }

        if (! in_array($logItem->type, [
            MonitoredScheduledTaskLogItem::TYPE_FAILED,
            MonitoredScheduledTaskLogItem::TYPE_FINISHED,
        ])) {
            return $this;
        }

        dispatch(new PingOhDearJob($logItem));

        return $this;
    }

    protected function createLogItem(string $type): MonitoredScheduledTaskLogItem
    {
        return $this->logItems()->create([
            'type' => $type,
        ]);
    }
}
